# maatify/image-profile — Extraction Checklist

Use this checklist before publishing the package to Packagist or moving it into
a standalone repository. Every item must be verified **green** before release.

---

## 1. Namespace Purity

- [x] No class inside `src/` references any namespace from the host project
      (e.g. no `App\`, no `AdminControlPanel\`, no `Maatify\AdminPanel\`)
- [x] No class inside `src/` references `Adapter\` or `Storage\` namespaces —
      those live outside `src/` by design
- [x] All `src/` files use the namespace root `Maatify\ImageProfile\` or a
      sub-namespace thereof (`Contract\`, `DTO\`, `Entity\`, `Enum\`,
      `Exception\`, `Infrastructure\`, `Provider\`, `Reader\`, `Validator\`,
      `ValueObject\`)

---

## 2. Framework & Cloud Independence (core `src/`)

- [x] No `use` statement inside `src/` imports from `Psr\Http\Message\`
- [x] No `use` statement inside `src/` imports from `Slim\`
- [x] No `use` statement inside `src/` imports from `Aws\`
- [x] No `use` statement inside `src/` imports from `Symfony\`, `Laravel\`, or
      any other framework
- [x] Runtime `$_FILES` superglobal access is **not** used in `src/`
- [x] Runtime `$_SERVER` and `$_REQUEST` superglobal access is **not** used in `src/`

---

## 3. Dependency Declaration (`composer.json`)

- [x] `require` block lists **only** `php ^8.1` (plus PHP extensions as needed)
- [x] `psr/http-message` is listed under `suggest`, NOT `require`
- [x] `aws/aws-sdk-php` is listed under `suggest`, NOT `require`
- [ ] Both are in `require-dev` for test/development use
- [x] `autoload.psr-4` maps `Maatify\ImageProfile\` → `src/`
- [x] `autoload.psr-4` also maps `Application\` and `Infrastructure\`
      namespaces for project-layer code
- [x] `autoload-dev.psr-4` maps the test namespace `Maatify\ImageProfile\Tests\`
      → `tests/`

---

## 4. No-Array Contract

- [x] No public method in `src/` returns `array` for a collection of domain
      objects — all use typed `*CollectionDTO` or `*Collection` value objects
- [x] No public method in `Application/` or `Infrastructure/` returns `array`
      for a collection of domain objects
- [x] `ImageProfileCollectionDTO` is used everywhere profiles are returned in bulk
- [x] `ImageValidationErrorCollectionDTO` is used for error lists
- [x] `ImageValidationWarningCollectionDTO` is used for warning lists
- [x] `AllowedExtensionCollection` is used for extension sets
- [x] `AllowedMimeTypeCollection` is used for MIME type sets

---

## 5. Immutability

- [ ] All `src/Entity/` and `src/DTO/` classes are `final readonly`
- [x] All `src/ValueObject/` classes are `final` with no public setters
- [x] Collection `with()` methods return **new** instances — they do not mutate
      the receiver
- [x] `ImageProfileCollectionDTO::filterActive()` returns a new instance

---

## 6. Contract Stability (`ValidationErrorCodeEnum`)

- [x] All 15 enum cases exist (verified by `ValidationErrorCodeEnumStabilityTest`)
- [x] String values match exactly:
      `profile_not_found`, `profile_inactive`, `file_not_found`,
      `file_not_readable`, `metadata_unreadable`, `mime_not_allowed`,
      `extension_not_allowed`, `width_too_small`, `height_too_small`,
      `width_too_large`, `height_too_large`, `file_too_large`,
      `aspect_ratio_too_narrow`, `aspect_ratio_too_wide`, `transparency_required`
- [ ] `ValidationErrorCodeEnum::from()` round-trips all 12 values correctly
- [x] `ValidationErrorCodeEnum::from()` round-trips all 15 values correctly

---

## 7. Test Suite

- [ ] All Unit tests pass: `./vendor/bin/phpunit --testsuite Unit`
- [ ] All Integration tests pass: `./vendor/bin/phpunit --testsuite Integration`
- [ ] All Contract tests pass: `./vendor/bin/phpunit --testsuite Contract`
- [x] No test requires an external database (SQLite in-memory only)
- [x] No test leaks temp files (every test class using `TestImageFactory` calls
      `TestImageFactory::cleanup()` in `tearDown()`)
- [ ] `ext-gd` available for Validator and Reader unit tests
- [ ] `ext-pdo` + `ext-pdo_sqlite` available for Integration tests

---

## 8. Static Analysis

- [ ] PHPStan passes at level 10 with zero errors:
      `./vendor/bin/phpstan analyse`
- [x] `phpstan.neon` covers all five source trees:
      `src/`, `Application/`, `Infrastructure/`, `Adapter/`, `Storage/`

---

## 9. Validator Behaviour Invariants

- [x] `validateByCode()` NEVER throws — it always returns `ImageValidationResultDTO`
- [x] Infrastructure failures (missing profile, missing file, unreadable metadata)
      short-circuit immediately with a single error in the result
- [x] Rule failures (mime, extension, dimensions, size) are **all** collected
      before returning — no premature short-circuit
- [x] `isValid() === true` ⟹ `errors` collection is empty (verified by contract test)
- [x] `findByCode()` on providers does **not** filter by `is_active` — the
      validator owns that check

---

## 10. Read / Write Separation

- [x] `ImageProfileProviderInterface` is read-only (no `save`, `update`, or
      `delete` methods)
- [x] `ImageProfileRepositoryInterface` is write-only (no `findByCode`,
      `listAll`, or `listActive` methods)
- [x] `ImageProfileValidator` depends only on `ImageProfileProviderInterface`,
      never on `ImageProfileRepositoryInterface`
- [x] Application services depend only on `ImageProfileRepositoryInterface` for
      writes, never on the provider directly

---

## 10.1 Public Validation Entry (consumer-facing)

- [x] `ImageProfileValidationService` exists as a neutral public entry for
      profile lookup/list + `validateByCode()`
- [x] `ImageProfileComposition` provides framework-agnostic wiring guidance
      (explicit provider path + ready PDO path)
- [x] Core validation usage does not require any processing/variants API

---

## 10.2 Canonical Consumption Surface

- [x] Validation-first consumers can rely on `ImageProfileValidationServiceInterface`
      with `ImageFileInputDTO` → `ImageValidationResultDTO`
- [x] Write use-cases use canonical command/input DTOs:
      `CreateImageProfileRequest`, `UpdateImageProfileRequest`
- [x] Write orchestration depends on `ImageProfileRepositoryInterface`
      and library services, not loose arrays
- [x] Processing metadata is optional extension data via
      `ImageProfileProcessingExtensionDTO`, not canonical validation shape

---

## 11. Schema & Infrastructure

- [x] `src/Infrastructure/Schema/image_profiles.sql` is present and up to date
- [x] `code` column has a UNIQUE constraint
- [x] `is_active` column has an index
- [x] `allowed_extensions` and `allowed_mime_types` are stored as
      comma-separated strings and parsed via `fromDelimitedString()`
- [x] `PdoImageProfileRepository` re-fetches the row after every mutation
      (INSERT, UPDATE, toggleActive) so the returned entity reflects DB state

---

## 12. Exception Hierarchy

- [x] All package exceptions extend `ImageProfileException` (the base class)
- [x] No raw `\Exception` or `\RuntimeException` is thrown directly by package
      code in `src/`
- [x] `PDOException` is always caught and re-thrown as `ImageProfileException`
- [x] `AwsException` is always caught and re-thrown as `ImageProfileException`
      (in `DoSpacesImageStorage`)

---

## 13. Adapters & Storage (outside `src/`)

- [x] `Adapter/SlimUploadedFileAdapter.php` requires `psr/http-message` at
      runtime — this dependency is on the **project**, not the library
- [x] `Storage/DoSpacesImageStorage.php` requires `aws/aws-sdk-php` at runtime
      — this dependency is on the **project**, not the library
- [x] Both adapters throw `InvalidImageInputException::uploadError()` on any
      `UPLOAD_ERR_*` code other than `UPLOAD_ERR_OK`

---

## 14. CHANGELOG & Versioning

- [x] `CHANGELOG.md` documents all released versions following Keep a Changelog
- [x] Each version entry lists Added, Changed, Fixed, Removed, Architecture as
      applicable
- [x] `[Unreleased]` section exists and is kept empty between releases
- [x] Version links at the bottom of `CHANGELOG.md` point to the correct GitHub
      diff URLs

---

*All items must be green before tagging a release. If any item fails, do not
publish. Fix the issue, re-run the full test suite, and re-check this list.*
