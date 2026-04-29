<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Capability: create and delete languages.
 */
interface LanguageCapabilityInterface {

  /**
   * Creates a language.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   Language stub. Must carry a 'langcode' value.
   *
   * @return \Drupal\Driver\Entity\EntityStubInterface|false
   *   The saved stub, or FALSE if the language already exists.
   */
  public function languageCreate(EntityStubInterface $stub): EntityStubInterface|false;

  /**
   * Deletes a language.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   Language stub. Must carry a 'langcode' value.
   */
  public function languageDelete(EntityStubInterface $stub): void;

}
