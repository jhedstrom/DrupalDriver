<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\LinkHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the LinkHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class LinkHandlerTest extends TestCase {

  /**
 * Tests link field expansion.
 *
 * @param array<int, mixed> $input
 *   The input values to expand.
 * @param array<int, mixed> $expected
 *   The expected expanded values.
 *
 * @dataProvider dataProviderExpand
 */
  #[DataProvider('dataProviderExpand')]
  public function testExpand(array $input, array $expected): void {
    $handler = $this->createHandler();
    $result = $handler->expand($input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpand().
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'numeric indices' => [
        [['My link', 'https://example.com']],
        [['title' => 'My link', 'uri' => 'https://example.com', 'options' => []]],
    ];
    yield 'named keys' => [
        [['title' => 'My link', 'uri' => 'https://example.com']],
        [['title' => 'My link', 'uri' => 'https://example.com', 'options' => []]],
    ];
    yield 'numeric indices with options' => [
        [['My link', 'https://example.com', 'target=_blank&rel=nofollow']],
        [[
          'title' => 'My link',
          'uri' => 'https://example.com',
          'options' => ['target' => '_blank', 'rel' => 'nofollow'],
        ],
        ],
    ];
    yield 'named keys with options' => [
        [['title' => 'My link', 'uri' => 'https://example.com', 'options' => 'target=_blank']],
        [['title' => 'My link', 'uri' => 'https://example.com', 'options' => ['target' => '_blank']]],
    ];
    yield 'multiple values' => [
        [
          ['First', 'https://first.com'],
          ['title' => 'Second', 'uri' => 'https://second.com'],
        ],
        [
          ['title' => 'First', 'uri' => 'https://first.com', 'options' => []],
          ['title' => 'Second', 'uri' => 'https://second.com', 'options' => []],
        ],
    ];
    yield 'no options returns empty array' => [
        [['title' => 'Link', 'uri' => 'https://example.com']],
        [['title' => 'Link', 'uri' => 'https://example.com', 'options' => []]],
    ];
    yield 'uri-only string' => [
        ['https://example.com'],
        [['uri' => 'https://example.com', 'options' => []]],
    ];
    yield 'mixed uri-only and full' => [
        [
          'https://first.com',
          ['title' => 'Second', 'uri' => 'https://second.com'],
        ],
        [
          ['uri' => 'https://first.com', 'options' => []],
          ['title' => 'Second', 'uri' => 'https://second.com', 'options' => []],
        ],
    ];
  }

  /**
   * Creates a LinkHandler instance that bypasses the parent constructor.
   *
   * @return \Drupal\Driver\Core\Field\LinkHandler
   *   The handler instance.
   */
  protected function createHandler(): LinkHandler {
    $reflection = new \ReflectionClass(LinkHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

}
