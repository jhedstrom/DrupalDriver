<?php

namespace Drupal\Driver\Cores;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Driver\Exception\BootstrapException;
use Drupal\Driver\Wrapper\Entity\DriverEntityWrapperInterface;
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
use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;

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
    $entity = $this->getNewEntity('node');
    $entity->setFields((array) $node);
    $entity->save();
    $node->nid = $entity->id();
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete($node) {
    $nid = $node instanceof NodeInterface ? $node->id() : $node->nid;
    $entity = $this->getNewEntity('node');
    $entity->load($nid);
    $entity->delete();
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
    // @todo determine if this needs to be here. It disrupts the new kernel
    // tests.
    $this->validateDrupalSite();

    $entity = $this->getNewEntity('user');
    $entity->setFields((array) $user);
    $entity->save();
    $user->uid = $entity->id();
    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions) {
    // Generate a random machine name & label.
    $id = strtolower($this->random->name(8, TRUE));
    $label = trim($this->random->name(8, TRUE));

    $entity = $this->getNewEntity('role');
    $entity->set('id', $id);
    $entity->set('label', $label);
    $entity->grantPermissions($permissions);
    $entity->save();
    return $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete($role_name) {
    $role = $this->getNewEntity('role');
    $role->load($role_name);
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
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user) {
    // Not using user_cancel here leads to an error when batch_process()
    // is subsequently called.
    user_cancel(array(), $user->uid, 'user_cancel_delete');
    //$entity = $this->getNewEntity('user');
    //$entity->load($user->uid);
    //$entity->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $uid = $user->uid;
    $user = $this->getNewEntity('user');
    $user->load($uid);
    $user->addRole($role_name);
    $user->save();
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
    $entity = $this->getNewEntity('taxonomy_term');
    $entity->setFields((array) $term);
    $entity->save();
    $term->tid = $entity->id();
    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term) {
    $entity = $this->getNewEntity('taxonomy_term');
    $entity->load($term->tid);
    $entity->delete();
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
   * Get a new driver entity wrapper.
   *
   * @return \Drupal\Driver\Wrapper\Entity\DriverEntityWrapperInterface;
   */
  public function getNewEntity($type, $bundle = NULL) {
    $entity = new DriverEntityDrupal8($type, $bundle);
    return $entity;
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
      $entity = $this->getNewEntity('configurable_language');
      $entity->set('id', $langcode);
      $entity->set('label', $langcode);
      $entity->save();
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
    $entityWrapped = $this->getNewEntity($entity_type);
    $entityWrapped->setFields((array) $entity);
    $entityWrapped->save();
    $entity->id = $entityWrapped->id();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete($entity_type, $entity) {
    $eid = $entity instanceof EntityInterface ? $entity->id() : $entity->id;
    $entity = $this->getNewEntity($entity_type);
    $entity->load($eid);
    $entity->delete();
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
        // @todo find a bettter way of standardising single/multi value fields
        if (is_array($entity->$field_name)) {
          $fieldValues = $entity->$field_name;
        }
        else {
          $fieldValues = [$entity->$field_name];
        }
        $field = New DriverFieldDrupal8(
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
