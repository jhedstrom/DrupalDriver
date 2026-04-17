<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Fields\Drupal8;

use Drupal\Driver\Fields\Drupal8\TextWithSummaryHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the TextWithSummaryHandler field handler.
 */
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
