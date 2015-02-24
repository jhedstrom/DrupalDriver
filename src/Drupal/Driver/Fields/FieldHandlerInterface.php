<?php
/**
 * Created by PhpStorm.
 * User: ademarco
 * Date: 2/8/15
 * Time: 11:24 AM
 */

namespace Drupal\Driver\Fields;

/**
 * Interface FieldHandlerInterface
 * @package Drupal\Driver\Fields
 */
interface FieldHandlerInterface {

  /**
   * Expand field values ready to be processed by entity_save().
   *
   * @param $values
   * @return array
   */
  public function expand($values);
}
