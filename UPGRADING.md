# Upgrading

## From v2 to v3

v3 reworks the driver contract into a set of composable capability interfaces,
narrows entity stubs to a typed value object, drops Drupal 6/7, and tightens
the supported PHP/Drupal range. Most changes are mechanical; a small amount of
consumer-side code (typically in DrupalExtension integrations) may need
updating.

### Platform requirements

- PHP `^8.2` (was `>=7.4`).
- Drupal `^10 || ^11`. Drupal 6, 7, 8, and 9 are no longer supported.
  `DrupalDriver::detectMajorVersion()` throws a `BootstrapException` when it
  detects Drupal < 10.
- Symfony `^6.4 || ^7` for `dependency-injection`, `process`, and
  `phpunit-bridge`.
- Sites that need to stay on PHP 8.1 or Drupal 9 should pin to the 2.x line,
  now maintained on the `2.x` branch. `master` is the active 3.x branch.

### Namespace and source layout changes

Cores and field handlers moved out of the legacy `Cores/Drupal8/` and
`Fields/Drupal8/` directories into a single `Core/` tree.

| v2 namespace / path | v3 namespace / path |
|---|---|
| `Drupal\Driver\Cores\Drupal8` (`src/Drupal/Driver/Cores/Drupal8.php`) | `Drupal\Driver\Core\Core` (`src/Drupal/Driver/Core/Core.php`) |
| `Drupal\Driver\Cores\AbstractCore` | merged into `Drupal\Driver\Core\Core` (extend `Core` directly) |
| `Drupal\Driver\Cores\CoreInterface` | `Drupal\Driver\Core\CoreInterface` |
| `Drupal\Driver\Fields\FieldHandlerInterface` | `Drupal\Driver\Core\Field\FieldHandlerInterface` |
| `Drupal\Driver\Fields\Drupal8\*Handler` | `Drupal\Driver\Core\Field\*Handler` |
| `Drupal\Driver\Fields\Drupal6\*`, `Drupal7\*` | removed |

