<?php
namespace Drupal\Driver\Plugin\DriverEntity;

use Drupal\Driver\Plugin\DriverEntityPluginDrupal8Base;

/**
 * A driver field plugin used to test selecting an arbitrary plugin.
 *
 * @DriverEntity(
 *   id = "node8",
 *   version = 8,
 *   weight = -100,
 *   entityTypes = {
 *     "node",
 *   },
 * )
 */
class NodeDrupal8 extends DriverEntityPluginDrupal8Base {

  /**
   * The id of the attached node.
   *
   * @var integer;
   *
   * @deprecated Use id() instead.
   */
  public $nid;

  /**
   * {@inheritdoc}
   */
  public function save() {
    parent::save();
    $this->nid = $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function set($identifier, $field) {
    if ($identifier === 'author') {
      $identifier = 'uid';
    }
    parent::set($identifier, $field);
  }
}