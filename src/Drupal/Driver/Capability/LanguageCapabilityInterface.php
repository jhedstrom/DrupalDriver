<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: create and delete languages.
 */
interface LanguageCapabilityInterface {

  /**
   * Creates a language.
   *
   * @param \stdClass $language
   *   Object with at least a `langcode` property.
   *
   * @return \stdClass|false
   *   The language object, or FALSE if the language already exists.
   */
  public function languageCreate(\stdClass $language): \stdClass|false;

  /**
   * Deletes a language.
   *
   * @param \stdClass $language
   *   Object with at least a `langcode` property.
   */
  public function languageDelete(\stdClass $language): void;

}
