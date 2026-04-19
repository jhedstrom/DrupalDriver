<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store\Entity\Store;
use Drupal\Driver\Core\Core;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test exercising 'entityCreate()' on a 'commerce_product' stub.
 *
 * This is the canonical scenario from #270: a stub sets
 * 'commerce_product.variations' - a BASE entity_reference field targeting
 * 'commerce_product_variation' - and expects the driver to resolve each
 * referenced variation and attach it to the product on save. Without the
 * base-field auto-detection in 'expandEntityFields()', variations are
 * filtered out of the field-handler pipeline, reach entity storage in raw
 * scalar form, and the product is saved with no variations attached.
 *
 * The test dogfoods the driver end-to-end: both the variation and the
 * product are created via 'Core::entityCreate()', then the product is
 * loaded back via the entity type manager to assert the resolved
 * relationship.
 *
 * @group core
 */
#[Group('core')]
class CoreEntityCreateCommerceKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'options',
    'views',
    'path',
    'path_alias',
    'address',
    'datetime',
    'entity',
    'inline_entity_form',
    'state_machine',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
  ];

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
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('commerce_currency');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['system', 'user', 'filter', 'commerce_store', 'commerce_product']);

    // Import USD so the price-backed product variation type can resolve
    // its currency; required even when the stub does not set a price.
    $this->container->get('commerce_price.currency_importer')->import('USD');

    // Create a default store so products have a resolvable owner context.
    Store::create([
      'type' => 'online',
      'name' => 'Default',
      'mail' => 'admin@example.com',
      'default_currency' => 'USD',
      'address' => ['country_code' => 'US'],
    ])->save();

    $this->core = new Core($this->root);
  }

  /**
   * Tests 'entityCreate()' resolves 'commerce_product.variations'.
   */
  public function testEntityCreateExpandsProductVariationsBaseField(): void {
    $variation_stub = (object) [
      'type' => 'default',
      'sku' => 'SKU-001',
      'title' => 'Test variation',
    ];
    $this->core->entityCreate('commerce_product_variation', $variation_stub);

    $this->assertNotEmpty(
      $variation_stub->variation_id,
      'entityCreate populated commerce_product_variation.variation_id on the stub.',
    );

    $product_stub = (object) [
      'type' => 'default',
      'title' => 'Test product',
      'variations' => [$variation_stub->variation_id],
    ];
    $this->core->entityCreate('commerce_product', $product_stub);

    $this->assertNotEmpty(
      $product_stub->product_id,
      'entityCreate populated commerce_product.product_id on the stub.',
    );

    $product = Product::load((int) $product_stub->product_id);
    $this->assertInstanceOf(Product::class, $product);

    $variation_ids = array_map(intval(...), $product->getVariationIds());
    $this->assertContains(
      (int) $variation_stub->variation_id,
      $variation_ids,
      'product.variations base entity_reference resolved to the variation id.',
    );
  }

}
