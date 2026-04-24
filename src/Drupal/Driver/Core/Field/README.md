# Field Handler Truth Table

The complete matrix of field variants the driver must reason about, and the
resolution for each. Every legal combination of origin, storage profile, and
writability appears as its own row so downstream code can reference cases by ID.

## Axes

- **Origin** - where the field definition is declared.
- **Storage profile** - how (or whether) the value is persisted.
- **Writable?** - does assignment to a stub have observable effect at save.
- **Resolution** - what the driver's expansion pipeline does for this category.

Cardinality (single vs multi) and value shape (scalar vs compound vs
entity-reference) are orthogonal to the origin/storage decision - they are
handler-selection inputs once a field enters the pipeline. See the
handler-selection sub-table below.

## Primary table - does the field enter the pipeline?

| ID | Origin                                                          | Storage profile | Writable?              | Example field                                                                        | Resolution         | Notes                                                                                                                                                                                                                                                                                                             |
|----|-----------------------------------------------------------------|-----------------|------------------------|--------------------------------------------------------------------------------------|--------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| F1 | `baseFieldDefinitions()`                                        | standard        | yes                    | `node.title`, `node.uid`, `node.status`, `commerce_product.variations`, `user.roles` | Expand via handler | Identified by `fieldIsBaseStandard()`. Iterated by `getEntityFieldTypes()`, routed through `getFieldHandler()`. Handler chosen by field type (see sub-table); `DefaultHandler` used only for H1 scalars. |
| F2 | `baseFieldDefinitions()`                                        | computed        | read-only              | TBD (pure derived accessors; examples sought)                                        | Skip entirely      | Identified by `fieldIsBaseComputedReadOnly()`. Rejected by `getEntityFieldTypes()`. Stub property, if set, flows untouched to the entity constructor; Drupal discards it on save because the field has no storage. |
| F3 | `baseFieldDefinitions()`                                        | computed        | writable (side-effect) | `node.moderation_state`                                                              | Skip entirely      | Identified by `fieldIsBaseComputedWritable()`. Rejected by `getEntityFieldTypes()`. Stub property flows untouched into `Node::create((array) $stub)`; the field class's item-list captures the raw value and performs its side-effect at save (e.g. `moderation_state` writes a `ContentModerationState` revision). |
| F4 | `baseFieldDefinitions()`                                        | custom storage  | yes                    | TBD (examples sought)                                                                | Skip entirely      | Identified by `fieldIsBaseCustomStorage()`. Rejected by `getEntityFieldTypes()`. Stub property flows untouched to the entity constructor; the declaring module's custom storage layer owns whatever happens next. |
| F5 | `FieldStorageConfig` + `FieldConfig`                            | standard        | yes                    | `node.field_tags`, `node.body`, `node.field_image`                                   | Expand via handler | Identified by `fieldIsConfigurable()`. Iterated by `getEntityFieldTypes()` via `getFieldStorageDefinitions()` and routed through `getFieldHandler()`. The common configurable-field path; handler chosen by field type (see sub-table). |
| F6 | `bundleFieldDefinitions()` alone (no storage-info sibling)      | computed        | read-only              | TBD                                                                                  | Skip entirely      | Identified by `fieldIsBundleComputedReadOnly()`. Absent from both `getFieldStorageDefinitions()` and `getBaseFieldDefinitions()`, so it never enters the pipeline. Stub property, if set, flows to the entity constructor and is discarded by Drupal. |
| F7 | `bundleFieldDefinitions()` alone                                | computed        | writable (side-effect) | TBD                                                                                  | Skip entirely      | Identified by `fieldIsBundleComputedWritable()`. Absent from pipeline sources - never reaches `getEntityFieldTypes()`. Stub property flows untouched to the entity constructor; the bundle-scoped field class captures the value at save. Analogous to F3 but bundle-scoped. |
| F8 | `bundleFieldDefinitions()` alone                                | custom storage  | yes                    | TBD                                                                                  | Skip entirely      | Identified by `fieldIsBundleCustomStorage()`. Absent from pipeline sources. Stub property flows untouched to the entity constructor; the module's custom storage layer owns whatever happens next. Analogous to F4 but bundle-scoped. |
| F9 | `hook_entity_field_storage_info()` + `bundleFieldDefinitions()` | standard        | yes                    | TBD (examples sought)                                                                | Expand via handler | Identified by `fieldIsBundleStorageBacked()`. Present in `getFieldStorageDefinitions()` via `hook_entity_field_storage_info()` but not a `FieldStorageConfig`. `getEntityFieldTypes()` iterates the field and admits it via the new predicate; per-bundle field definitions are consulted for handler metadata. Handler chosen by field type (see sub-table). |

## Handler selection sub-table - for rows with "Expand via handler"

When a field enters the pipeline (F1, F5, F9), handler selection is by the
field-type string returned by `FieldDefinitionInterface::getType()`.

