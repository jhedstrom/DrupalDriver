<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core\Field;

/**
 * Kernel round-trip test for link fields via the Core driver.
 *
 * Link is a multi-property field (uri, title, options). This test verifies
 * the base class helper handles associative-array deltas correctly and that
 * LinkHandler's output - including the enforced empty 'options' array -
 * round-trips through real storage.
 */
class LinkHandlerKernelTest extends FieldHandlerKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    ...self::BASE_MODULES,
    'link',
  ];

  /**
   * Tests round-trip for a link with both uri and title.
   */
  public function testLinkWithTitleRoundTrip(): void {
    $this->attachField('field_homepage', 'link');

    $this->assertFieldRoundTripViaDriver('field_homepage', [
      ['uri' => 'https://example.com', 'title' => 'Example'],
    ]);
  }

  /**
   * Tests round-trip when the handler is given a URI-only string.
   *
   * LinkHandler converts a bare string into ['uri' => $string] during expand,
   * so the driver-mutated stub holds an array after entityCreate. The base
   * assertion compares that mutated array against the stored field - which
   * proves the scalar-to-array normalization reached storage intact.
   */
  public function testUriOnlyStringRoundTrip(): void {
    $this->attachField('field_homepage', 'link');

    $this->assertFieldRoundTripViaDriver('field_homepage', ['https://example.com']);
  }

}
