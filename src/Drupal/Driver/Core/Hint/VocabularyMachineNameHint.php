<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Hint;

use Drupal\Driver\Entity\EntityStubInterface;
use Drupal\Driver\Hint\PreCreateHintInterface;

/**
 * Renames 'vocabulary_machine_name' on a term stub to 'vid'.
 *
 * The typed bundle constructor argument and an explicit 'vid' value both
 * take priority over the alias. When neither is present the alias value
 * is copied to 'vid'. The alias key is always removed once handled. The
 * hint does not validate vocabulary existence - the create method does
 * that after all pre-create hints have run.
 */
class VocabularyMachineNameHint implements PreCreateHintInterface {

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'vocabulary_machine_name';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'taxonomy_term';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return "Selects the vocabulary for a taxonomy term by machine name. Maps to 'vid' on the stub when no bundle or 'vid' is already set.";
  }

  /**
   * {@inheritdoc}
   */
  public function applyToStub(EntityStubInterface $stub): void {
    if (!$stub->hasValue('vocabulary_machine_name')) {
      return;
    }

    $vid = $stub->getValue('vocabulary_machine_name');

    if ($stub->getBundle() === NULL && !$stub->hasValue('vid')) {
      $stub->setValue('vid', (string) $vid);
    }

    $stub->removeValue('vocabulary_machine_name');
  }

}
