<?php

namespace Drupal\Driver\Cores;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Driver\Exception\BootstrapException;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\mailsystem\MailsystemManager;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Driver\Plugin\DriverFieldPluginManager;
use Drupal\Driver\Wrapper\Field\DriverFieldDrupal8;

/**
 * Drupal 8 core.
 */
class Drupal8 extends AbstractCore {

  /**
   * Tracks original configuration values.
   *
   * This is necessary since configurations modified here are actually saved so
   * that they persist values across bootstraps.
   *
   * @var array
   *   An array of data, keyed by configuration name.
   */
  protected $originalConfiguration = [];

  /**
   * {@inheritdoc}
   */
  public function bootstrap() {
    // Validate, and prepare environment for Drupal bootstrap.
    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', $this->drupalRoot);
    }

    // Bootstrap Drupal.
    chdir(DRUPAL_ROOT);
    $autoloader = require DRUPAL_ROOT . '/autoload.php';
    require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';
    $this->validateDrupalSite();

    $request = Request::createFromGlobals();
    $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
    $kernel->boot();
    $kernel->prepareLegacyRequest($request);

    // Initialise an anonymous session. required for the bootstrap.
    \Drupal::service('session_manager')->start();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache() {
    // Need to change into the Drupal root directory or the registry explodes.
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCreate($node) {
    // Throw an exception if the node type is missing or does not exist.
    if (!isset($node->type) || !$node->type) {
      throw new \Exception("Cannot create content because it is missing the required property 'type'.");
    }
    $bundles = \Drupal::entityManager()->getBundleInfo('node');
    if (!in_array($node->type, array_keys($bundles))) {
      throw new \Exception("Cannot create content because provided content type '$node->type' does not exist.");
    }
    // If 'author' is set, remap it to 'uid'.
    if (isset($node->author)) {
      $user = user_load_by_name($node->author);
      if ($user) {
        $node->uid = $user->id();
      }
    }
    $this->expandEntityFields('node', $node);
    $entity = Node::create((array) $node);
    $entity->save();

    $node->nid = $entity->id();

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete($node) {
    $node = $node instanceof NodeInterface ? $node : Node::load($node->nid);
    if ($node instanceof NodeInterface) {
      $node->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function runCron() {
    return \Drupal::service('cron')->run();
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user) {
    //$this->validateDrupalSite();

    // Default status to TRUE if not explicitly creating a blocked user.
    if (!isset($user->status)) {
      $user->status = 1;
    }

    // Clone user object, otherwise user_save() changes the password to the
    // hashed password.
    $this->expandEntityFields('user', $user);
    $account = entity_create('user', (array) $user);
    $account->save();

    // Store UID.
    $user->uid = $account->id();
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions) {
    // Generate a random, lowercase machine name.
    $rid = strtolower($this->random->name(8, TRUE));

    // Generate a random label.
    $name = trim($this->random->name(8, TRUE));

    // Convert labels to machine names.
    $this->convertPermissions($permissions);

    // Check the all the permissions strings are valid.
    $this->checkPermissions($permissions);

    // Create new role.
    $role = entity_create('user_role', array(
      'id' => $rid,
      'label' => $name,
    ));
    $result = $role->save();

    if ($result === SAVED_NEW) {
      // Grant the specified permissions to the role, if any.
      if (!empty($permissions)) {
        user_role_grant_permissions($role->id(), $permissions);
      }
      return $role->id();
    }

    throw new \RuntimeException(sprintf('Failed to create a role with "%s" permission(s).', implode(', ', $permissions)));
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete($role_name) {
    $role = user_role_load($role_name);

    if (!$role) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    $role->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch() {
    $this->validateDrupalSite();
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    batch_process();
  }

  /**
   * Retrieve all permissions.
   *
   * @return array
   *   Array of all defined permissions.
   */
  protected function getAllPermissions() {
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
  protected function convertPermissions(array &$permissions) {
    $all_permissions = $this->getAllPermissions();

    foreach ($all_permissions as $name => $definition) {
      $key = array_search($definition['title'], $permissions);
      if (FALSE !== $key) {
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
  protected function checkPermissions(array &$permissions) {
    $available = array_keys($this->getAllPermissions());

    foreach ($permissions as $permission) {
      if (!in_array($permission, $available)) {
        throw new \RuntimeException(sprintf('Invalid permission "%s".', $permission));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user) {
    user_cancel(array(), $user->uid, 'user_cancel_delete');
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role_name) {
    // Allow both machine and human role names.
    $roles = user_role_names();
    $id = array_search($role_name, $roles);
    if (FALSE !== $id) {
      $role_name = $id;
    }

    if (!$role = user_role_load($role_name)) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    $account = \user_load($user->uid);
    $account->addRole($role->id());
    $account->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrupalSite() {
    if ('default' !== $this->uri) {
      // Fake the necessary HTTP headers that Drupal needs:
      $drupal_base_url = parse_url($this->uri);
      // If there's no url scheme set, add http:// and re-parse the url
      // so the host and path values are set accurately.
      if (!array_key_exists('scheme', $drupal_base_url)) {
        $drupal_base_url = parse_url($this->uri);
      }
      // Fill in defaults.
      $drupal_base_url += array(
        'path' => NULL,
        'host' => NULL,
        'port' => NULL,
      );
      $_SERVER['HTTP_HOST'] = $drupal_base_url['host'];

      if ($drupal_base_url['port']) {
        $_SERVER['HTTP_HOST'] .= ':' . $drupal_base_url['port'];
      }
      $_SERVER['SERVER_PORT'] = $drupal_base_url['port'];

      if (array_key_exists('path', $drupal_base_url)) {
        $_SERVER['PHP_SELF'] = $drupal_base_url['path'] . '/index.php';
      }
      else {
        $_SERVER['PHP_SELF'] = '/index.php';
      }
    }
    else {
      $_SERVER['HTTP_HOST'] = 'default';
      $_SERVER['PHP_SELF'] = '/index.php';
    }

    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'];
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['REQUEST_METHOD'] = NULL;

    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['HTTP_USER_AGENT'] = NULL;

    $conf_path = DrupalKernel::findSitePath(Request::createFromGlobals());
    $conf_file = $this->drupalRoot . "/$conf_path/settings.php";
    if (!file_exists($conf_file)) {
      throw new BootstrapException(sprintf('Could not find a Drupal settings.php file at "%s"', $conf_file));
    }
    $drushrc_file = $this->drupalRoot . "/$conf_path/drushrc.php";
    if (file_exists($drushrc_file)) {
      require_once $drushrc_file;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function termCreate(\stdClass $term) {
    $term->vid = $term->vocabulary_machine_name;

    if (isset($term->parent)) {
      $parent = \taxonomy_term_load_multiple_by_name($term->parent, $term->vocabulary_machine_name);
      if (!empty($parent)) {
        $parent = reset($parent);
        $term->parent = $parent->id();
      }
    }

    $this->expandEntityFields('taxonomy_term', $term);
    $entity = Term::create((array) $term);
    $entity->save();

    $term->tid = $entity->id();
    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term) {
    $term = $term instanceof TermInterface ? $term : Term::load($term->tid);
    if ($term instanceof TermInterface) {
      $term->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleList() {
    return array_keys(\Drupal::moduleHandler()->getModuleList());
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionPathList() {
    $paths = array();

    // Get enabled modules.
    foreach (\Drupal::moduleHandler()->getModuleList() as $module) {
      $paths[] = $this->drupalRoot . DIRECTORY_SEPARATOR . $module->getPath();
    }

    return $paths;
  }

  /**
   * Expands specified base fields on the entity object.
   *
   * @param string $entity_type
   *   The entity type for which to return the field types.
   * @param \stdClass $entity
   *   Entity object.
   * @param array $base_fields
   *   Base fields to be expanded in addition to user defined fields.
   */
  public function expandEntityBaseFields($entity_type, \stdClass $entity, array $base_fields) {
    $this->expandEntityFields($entity_type, $entity, $base_fields);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFieldTypes($entity_type, array $base_fields = array()) {
    $return = array();
    $fields = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type);
    foreach ($fields as $field_name => $field) {
      if ($this->isField($entity_type, $field_name)
        || (in_array($field_name, $base_fields) && $this->isBaseField($entity_type, $field_name))) {
        $return[$field_name] = $field->getType();
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function isField($entity_type, $field_name) {
    $fields = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type);
    return (isset($fields[$field_name]) && $fields[$field_name] instanceof FieldStorageConfig);
  }

  /**
   * {@inheritdoc}
   */
  public function isBaseField($entity_type, $field_name) {
    $fields = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type);
    return (isset($fields[$field_name]) && $fields[$field_name] instanceof BaseFieldDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public function languageCreate(\stdClass $language) {
    $langcode = $language->langcode;

    // Enable a language only if it has not been enabled already.
    if (!ConfigurableLanguage::load($langcode)) {
      $created_language = ConfigurableLanguage::createFromLangcode($language->langcode);
      if (!$created_language) {
        throw new InvalidArgumentException("There is no predefined language with langcode '{$langcode}'.");
      }
      $created_language->save();
      return $language;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function languageDelete(\stdClass $language) {
    $configurable_language = ConfigurableLanguage::load($language->langcode);
    $configurable_language->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches() {
    drupal_static_reset();
    \Drupal::service('cache_tags.invalidator')->resetChecksums();
  }

  /**
   * {@inheritdoc}
   */
  public function configGet($name, $key = '') {
    return \Drupal::config($name)->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet($name, $key, $value) {
    \Drupal::configFactory()->getEditable($name)
      ->set($key, $value)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreate($entity_type, $entity) {
    // If the bundle field is empty, put the inferred bundle value in it.
    $bundle_key = \Drupal::entityManager()->getDefinition($entity_type)->getKey('bundle');
    if (!isset($entity->$bundle_key) && isset($entity->step_bundle)) {
      $entity->$bundle_key = $entity->step_bundle;
    }

    // Throw an exception if a bundle is specified but does not exist.
    if (isset($entity->$bundle_key) && ($entity->$bundle_key !== NULL)) {
      $bundles = \Drupal::entityManager()->getBundleInfo($entity_type);
      if (!in_array($entity->$bundle_key, array_keys($bundles))) {
        throw new \Exception("Cannot create entity because provided bundle " . $entity->$bundle_key . " does not exist.");
      }
    }
    if (empty($entity_type)) {
      throw new \Exception("You must specify an entity type to create an entity.");
    }

    $this->expandEntityFields($entity_type, $entity);
    $createdEntity = entity_create($entity_type, (array) $entity);
    $createdEntity->save();

    $entity->id = $createdEntity->id();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete($entity_type, $entity) {
    $entity = $entity instanceof EntityInterface ? $entity : entity_load($entity_type, $entity->id);
    if ($entity instanceof EntityInterface) {
      $entity->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function startCollectingMail() {
    $config = \Drupal::configFactory()->getEditable('system.mail');
    $data = $config->getRawData();

    // Save the original values for restoration after.
    $this->originalConfiguration['system.mail'] = $data;

    // @todo Use a collector that supports html after D#2223967 lands.
    $data['interface'] = ['default' => 'test_mail_collector'];
    $config->setData($data)->save();
    // Disable the mail system module's mail if enabled.
    $this->startCollectingMailSystemMail();
  }

  /**
   * {@inheritdoc}
   */
  public function stopCollectingMail() {
    $config = \Drupal::configFactory()->getEditable('system.mail');
    $config->setData($this->originalConfiguration['system.mail'])->save();
    // Re-enable the mailsystem module's mail if enabled.
    $this->stopCollectingMailSystemMail();
  }

  /**
   * {@inheritdoc}
   */
  public function getMail() {
    \Drupal::state()->resetCache();
    $mail = \Drupal::state()->get('system.test_mail_collector') ?: [];
    // Discard cancelled mail.
    $mail = array_values(array_filter($mail, function ($mailItem) {
      return ($mailItem['send'] == TRUE);
    }));
    return $mail;
  }

  /**
   * {@inheritdoc}
   */
  public function clearMail() {
    \Drupal::state()->set('system.test_mail_collector', []);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail($body = '', $subject = '', $to = '', $langcode = '') {
    // Send the mail, via the system module's hook_mail.
    $params['context']['message'] = $body;
    $params['context']['subject'] = $subject;
    $mailManager = \Drupal::service('plugin.manager.mail');
    $result = $mailManager->mail('system', '', $to, $langcode, $params, NULL, TRUE);
    return $result;
  }

  /**
   * If the Mail System module is enabled, collect that mail too.
   *
   * @see MailsystemManager::getPluginInstance()
   */
  protected function startCollectingMailSystemMail() {
    if (\Drupal::moduleHandler()->moduleExists('mailsystem')) {
      $config = \Drupal::configFactory()->getEditable('mailsystem.settings');
      $data = $config->getRawData();

      // Track original data for restoration.
      $this->originalConfiguration['mailsystem.settings'] = $data;

      // Convert all of the 'senders' to the test collector.
      $data = $this->findMailSystemSenders($data);
      $config->setData($data)->save();
    }
  }

  /**
   * Find and replace all the mail system sender plugins with the test plugin.
   *
   * This method calls itself recursively.
   */
  protected function findMailSystemSenders(array $data) {
    foreach ($data as $key => $values) {
      if (is_array($values)) {
        if (isset($values[MailsystemManager::MAILSYSTEM_TYPE_SENDING])) {
          $data[$key][MailsystemManager::MAILSYSTEM_TYPE_SENDING] = 'test_mail_collector';
        }
        else {
          $data[$key] = $this->findMailSystemSenders($values);
        }
      }
    }
    return $data;
  }

  /**
   * If the Mail System module is enabled, stop collecting those mails.
   */
  protected function stopCollectingMailSystemMail() {
    if (\Drupal::moduleHandler()->moduleExists('mailsystem')) {
      \Drupal::configFactory()->getEditable('mailsystem.settings')->setData($this->originalConfiguration['mailsystem.settings'])->save();
    }
  }

  /**
   * Expands properties on the given entity object to the expected structure.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \stdClass $entity
   *   Entity object.
   */
  protected function expandEntityFields($entity_type, \stdClass $entity) {
    $field_types = $this->getEntityFieldTypes($entity_type);
    $bundle_key = \Drupal::entityManager()->getDefinition($entity_type)->getKey('bundle');
    if (isset($entity->$bundle_key) && ($entity->$bundle_key !== NULL)) {
      $bundle = $entity->$bundle_key;
    }
    else {
      $bundle = $entity_type;
    }

    foreach ($field_types as $field_name => $type) {
      if (isset($entity->$field_name)) {
        $namespaces = \Drupal::service('container.namespaces');
        $cache_backend = \Drupal::service('cache.discovery');
        $module_handler = \Drupal::service('module_handler');
        $fieldPluginManager = New DriverFieldPluginManager($namespaces, $cache_backend, $module_handler);
        // @todo find a bettter way of standardising single/multi value fields
        if (is_array($entity->$field_name)) {
          $fieldValues = $entity->$field_name;
        }
        else {
          $fieldValues = [$entity->$field_name];
        }
        $field = New DriverFieldDrupal8(
          $fieldPluginManager,
          $fieldValues,
          $field_name,
          $entity_type,
          $bundle
        );
        $entity->$field_name = $field->getProcessedValues();
      }
    }
  }

}