Consumers that referenced the old namespaces (custom field handlers, custom
cores, `instanceof` checks) must update the `use` statements. A future Drupal
version that needs to override behaviour can ship `Drupal\Driver\Core{N}\Core`
or `Drupal\Driver\Core{N}\Field\*Handler` classes; the lookup chains in
`DrupalDriver::setCoreFromVersion()` and `Core::getFieldHandler()` walk that
chain and fall back to the default `Core\` implementation.

### New interface layout

- `Drupal\Driver\DriverInterface` - minimum every driver must satisfy
  (`bootstrap`, `isBootstrapped`, `getRandom`).
- `Drupal\Driver\Capability\*CapabilityInterface` - operational capabilities.
  Drivers opt in by implementing them.
- `Drupal\Driver\DrupalDriverInterface`, `DrushDriverInterface`,
  `BlackboxDriverInterface` - composite contracts for each driver type.

| Driver | Composite contract | Capability set |
|---|---|---|
| `DrupalDriver` | `DrupalDriverInterface` | All capabilities + `SubDriverFinderInterface` |
| `DrushDriver` | `DrushDriverInterface` | Cache, Config, Cron, Module, Role, User, Watchdog |
| `BlackboxDriver` | `BlackboxDriverInterface` | None |

### Removed classes and interfaces

- `Drupal\Driver\BaseDriver` - the throw-unsupported abstract base. Replaced
  by explicit capability interfaces. Drivers no longer inherit method stubs.
- `Drupal\Driver\AuthenticationDriverInterface` - replaced by
  `Drupal\Driver\Capability\AuthenticationCapabilityInterface`.
- `Drupal\Driver\Core\CoreAuthenticationInterface` - replaced by the same
  capability interface.
- `Drupal\Driver\Core\AbstractCore` - merged into `Core`. Custom cores should
  extend `Core` directly and override the methods they need.

### Field classification moved to `FieldClassifier`

The predicates `fieldExists()` and `fieldIsBase()` are gone from `DrupalDriver`
and `Core`. The empty `FieldCapabilityInterface` is removed entirely. Field
classification into the nine F-row categories (F1-F9) now lives on
`Drupal\Driver\Core\Field\FieldClassifierInterface`, implemented by
`Drupal\Driver\Core\Field\FieldClassifier`. See
`src/Drupal/Driver/Core/Field/README.md` for the full truth table.

If consumer code called `$driver->fieldExists(...)` or `$driver->fieldIsBase(...)`,
replace with:

- `fieldExists($type, $name)` → `$core->getFieldClassifier()->fieldIsConfigurable($type, $name)` (if you were checking for a configurable field)
- `fieldIsBase($type, $name)` → `$core->getFieldClassifier()->fieldIsBaseStandard($type, $name)` (or one of the more specific F-row predicates, depending on intent)

The classifier is the single source of truth for field classification; the
old two-predicate API was insufficient to distinguish F1-F9 correctly and
caused downstream bugs (notably with computed writable base fields like
`moderation_state`).

The accessor on `CoreInterface` is `getFieldClassifier()`. An earlier 3.x
prerelease named it `classifier()`; that name was renamed before
3.0.0-alpha1 for consistency with the other `getX()` accessors
(`getRandom()`, `getModuleList()`, `getFieldHandler()`,
`getEntityFieldTypes()`).

### Field-expansion pipeline signature changes

The pipeline that drives `entityCreate()` was rewritten around the classifier.
The following methods on `Core` / `CoreInterface` changed or were removed:

| v2 | v3 |
|---|---|
| `expandEntityFields(string $type, \stdClass $entity, array $base_fields = [])` | `expandEntityFields(string $type, EntityStubInterface $entity)` (no `$base_fields`) |
| `getEntityFieldTypes(string $type, array $base_fields = [])` | `getEntityFieldTypes(string $type, ?string $bundle = NULL)` |
| `expandEntityBaseFields()` | removed (callers use `expandEntityFields()` directly) |
| `detectBaseFieldsOnEntity()` | removed (replaced by an internal `resolveBundleFromEntity()` helper) |
| `fieldExists()`, `fieldIsBase()` | removed (use `FieldClassifier` predicates) |

`DefaultHandler::expand()` now throws `\RuntimeException` for any field whose
storage schema is not a single `value` column, instead of silently emitting
garbage. Custom handlers for multi-column types must be registered explicitly;
the new `FieldTypeCoverageKernelTest` will fail at CI time if a registered
core type has no handler.

### DrushDriver no longer supports Content or Field capabilities

`DrushDriver` used to rely on a companion module
(`drush-ops/behat-drush-endpoint`) installed on the site-under-test to provide
entity CRUD and field introspection over Drush. That dev dependency and the
indirection have been removed: `DrushDriver` now exposes only operations that
Drush services natively.

`DrushDriverInterface` no longer extends `ContentCapabilityInterface` or
`FieldCapabilityInterface`. The following methods are gone from `DrushDriver`:

- `nodeCreate`, `nodeDelete`
- `termCreate`, `termDelete`
- `entityCreate`, `entityDelete`
- `fieldExists`, `fieldIsBase`

Consumers that need entity CRUD or field introspection should use
`DrupalDriver` (which bootstraps Drupal and delegates to `Core`) or implement
the missing behaviour themselves. Test the capability with
`instanceof ContentCapabilityInterface` (or the relevant capability interface)
before calling.

### CoreInterface expanded

`Drupal\Driver\Core\CoreInterface` now extends every capability interface in
addition to declaring its bootstrap internals (`validateDrupalSite`,
`getModuleList`, `getExtensionPathList`, `getFieldHandler`,
`getEntityFieldTypes`, `processBatch`, `getFieldClassifier`).
`DrupalDriver::getCore()` still returns `CoreInterface` - you get the full
capability surface from the same type hint.

### DrupalDriverInterface tightened, properties narrowed

Three accessors that previously lived only on the concrete `DrupalDriver` are
now part of the `DrupalDriverInterface` contract:

- `getCore(): CoreInterface`
- `setCore(CoreInterface $core): void`
- `getDrupalVersion(): int`

Consumers that hand-rolled a class implementing `DrupalDriverInterface` must
add these three methods.

`DrupalDriver::$core` and `DrupalDriver::$version` were narrowed from `public`
to `protected`. Replace direct property access with the public accessors:

```php
// v2
$core    = $driver->core;
$version = $driver->version;

// v3
$core    = $driver->getCore();
$version = $driver->getDrupalVersion();
```

`DrupalDriver::setCore()` also changed shape. v2 took an array of
version-keyed `Core` classes; v3 takes a single `CoreInterface` instance:

```php
// v2
$driver->setCore([10 => Drupal8::class, 11 => Drupal8::class]);

// v3
$driver->setCore(new \Drupal\Driver\Core\Core($driver));
```

The convention-based `setCoreFromVersion()` lookup that walks
`Drupal\Driver\Core{N}\Core` classes is unchanged and still the recommended
way to wire a core for the detected Drupal version.

### Entity stubs are now typed

Every capability method that previously accepted or returned `\stdClass` now
declares `Drupal\Driver\Entity\EntityStubInterface`. This affects
`AuthenticationCapabilityInterface::login()` and every create/delete method
on `Block*`, `Content*`, `Language*`, and `User*` capability interfaces.

```php
// v2 - construct a stub as an anonymous \stdClass
$node = (object) [
    'type'  => 'page',
    'title' => 'Example',
];
$created = $driver->nodeCreate($node);
// guess: $created->nid? $created->id?

// v3 - construct a typed stub
use Drupal\Driver\Entity\EntityStub;