| ID  | Field-type shape                   | Representative types                                                                                               | Handler                                                                                                | Rationale                                                                                                                  |
|-----|------------------------------------|--------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------|
| H1  | Single-column scalar               | `string`, `integer`, `boolean`, `float`, `decimal`, `email`, `telephone`, `uri`, `timestamp`, `created`, `changed` | `DefaultHandler`                                                                                       | `(array) $value` produces the correct `['value' => ...]` shape for all single-column scalars.                              |
| H2  | Multi-column compound              | `text`, `text_long`, `text_with_summary`, `link`, `address`, `daterange`                                           | Typed handler required (`TextHandler`, `TextLongHandler`, `TextWithSummaryHandler`, `LinkHandler`, `AddressHandler`, `DaterangeHandler`) | `DefaultHandler` must throw if it is invoked on a field type outside H1. See "DefaultHandler loud-failure policy" below.   |
| H3  | Simple datetime                    | `datetime`                                                                                                         | `DatetimeHandler`                                                                                      | Parses human date strings to ISO 8601 storage shape.                                                                       |
| H4  | Entity reference (single target)   | `entity_reference`, `file`, `image`                                                                                | `EntityReferenceHandler`, `FileHandler`, `ImageHandler`                                                | Resolve human-readable label/path/filename to `target_id`. `FileHandler`/`ImageHandler` first try to reuse an existing managed file at the given URI or bare basename (searching `public://` and `private://`) before falling back to uploading a new file under `public://<uniqid>.<ext>`. |
| H5  | Entity reference with revision     | `entity_reference_revisions` (paragraphs)                                                                          | `EntityReferenceRevisionsHandler`                                                                      | Composite `target_id` + `target_revision_id`. Resolves target and auto-populates the current revision id.                  |
| H6  | Typed list (allowed values)        | `list_string`, `list_integer`, `list_float`                                                                        | `ListStringHandler`, `ListIntegerHandler`, `ListFloatHandler`                                          | Allow matching on label or key; store the key.                                                                             |
| H7  | Typed list (boolean as list)       | `boolean` on list widget                                                                                           | `BooleanHandler`                                                                                       | Accept truthy aliases ("yes", "on", "true", field label).                                                                  |
| H8  | Name field (contrib)               | `name`                                                                                                             | `NameHandler`                                                                                          | Composite name components.                                                                                                 |
| H9  | Organic Groups reference (contrib) | `og_standard_reference`                                                                                            | `OgStandardReferenceHandler`                                                                           | OG-specific lookup.                                                                                                        |
| H10 | Embedded asset reference (contrib) | `embridge_asset_item`                                                                                              | `EmbridgeAssetItemHandler`                                                                             | Embridge-specific shape.                                                                                                   |

## Cardinality

Independent of resolution. Handlers must accept either a scalar or an array.
Internally, they normalize a scalar to `[$scalar]` before returning the storage
shape. No category in the primary table changes behavior based on cardinality.

## DefaultHandler loud-failure policy

`DefaultHandler` is the fallback when no typed handler matches a field's type
string. It runs `(array) $value`, which is only correct for H1 (single-column
scalars). For H2-H10 it would silently produce a malformed storage shape that
the entity layer then persists as broken data (entity reference by string
instead of id, datetime stored as raw user input, address fields left null,
etc.).

**`DefaultHandler` loudly fails when invoked on a field type outside H1.**

Detection criterion: the field's storage definition has exactly one column (the
canonical `value` column). If the field has multiple columns or its single
column is not named `value`, `DefaultHandler::expand()` throws a clearly-worded
exception identifying the field name, entity type, bundle, and field-type
string, and stating that a dedicated handler must be implemented for this
field type. The error is a direct call to action: implement the handler (or
register one), then re-run.

In typical scenarios (node title, boolean status, integer counters, etc.)
nothing changes - `DefaultHandler` works as today. In edge cases where a user
stubs a compound field that has no registered handler, they get an immediate,
actionable error instead of silently corrupted data downstream.

## Handler-coverage safety net

`FieldTypeCoverageKernelTest` enumerates every field-type plugin the loaded
Drupal install exposes and asserts that each one is either (a) backed by a
registered handler, (b) schema-compatible with `DefaultHandler` (single
`value` column), or (c) listed in the test's `SKIP` map with a documented
reason (computed, write-only, composite-lifecycle, etc.). Adding a new core
field type without a handler or a SKIP entry fails that test, preventing
the type from silently falling through to `DefaultHandler` and blowing up
the first time a scenario references it.

## What each resolution actually means in code

- **Expand via handler**: field enters `getEntityFieldTypes()`, survives all
  predicates, `getFieldHandler()` resolves a handler by field type,
  `$entity->$field_name = $handler->expand($entity->$field_name)` runs.
