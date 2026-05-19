<?php

declare(strict_types=1);

namespace Drupal\Driver\Core\Field;

/**
 * Field handler contract.
 */
interface FieldHandlerInterface {

  /**
   * Transforms loose field input into the storage shape.
   *
   * @param mixed $values
   *   Whatever shape the caller produced.
   *
   * @return array<int|string, mixed>
   *   Field values in the format expected by Drupal's entity storage.
   */
  public function expand(mixed $values): array;

}