$node    = new EntityStub('node', 'page', ['title' => 'Example']);
$created = $driver->nodeCreate($node);
// typed access:
$id     = $created->getId();           // saved entity id
$entity = $created->getSavedEntity();  // EntityInterface
$saved  = $created->isSaved();         // bool
```

There is no `\stdClass` shim. Callers must construct `EntityStub` instances
directly. The field-handler boundary (`AbstractHandler::__construct()`,
`Core::getFieldHandler()`) is also typed, so custom handler subclasses see the
typed stub instead of a synthesised `\stdClass`.

### entityCreate() now writes the entity-type-specific id key

`entityCreate()` previously always populated `$entity->id` after save. v3
resolves the id key per entity type and writes there instead:

| Entity type | Property populated |
|---|---|
| `node` | `$stub->nid` |
| `user` | `$stub->uid` |
| `taxonomy_term` | `$stub->tid` |
| custom | `$entity_type_definition->getKey('id')` |

Consumers that read `$stub->id` after `entityCreate('user', $stub)` must
switch to `$stub->getId()` (preferred), `$stub->uid`, or read from the
returned entity. `entityDelete()` was changed in the same way - it loads via
the resolved id key instead of `$stub->id`.

`entityCreate()` also auto-detects base fields set as properties on the stub
(excluding the id and bundle keys) and routes them through the field-handler
pipeline. Base entity-reference fields like `commerce_product.variations` no
longer reach storage in raw label form.

### Removed handler classes

- `TaxonomyTermReferenceHandler` was removed. The legacy
  `taxonomy_term_reference` field type was deleted from Drupal core in
  8.0.0-beta10 (2015) and is unreachable on every supported Drupal version.
  Consumers that subclassed it should subclass `EntityReferenceHandler`
  instead. Modern taxonomy references are `entity_reference` with
  `target_type = taxonomy_term` and route through `EntityReferenceHandler`
  automatically.

### Renamed driver methods

Every method now starts with its capability name for consistency. Renames
on `DrupalDriver` and `Core` (and on `DrushDriver` where it still supports
that capability):

| v2 | v3 | Capability |
|---|---|---|
| `createNode` | `nodeCreate` | Content |
| `createTerm` | `termCreate` | Content |
| `createEntity` | `entityCreate` | Content |
| `clearCache` | `cacheClear` | Cache |
| `clearStaticCaches` | `cacheClearStatic` | Cache |
| `runCron` | `cronRun` | Cron |
| `fetchWatchdog` | `watchdogFetch` | Watchdog |
| `startCollectingMail` | `mailStartCollecting` | Mail |
| `stopCollectingMail` | `mailStopCollecting` | Mail |
| `getMail` | `mailGet` | Mail |
| `clearMail` | `mailClear` | Mail |
| `sendMail` | `mailSend` | Mail |

`login` and `logout` keep their verb-only names - they don't take a subject
prefix naturally. All other capability methods (`user*`, `role*`, `module*`,
`config*`, `language*`) already followed the pattern.

### Tightened signatures

Every source and test file declares `declare(strict_types=1)`, and parameter
/ return / property types were tightened across all interfaces and concrete
classes via Rector's PHP 8.2 typeDeclarations set. Callers that previously
relied on PHP loose-mode coercion (passing strings where ints are expected,
returning the wrong type from an overridden method, etc.) will now hit
`TypeError` at the boundary. Audit any consumer code that calls into `Core`,
`DrupalDriver`, `DrushDriver`, or any field handler.

Where v2 accepted `\stdClass` for entity stubs, v3 expects
`EntityStubInterface` - see "Entity stubs are now typed" above for the
migration.

### New methods

- `DrupalDriver::configGetOriginal()` - previously only available on `Core`.
- `DrupalDriver::watchdogFetch()` now delegates to `Core::watchdogFetch()`
  instead of throwing. `Core::watchdogFetch()` is a new implementation built
  against the `dblog` module.

### Consumer migration

If your code type-hints against `DriverInterface` and calls capability
methods, switch to the relevant capability interface or the appropriate
composite:

```php
// v2
function setUpFixtures(DriverInterface $driver): void {
    $driver->userCreate($user);
    $driver->languageCreate($language);
}

// v3
function setUpFixtures(UserCapabilityInterface&LanguageCapabilityInterface $driver): void {
    $driver->userCreate($user);
    $driver->languageCreate($language);
}
```

Or, if you know you're dealing with the full Drupal driver:

```php
function setUpFixtures(DrupalDriverInterface $driver): void {
    $driver->userCreate($user);
    $driver->languageCreate($language);
}
```

Instead of catching `UnsupportedDriverActionException`, check capability
support via `instanceof`:

```php
// v2
try {
    $driver->languageCreate($language);
}
catch (UnsupportedDriverActionException $e) {
    // Fallback...
}

// v3
if ($driver instanceof LanguageCapabilityInterface) {
    $driver->languageCreate($language);
}
else {
    // Fallback...
}
```

### What stays the same

- The three driver class names (`BlackboxDriver`, `DrupalDriver`,
  `DrushDriver`) are unchanged.
- `Core` is still the single Drupal-bootstrap implementation, now declaring
  the capability interfaces directly.
- `UnsupportedDriverActionException` remains available for genuine runtime
  failures but is no longer used to signal missing capabilities.
