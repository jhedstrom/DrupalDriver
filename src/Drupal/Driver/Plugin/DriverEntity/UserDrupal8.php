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
class UserDrupal8 extends DriverEntityPluginDrupal8Base
{

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
    public function delete()
    {
        user_cancel(array(), $this->id(), 'user_cancel_delete');
    }

  /**
   * {@inheritdoc}
   */
    public function load($entityId)
    {
        $entity = parent::load($entityId);
        $this->uid = is_null($this->entity) ? NULL : $this->id();
        return $entity;
    }

  /**
   * {@inheritdoc}
   */
    public function save()
    {
        parent::save();
        $this->uid = $this->id();
    }

  /**
   * {@inheritdoc}
   */
    public function set($identifier, $field)
    {
      // Ignore the role key passed by Drupal extension.
      if ($identifier !== 'role') {
        parent::set($identifier, $field);
      }
    }

  /**
   * {@inheritdoc}
   */
    protected function getNewEntity()
    {
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
    public function addRole($roleIdentifier)
    {
        // Use a driver field to convert identifier to id.
        $driverField = $this->getNewDriverField('roles', $roleIdentifier);
        $roleId = $driverField->getProcessedValues()[0]['target_id'];

        $roles = $this->getEntity()->getRoles(true);
        $roles[] = $roleId;
        $this->getEntity()->set('roles', array_unique($roles));
    }
}
