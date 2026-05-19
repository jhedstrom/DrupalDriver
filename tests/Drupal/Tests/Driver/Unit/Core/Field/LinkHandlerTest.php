<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Field;

use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Core\Field\LinkHandler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the LinkHandler field handler.
 *
 * @group fields
 */
#[Group('fields')]
class LinkHandlerTest extends FieldHandlerUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createHandler(): FieldHandlerInterface {
    return (new \ReflectionClass(LinkHandler::class))->newInstanceWithoutConstructor();
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderExpand(): \Iterator {
    yield 'uri only via list' => [
      ['https://example.com'],
      [['uri' => 'https://example.com', 'options' => []]],
      NULL,
      NULL,
    ];
    yield 'positional title and uri' => [
      [['My link', 'https://example.com']],
      [['title' => 'My link', 'uri' => 'https://example.com', 'options' => []]],
      NULL,
      NULL,
    ];
    yield 'positional with options query string' => [
      [['My link', 'https://example.com', 'target=_blank&rel=nofollow']],
      [[
        'title' => 'My link',
        'uri' => 'https://example.com',
        'options' => ['target' => '_blank', 'rel' => 'nofollow'],
      ],
      ],
      NULL,
      NULL,
    ];
    yield 'keyed record' => [
      [['title' => 'My link', 'uri' => 'https://example.com']],
      [['title' => 'My link', 'uri' => 'https://example.com', 'options' => []]],
      NULL,
      NULL,
    ];
    yield 'keyed record with options string' => [
      [['title' => 'My link', 'uri' => 'https://example.com', 'options' => 'target=_blank']],
      [['title' => 'My link', 'uri' => 'https://example.com', 'options' => ['target' => '_blank']]],
      NULL,
      NULL,
    ];
    yield 'keyed record with options array' => [
      [['title' => 'My link', 'uri' => 'https://example.com', 'options' => ['target' => '_blank']]],
      [['title' => 'My link', 'uri' => 'https://example.com', 'options' => ['target' => '_blank']]],
      NULL,
      NULL,
    ];
    yield 'multi-delta mixed shapes' => [
      [
        'https://first.com',
        ['title' => 'Second', 'uri' => 'https://second.com'],
      ],
      [
        ['uri' => 'https://first.com', 'options' => []],
        ['title' => 'Second', 'uri' => 'https://second.com', 'options' => []],
      ],
      NULL,
      NULL,
    ];

    yield 'top-level mixed positional and named keys rejected' => [
      ['https://first.com', 'title' => 'Mixed'],
      NULL,
      \InvalidArgumentException::class,
      'Link field value cannot mix positional and named keys',
    ];
    yield 'record missing uri rejected' => [
      [['title' => 'No URI']],
      NULL,
      \InvalidArgumentException::class,
      'Link field record must include a uri',
    ];
  }

}
