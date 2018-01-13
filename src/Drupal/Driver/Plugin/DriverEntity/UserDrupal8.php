<?php
namespace Drupal\Driver\Plugin\DriverEntity;

use Drupal\Driver\Plugin\DriverEntityPluginDrupal8Base;

/**
 * A driver field plugin used to test selecting an arbitrary plugin.
 *
 * @DriverEntity(
 *   id = "user8",
 *   version = 8,
 *   weight = -100,
 *   entityTypes = {
 *     "user",
 *   },
 *   labelKeys = {
 *     "name",
 *     "mail",
 *   },
 * )
 */
class UserDrupal8 extends DriverEntityPluginDrupal8Base {

  /**
   * The id of the attached user.
   *
   * @var integer;
   *
   * @deprecated Use id() instead.
   */
  public $uid;

  /**
   * {@inheritdoc}
   */
  public function save() {
    parent::save();
    $this->uid = $this->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntity() {
    $entity = parent::getNewEntity();
    $entity->set('status', 1);
    return $entity;
  }

  /**
   * Add a role by human-friendly identifier.
   *
   * @param string $roleIdentifier
   *   A human-friendly string identifying a role.
   */
  public function addRole($roleIdentifier) {
    $driverField = $this->getNewDriverField('roles', $roleIdentifier);
    $roleId = $driverField->getProcessedValues()[0]['target_id'];
    $roles = $this->getEntity()->getRoles(TRUE);
    $roles[] = $roleId;
    $this->getEntity()->set('roles', array_unique($roles));
    $this->save();
  }

}