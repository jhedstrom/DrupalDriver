<?php

declare(strict_types=1);

namespace Drupal\Driver\Core;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Driver\Exception\BootstrapException;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\mailsystem\MailsystemManager;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Default Drupal core implementation.
 */
class Core extends AbstractCore implements CoreAuthenticationInterface {

  /**
   * Tracks original configuration values.
   *
   * This is necessary since configurations modified here are actually saved so
   * that they persist values across bootstraps.
   *
   * @var array<string, mixed>
   *   An array of data, keyed by configuration name.
   */
  protected array $originalConfiguration = [];

  /**
   * {@inheritdoc}
   */
  public function bootstrap(): void {
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
    // A route is required for route matching. In order to support Drupal 10
    // along with 8/9, we use the hardcoded values of RouteObjectInterface
    // constants ROUTE_NAME and ROUTE_OBJECT.
    // @see https://www.drupal.org/node/3151009
    $request->attributes->set('_route_object', new Route('<none>'));
    $request->attributes->set('_route', '<none>');

    $kernel->preHandle($request);

    // Initialise an anonymous session. required for the bootstrap.
    \Drupal::service('session_manager')->start();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {
    // Need to change into the Drupal root directory or the registry explodes.
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCreate($node): object {
    // Throw an exception if the node type is missing or does not exist.
    /** @var \stdClass $node */
    if (!isset($node->type) || !$node->type) {
      throw new \Exception("Cannot create content because it is missing the required property 'type'.");
    }

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundles = $bundle_info->getBundleInfo('node');
    if (!in_array($node->type, array_keys($bundles))) {
      throw new \Exception(sprintf('Cannot create content because provided content type %s does not exist.', $node->type));
    }
    // If 'author' is set, remap it to 'uid'.
    if (isset($node->author)) {
      $user = user_load_by_name($node->author);
      /** @var \Drupal\user\Entity\User $user */
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
  public function nodeDelete($node): void {
    $node = $node instanceof NodeInterface ? $node : Node::load($node->nid);
    if ($node instanceof NodeInterface) {
      $node->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function runCron(): bool {
    $_SERVER['REQUEST_TIME'] = time();
    \Drupal::request()->server->set('REQUEST_TIME', $_SERVER['REQUEST_TIME']);
    return \Drupal::service('cron')->run();
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user): void {
    // Default status to TRUE if not explicitly creating a blocked user.
    if (!isset($user->status)) {
      $user->status = 1;
    }

    $this->expandEntityFields('user', $user);
    $account = \Drupal::entityTypeManager()->getStorage('user')->create((array) $user);
    $account->save();

    // Store UID.
    $user->uid = $account->id();
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions): int|string {
    // Generate a random, lowercase machine name.
    $rid = strtolower($this->random->name(8, TRUE));

    // Generate a random label.
    $name = trim($this->random->name(8, TRUE));

    // Convert labels to machine names.
    $this->convertPermissions($permissions);

    // Check the all the permissions strings are valid.
    $this->checkPermissions($permissions);

    // Create new role.
    $role = \Drupal::entityTypeManager()->getStorage('user_role')->create([
      'id' => $rid,
      'label' => $name,
    ]);
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
  public function roleDelete($role_name): void {
    $role = Role::load($role_name);

    if ($role) {
      $role->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch(): void {
    $this->validateDrupalSite();
    if ($batch =& batch_get()) {
      $batch['progressive'] = FALSE;
      batch_process();
    }
  }

  /**
   * Retrieve all permissions.
   *
   * @return array<string, mixed>
   *   Array of all defined permissions.
   */
  protected function getAllPermissions(): array {
    $permissions = &drupal_static(__FUNCTION__);

    if (!isset($permissions)) {
      $permissions = \Drupal::service('user.permissions')->getPermissions();
    }

    return $permissions;
  }

  /**
   * Convert any permission labels to machine name.
   *
   * @param array<string> &$permissions
   *   Array of permission names.
   */
  protected function convertPermissions(array &$permissions): void {
    $all_permissions = $this->getAllPermissions();

    foreach ($all_permissions as $name => $definition) {
      // Cast the title to string: Drupal returns TranslatableMarkup objects
      // for permission titles, which would never strictly equal the plain
      // string labels supplied by callers.
      $key = array_search((string) $definition['title'], $permissions, TRUE);
      if ($key !== FALSE) {
        $permissions[$key] = $name;
      }
    }
  }

  /**
   * Check to make sure that the array of permissions are valid.
   *
   * @param array<string> $permissions
   *   Permissions to check.
   */
  protected function checkPermissions(array &$permissions): void {
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
  public function userDelete(\stdClass $user): void {
    user_cancel([], $user->uid, 'user_cancel_delete');
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role_name): void {
    // Allow both machine and human role names.
    $query = \Drupal::entityQuery('user_role');
    $conditions = $query->orConditionGroup()
      ->condition('id', $role_name)
      ->condition('label', $role_name);
    $rids = $query
      ->condition($conditions)
      ->execute();
    if (!$rids) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    $account = User::load($user->uid);
    $account->addRole(reset($rids));
    $account->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrupalSite(): void {
    if ('default' !== $this->uri) {
      // Fake the necessary HTTP headers that Drupal needs:
      $drupal_base_url = parse_url($this->uri);
      // If there's no url scheme set, add http:// and re-parse the url
      // so the host and path values are set accurately.
      if (!array_key_exists('scheme', $drupal_base_url)) {
        $drupal_base_url = parse_url($this->uri);
      }
      // Fill in defaults.
      $drupal_base_url += [
        'path' => NULL,
        'host' => NULL,
        'port' => NULL,
      ];
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

    $conf_path = DrupalKernel::findSitePath(Request::createFromGlobals());
    $conf_file = $this->drupalRoot . sprintf('/%s/settings.php', $conf_path);
    if (!file_exists($conf_file)) {
      throw new BootstrapException(sprintf('Could not find a Drupal settings.php file at "%s"', $conf_file));
    }
    $drushrc_file = $this->drupalRoot . sprintf('/%s/drushrc.php', $conf_path);
    if (file_exists($drushrc_file)) {
      require_once $drushrc_file;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function termCreate(\stdClass $term): \stdClass {
    $term->vid = $term->vocabulary_machine_name;

    if (!empty($term->parent)) {
      $query = \Drupal::entityQuery('taxonomy_term')
        ->accessCheck(FALSE)
        ->condition('name', $term->parent)
        ->condition('vid', $term->vocabulary_machine_name);
      $parent_terms = $query->execute();
      if (!empty($parent_terms)) {
        $term->parent = reset($parent_terms);
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
  public function termDelete(\stdClass $term): bool {
    $term = $term instanceof TermInterface ? $term : Term::load($term->tid);
    if ($term instanceof TermInterface) {
      $term->delete();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleList(): array {
    return array_keys(\Drupal::moduleHandler()->getModuleList());
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionPathList(): array {
    $paths = [];

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
   * @param \StdClass $entity
   *   Entity object.
   * @param array<string> $base_fields
   *   Base fields to be expanded in addition to user defined fields.
   */
  public function expandEntityBaseFields(string $entity_type, \StdClass $entity, array $base_fields): void {
    $this->expandEntityFields($entity_type, $entity, $base_fields);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFieldTypes($entity_type, array $base_fields = []): array {
    $return = [];
    $entity_field_manager = $this->getEntityFieldManager();
    $fields = $entity_field_manager->getFieldStorageDefinitions($entity_type);
    if ($base_fields !== []) {
      $fields += $entity_field_manager->getBaseFieldDefinitions($entity_type);
    }
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
  public function isField($entity_type, $field_name): bool {
    $fields = $this->getEntityFieldManager()->getFieldStorageDefinitions($entity_type);
    return (isset($fields[$field_name]) && $fields[$field_name] instanceof FieldStorageConfig);
  }

  /**
   * {@inheritdoc}
   */
  public function isBaseField($entity_type, $field_name): bool {
    $base_fields = $this->getEntityFieldManager()->getBaseFieldDefinitions($entity_type);
    return isset($base_fields[$field_name]);
  }

  /**
   * Returns the entity field manager service.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field manager.
   */
  protected function getEntityFieldManager(): EntityFieldManagerInterface {
    return \Drupal::service('entity_field.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function languageCreate(\stdClass $language): \stdClass|false {
    $langcode = $language->langcode;

    // Enable a language only if it has not been enabled already.
    if (!ConfigurableLanguage::load($langcode)) {
      $created_language = ConfigurableLanguage::createFromLangcode($language->langcode);
      if (!$created_language) {
        throw new \InvalidArgumentException(sprintf("There is no predefined language with langcode '%s'.", $langcode));
      }
      $created_language->save();
      return $language;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function languageDelete(\stdClass $language): void {
    $configurable_language = ConfigurableLanguage::load($language->langcode);
    $configurable_language->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches(): void {
    drupal_static_reset();
    \Drupal::service('cache_tags.invalidator')->resetChecksums();
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $definition) {
      \Drupal::entityTypeManager()->getAccessControlHandler($definition->id())->resetCache();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configGet($name, $key = ''): mixed {
    return \Drupal::config($name)->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function configGetOriginal($name, $key = ''): mixed {
    return \Drupal::config($name)->getOriginal($key, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet($name, $key, $value): void {
    \Drupal::configFactory()->getEditable($name)
      ->set($key, $value)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreate($entity_type, $entity): EntityInterface {
    // If the bundle field is empty, put the inferred bundle value in it.
    $bundle_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('bundle');
    if (!isset($entity->$bundle_key) && isset($entity->step_bundle)) {
      $entity->$bundle_key = $entity->step_bundle;
    }

    // Throw an exception if a bundle is specified but does not exist.
    if (isset($entity->$bundle_key) && ($entity->$bundle_key !== NULL)) {
      /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $bundle_info */
      $bundle_info = \Drupal::service('entity_type.bundle.info');
      $bundles = $bundle_info->getBundleInfo($entity_type);
      if (!in_array($entity->$bundle_key, array_keys($bundles))) {
        throw new \Exception(sprintf("Cannot create entity because provided bundle '%s' does not exist.", $entity->$bundle_key));
      }
    }
    if (empty($entity_type)) {
      throw new \Exception("You must specify an entity type to create an entity.");
    }

    $this->expandEntityFields($entity_type, $entity);
    $created_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->create((array) $entity);
    $created_entity->save();

    // Mutate the stub so callers holding the reference can still read ->id.
    $entity->id = $created_entity->id();

    return $created_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete($entity_type, $entity): void {
    $entity = $entity instanceof EntityInterface ? $entity : \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity->id);
    if ($entity instanceof EntityInterface) {
      $entity->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function moduleInstall($module_name): void {
    \Drupal::service('module_installer')->install([$module_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleUninstall($module_name): void {
    \Drupal::service('module_installer')->uninstall([$module_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function startCollectingMail(): void {
    $config = \Drupal::configFactory()->getEditable('system.mail');
    $mail_config = $config->getRawData();

    $this->storeOriginalConfiguration('system.mail', $mail_config);

    // @todo Use a collector that supports html after D#2223967 lands.
    $mail_config['interface'] = ['default' => 'test_mail_collector'];
    $config->setData($mail_config)->save();
    // Disable the mail system module's mail if enabled.
    $this->startCollectingMailSystemMail();
  }

  /**
   * {@inheritdoc}
   */
  public function stopCollectingMail(): void {
    $config = \Drupal::configFactory()->getEditable('system.mail');
    $config->setData($this->originalConfiguration['system.mail'])->save();
    // Re-enable the mailsystem module's mail if enabled.
    $this->stopCollectingMailSystemMail();
  }

  /**
   * {@inheritdoc}
   */
  public function getMail(): array {
    \Drupal::state()->resetCache();
    $mail = \Drupal::state()->get('system.test_mail_collector') ?: [];
    // Discard cancelled mail.
    $mail = array_values(array_filter($mail, fn(array $mail_item): bool => $mail_item['send'] == TRUE));
    return $mail;
  }

  /**
   * {@inheritdoc}
   */
  public function clearMail(): void {
    \Drupal::state()->set('system.test_mail_collector', []);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail($body = '', $subject = '', $to = '', $langcode = ''): bool {
    // Send the mail, via the system module's hook_mail.
    $params['context']['message'] = $body;
    $params['context']['subject'] = $subject;
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $result = $mail_manager->mail('system', '', $to, $langcode, $params, NULL, TRUE);
    return !empty($result['result']);
  }

  /**
   * If the Mail System module is enabled, collect that mail too.
   *
   * @see MailsystemManager::getPluginInstance()
   */
  protected function startCollectingMailSystemMail(): void {
    if (!\Drupal::moduleHandler()->moduleExists('mailsystem')) {
      return;
    }

    $config = \Drupal::configFactory()->getEditable('mailsystem.settings');
    $mailsystem_config = $config->getRawData();

    $this->storeOriginalConfiguration('mailsystem.settings', $mailsystem_config);

    $mailsystem_config = $this->replaceMailSenders($mailsystem_config);
    $config->setData($mailsystem_config)->save();
  }

  /**
   * Recursively replaces mail sender plugins with the test collector.
   *
   * @param array<string, mixed> $config_tree
   *   The configuration tree to process.
   *
   * @return array<string, mixed>
   *   The modified configuration tree.
   */
  protected function replaceMailSenders(array $config_tree): array {
    foreach ($config_tree as $key => $values) {
      if (!is_array($values)) {
        continue;
      }

      if (isset($values[MailsystemManager::MAILSYSTEM_TYPE_SENDING])) {
        $config_tree[$key][MailsystemManager::MAILSYSTEM_TYPE_SENDING] = 'test_mail_collector';
      }
      else {
        $config_tree[$key] = $this->replaceMailSenders($values);
      }
    }

    return $config_tree;
  }

  /**
   * If the Mail System module is enabled, stop collecting those mails.
   */
  protected function stopCollectingMailSystemMail(): void {
    if (\Drupal::moduleHandler()->moduleExists('mailsystem')) {
      \Drupal::configFactory()->getEditable('mailsystem.settings')->setData($this->originalConfiguration['mailsystem.settings'])->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function login(\stdClass $user): void {
    $account = User::load($user->uid);
    \Drupal::service('account_switcher')->switchTo($account);
  }

  /**
   * {@inheritdoc}
   */
  public function logout(): void {
    // AccountSwitcher::switchBack() throws RuntimeException when the stack is
    // empty. Loop until that happens to ensure all stacked accounts are popped.
    try {
      while (TRUE) {
        \Drupal::service('account_switcher')->switchBack();
      }
    }
    catch (\RuntimeException) {
    }
  }

  /**
   * Store the original value for a piece of configuration.
   *
   * If an original value has previously been stored, it is not updated.
   *
   * @param string $name
   *   The name of the configuration.
   * @param mixed $value
   *   The original value of the configuration.
   */
  protected function storeOriginalConfiguration(string $name, mixed $value): void {
    if (!isset($this->originalConfiguration[$name])) {
      $this->originalConfiguration[$name] = $value;
    }
  }

}
