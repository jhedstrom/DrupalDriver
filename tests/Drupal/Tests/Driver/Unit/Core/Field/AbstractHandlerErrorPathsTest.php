<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\DefaultHandler;
use Drupal\Driver\Entity\EntityStub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests AbstractHandler constructor guards that need no Drupal kernel.
 *
 * 'DefaultHandler' is the simplest concrete subclass and is used here to
 * exercise the base class error branches.
 *
 * @group fields
 */
#[Group('fields')]
class AbstractHandlerErrorPathsTest extends TestCase {

  /**
   * Tests that the constructor rejects an empty entity type.
   *
   * The throw precedes every 'Drupal::service()' call, so no kernel bootstrap
   * is required.
   */
  public function testConstructorRejectsEmptyEntityType(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/You must specify an entity type/');

    new DefaultHandler(new EntityStub(''), '', 'field_any');
  }

}
