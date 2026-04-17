<?php

namespace Drupal\Tests\Driver\Fields\Drupal8;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Driver\Fields\Drupal8\ImageHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ImageHandler field handler.
 */
class ImageHandlerTest extends TestCase {

  /**
   * {@inheritdoc}
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
    $this->expectExceptionMessage('Error reading file /tmp/drupal-driver-nonexistent-image.jpg.');

    @$handler->expand(['/tmp/drupal-driver-nonexistent-image.jpg']);
  }

  /**
   * Tests that a readable path is expanded into an image field value.
   */
  public function testExpandReturnsImageValueWithDefaultAltAndTitle() {
    $path = tempnam(sys_get_temp_dir(), 'drupal-driver-') . '.jpg';
    file_put_contents($path, 'fixture');
    $this->setFileRepositoryWithReturnId(7);

    $handler = $this->createHandler();

    $result = $handler->expand([$path]);

    $this->assertSame(['target_id' => 7, 'alt' => NULL, 'title' => NULL], $result);
  }

  /**
   * Tests that alt and title extras are propagated when provided.
   */
  public function testExpandPropagatesAltAndTitleExtras() {
    $path = tempnam(sys_get_temp_dir(), 'drupal-driver-') . '.jpg';
    file_put_contents($path, 'fixture');
    $this->setFileRepositoryWithReturnId(12);

    $handler = $this->createHandler();

    $values = [$path, 'alt' => 'Alt text', 'title' => 'Title text'];
    $result = $handler->expand($values);

    $this->assertSame(['target_id' => 12, 'alt' => 'Alt text', 'title' => 'Title text'], $result);
  }

  /**
   * Creates an ImageHandler that bypasses the parent constructor.
   */
  protected function createHandler() {
    $reflection = new \ReflectionClass(ImageHandler::class);
    return $reflection->newInstanceWithoutConstructor();
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
