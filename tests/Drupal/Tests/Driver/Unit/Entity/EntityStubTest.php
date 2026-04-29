<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Entity;

use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Entity\EntityStubInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the EntityStub typed envelope.
 *
 * @group entity
 */
#[Group('entity')]
class EntityStubTest extends TestCase {

  /**
   * Tests that the constructor pins entity type and bundle.
   */
  public function testConstructorPinsTypeAndBundle(): void {
    $stub = new EntityStub('node', 'article');

    $this->assertSame('node', $stub->getEntityType());
    $this->assertSame('article', $stub->getBundle());
  }

  /**
   * Tests that the constructor accepts an initial values bag.
   */
  public function testConstructorAcceptsInitialValues(): void {
    $stub = new EntityStub('node', 'article', ['title' => 'Hello']);

    $this->assertTrue($stub->hasValue('title'));
    $this->assertSame('Hello', $stub->getValue('title'));
    $this->assertSame(['title' => 'Hello'], $stub->getValues());
  }

  /**
   * Tests that the bundle defaults to NULL for entity types without bundles.
   */
  public function testBundleDefaultsToNull(): void {
    $stub = new EntityStub('user');

    $this->assertNull($stub->getBundle());
  }

  /**
   * Tests that 'getValue()' returns the supplied default for unset keys.
   */
  public function testGetValueReturnsDefaultWhenAbsent(): void {
    $stub = new EntityStub('node', 'article');

    $this->assertNull($stub->getValue('title'));
    $this->assertSame('fallback', $stub->getValue('title', 'fallback'));
  }

  /**
   * Tests that 'setValue()' returns $this for chaining.
   */
  public function testSetValueIsChainable(): void {
    $stub = new EntityStub('node', 'article');

    $returned = $stub->setValue('title', 'Hello')->setValue('promote', 1);

    $this->assertSame($stub, $returned);
    $this->assertSame('Hello', $stub->getValue('title'));
    $this->assertSame(1, $stub->getValue('promote'));
  }

  /**
   * Tests that 'hasValue()' is true even when the stored value is NULL.
   */
  public function testHasValueDistinguishesNullFromAbsent(): void {
    $stub = new EntityStub('node', 'article');
    $stub->setValue('title', NULL);

    $this->assertTrue($stub->hasValue('title'), 'NULL is a stored value.');
    $this->assertFalse($stub->hasValue('promote'), 'unset key is not a stored value.');
  }

  /**
   * Tests that 'removeValue()' deletes a key from the bag.
   */
  public function testRemoveValueDeletesKey(): void {
    $stub = new EntityStub('node', 'article', ['title' => 'Hello']);

    $stub->removeValue('title');

    $this->assertFalse($stub->hasValue('title'));
    $this->assertSame([], $stub->getValues());
  }

  /**
   * Tests that 'setValues()' replaces the bag wholesale.
   */
  public function testSetValuesReplacesBag(): void {
    $stub = new EntityStub('node', 'article', ['title' => 'Old']);

    $stub->setValues(['promote' => 1]);

    $this->assertFalse($stub->hasValue('title'), 'old keys were dropped.');
    $this->assertSame(1, $stub->getValue('promote'));
  }

  /**
   * Tests that 'isSaved()' flips after 'markSaved()'.
   */
  public function testIsSavedFlipsAfterMarkSaved(): void {
    $stub = new EntityStub('node', 'article');

    $this->assertFalse($stub->isSaved());

    $stub->markSaved((object) ['id' => 7]);

    $this->assertTrue($stub->isSaved());
  }

  /**
   * Tests that 'getSavedEntity()' returns the supplied entity.
   */
  public function testGetSavedEntityReturnsAttachedObject(): void {
    $entity = (object) ['id' => 7];
    $stub = new EntityStub('node', 'article');
    $stub->markSaved($entity);

    $this->assertSame($entity, $stub->getSavedEntity());
  }

  /**
   * Tests that 'getSavedEntity()' throws on an unsaved stub.
   */
  public function testGetSavedEntityThrowsWhenUnsaved(): void {
    $stub = new EntityStub('node', 'article');

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessageMatches('/EntityStub for "node" has not been saved/');

    $stub->getSavedEntity();
  }

  /**
   * Tests that 'getId()' resolves through the saved entity's id() method.
   */
  public function testGetIdReadsFromSavedEntity(): void {
    $entity = new class() {

      /**
       * Returns a fixed identifier mirroring Drupal's entity 'id()' contract.
       */
      public function id(): int {
        return 42;
      }

    };

    $stub = new EntityStub('node', 'article');
    $stub->markSaved($entity);

    $this->assertSame(42, $stub->getId());
  }

  /**
   * Tests that 'getId()' returns NULL when the stub is not saved.
   */
  public function testGetIdReturnsNullWhenUnsaved(): void {
    $stub = new EntityStub('node', 'article');

    $this->assertNull($stub->getId());
  }

  /**
   * Tests that 'getId()' returns NULL when the saved entity has no 'id()'.
   */
  public function testGetIdReturnsNullWhenSavedEntityHasNoIdMethod(): void {
    $stub = new EntityStub('node', 'article');
    $stub->markSaved((object) ['identifier' => 'x']);

    $this->assertNull($stub->getId());
  }

  /**
   * Tests that the bundle key defaults to 'type' and is mutable.
   */
  public function testBundleKeyDefaultsToTypeAndIsMutable(): void {
    $stub = new EntityStub('taxonomy_term', 'tags');

    $this->assertSame(EntityStubInterface::DEFAULT_BUNDLE_KEY, $stub->getBundleKey());
    $this->assertSame('type', $stub->getBundleKey());

    $returned = $stub->setBundleKey('vid');

    $this->assertSame($stub, $returned);
    $this->assertSame('vid', $stub->getBundleKey());
  }

  /**
   * Tests that the stub implements the documented interface.
   */
  public function testImplementsInterface(): void {
    $this->assertInstanceOf(EntityStubInterface::class, new EntityStub('node'));
  }

}
