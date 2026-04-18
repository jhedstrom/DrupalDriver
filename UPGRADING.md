# Upgrading

## From v2 to v3

v3 reworks the driver contract into a set of composable capability interfaces.
Most changes are mechanical; a small amount of consumer-side code (typically in
DrupalExtension integrations) may need updating.

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
| `DrushDriver` | `DrushDriverInterface` | Cache, Config, Content, Field, Module, Role, User, Watchdog |
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

### CoreInterface expanded

`Drupal\Driver\Core\CoreInterface` now extends every capability interface in
addition to declaring its bootstrap internals (`validateDrupalSite`,
`getModuleList`, `getExtensionPathList`, `getFieldHandler`,
`getEntityFieldTypes`, `processBatch`). `DrupalDriver::getCore()` still
returns `CoreInterface` - you get the full capability surface from the same
type hint.

### Renamed driver methods

These methods on `DrupalDriver` and `DrushDriver` were renamed for consistency
with the corresponding `Core` methods:

| v2 | v3 |
|---|---|
| `createNode($node)` | `nodeCreate(\stdClass $node)` |
| `createTerm($term)` | `termCreate(\stdClass $term)` |
| `createEntity($type, $entity)` | `entityCreate(string $type, \stdClass $entity)` |

### Tightened signatures

Parameter and return types were tightened to match the capability contracts.
Where v2 accepted untyped arguments, v3 expects `string`, `\stdClass`, or
`object` as declared by each capability interface. The change is transparent
to well-typed callers and will surface via PHP's built-in type errors
otherwise.

### New methods

- `DrupalDriver::configGetOriginal()` - previously only available on `Core`.
- `DrupalDriver::fetchWatchdog()` now delegates to `Core::fetchWatchdog()`
  instead of throwing. `Core::fetchWatchdog()` is a new implementation built
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
