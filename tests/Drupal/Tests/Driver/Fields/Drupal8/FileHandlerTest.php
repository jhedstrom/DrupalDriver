<?php

namespace Drupal\Tests\Driver\Fields\Drupal8;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Fields\Drupal8\FileHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the FileHandler field handler.
 */
class FileHandlerTest extends TestCase {

  /**
   * Restores the Drupal container after each test.
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * Tests that unreadable files throw a descriptive exception.
   */
  public function testExpandThrowsWhenFileCannotBeRead() {
    $handler = $this->createHandler();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Error reading file /tmp/drupal-driver-nonexistent-file.bin.');

    @$handler->expand(['/tmp/drupal-driver-nonexistent-file.bin']);
  }

  /**
   * Tests that string paths produce a single target entry with defaults.
   */
  public function testExpandHandlesStringValueWithDefaults() {
    $path = $this->createTempFile('png');
    $this->setFileRepositoryWithReturnId(99);

    $handler = $this->createHandler();

    $result = $handler->expand([$path]);

    $this->assertSame([
      ['target_id' => 99, 'display' => 1, 'description' => ''],
    ], $result);
  }

  /**
   * Tests that keyed array values honour their explicit overrides.
   */
  public function testExpandHandlesArrayValueWithOverrides() {
    $path = $this->createTempFile('pdf');
    $this->setFileRepositoryWithReturnId(42);

    $handler = $this->createHandler();

    $result = $handler->expand([
      [
        'target_id' => $path,
        'display' => 0,
        'description' => 'Spec sheet',
      ],
    ]);

    $this->assertSame([
      ['target_id' => 42, 'display' => 0, 'description' => 'Spec sheet'],
    ], $result);
  }

  /**
   * Creates a FileHandler that bypasses the parent constructor.
   */
  protected function createHandler() {
    $reflection = new \ReflectionClass(FileHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

  /**
   * Creates a temporary file and returns its path.
   */
  protected function createTempFile($extension) {
    $path = tempnam(sys_get_temp_dir(), 'drupal-driver-') . '.' . $extension;
    file_put_contents($path, 'fixture');
    return $path;
  }

  /**
   * Registers a mocked file.repository service returning a file with an ID.
   */
  protected function setFileRepositoryWithReturnId($file_id) {
    $file = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id', 'save'])
      ->getMock();
    $file->method('id')->willReturn($file_id);

    $repository = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['writeData'])
      ->getMock();
    $repository->method('writeData')->willReturn($file);

    $container = new ContainerBuilder();
    $container->set('file.repository', $repository);
    \Drupal::setContainer($container);
  }

}