- **Skip entirely**: field never enters `getEntityFieldTypes()`. Stub property
  is left exactly as the caller set it. Value flows untouched into the entity
  via `Node::create((array) $stub)` or equivalent. The field class, Drupal
  storage layer, or module hooks take over from there.

The intentional consequence of "Skip entirely" for F2-F4 and F6-F8: the driver
treats computed and custom-storage fields as *transparent* - it neither fights
the field's own persistence mechanism nor tries to normalize a value it has no
schema for. The scenario author's raw value arrives at the entity; whatever
happens after is Drupal's and the declaring module's responsibility.

## Predicate design

One predicate per F-row, all on `FieldClassifierInterface`, implemented by
`Core\Field\FieldClassifier`.

| F-row | Predicate                                              | Returns TRUE when                                                                                                             |
|-------|--------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------|
| F1    | `fieldIsBaseStandard($type, $name)`                    | field is in `getBaseFieldDefinitions($type)`, not computed, not custom-storage                                                |
| F2    | `fieldIsBaseComputedReadOnly($type, $name)`            | in `getBaseFieldDefinitions($type)`, computed, `isReadOnly()` returns TRUE                                                    |
| F3    | `fieldIsBaseComputedWritable($type, $name)`            | in `getBaseFieldDefinitions($type)`, computed, `isReadOnly()` returns FALSE                                                   |
| F4    | `fieldIsBaseCustomStorage($type, $name)`               | in `getBaseFieldDefinitions($type)`, `hasCustomStorage()` returns TRUE                                                        |
| F5    | `fieldIsConfigurable($type, $name)`                    | present in `getFieldStorageDefinitions($type)` as `FieldStorageConfig` instance                                               |
| F6    | `fieldIsBundleComputedReadOnly($type, $name, $bundle)` | in `getFieldDefinitions($type, $bundle)`, computed, read-only, not in base definitions                                        |
| F7    | `fieldIsBundleComputedWritable($type, $name, $bundle)` | in `getFieldDefinitions($type, $bundle)`, computed, writable, not in base definitions                                         |
| F8    | `fieldIsBundleCustomStorage($type, $name, $bundle)`    | in `getFieldDefinitions($type, $bundle)`, custom storage, not in base definitions                                             |
| F9    | `fieldIsBundleStorageBacked($type, $name, $bundle)`    | present in `getFieldStorageDefinitions($type)` (via `hook_entity_field_storage_info()`), not a `FieldStorageConfig`, not base |

No aggregate predicate. Code that needs to decide whether a field enters the
expansion pipeline OR's the three expand-row predicates inline:

```php
if ($this->classifier()->fieldIsBaseStandard($entity_type, $field_name)
  || $this->classifier()->fieldIsConfigurable($entity_type, $field_name)
  || $this->classifier()->fieldIsBundleStorageBacked($entity_type, $field_name, $bundle)) {
  // expand
}
```

The verbosity is the point: each call site documents exactly which F-rows it
cares about. If a future F-row needs inclusion, it is added explicitly at each
call site rather than slipped into an aggregate that callers no longer see.

## Classifier discovery

The classifier follows the same version-directory pattern the repo uses for
`Core` itself and for field handlers. Future Drupal versions ship their own
classifier in `Core{N}\Field\FieldClassifier`; the parent's discovery logic
picks it up automatically when instantiated from a `Core{N}\Core` subclass.

`Core` exposes `createFieldClassifier()` as a factory method. Subclasses
override it only when they need a version-specific classifier class:

```php
// Core11\Core (hypothetical future)
protected function createFieldClassifier(): FieldClassifierInterface {
  return new \Drupal\Driver\Core11\Field\FieldClassifier(...);
}
```

The default implementation returns the base `FieldClassifier`. The pattern
mirrors `registerDefaultFieldHandlers()`, which likewise allows subclasses to
extend handler registration per version.

## Pipeline walk-through

For reference, here is the complete flow when `entityCreate()` is called with
a stub:

1. `entityCreate($entity_type, $entity)` calls `expandEntityFields($entity_type, $entity)`.
2. `expandEntityFields()` resolves the bundle from the stub and calls
   `getEntityFieldTypes($entity_type, $bundle)`.
3. `getEntityFieldTypes()` iterates `getFieldStorageDefinitions()`,
   `getBaseFieldDefinitions()`, and per-bundle definitions (when a bundle is
   known). Each field is kept iff `fieldIsBaseStandard()`,
   `fieldIsConfigurable()`, or `fieldIsBundleStorageBacked()` is TRUE.
4. `expandEntityFields()` iterates the returned map and, for each field whose
   name is also set as a property on the stub, calls `getFieldHandler()` to
   resolve a typed handler by field-type string (falling back to
   `DefaultHandler`). The handler's `expand()` transforms the stub value
   into Drupal's storage shape.
5. Fields not in the returned map (F2/F3/F4/F6/F7/F8) keep their original
   stub values. The entity constructor receives the full stub as an array;
   Drupal's field classes and storage layer take over from there.
