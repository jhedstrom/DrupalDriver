<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\TextWithSummaryHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TextWithSummaryHandler field handler.
 */
#[Group('fields')]
class TextWithSummaryHandlerTest extends TestCase {

  /**
   * Tests that expand() returns the input unchanged.
   */
  public function testExpandReturnsValuesUnchanged(): void {
    $handler = new TextWithSummaryHandler();

    $values = [
      ['value' => 'body text', 'summary' => 'short'],
    ];

    $this->assertSame($values, $handler->expand($values));
  }

}
