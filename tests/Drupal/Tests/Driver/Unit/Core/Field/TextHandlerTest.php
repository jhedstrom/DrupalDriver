<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\TextHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TextHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class TextHandlerTest extends TestCase {

  /**
   * Tests that expand() returns the input unchanged.
   */
  public function testExpandReturnsValuesUnchanged(): void {
    $handler = new TextHandler();

    $values = [
      ['value' => 'Inline text.', 'format' => 'plain_text'],
    ];

    $this->assertSame($values, $handler->expand($values));
  }

}
