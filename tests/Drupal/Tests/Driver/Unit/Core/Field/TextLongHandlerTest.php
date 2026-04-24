<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\TextLongHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TextLongHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class TextLongHandlerTest extends TestCase {

  /**
   * Tests that expand() returns the input unchanged.
   */
  public function testExpandReturnsValuesUnchanged(): void {
    $handler = new TextLongHandler();

    $values = [
      ['value' => 'Body copy.', 'format' => 'plain_text'],
    ];

    $this->assertSame($values, $handler->expand($values));
  }

}
