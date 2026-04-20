<?php

declare(strict_types=1);

namespace Drupal\Driver\Core;

use Drupal\Component\Utility\Random;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Driver\Core\Field\DefaultHandler;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
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
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Default Drupal core implementation.
 */
class Core implements CoreInterface {

  /**
   * System path to the Drupal installation.
   */
  protected string $drupalRoot;

  /**
   * URI of the Drupal site.
   */
  protected string $uri;

  /**
   * Random value generator.
   */
  protected Random $random;

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
   * Set up the Core implementation.
   *
   * @param string $drupal_root
   *   The absolute path to the Drupal root directory.
   * @param string $uri
   *   URI that is accessing Drupal. Defaults to 'default'.
   * @param \Drupal\Component\Utility\Random|null $random
   *   Optional random-value generator.
   */
  public function __construct(string $drupal_root, string $uri = 'default', ?Random $random = NULL) {
    $resolved = realpath($drupal_root);

    if ($resolved === FALSE) {
      throw new BootstrapException(sprintf('Could not resolve Drupal root "%s".', $drupal_root));
    }

    $this->drupalRoot = $resolved;
    $this->uri = $uri;
    $this->random = $random ?? new Random();
  }

  /**
   * {@inheritdoc}
   */
  public function getRandom(): Random {
    return $this->random;
  }

  /**
   * Returns the Drupal major version this Core targets.
   *
   * The default Core returns 0 - the lookup chain iterates only when version
   * is >= 10, so 0 skips the versioned directories and falls through to the
   * default handlers in 'Core\Field\'.
   *
   * @return int
   *   The Drupal major version, or 0 for the default (no version-specific dir).
   */
  protected function getVersion(): int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldHandler(object $entity, string $entity_type, string $field_name): FieldHandlerInterface {
    $field_types = $this->getEntityFieldTypes($entity_type, [$field_name]);

    if (!isset($field_types[$field_name])) {
      throw new \RuntimeException(sprintf('Field "%s" not found on entity type "%s".', $field_name, $entity_type));
    }

    $camelized_type = Container::camelize($field_types[$field_name]);
    $version = $this->getVersion();

    $candidates = [];

    for ($n = $version; $n >= 10; $n--) {
      $candidates[] = sprintf('\\Drupal\\Driver\\Core%d\\Field\\%sHandler', $n, $camelized_type);
    }

    $candidates[] = sprintf('\\Drupal\\Driver\\Core\\Field\\%sHandler', $camelized_type);

    for ($n = $version; $n >= 10; $n--) {
      $candidates[] = sprintf('\\Drupal\\Driver\\Core%d\\Field\\DefaultHandler', $n);
    }

    foreach ($candidates as $class) {
      if (!class_exists($class)) {
        continue;
      }

      return new $class($entity, $entity_type, $field_name);
    }

    return new DefaultHandler($entity, $entity_type, $field_name);
  }

  /**
   * Expands properties on the given entity object to the expected structure.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \stdClass $entity
   *   Entity object.
   * @param array<string> $base_fields
   *   Optional. Define base fields that will be expanded in addition to user
   *   defined fields.
   */
  protected function expandEntityFields(string $entity_type, \stdClass $entity, array $base_fields = []): void {
    // Include any base fields present as properties on the entity object so
    // their values travel through the field-handler pipeline alongside
    // configured fields. Without this, base entity-reference fields such as
    // 'commerce_product.variations' or 'user.roles' would never reach
    // EntityReferenceHandler and could not be populated from a stub.
    $base_fields = array_values(array_unique(array_merge($base_fields, $this->detectBaseFieldsOnEntity($entity_type, $entity))));

    $field_types = $this->getEntityFieldTypes($entity_type, $base_fields);

    foreach (array_keys($field_types) as $field_name) {
      if (isset($entity->$field_name)) {
        $entity->$field_name = $this->getFieldHandler($entity, $entity_type, $field_name)
          ->expand($entity->$field_name);
      }
    }
  }

