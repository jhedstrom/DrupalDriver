<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Unit\Core\Hint;

use Drupal\Driver\Core\Hint\AuthorHint;
use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Exception\CreationHintResolutionException;
use Drupal\Driver\Hint\PreCreateHintInterface;
use Drupal\Tests\Driver\Unit\Fixtures\FakeUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the 'AuthorHint' creation hint.
 *
 * @group hints
 */
#[Group('hints')]
class AuthorHintTest extends TestCase {

  /**
   * Tests metadata accessors.
   */
  public function testMetadataAccessors(): void {
    $hint = new AuthorHint(static fn (): ?object => NULL);

    $this->assertInstanceOf(PreCreateHintInterface::class, $hint);
    $this->assertSame('author', $hint->getName());
    $this->assertSame('node', $hint->getEntityType());
    $this->assertNotSame('', $hint->getDescription());
  }

  /**
   * Tests that a known username resolves to 'uid' and removes 'author'.
   */
  public function testApplyToStubResolvesKnownUser(): void {
    $hint = new AuthorHint(static fn (string $name): object => new FakeUser(42));

    $stub = new EntityStub('node', 'article', ['title' => 'Hello', 'author' => 'alice']);

    $hint->applyToStub($stub);

    $this->assertSame(42, $stub->getValue('uid'));
    $this->assertFalse($stub->hasValue('author'));
    $this->assertSame('Hello', $stub->getValue('title'));
  }

  /**
   * Tests that an unknown username throws and leaves the stub alone.
   */
  public function testApplyToStubThrowsOnUnknownUser(): void {
    $hint = new AuthorHint(static fn (): ?object => NULL);

    $stub = new EntityStub('node', 'article', ['author' => 'auther']);

    try {
      $hint->applyToStub($stub);
      $this->fail('Expected CreationHintResolutionException.');
    }
    catch (CreationHintResolutionException $e) {
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
   */
  #[DataProvider('dataProviderApplyToStubCoercesValueToString')]
  public function testApplyToStubCoercesValueToString(mixed $author, string $expected_lookup): void {
    $received = NULL;
    $hint = new AuthorHint(static function (string $name) use (&$received): object {
      $received = $name;

      return new FakeUser(1);
    });

    $stub = new EntityStub('node', 'article', ['author' => $author]);

    $hint->applyToStub($stub);

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
   */
  #[DataProvider('dataProviderApplyToStubThrowsOnEmptyAuthor')]
  public function testApplyToStubThrowsOnEmptyAuthor(mixed $author): void {
    $hint = new AuthorHint(static fn (): object => new FakeUser(1));

    $stub = new EntityStub('node', 'article', ['author' => $author]);

    $this->expectException(CreationHintResolutionException::class);
    $this->expectExceptionMessageMatches("/'author' creation hint is set but empty/");

    $hint->applyToStub($stub);
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
