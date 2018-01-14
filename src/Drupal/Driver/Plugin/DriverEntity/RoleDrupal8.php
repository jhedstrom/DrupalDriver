<?php
namespace Drupal\Driver\Plugin\DriverEntity;

use Drupal\Driver\Plugin\DriverEntityPluginDrupal8Base;

/**
 * A driver field plugin used to test selecting an arbitrary plugin.
 *
 * @DriverEntity(
 *   id = "role8",
 *   version = 8,
 *   weight = -100,
 *   entityTypes = {
 *     "user_role",
 *   },
 * )
 */
class RoleDrupal8 extends DriverEntityPluginDrupal8Base
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
        $this->uid = $this->id();
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
    protected function getNewEntity()
    {
        $entity = parent::getNewEntity();
        $entity->set('status', 1);
        return $entity;
    }

  /**
   * Grant permissions to role by permission machine name or label.
   *
   * @param string|array $permissions
   *   The permissions to be granted, identifed by string machine nam or label.
   */
    public function grantPermissions($permissions)
    {
        // Allow single string value, as Role::grantPermission does.
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        // Convert labels to machine names.
        $this->convertPermissions($permissions);
        // Check the all the permissions strings are valid.
        $this->checkPermissions($permissions);

        $this->set('permissions', $permissions);
    }

    /**
     * Retrieve all permissions.
     *
     * @return array
     *   Array of all defined permissions.
     */
    protected function getAllPermissions()
    {
        $permissions = &drupal_static(__FUNCTION__);

        if (!isset($permissions)) {
            $permissions = \Drupal::service('user.permissions')->getPermissions();
        }

        return $permissions;
    }

    /**
     * Convert any permission labels to machine name.
     *
     * @param array &$permissions
     *   Array of permission names.
     */
    protected function convertPermissions(array &$permissions)
    {
        $all_permissions = $this->getAllPermissions();

        foreach ($all_permissions as $name => $definition) {
            $key = array_search($definition['title'], $permissions);
            if (false !== $key) {
                $permissions[$key] = $name;
            }
        }
    }

    /**
     * Check to make sure that the array of permissions are valid.
     *
     * @param array $permissions
     *   Permissions to check.
     */
    protected function checkPermissions(array &$permissions)
    {
        $available = array_keys($this->getAllPermissions());

        foreach ($permissions as $permission) {
            if (!in_array($permission, $available)) {
                throw new \RuntimeException(sprintf('Invalid permission "%s".', $permission));
            }
        }
    }
}
