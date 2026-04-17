Extending DrupalDriver for new Drupal versions
===============================================

This document describes how DrupalDriver is structured to support multiple
Drupal core versions, and how to add or override behavior for a specific
Drupal version without copying the entire implementation.

The design has three goals:

1. **One default implementation** that works for every supported Drupal
   version unless the API changes.
2. **No empty placeholder classes.** Version directories only contain
   files for behavior that actually differs from the default.
3. **A predictable lookup chain.** When a Drupal version needs to
   override a class, you create one file; the framework finds it.

Source layout
-------------

::

    src/Drupal/Driver/
    │
    ├── DriverInterface.php                 ← top-level contracts
    ├── AuthenticationDriverInterface.php
    ├── SubDriverFinderInterface.php
    │
    ├── BaseDriver.php                      ← driver implementations (flat)
    ├── BlackboxDriver.php
    ├── DrupalDriver.php
    ├── DrushDriver.php
    │
    ├── Core/                               ← abstractions + default implementation
    │   ├── CoreInterface.php
    │   ├── CoreAuthenticationInterface.php
    │   ├── AbstractCore.php
    │   ├── Core.php                        ← the default Drupal core
    │   └── Field/
    │       ├── FieldHandlerInterface.php
    │       ├── AbstractHandler.php
    │       └── (handlers - the default set)
    │
    ├── Core12/                             ← per-version override directory
    │   └── Field/
    │       └── FileHandler.php             ← only the handler that differs in D12
    │
    ├── Core13/
    │   └── Core.php                        ← only the Core class differs in D13
    │
    └── Exception/

Three layers
------------

Drivers
~~~~~~~

The driver classes (``BlackboxDriver``, ``DrupalDriver``, ``DrushDriver``)
sit at the root of the namespace. They are the user-facing entry points and
are not version-specific.

Core
~~~~

The ``Core/`` namespace holds the abstract Drupal core layer:

- **Contracts:** ``CoreInterface``, ``CoreAuthenticationInterface``,
  ``Field\FieldHandlerInterface``.
- **Shared base:** ``AbstractCore``, ``Field\AbstractHandler``.
- **Default implementation:** ``Core``  and ``Field\*Handler`` classes
  that work for every currently-supported Drupal version.

In other words, ``Core/`` is *both* the abstraction and the default Drupal
implementation. There is no separate "version 10" or "version 11" directory
when nothing diverges - the default in ``Core/`` is used.

Version overrides
~~~~~~~~~~~~~~~~~

If a future Drupal version (say D12) introduces an API change that affects
one handler, you create:

::

    Core12/
    └── Field/
        └── FileHandler.php

That class extends the default and overrides only what changed:

.. code-block:: php

    namespace Drupal\Driver\Core12\Field;

    use Drupal\Driver\Core\Field\FileHandler as DefaultFileHandler;

    class FileHandler extends DefaultFileHandler {

      public function expand(mixed $values): array {
        // D12-specific behavior; delegate to parent for the common path.
        return parent::expand($values);
      }

    }

No other files in ``Core12/`` are needed. Everything else continues to use
the defaults from ``Core/``.

The lookup chain
----------------

When ``DrupalDriver`` instantiates the Core, it walks from the detected
Drupal version down to the default:

::

    Detected Drupal version: 13
    Try: Drupal\Driver\Core13\Core   ← exists, use it
    (lookup short-circuits on the first match; later candidates are not reached)

When the resolved Core looks up a field handler, it walks the same chain:

::

    Field handler: FileHandler, version: 13
    Try: Drupal\Driver\Core13\Field\FileHandler  ← does not exist
    Try: Drupal\Driver\Core12\Field\FileHandler  ← exists, use it
    ...

Older overrides automatically apply to newer versions until a newer
override supersedes them. This means a fix landed in ``Core12/`` carries
forward to D13, D14, D15... until something specifically changes again.

Adding support for a new Drupal version
---------------------------------------

If the new Drupal version is API-compatible with the current default:

1. **Do nothing.** The default ``Core\Core`` and the default handlers in
   ``Core\Field\`` will be used automatically once the version is
   accepted by ``DrupalDriver::detectMajorVersion()``.
2. Bump composer version constraints if needed.
3. Add the version to the CI matrix.

If the new version requires customization:

1. Create ``Core{N}/`` (e.g., ``Core14/``).
2. For each behavior that differs, create a class that extends its
   counterpart in the most recent prior version (or in ``Core/`` if no
   intermediate override exists).
3. The class should override only the methods that change. Everything
   else inherits.
4. Add CI matrix entries for the new version.

Adding a customization for an existing Drupal version
-----------------------------------------------------

If you discover that one Drupal version needs special handling for a
particular operation:

1. Identify the existing class in ``Core/`` (or in a prior override
   directory if one already exists).
2. Create a subclass under ``Core{N}/`` that mirrors the path.
3. Override only the methods that need to change.
4. Add a test that covers the version-specific behavior.

Why not simpler?
----------------

Two simpler approaches were considered and rejected:

- **One class with version branching inside methods**
  (``if (version_compare(...))``). This works for one or two minor
  divergences but turns into a tangle when the API changes meaningfully.
  It also makes the version-specific code invisible in the file tree.

- **A complete copy of every class per version.** Honest about isolation,
  but maintaining identical 17-handler trees in five directories is a
  multiplier on every bug fix.

The lookup-chain approach gives version-specific code a dedicated home
without forcing every version to carry the full set of files.
