# Architecture: json-schema

## Purpose
A PHP implementation of the JSON Schema specification (drafts 3, 4, 6, 7). Validates JSON data against a schema, supporting `$ref` resolution, URI retrieval, format validators, and coercive type checking.

## Directory Structure
```
src/JsonSchema/
  Validator.php                    # Public entry point — validate($data, $schema)
  Schema_Storage.php               # Resolves and caches schemas by URI
  Constraint_Error.php             # Value object for a single validation error
  Constraints/
    Constraint_Interface.php       # All constraint implementations share this contract
    Base_Constraint.php            # Shared error collection and sub-constraint dispatch
    Constraint.php                 # Root constraint dispatcher (routes to sub-constraints)
    Collection_Constraint.php      # Arrays / objects validation
    Const_Constraint.php           # const keyword
    # ~15 more constraint classes for: type, enum, format, min/max, pattern, etc.
    Drafts/
      Draft06/                     # Overrides for JSON Schema draft-06 semantics
  Entity/
    Json_Pointer.php               # RFC 6901 JSON Pointer implementation
  Exception/                       # Typed exceptions for invalid schemas, URIs, etc.
  Iterators/
    Object_Iterator.php            # Iterates over stdClass properties uniformly
  Tool/
    Deep_Comparer.php              # Deep equality comparison for JSON values
    Deep_Copy.php                  # Deep clone for stdClass/array trees
    Validator/                     # URI/relative-reference validators
  Uri/
    Uri_Resolver.php               # Resolves $ref URIs relative to a base
    Uri_Retriever.php              # Fetches schema content by URI
    Retrievers/
      Curl.php                     # HTTP retrieval via cURL
      File_Get_Contents.php        # HTTP/file retrieval via file_get_contents
      Predefined_Array.php         # In-memory schema map (useful for testing)
demo/
  demo.php                         # Quick-start example
tests/
```

## Key Design Decisions
- **Constraint chain** — validation is decomposed into discrete constraint classes. The root `Constraint` dispatcher routes properties to the appropriate sub-constraint (type, enum, format, etc.).
- **Schema storage with URI resolution** — `Schema_Storage` handles `$ref` dereference and caches resolved schemas. URI retrieval is pluggable via `Uri_Retriever`.
- **Draft versioning** — draft-06 semantics are implemented as subclasses in `Constraints/Drafts/Draft06/` that override or extend base constraint behaviour.
- **Coercive mode** — the validator can optionally coerce strings to numbers/booleans before validation, controlled via the `Constraint::CHECK_MODE_COERCE_TYPES` flag.

## Extension Points
- Implement a custom `UriRetriever` to load schemas from a database, cache, or in-memory map.
- Register custom format validators via `Constraint::addFormat()`.
- Use `CHECK_MODE_APPLY_DEFAULTS` to populate missing properties with schema defaults.

## Dependency Flow
```
Validator::validate($data, $schema)
  └─ Constraint::check($data, $schema)
       ├─ Constraint::checkType()
       ├─ Constraint::checkFormat()
       ├─ Collection_Constraint::check() — for objects/arrays
       │    └─ recurses into nested constraints
       └─ Base_Constraint::addError() → Constraint_Error[]
```
