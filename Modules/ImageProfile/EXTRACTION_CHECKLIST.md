# maatify/image-profile — Extraction Checklist

Use this checklist before publishing the package to Packagist or moving it into
a standalone repository. Every item must be verified **green** before release.

---

## 1. Namespace Purity

- [ ] No class inside `src/` references any namespace from the host project
      (e.g. no `App\`, no `AdminControlPanel\`, no `Maatify\AdminPanel\`)
- [ ] No class inside `src/` references `Adapter\` or `Storage\` namespaces —
      those live outside `src/` by design
- [ ] All `src/` files use the namespace root `Maatify\ImageProfile\` or a
      sub-namespace thereof (`Contract\`, `DTO\`, `Entity\`, `Enum\`,
      `Exception\`, `Infrastructure\`, `Provider\`, `Reader\`, `Validator\`,
      `ValueObject\`)

---

## 2. Framework & Cloud Independence (core `src/`)

- [ ] No `use` statement inside `src/` imports from `Psr\Http\Message\`
- [ ] No `use` statement inside `src/` imports from `Slim\`
- [ ] No `use` statement inside `src/` imports from `Aws\`
- [ ] No `use` statement inside `src/` imports from `Symfony\`, `Laravel\`, or
      any other framework
- [ ] `$_FILES` superglobal is **not** referenced anywhere in `src/`
- [ ] `$_SERVER` and `$_REQUEST` superglobals are **not** referenced in `src/`

---

## 3. Dependency Declaration (`composer.json`)

- [ ] `require` block lists **only** `php ^8.1` (plus PHP extensions as needed)
- [ ] `psr/http-message` is listed under `suggest`, NOT `require`
- [ ] `aws/aws-sdk-php` is listed under `suggest`, NOT `require`
- [ ] Both are in `require-dev` for test/development use
- [ ] `autoload.psr-4` maps `Maatify\ImageProfile\` → `src/`
- [ ] `autoload.psr-4` also maps `Application\` and `Infrastructure\`
      namespaces for project-layer code
- [ ] `autoload-dev.psr-4` maps the test namespace `Maatify\ImageProfile\Tests\`
      → `tests/`

---

## 4. No-Array Contract

- [ ] No public method in `src/` returns `array` for a collection of domain
      objects — all use typed `*CollectionDTO` or `*Collection` value objects
- [ ] No public method in `Application/` or `Infrastructure/` returns `array`
      for a collection of domain objects
- [ ] `ImageProfileCollectionDTO` is used everywhere profiles are returned in bulk
- [ ] `ImageValidationErrorCollectionDTO` is used for error lists
- [ ] `ImageValidationWarningCollectionDTO` is used for warning lists
- [ ] `AllowedExtensionCollection` is used for extension sets
- [ ] `AllowedMimeTypeCollection` is used for MIME type sets

---

## 5. Immutability

- [ ] All `src/Entity/` and `src/DTO/` classes are `final readonly`
- [ ] All `src/ValueObject/` classes are `final` with no public setters
- [ ] Collection `with()` methods return **new** instances — they do not mutate
      the receiver
- [ ] `ImageProfileCollectionDTO::filterActive()` returns a new instance

---

## 6. Contract Stability (`ValidationErrorCodeEnum`)

- [ ] All 12 enum cases exist (verified by `ValidationErrorCodeEnumStabilityTest`)
- [ ] String values match exactly:
      `profile_not_found`, `profile_inactive`, `file_not_found`,
      `file_not_readable`, `metadata_unreadable`, `mime_not_allowed`,
      `extension_not_allowed`, `width_too_small`, `height_too_small`,
      `width_too_large`, `height_too_large`, `file_too_large`
- [ ] `ValidationErrorCodeEnum::from()` round-trips all 12 values correctly

---

## 7. Test Suite

- [ ] All Unit tests pass: `./vendor/bin/phpunit --testsuite Unit`
- [ ] All Integration tests pass: `./vendor/bin/phpunit --testsuite Integration`
- [ ] All Contract tests pass: `./vendor/bin/phpunit --testsuite Contract`
- [ ] No test requires an external database (SQLite in-memory only)
- [ ] No test leaks temp files (every test class using `TestImageFactory` calls
      `TestImageFactory::cleanup()` in `tearDown()`)
- [ ] `ext-gd` available for Validator and Reader unit tests
- [ ] `ext-pdo` + `ext-pdo_sqlite` available for Integration tests

---

## 8. Static Analysis

- [ ] PHPStan passes at level 10 with zero errors:
      `./vendor/bin/phpstan analyse`
- [ ] `phpstan.neon` covers all five source trees:
      `src/`, `Application/`, `Infrastructure/`, `Adapter/`, `Storage/`

---

## 9. Validator Behaviour Invariants

- [ ] `validateByCode()` NEVER throws — it always returns `ImageValidationResultDTO`
- [ ] Infrastructure failures (missing profile, missing file, unreadable metadata)
      short-circuit immediately with a single error in the result
- [ ] Rule failures (mime, extension, dimensions, size) are **all** collected
      before returning — no premature short-circuit
- [ ] `isValid() === true` ⟹ `errors` collection is empty (verified by contract test)
- [ ] `findByCode()` on providers does **not** filter by `is_active` — the
      validator owns that check

---

## 10. Read / Write Separation

- [ ] `ImageProfileProviderInterface` is read-only (no `save`, `update`, or
      `delete` methods)
- [ ] `ImageProfileRepositoryInterface` is write-only (no `findByCode`,
      `listAll`, or `listActive` methods)
- [ ] `ImageProfileValidator` depends only on `ImageProfileProviderInterface`,
      never on `ImageProfileRepositoryInterface`
- [ ] Application services depend only on `ImageProfileRepositoryInterface` for
      writes, never on the provider directly

---

## 11. Schema & Infrastructure

- [ ] `src/Infrastructure/Schema/image_profiles.sql` is present and up to date
- [ ] `code` column has a UNIQUE constraint
- [ ] `is_active` column has an index
- [ ] `allowed_extensions` and `allowed_mime_types` are stored as
      comma-separated strings and parsed via `fromDelimitedString()`
- [ ] `PdoImageProfileRepository` re-fetches the row after every mutation
      (INSERT, UPDATE, toggleActive) so the returned entity reflects DB state

---

## 12. Exception Hierarchy

- [ ] All package exceptions extend `ImageProfileException` (the base class)
- [ ] No raw `\Exception` or `\RuntimeException` is thrown directly by package
      code in `src/`
- [ ] `PDOException` is always caught and re-thrown as `ImageProfileException`
- [ ] `AwsException` is always caught and re-thrown as `ImageProfileException`
      (in `DoSpacesImageStorage`)

---

## 13. Adapters & Storage (outside `src/`)

- [ ] `Adapter/SlimUploadedFileAdapter.php` requires `psr/http-message` at
      runtime — this dependency is on the **project**, not the library
- [ ] `Storage/DoSpacesImageStorage.php` requires `aws/aws-sdk-php` at runtime
      — this dependency is on the **project**, not the library
- [ ] Both adapters throw `InvalidImageInputException::uploadError()` on any
      `UPLOAD_ERR_*` code other than `UPLOAD_ERR_OK`

---

## 14. CHANGELOG & Versioning

- [ ] `CHANGELOG.md` documents all released versions following Keep a Changelog
- [ ] Each version entry lists Added, Changed, Fixed, Removed, Architecture as
      applicable
- [ ] `[Unreleased]` section exists and is kept empty between releases
- [ ] Version links at the bottom of `CHANGELOG.md` point to the correct GitHub
      diff URLs

---

*All items must be green before tagging a release. If any item fails, do not
publish. Fix the issue, re-run the full test suite, and re-check this list.*
