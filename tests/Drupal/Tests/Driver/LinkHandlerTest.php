<?php

namespace Drupal\Tests\Driver;

use Drupal\Driver\Fields\Drupal8\LinkHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests the LinkHandler field handler.
 */
class LinkHandlerTest extends TestCase {

  /**
   * Tests link field expansion.
   *
   * @param array $input
   *   The input values to expand.
   * @param array $expected
   *   The expected expanded values.
   *
   * @dataProvider dataProviderExpand
   */
  public function testExpand(array $input, array $expected) {
    $handler = $this->createHandler();
    $result = $handler->expand($input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testExpand().
   */
  public function dataProviderExpand() {
    return [
      'numeric indices' => [
        [['My link', 'https://example.com']],
        [['title' => 'My link', 'uri' => 'https://example.com', 'options' => []]],
      ],
      'named keys' => [
        [['title' => 'My link', 'uri' => 'https://example.com']],
        [['title' => 'My link', 'uri' => 'https://example.com', 'options' => []]],
      ],
      'numeric indices with options' => [
        [['My link', 'https://example.com', 'target=_blank&rel=nofollow']],
        [[
          'title' => 'My link',
          'uri' => 'https://example.com',
          'options' => ['target' => '_blank', 'rel' => 'nofollow'],
        ],
        ],
      ],
      'named keys with options' => [
        [['title' => 'My link', 'uri' => 'https://example.com', 'options' => 'target=_blank']],
        [['title' => 'My link', 'uri' => 'https://example.com', 'options' => ['target' => '_blank']]],
      ],
      'multiple values' => [
        [
          ['First', 'https://first.com'],
          ['title' => 'Second', 'uri' => 'https://second.com'],
        ],
        [
          ['title' => 'First', 'uri' => 'https://first.com', 'options' => []],
          ['title' => 'Second', 'uri' => 'https://second.com', 'options' => []],
        ],
      ],
      'no options returns empty array' => [
        [['title' => 'Link', 'uri' => 'https://example.com']],
        [['title' => 'Link', 'uri' => 'https://example.com', 'options' => []]],
      ],
      'uri-only string' => [
        ['https://example.com'],
        [['uri' => 'https://example.com', 'options' => []]],
      ],
      'mixed uri-only and full' => [
        [
          'https://first.com',
          ['title' => 'Second', 'uri' => 'https://second.com'],
        ],
        [
          ['uri' => 'https://first.com', 'options' => []],
          ['title' => 'Second', 'uri' => 'https://second.com', 'options' => []],
        ],
      ],
    ];
  }

  /**
   * Creates a LinkHandler instance that bypasses the parent constructor.
   *
   * @return \Drupal\Driver\Fields\Drupal8\LinkHandler
   *   The handler instance.
   */
  protected function createHandler() {
    $reflection = new \ReflectionClass(LinkHandler::class);
    return $reflection->newInstanceWithoutConstructor();
  }

}
