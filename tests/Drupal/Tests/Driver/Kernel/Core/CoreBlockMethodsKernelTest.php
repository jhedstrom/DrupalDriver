<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Driver\Core\Core;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel tests for the block capability methods on Core.
 *
 * Covers all four methods of 'BlockCapabilityInterface':
 *  - 'blockPlace()' / 'blockDelete()' round-trip a 'block' config entity
 *    (placement in a region of a theme).
 *  - 'blockContentCreate()' / 'blockContentDelete()' round-trip a
 *    'block_content' content entity (the reusable block body).
 *
 * @group core
 * @group block
 */
#[Group('core')]
#[Group('block')]
class CoreBlockMethodsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = ['system', 'user', 'block', 'block_content'];

  /**
   * The Core driver under test.
   */
  protected Core $core;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
    // Only 'system' config is installed. Installing 'block_content' config
    // on Drupal 10 / Drupal 11-lowest pulls in
    // 'field.storage.block_content.body', whose schema references the 'text'
    // module - unnecessary surface for this test, which creates its own
    // body-less 'block_content_type' inline.
    $this->installConfig(['system']);
    \Drupal::service('theme_installer')->install(['stark']);
    $this->core = new Core($this->root);
  }

  /**
   * Tests that 'blockPlace()' creates a placement in the given region.
   */
  public function testBlockPlaceAndDeleteRoundTrip(): void {
    $stub = (object) [
      'id' => 'test_powered_by',
      'plugin' => 'system_powered_by_block',
      'theme' => 'stark',
      'region' => 'content',
      'weight' => 0,
      'settings' => ['label' => 'Powered by', 'label_display' => 'visible'],
    ];

    $placement = $this->core->blockPlace($stub);

    $this->assertInstanceOf(Block::class, $placement);

    $reloaded = Block::load('test_powered_by');
    $this->assertInstanceOf(Block::class, $reloaded);
    $this->assertSame('content', $reloaded->getRegion());
    $this->assertSame('stark', $reloaded->getTheme());
    $this->assertSame('system_powered_by_block', $reloaded->getPluginId());

    $this->core->blockDelete($stub);
    $this->assertNull(Block::load('test_powered_by'));
  }

  /**
   * Tests that 'blockPlace()' auto-generates an id when the stub omits it.
   */
  public function testBlockPlaceGeneratesIdWhenAbsent(): void {
    $stub = (object) [
      'plugin' => 'system_powered_by_block',
      'theme' => 'stark',
      'region' => 'footer',
    ];

    $placement = $this->core->blockPlace($stub);

    $this->assertInstanceOf(Block::class, $placement);
    $this->assertNotEmpty($placement->id(), 'blockPlace populated an id on the saved placement.');
    $this->assertNotNull(Block::load($placement->id()));
  }

  /**
   * Tests that 'blockDelete()' accepts a loaded entity as well as a stub.
   */
  public function testBlockDeleteAcceptsLoadedEntity(): void {
    $stub = (object) [
      'id' => 'test_via_entity',
      'plugin' => 'system_powered_by_block',
      'theme' => 'stark',
      'region' => 'content',
    ];
    $this->core->blockPlace($stub);

    $loaded = Block::load('test_via_entity');
    $this->assertInstanceOf(Block::class, $loaded);

    $this->core->blockDelete($loaded);
    $this->assertNull(Block::load('test_via_entity'));
  }

  /**
   * Tests that 'blockDelete()' fails loudly when the stub has no id.
   */
  public function testBlockDeleteRequiresIdOnStub(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/id/');

    $this->core->blockDelete((object) ['plugin' => 'system_powered_by_block']);
  }

  /**
   * Tests that 'blockContentCreate()' creates a content-block entity.
   */
  public function testBlockContentCreateAndDeleteRoundTrip(): void {
    BlockContentType::create(['id' => 'basic', 'label' => 'Basic'])->save();

    $stub = (object) [
      'type' => 'basic',
      'info' => 'driver-test content block',
      'reusable' => TRUE,
    ];

    $created = $this->core->blockContentCreate($stub);

    $this->assertInstanceOf(BlockContent::class, $created);
    $this->assertNotEmpty($stub->id, 'blockContentCreate populated the id key on the stub.');
    $this->assertSame('driver-test content block', $created->label());

    $this->core->blockContentDelete($stub);
    $this->assertNull(BlockContent::load((int) $stub->id));
  }

}