  /**
   * Returns the names of base fields set as properties on the entity.
   *
   * The entity type's id key and bundle key are excluded: they identify the
   * record itself and must not pass through the field-handler pipeline, where
   * they would be treated as look-up values (e.g. expanding node's 'type'
   * through EntityReferenceHandler would try to resolve the bundle string as
   * a NodeType config entity reference and discard the plain string form).
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \stdClass $entity
   *   Entity stub whose properties are inspected.
   *
   * @return array<string>
   *   Base field names whose corresponding property is set on the stub.
   */
  protected function detectBaseFieldsOnEntity(string $entity_type, \stdClass $entity): array {
    $definition = \Drupal::entityTypeManager()->getDefinition($entity_type);
    $skip = array_filter([$definition->getKey('id'), $definition->getKey('bundle')]);

    $detected = [];

    foreach (array_keys($this->getEntityFieldManager()->getBaseFieldDefinitions($entity_type)) as $field_name) {
      if (in_array($field_name, $skip, TRUE)) {
        continue;
      }

      if (property_exists($entity, $field_name)) {
        $detected[] = $field_name;
      }
    }

    return $detected;
  }

  /**
   * {@inheritdoc}
   *
   * Executed only in consumer code that boots Drupal from outside a test,
   * so coverage is not measurable: kernel tests already have Drupal booted
   * and re-entering this path would tear the kernel down.
   *
   * @codeCoverageIgnore
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
  public function cacheClear(?string $type = NULL): void {
    // Need to change into the Drupal root directory or the registry explodes.
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCreate(\stdClass $node): object {
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
      /** @var \Drupal\user\Entity\User|null $user */
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
  public function nodeDelete(object $node): void {
    $node = $node instanceof NodeInterface ? $node : Node::load($node->nid);
    if ($node instanceof NodeInterface) {
      $node->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cronRun(): bool {
    $_SERVER['REQUEST_TIME'] = time();
    \Drupal::request()->server->set('REQUEST_TIME', $_SERVER['REQUEST_TIME']);
    return \Drupal::service('cron')->run();
  }

  /**
   * {@inheritdoc}
   */
  public function watchdogFetch(int $count = 10, ?string $type = NULL, ?string $severity = NULL): string {
    if (!\Drupal::moduleHandler()->moduleExists('dblog')) {
      throw new \RuntimeException('The dblog module is not installed; cannot fetch watchdog entries.');
    }

    $query = \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['type', 'severity', 'message', 'variables'])
      ->orderBy('wid', 'DESC')
      ->range(0, $count);

    if ($type !== NULL) {
      $query->condition('type', $type);
    }

    if ($severity !== NULL) {
      $query->condition('severity', $this->resolveSeverityLevel($severity));
    }

    $lines = [];

    foreach ($query->execute() as $row) {
      $variables = $row->variables ? @unserialize($row->variables, ['allowed_classes' => FALSE]) : [];
      $replacements = [];

      if (is_array($variables)) {
        foreach ($variables as $placeholder => $value) {
          $replacements[$placeholder] = is_scalar($value) ? (string) $value : '';
        }
      }

      $message = strtr((string) $row->message, $replacements);
      $lines[] = sprintf('[%s/%s] %s', $row->type, $this->resolveSeverityLabel((int) $row->severity), $message);
    }

    return implode("\n", $lines);
  }

  /**
   * Maps a severity name or numeric string to an RFC 5424 log level integer.
   */
  protected function resolveSeverityLevel(string $severity): int {
    $key = strtolower($severity);
    $levels = array_flip($this->severityLabels());

    if (isset($levels[$key])) {
      return $levels[$key];
    }

    if (ctype_digit($severity)) {
      return (int) $severity;
    }

    throw new \InvalidArgumentException(sprintf('Unknown severity level: %s', $severity));
  }

  /**
   * Returns the symbolic name for an RFC 5424 log level integer.
   */
  protected function resolveSeverityLabel(int $level): string {
    return $this->severityLabels()[$level] ?? (string) $level;
  }

  /**
   * Returns the RFC 5424 severity level-to-label map.
   *
   * @return array<int, string>
   *   Integer level keyed to symbolic name.
   */
  private function severityLabels(): array {
    return [
      0 => 'emergency',
      1 => 'alert',
      2 => 'critical',
      3 => 'error',
      4 => 'warning',
      5 => 'notice',
      6 => 'info',
      7 => 'debug',
    ];
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
  public function roleCreate(array $permissions, ?string $id = NULL, ?string $label = NULL): string {
    $rid = $id ?? strtolower($this->random->name(8, TRUE));
    $role_label = $label ?? ($id ?? trim($this->random->name(8, TRUE)));

    $this->convertPermissions($permissions);
    $this->checkPermissions($permissions);

    $role = \Drupal::entityTypeManager()->getStorage('user_role')->create([
      'id' => $rid,
      'label' => $role_label,
    ]);
    $role->save();

    if (!empty($permissions)) {
      user_role_grant_permissions($role->id(), $permissions);
    }

    return $role->id();
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete(string $role_name): void {
    $role = Role::load($role_name);

    if ($role) {
      $role->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch(): void {
    $batch =& batch_get();

    if ($batch) {
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

      if ($key === FALSE) {
        continue;
      }

      $permissions[$key] = $name;
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
      if (in_array($permission, $available)) {
        continue;
      }

      throw new \RuntimeException(sprintf('Invalid permission "%s".', $permission));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user): void {
    user_cancel([], $user->uid, 'user_cancel_delete');
    // user_cancel() schedules the deletion via batch; drive the batch to
    // completion so callers see synchronous deletion.
    $this->processBatch();
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, string $role): void {
    // Allow both machine and human role names.
    $query = \Drupal::entityQuery('user_role');
    $conditions = $query->orConditionGroup()
      ->condition('id', $role)
      ->condition('label', $role);
    $rids = $query->condition($conditions)->execute();

    if (!$rids) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role));
    }

    $account = User::load($user->uid);
    $account->addRole(reset($rids));
    $account->save();
  }

  /**
   * {@inheritdoc}
   *
   * Called exclusively from 'bootstrap()' during an in-process Drupal boot,
   * which the kernel test framework cannot exercise end-to-end. Excluded
   * from coverage to avoid a false negative on a genuinely untestable path.
   *
   * @codeCoverageIgnore
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
      $parent_terms = \Drupal::entityQuery('taxonomy_term')
        ->accessCheck(FALSE)
        ->condition('name', $term->parent)
        ->condition('vid', $term->vocabulary_machine_name)
        ->execute();

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
  public function termDelete(object $term): bool {
    $term = $term instanceof TermInterface ? $term : Term::load($term->tid);

    if (!$term instanceof TermInterface) {
      return FALSE;
    }

    $term->delete();

    return TRUE;
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
  public function getEntityFieldTypes(string $entity_type, array $base_fields = []): array {
    $entity_field_manager = $this->getEntityFieldManager();
    $fields = $entity_field_manager->getFieldStorageDefinitions($entity_type);

    if ($base_fields !== []) {
      $fields += $entity_field_manager->getBaseFieldDefinitions($entity_type);
    }

    $types = [];

    foreach ($fields as $field_name => $field) {
      $is_configured = $this->fieldExists($entity_type, $field_name);
      $is_requested_base = in_array($field_name, $base_fields) && $this->fieldIsBase($entity_type, $field_name);

      if (!$is_configured && !$is_requested_base) {
        continue;
      }

      $types[$field_name] = $field->getType();
    }

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldExists(string $entity_type, string $field_name): bool {
    $fields = $this->getEntityFieldManager()->getFieldStorageDefinitions($entity_type);
    return (isset($fields[$field_name]) && $fields[$field_name] instanceof FieldStorageConfig);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsBase(string $entity_type, string $field_name): bool {
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
    // Enable a language only if it has not been enabled already.
    if (ConfigurableLanguage::load($language->langcode)) {
      return FALSE;
    }

    ConfigurableLanguage::createFromLangcode($language->langcode)->save();

    return $language;
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
  public function cacheClearStatic(): void {
    drupal_static_reset();
    \Drupal::service('cache_tags.invalidator')->resetChecksums();

    foreach (\Drupal::entityTypeManager()->getDefinitions() as $definition) {
      \Drupal::entityTypeManager()->getAccessControlHandler($definition->id())->resetCache();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configGet(string $name, string $key = ''): mixed {
    return \Drupal::config($name)->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function configGetOriginal(string $name, string $key = ''): mixed {
    return \Drupal::config($name)->getOriginal($key, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet(string $name, string $key, mixed $value): void {
    \Drupal::configFactory()->getEditable($name)
      ->set($key, $value)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreate(string $entity_type, \stdClass $entity): EntityInterface {
    if ($entity_type === '') {
      throw new \InvalidArgumentException('You must specify an entity type to create an entity.');
    }

    $definition = \Drupal::entityTypeManager()->getDefinition($entity_type);
    $bundle_key = $definition->getKey('bundle');
    $id_key = $definition->getKey('id');

    // If the bundle field is empty, put the inferred bundle value in it.
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

    $this->expandEntityFields($entity_type, $entity);
    $created_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->create((array) $entity);
    $created_entity->save();

    // Mutate the stub under the entity type's own id key ('uid' for user,
    // 'nid' for node, 'tid' for term, 'id' for entity_test and others), so
    // callers can round-trip it back through entityDelete().
    $entity->$id_key = $created_entity->id();

    return $created_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete(string $entity_type, object $entity): void {
    if (!$entity instanceof EntityInterface) {
      $id_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('id');

      // Fail loudly if the stub does not carry the resolved id key. Without
      // this guard a missing property would silently call storage->load(NULL)
      // - the delete would appear to succeed while doing nothing.
      if (!is_string($id_key) || !isset($entity->$id_key)) {
        throw new \InvalidArgumentException(sprintf(
          'Cannot delete an entity of type "%s" from a stub without the id key "%s" set.',
          $entity_type,
          (string) $id_key,
        ));
      }

      $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity->$id_key);
    }

    if ($entity instanceof EntityInterface) {
      $entity->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function moduleInstall(string $module_name): void {
    \Drupal::service('module_installer')->install([$module_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleUninstall(string $module_name): void {
    \Drupal::service('module_installer')->uninstall([$module_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function mailStartCollecting(): void {
    $config = \Drupal::configFactory()->getEditable('system.mail');
    $mail_config = $config->getRawData();

    $this->storeOriginalConfiguration('system.mail', $mail_config);

    // @todo Use a collector that supports html after D#2223967 lands.
    $mail_config['interface'] = ['default' => 'test_mail_collector'];
    $config->setData($mail_config)->save();
    // Disable the mail system module's mail if enabled.
    $this->mailStartCollectingSystemMail();
  }

  /**
   * {@inheritdoc}
   */
  public function mailStopCollecting(): void {
    $config = \Drupal::configFactory()->getEditable('system.mail');
    $config->setData($this->originalConfiguration['system.mail'])->save();
    // Re-enable the mailsystem module's mail if enabled.
    $this->mailStopCollectingSystemMail();
  }

  /**
   * {@inheritdoc}
   */
  public function mailGet(): array {
    \Drupal::state()->resetCache();
    $mail = \Drupal::state()->get('system.test_mail_collector') ?: [];
    // Discard cancelled mail.
    $mail = array_values(array_filter($mail, fn(array $mail_item): bool => $mail_item['send'] == TRUE));
    return $mail;
  }

  /**
   * {@inheritdoc}
   */
  public function mailClear(): void {
    \Drupal::state()->set('system.test_mail_collector', []);
  }

  /**
   * {@inheritdoc}
   */
  public function mailSend(string $body, string $subject, string $to, string $langcode): bool {
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
  protected function mailStartCollectingSystemMail(): void {
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
  protected function mailStopCollectingSystemMail(): void {
    if (!\Drupal::moduleHandler()->moduleExists('mailsystem')) {
      return;
    }

    \Drupal::configFactory()->getEditable('mailsystem.settings')
      ->setData($this->originalConfiguration['mailsystem.settings'])
      ->save();
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
