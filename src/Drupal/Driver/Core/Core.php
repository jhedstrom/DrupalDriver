<?php

declare(strict_types=1);

namespace Drupal\Driver\Core;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Random;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Driver\Core\Field\DefaultHandler;
use Drupal\Driver\Core\Field\FieldClassifier;
use Drupal\Driver\Core\Field\FieldClassifierInterface;
use Drupal\Driver\Core\Field\FieldHandlerInterface;
use Drupal\Driver\Entity\EntityStubInterface;
use Drupal\Driver\Exception\BootstrapException;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\mailsystem\MailsystemManager;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
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
   * Registered field handler classes, keyed by field type id.
   *
   * Populated at construction with the project's built-in handlers and
   * extended at runtime via 'registerFieldHandler()'. Lookup in
   * 'getFieldHandler()' consults this map first, falling back to
   * 'DefaultHandler' when a field type has no registered class.
   *
   * @var array<string, class-string<FieldHandlerInterface>>
   */
  protected array $fieldHandlers = [];

  /**
   * Lazily created field classifier instance.
   */
  protected ?FieldClassifierInterface $fieldClassifier = NULL;

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

    $this->registerDefaultFieldHandlers();
  }

  /**
   * {@inheritdoc}
   */
  public function getRandom(): Random {
    return $this->random;
  }

  /**
   * {@inheritdoc}
   */
  public function registerFieldHandler(string $field_type, string $class): void {
    if (!is_subclass_of($class, FieldHandlerInterface::class)) {
      throw new \InvalidArgumentException(sprintf('Handler class "%s" must implement "%s".', $class, FieldHandlerInterface::class));
    }

    if ((new \ReflectionClass($class))->isAbstract()) {
      throw new \InvalidArgumentException(sprintf('Handler class "%s" must be instantiable.', $class));
    }

    $this->fieldHandlers[$field_type] = $class;
  }

  /**
   * Populates the field-handler registry with handlers this class ships.
   *
   * A 'Core' subclass that wants to add version-specific overrides should
   * override this method, call 'parent::registerDefaultFieldHandlers()'
   * first, and then call 'registerHandlersFromDirectory()' against its
   * own 'Field/' sibling directory. Each directory scan is independent,
   * so a subclass only re-registers the types it actually changes; types
   * it does not touch are inherited from the parent scan.
   */
  protected function registerDefaultFieldHandlers(): void {
    $this->registerHandlersFromDirectory(__DIR__ . '/Field', __NAMESPACE__ . '\\Field');
  }

  /**
   * Scans a directory for handler classes and registers each one.
   *
   * A class is registered when it (a) lives in '$namespace', (b) is named
   * '*Handler' but is not 'DefaultHandler' (the registry's fallback), (c)
   * is concrete, and (d) implements 'FieldHandlerInterface'. The field
   * type id it gets registered under is derived from the short class name:
   * 'EntityReferenceHandler' → 'entity_reference'.
   *
   * @param string $dir
   *   Absolute filesystem path to the directory containing '*Handler.php'
   *   files.
   * @param string $namespace
   *   Namespace the classes in '$dir' live under, without a trailing slash.
   */
  protected function registerHandlersFromDirectory(string $dir, string $namespace): void {
    foreach (glob($dir . '/*Handler.php') ?: [] as $file) {
      $short = basename($file, '.php');

      if ($short === 'DefaultHandler') {
        continue;
      }

      $class = $namespace . '\\' . $short;

      if (!is_subclass_of($class, FieldHandlerInterface::class)) {
        continue;
      }

      if ((new \ReflectionClass($class))->isAbstract()) {
        continue;
      }

      $this->registerFieldHandler($this->deriveFieldType($short), $class);
    }
  }

  /**
   * Converts a handler's short class name to its Drupal field type id.
   *
   * Strips the 'Handler' suffix and snake-cases the remainder:
   * 'EntityReferenceHandler' → 'entity_reference'.
   */
  protected function deriveFieldType(string $short_class_name): string {
    $bare = substr($short_class_name, 0, -strlen('Handler'));

    return strtolower((string) preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $bare));
  }

  /**
   * Creates the field classifier instance for this Core.
   *
   * Subclasses override this method when they ship a version-specific
   * classifier. The default returns the base 'FieldClassifier' which covers
   * Drupal 10 and 11.
   */
  protected function createFieldClassifier(): FieldClassifierInterface {
    return new FieldClassifier($this->getEntityFieldManager());
  }

  /**
   * {@inheritdoc}
   */
  public function classifier(): FieldClassifierInterface {
    if (!$this->fieldClassifier instanceof FieldClassifierInterface) {
      $this->fieldClassifier = $this->createFieldClassifier();
    }

    return $this->fieldClassifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldHandler(EntityStubInterface $stub, string $entity_type, string $field_name): FieldHandlerInterface {
    $bundle = $this->resolveBundle($stub);
    $field_types = $this->getEntityFieldTypes($entity_type, $bundle);

    if (!isset($field_types[$field_name])) {
      throw new \RuntimeException(sprintf('Field "%s" not found on entity type "%s".', $field_name, $entity_type));
    }

    $class = $this->fieldHandlers[$field_types[$field_name]] ?? DefaultHandler::class;

    return new $class($stub, $entity_type, $field_name);
  }

  /**
   * Expands values on the given stub through the field-handler pipeline.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   Stub whose values bag will be mutated in place.
   */
  protected function expandEntityFields(EntityStubInterface $stub): void {
    $entity_type = $stub->getEntityType();
    $definition = $this->loadEntityTypeDefinition($entity_type);

    // The id key and bundle key identify the record itself and must not pass
    // through the handler pipeline. For example, on 'commerce_product' the
    // bundle key 'type' is also a base entity_reference field; expanding it
    // would resolve the bundle machine name through EntityReferenceHandler
    // and overwrite the scalar with ['target_id' => ...], corrupting every
    // subsequent bundle lookup for the same stub.
    $skip = array_filter([$definition->getKey('id'), $definition->getKey('bundle')]);

    $bundle = $this->resolveBundle($stub);
    $field_types = $this->getEntityFieldTypes($entity_type, $bundle);

    foreach (array_keys($field_types) as $field_name) {
      if (in_array($field_name, $skip, TRUE)) {
        continue;
      }

      if (!$stub->hasValue($field_name)) {
        continue;
      }

      $expanded = $this->getFieldHandler($stub, $entity_type, $field_name)
        ->expand($stub->getValue($field_name));
      $stub->setValue($field_name, $expanded);
    }
  }

  /**
   * Resolves the bundle for an entity stub.
   *
   * Consults the entity type's bundle key in the values bag first, then the
   * typed 'bundle' constructor argument, then falls back to the entity type
   * id (single-bundle entities like 'user' use the type id as their bundle).
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The stub.
   *
   * @return string
   *   Bundle name. Never empty.
   */
  protected function resolveBundle(EntityStubInterface $stub): string {
    $bundle_key = $this->loadEntityTypeDefinition($stub->getEntityType())->getKey('bundle');

    if ($bundle_key && $stub->hasValue($bundle_key) && !empty($stub->getValue($bundle_key))) {
      return (string) $stub->getValue($bundle_key);
    }

    if ($stub->getBundle() !== NULL && $stub->getBundle() !== '') {
      return $stub->getBundle();
    }

    return $stub->getEntityType();
  }

  /**
   * Resolves an entity type definition, rethrowing with an actionable message.
   *
   * Drupal's EntityTypeManager throws 'PluginNotFoundException' with text like
   * "The 'xyz' plugin does not exist." That is technically correct but leaks
   * plugin-system vocabulary into what a scenario author experiences as a
   * driver-level error. Wrapping it lets us produce a message that names
   * what actually went wrong: the entity type argument they passed.
   *
   * @param string $entity_type
   *   Entity type id to load.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The resolved definition.
   *
   * @throws \InvalidArgumentException
   *   If the entity type id is not registered.
   */
  protected function loadEntityTypeDefinition(string $entity_type): EntityTypeInterface {
    try {
      return \Drupal::entityTypeManager()->getDefinition($entity_type);
    }
    catch (PluginNotFoundException $e) {
      throw new \InvalidArgumentException(sprintf('Unknown entity type "%s".', $entity_type), 0, $e);
    }
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
  public function nodeCreate(EntityStubInterface $stub): EntityStubInterface {
    $type = $stub->getBundle() ?? $stub->getValue('type');

    if (empty($type)) {
      throw new \Exception("Cannot create content because it is missing the required property 'type'.");
    }

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundles = $bundle_info->getBundleInfo('node');

    if (!in_array($type, array_keys($bundles))) {
      throw new \Exception(sprintf('Cannot create content because provided content type %s does not exist.', $type));
    }

    // 'Node::create()' reads the bundle from the 'type' values key, so make
    // sure it carries the resolved bundle even when the caller only set it
    // through the typed 'bundle' constructor argument.
    if (!$stub->hasValue('type')) {
      $stub->setValue('type', $type);
    }

    // If 'author' is set, remap it to 'uid'.
    if ($stub->hasValue('author')) {
      /** @var \Drupal\user\Entity\User|null $user */
      $user = user_load_by_name($stub->getValue('author'));

      if ($user) {
        $stub->setValue('uid', $user->id());
      }
    }

    $this->expandEntityFields($stub);
    $entity = Node::create($stub->getValues());
    $entity->save();

    $stub->setValue('nid', $entity->id());
    $stub->markSaved($entity);

    return $stub;
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete(EntityStubInterface $stub): void {
    $node = $stub->isSaved() ? $stub->getSavedEntity() : NULL;

    if (!$node instanceof NodeInterface) {
      $node = Node::load($stub->getValue('nid'));
    }

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
  public function userCreate(EntityStubInterface $stub): void {
    // Default status to TRUE if not explicitly creating a blocked user.
    if (!$stub->hasValue('status')) {
      $stub->setValue('status', 1);
    }

    $this->expandEntityFields($stub);
    $account = \Drupal::entityTypeManager()->getStorage('user')->create($stub->getValues());
    $account->save();

    // Store UID and the saved account.
    $stub->setValue('uid', $account->id());
    $stub->markSaved($account);
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
  public function userDelete(EntityStubInterface $stub): void {
    user_cancel([], $this->resolveUid($stub), 'user_cancel_delete');
    // user_cancel() schedules the deletion via batch; drive the batch to
    // completion so callers see synchronous deletion.
    $this->processBatch();
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(EntityStubInterface $stub, string $role): void {
    // Allow both machine and human role names.
    $query = \Drupal::entityQuery('user_role');
    $conditions = $query->orConditionGroup()
      ->condition('id', $role)
      ->condition('label', $role);
    $rids = $query->condition($conditions)->execute();

    if (!$rids) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role));
    }

    $account = User::load($this->resolveUid($stub));
    $account->addRole(reset($rids));
    $account->save();
  }

  /**
   * Resolves the user id from a stub.
   *
   * Prefers the saved-entity slot - that is the only authoritative source
   * after 'userCreate()' - then falls back to a 'uid' value the caller may
   * have populated manually.
   */
  protected function resolveUid(EntityStubInterface $stub): int|string|null {
    return $stub->getId() ?? $stub->getValue('uid');
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
  public function termCreate(EntityStubInterface $stub): EntityStubInterface {
    $vocabulary = $stub->getBundle() ?? $stub->getValue('vocabulary_machine_name');

    if (empty($vocabulary)) {
      throw new \InvalidArgumentException("Cannot create term because it is missing the required property 'vocabulary_machine_name'.");
    }

    if (Vocabulary::load($vocabulary) === NULL) {
      throw new \InvalidArgumentException(sprintf("Cannot create term because vocabulary '%s' does not exist.", $vocabulary));
    }

    $stub->setValue('vid', $vocabulary);

    if ($stub->hasValue('parent') && !empty($stub->getValue('parent'))) {
      $parent_name = $stub->getValue('parent');
      $parent_terms = \Drupal::entityQuery('taxonomy_term')
        ->accessCheck(FALSE)
        ->condition('name', $parent_name)
        ->condition('vid', $vocabulary)
        ->execute();

      if (empty($parent_terms)) {
        throw new \InvalidArgumentException(sprintf("Cannot create term because parent term '%s' does not exist in vocabulary '%s'.", $parent_name, $vocabulary));
      }

      $stub->setValue('parent', reset($parent_terms));
    }

    $this->expandEntityFields($stub);
    $entity = Term::create($stub->getValues());
    $entity->save();

    $stub->setValue('tid', $entity->id());
    $stub->markSaved($entity);

    return $stub;
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(EntityStubInterface $stub): bool {
    $term = $stub->isSaved() ? $stub->getSavedEntity() : NULL;

    if (!$term instanceof TermInterface) {
      $term = Term::load($stub->getValue('tid'));
    }

    if (!$term instanceof TermInterface) {
      return FALSE;
    }

    $term->delete();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function blockPlace(EntityStubInterface $stub): EntityStubInterface {
    // Generate a placement id when the caller did not supply one, matching
    // the 'nodeCreate'/'roleCreate' convention of tolerating ID-less stubs.
    // Block config entities require an id, so we must fill it before save.
    if (!$stub->hasValue('id') || $stub->getValue('id') === '') {
      $stub->setValue('id', strtolower($this->random->name(8, TRUE)));
    }

    $placement = \Drupal::entityTypeManager()->getStorage('block')->create($stub->getValues());
    $placement->save();
    $stub->markSaved($placement);

    return $stub;
  }

  /**
   * {@inheritdoc}
   */
  public function blockDelete(EntityStubInterface $stub): void {
    $entity = $stub->isSaved() ? $stub->getSavedEntity() : NULL;

    if (!$entity instanceof EntityInterface) {
      $id = $stub->getValue('id');

      if (!is_string($id) || $id === '') {
        throw new \InvalidArgumentException('Cannot delete a block placement from a stub without a string "id" property.');
      }

      $entity = \Drupal::entityTypeManager()->getStorage('block')->load($id);
    }

    if ($entity instanceof EntityInterface) {
      $entity->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockContentCreate(EntityStubInterface $stub): EntityStubInterface {
    return $this->entityCreate($stub);
  }

  /**
   * {@inheritdoc}
   */
  public function blockContentDelete(EntityStubInterface $stub): void {
    $this->entityDelete($stub);
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
   * {@inheritdoc}
   */
  public function getEntityFieldTypes(string $entity_type, ?string $bundle = NULL): array {
    $entity_field_manager = $this->getEntityFieldManager();
    $bundle_fields = [];
    $fields = $entity_field_manager->getFieldStorageDefinitions($entity_type)
      + $entity_field_manager->getBaseFieldDefinitions($entity_type);

    if ($bundle !== NULL) {
      $bundle_fields = $entity_field_manager->getFieldDefinitions($entity_type, $bundle);
      $fields += $bundle_fields;
    }

    $types = [];

    foreach ($fields as $field_name => $field) {
      // See src/Drupal/Driver/Core/Field/README.md. Only F1, F5, F9 enter the
      // expansion pipeline; the OR below names those rows explicitly.
      // F5 is additionally scoped to the bundle when known - otherwise a
      // configurable field storage attached only to other bundles would slip
      // into the type map and blow up in AbstractHandler::__construct().
      $is_base_standard = $this->classifier()->fieldIsBaseStandard($entity_type, $field_name);
      $is_configurable = $this->classifier()->fieldIsConfigurable($entity_type, $field_name)
        && ($bundle === NULL || isset($bundle_fields[$field_name]));
      $is_bundle_storage_backed = $bundle !== NULL
        && $this->classifier()->fieldIsBundleStorageBacked($entity_type, $field_name, $bundle);

      if (!$is_base_standard && !$is_configurable && !$is_bundle_storage_backed) {
        continue;
      }

      $types[$field_name] = $field->getType();
    }

    return $types;
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
  public function languageCreate(EntityStubInterface $stub): EntityStubInterface|false {
    $langcode = $stub->getValue('langcode');

    // Enable a language only if it has not been enabled already.
    if (ConfigurableLanguage::load($langcode)) {
      return FALSE;
    }

    $entity = ConfigurableLanguage::createFromLangcode($langcode);
    $entity->save();
    $stub->markSaved($entity);

    return $stub;
  }

  /**
   * {@inheritdoc}
   */
  public function languageDelete(EntityStubInterface $stub): void {
    $configurable_language = ConfigurableLanguage::load($stub->getValue('langcode'));
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
  public function entityCreate(EntityStubInterface $stub): EntityStubInterface {
    $entity_type = $stub->getEntityType();

    if ($entity_type === '') {
      throw new \InvalidArgumentException('You must specify an entity type to create an entity.');
    }

    $definition = $this->loadEntityTypeDefinition($entity_type);
    $bundle_key = $definition->getKey('bundle');
    $id_key = $definition->getKey('id');

    // Sync the typed bundle property into the values bag so
    // storage->create() picks it up under the entity type's own bundle key.
    if ($bundle_key && !$stub->hasValue($bundle_key) && $stub->getBundle() !== NULL) {
      $stub->setValue($bundle_key, $stub->getBundle());
    }

    // Throw an exception if a bundle is specified but does not exist.
    if ($bundle_key && $stub->hasValue($bundle_key) && $stub->getValue($bundle_key) !== NULL) {
      /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $bundle_info */
      $bundle_info = \Drupal::service('entity_type.bundle.info');
      $bundles = $bundle_info->getBundleInfo($entity_type);

      if (!in_array($stub->getValue($bundle_key), array_keys($bundles))) {
        throw new \InvalidArgumentException(sprintf("Cannot create entity because provided bundle '%s' does not exist.", $stub->getValue($bundle_key)));
      }
    }

    $this->expandEntityFields($stub);
    $created_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->create($stub->getValues());
    $created_entity->save();

    // Mutate the stub under the entity type's own id key ('uid' for user,
    // 'nid' for node, 'tid' for term, 'id' for entity_test and others), so
    // callers can round-trip it back through entityDelete().
    $stub->setValue($id_key, $created_entity->id());
    $stub->markSaved($created_entity);

    return $stub;
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete(EntityStubInterface $stub): void {
    $entity_type = $stub->getEntityType();
    $entity = $stub->isSaved() ? $stub->getSavedEntity() : NULL;

    if (!$entity instanceof EntityInterface) {
      $id_key = $this->loadEntityTypeDefinition($entity_type)->getKey('id');

      // Fail loudly if the stub does not carry the resolved id key. Without
      // this guard a missing property would silently call storage->load(NULL)
      // - the delete would appear to succeed while doing nothing.
      if (!is_string($id_key) || !$stub->hasValue($id_key)) {
        throw new \InvalidArgumentException(sprintf(
          'Cannot delete an entity of type "%s" from a stub without the id key "%s" set.',
          $entity_type,
          (string) $id_key,
        ));
      }

      $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($stub->getValue($id_key));
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
  public function login(EntityStubInterface $stub): void {
    $account = User::load($this->resolveUid($stub));
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
