<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Alias;

use Drupal\Driver\Alias\PreCreateAliasInterface;
use Drupal\Driver\Core\Alias\AuthorAlias;
use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Exception\CreationAliasResolutionException;
use Drupal\Tests\Driver\Unit\Fixtures\FakeUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the 'AuthorAlias' creation alias.
 *
 * @group aliases
 */
#[Group('aliases')]
class AuthorAliasTest extends TestCase {

  /**
   * Tests metadata accessors.
   */
  public function testMetadataAccessors(): void {
    $alias = new AuthorAlias(static fn (): ?object => NULL);

    $this->assertInstanceOf(PreCreateAliasInterface::class, $alias);
    $this->assertSame('author', $alias->getName());
    $this->assertSame('node', $alias->getEntityType());
    $this->assertNotSame('', $alias->getDescription());
  }

  /**
   * Tests that a known username resolves to 'uid' and removes 'author'.
   */
  public function testApplyToStubResolvesKnownUser(): void {
    $alias = new AuthorAlias(static fn (string $name): object => new FakeUser(42));

    $stub = new EntityStub('node', 'article', ['title' => 'Hello', 'author' => 'alice']);

    $alias->applyToStub($stub);

    $this->assertSame(42, $stub->getValue('uid'));
    $this->assertFalse($stub->hasValue('author'));
    $this->assertSame('Hello', $stub->getValue('title'));
  }

  /**
   * Tests that an unknown username throws and leaves the stub alone.
   */
  public function testApplyToStubThrowsOnUnknownUser(): void {
    $alias = new AuthorAlias(static fn (): ?object => NULL);

    $stub = new EntityStub('node', 'article', ['author' => 'auther']);

    try {
      $alias->applyToStub($stub);
      $this->fail('Expected CreationAliasResolutionException.');
    }
    catch (CreationAliasResolutionException $e) {
      $this->assertStringContainsString("'auther'", $e->getMessage());
      $this->assertTrue($stub->hasValue('author'), 'Stub must still carry the alias when resolution fails.');
      $this->assertFalse($stub->hasValue('uid'), 'No uid should be written when resolution fails.');
    }
  }

  /**
   * Tests that values are coerced to strings before lookup.
   *
   * @param mixed $author
   *   The raw 'author' value placed on the stub.
   * @param string $expected_lookup
   *   The string the closure is expected to receive.
   *
   * @dataProvider dataProviderApplyToStubCoercesValueToString
   */
  #[DataProvider('dataProviderApplyToStubCoercesValueToString')]
  public function testApplyToStubCoercesValueToString(mixed $author, string $expected_lookup): void {
    $received = NULL;
    $alias = new AuthorAlias(static function (string $name) use (&$received): object {
      $received = $name;

      return new FakeUser(1);
    });

    $stub = new EntityStub('node', 'article', ['author' => $author]);

    $alias->applyToStub($stub);

    $this->assertSame($expected_lookup, $received);
  }

  /**
   * Data provider for 'testApplyToStubCoercesValueToString()'.
   *
   * @return iterable<string, array<int, mixed>>
   *   Cases of stub value, expected closure input.
   */
  public static function dataProviderApplyToStubCoercesValueToString(): iterable {
    yield 'plain string' => ['alice', 'alice'];
    yield 'integer-like string' => ['7', '7'];
    yield 'integer coerced' => [7, '7'];
  }

  /**
   * Tests that empty or null 'author' values throw a clear error.
   *
   * Empty strings used to be coerced into a 'user "" not found' message
   * which buried the actual problem (the alias is set but empty). The
   * resolver now throws before the lookup with the empty-alias signal.
   *
   * @param mixed $author
   *   The empty-ish 'author' value placed on the stub.
   *
   * @dataProvider dataProviderApplyToStubThrowsOnEmptyAuthor
   */
  #[DataProvider('dataProviderApplyToStubThrowsOnEmptyAuthor')]
  public function testApplyToStubThrowsOnEmptyAuthor(mixed $author): void {
    $alias = new AuthorAlias(static fn (): object => new FakeUser(1));

    $stub = new EntityStub('node', 'article', ['author' => $author]);

    $this->expectException(CreationAliasResolutionException::class);
    $this->expectExceptionMessageMatches("/'author' creation alias is set but empty/");

    $alias->applyToStub($stub);
  }

  /**
   * Data provider for 'testApplyToStubThrowsOnEmptyAuthor()'.
   *
   * @return iterable<string, array<int, mixed>>
   *   Cases of empty-ish 'author' value.
   */
  public static function dataProviderApplyToStubThrowsOnEmptyAuthor(): iterable {
    yield 'empty string' => [''];
    yield 'null' => [NULL];
  }

}
