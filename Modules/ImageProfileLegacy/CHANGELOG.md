# Changelog

All notable changes to `maatify/image-profile` will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [0.9.0] — 2026-04-17

### Added

- `src/Enum/ValidationErrorCodeEnum` — 3 new cases (all with stable string values): `AspectRatioTooNarrow` (`aspect_ratio_too_narrow`), `AspectRatioTooWide` (`aspect_ratio_too_wide`), `TransparencyRequired` (`transparency_required`). Total case count: 15.
- `src/Entity/ImageProfile` — 6 new fields (all backward-compatible with safe defaults): `minAspectRatio(?float)`, `maxAspectRatio(?float)`, `requiresTransparency(bool = false)`, `preferredFormat(?ImageFormatEnum)`, `preferredQuality(?int)`, `variants(VariantDefinitionCollectionDTO)`. New helpers: `hasAspectRatioConstraint()`, `hasVariants()`.
- `src/Validator/ImageProfileValidator::collectRuleErrors()` — 3 new rule checks collected exhaustively alongside existing rules: aspect ratio too narrow (`width/height < minAspectRatio`), aspect ratio too wide (`width/height > maxAspectRatio`), transparency required (`requiresTransparency = true` + MIME not in `[image/png, image/webp]`).
- `src/DTO/ImageValidationErrorDTO` — 3 new named constructors: `aspectRatioTooNarrow(float, float)`, `aspectRatioTooWide(float, float)`, `transparencyRequired(string)`. All include `expected` and `actual` context fields. Existing constructors updated to use PascalCase enum names (internal refactor; wire format unchanged).
- `src/DTO/VariantDefinitionCollectionDTO::fromJsonArray(array)` — deserialise from a JSON-decoded array; malformed entries are silently skipped. `fromJsonString(?string)` — deserialise from a raw DB TEXT column; returns empty collection on null/empty/invalid JSON.
- `src/Infrastructure/Schema/image_profiles.sql` — 6 new columns: `min_aspect_ratio DECIMAL(8,4)`, `max_aspect_ratio DECIMAL(8,4)`, `requires_transparency TINYINT(1) NOT NULL DEFAULT 0`, `preferred_format VARCHAR(10)`, `preferred_quality TINYINT UNSIGNED`, `variants JSON`. ALTER TABLE migration script included as a comment block for upgrading pre-Phase-9 tables.
- `src/Infrastructure/Persistence/PDO/PdoImageProfileProvider` — `mapRow()` reads all 6 new columns; `ImageFormatEnum::tryFrom()` for format; `VariantDefinitionCollectionDTO::fromJsonString()` for variants.
- `Infrastructure/Repository/PDO/PdoImageProfileRepository` — `save()` / `update()` bind all 6 new columns; `mapRow()` reads them; `serializeVariants()` helper encodes `VariantDefinitionCollectionDTO` to JSON for storage.
- `Application/DTO/CreateImageProfileRequest` — 6 new fields with safe defaults.
- `Application/DTO/UpdateImageProfileRequest` — 6 new fields with safe defaults.
- `tests/Fixtures/ImageProfileFixtureFactory` — 4 new factory methods: `landscapeOnly()` (minAspectRatio = 16/9), `portraitOrSquare()` (maxAspectRatio = 1.0), `requiresTransparency()`, `withVariants()` (thumbnail WebP + medium JPEG variants, preferredFormat = Webp).
- `tests/Unit/Validator/ImageProfileValidatorPhase9Test.php` — 12 tests covering: portrait image triggers `aspect_ratio_too_narrow`; error carries `actual`/`expected` context; square passes `portrait_or_square`; JPEG triggers `transparency_required`; error carries context; PNG passes; WebP passes; both aspect-ratio and transparency errors collected simultaneously (≥ 2 errors in one call); profile with variants validates normally; variant collection attached to profile; `hasAspectRatioConstraint()` true/false helpers.

### Changed

- `src/Enum/ValidationErrorCodeEnum` — case names changed from `UPPER_SNAKE_CASE` to `PascalCase` (PHP 8.1 convention). **String values are unchanged** — this is a source-level rename only. Any code referencing e.g. `ValidationErrorCodeEnum::PROFILE_NOT_FOUND` must be updated to `ValidationErrorCodeEnum::ProfileNotFound`.
- `tests/Contract/ValidationErrorCodeEnumStabilityTest` — count assertion updated 12→15; 3 new value assertions; 3 new entries in `allCodesProvider`.

### Architecture

- `preferredFormat` and `preferredQuality` are advisory fields. They guide the processing layer (Phase 8) but are intentionally NOT validated by `ImageProfileValidator`. Decoupling advice from enforcement keeps the validator focused.
- `variants` on `ImageProfile` is also advisory — it tells the processing layer what to generate after a successful upload. The validator never reads it.
- Aspect ratio is computed as `width / height`. A ratio > 1 is landscape; = 1 is square; < 1 is portrait.

---

## [0.8.0] — 2026-04-17

### Added

- `src/Enum/ResizeModeEnum.php` — stable string enum: `Fit` (`fit`), `Fill` (`fill`), `Stretch` (`stretch`). Documents the semantic difference between each mode (no-crop / centre-crop / distort).
- `src/Enum/ImageFormatEnum.php` — stable string enum: `Jpeg` (`jpg`), `Png` (`png`), `Webp` (`webp`), `Gif` (`gif`). Provides `mimeType()` helper and `fromString()` factory that resolves from both file extension and MIME type strings.
- `src/DTO/ResizeOptionsDTO.php` — `final readonly`, implements `JsonSerializable`; fields: `width`, `height`, `mode` (ResizeModeEnum), `quality` (1–100), `outputFormat` (?ImageFormatEnum). Guards against non-positive dimensions and out-of-range quality. Named constructors: `fit()`, `fill()`, `webpThumbnail()`.
- `src/DTO/OptimizationOptionsDTO.php` — `final readonly`, implements `JsonSerializable`; fields: `quality`, `stripMetadata`, `targetFormat`. Named constructors: `recompress()`, `toWebp()`, `lossless()`.
- `src/DTO/ProcessedImageDTO.php` — `final readonly`, implements `JsonSerializable`; fields: `outputPath`, `width`, `height`, `sizeBytes`, `mimeType`, `format`, `processingTimeMs`. Helpers: `fileName()`, `directory()`.
- `src/DTO/VariantDefinitionDTO.php` — `final readonly`, implements `JsonSerializable`; fields: `name`, `options` (ResizeOptionsDTO). Named constructors: `thumbnail()`, `medium()`, `large()`.
- `src/DTO/VariantDefinitionCollectionDTO.php` — implements `IteratorAggregate, Countable, JsonSerializable`; immutable `with()`, `findByName()`, `hasName()`.
- `src/DTO/GeneratedVariantDTO.php` — `final readonly`, implements `JsonSerializable`; fields: `name`, `result` (ProcessedImageDTO).
- `src/DTO/GeneratedVariantCollectionDTO.php` — implements `IteratorAggregate, Countable, JsonSerializable`; immutable `with()`, `findByName()`, `hasName()`, `totalSizeBytes()`.
- `src/Contract/ImageProcessorInterface.php` — `resize()`, `optimize()`, `convertToWebp()`; all return `ProcessedImageDTO`, all throw `ImageProfileException` on failure, never null, intentionally separate from validation.
- `src/Contract/ImageVariantGeneratorInterface.php` — `generate(string $sourcePath, string $targetDirectory, VariantDefinitionCollectionDTO): GeneratedVariantCollectionDTO`.
- `src/Processor/NativeImageProcessor.php` — GD-based implementation of `ImageProcessorInterface`. Supports JPEG, PNG, WebP, GIF sources. Resize strategies: Fit (uniform scale to fit box), Fill (cover + centre-crop to exact dimensions), Stretch (force exact size). Alpha channel preserved for PNG/WebP. Measures `processingTimeMs` via `hrtime()`. All GD errors wrapped as anonymous `ImageProfileException` subclasses.
- `src/Processor/NativeImageVariantGenerator.php` — implements `ImageVariantGeneratorInterface`; delegates each variant to `ImageProcessorInterface::resize()`; validates directory existence and writability before processing; names output files `{variantName}.{ext}` inside target directory.
- `src/Exception/InvalidImageInputException::invalidProcessingOption()` — new named constructor for invalid processing option values (non-positive dimensions, out-of-range quality).
- `tests/Unit/Processor/NativeImageProcessorTest.php` — 20 tests covering: return types, Fit/Fill/Stretch exact dimensions, format conversion (JPEG→WebP), PNG/WebP sources, missing source throws, non-image source throws, optimise recompress, optimise to WebP, convertToWebp, JSON serialization.
- `tests/Unit/Processor/NativeImageVariantGeneratorTest.php` — 12 tests covering: return type, single/multi variant generation, file existence on disk, empty collection, totalSizeBytes, non-existent directory throws, WebP variant, JSON serialization.

### Architecture

- **Processing is strictly separated from validation.** `NativeImageProcessor` and `NativeImageVariantGenerator` know nothing about image profiles, upload rules, or `ImageValidationResultDTO`. They are purely input→output transformers.
- `NativeImageProcessor` re-saves through GD for every operation, which automatically strips EXIF/XMP/IPTC metadata (`stripMetadata` in `OptimizationOptionsDTO` is honoured because GD never preserves metadata on output).
- No new external dependencies added. Processing relies entirely on `ext-gd` (already required by tests).

---

## [0.7.0] — 2026-04-17

### Added

- `.github/workflows/ci.yml` — GitHub Actions CI pipeline running on PHP 8.1, 8.2, and 8.3 against both `prefer-lowest` and `prefer-stable` dependency versions. Three jobs: `tests` (Unit + Integration + Contract suites; Xdebug coverage uploaded to Codecov on PHP 8.2 stable), `static-analysis` (PHPStan level 10 on all three PHP versions), `audit` (Composer vulnerability audit).
- `CONTRIBUTING.md` — full contributor guide: requirements, local setup, running individual test suites, PHPStan, coding standards table (no-array, no framework imports in `src/`, immutability, enum stability), architectural rules table (read/write split, validator never throws on business failures, exhaustive rule collection, PDO re-fetch after mutation), branch strategy, Conventional Commits format, PR checklist.
- `tests/Integration/Validator/ImageProfileValidatorIntegrationTest.php` — end-to-end full-stack integration test: SQLite in-memory database seeded with five profiles (permissive, banner, webp_only, tiny_limit, retired/inactive) wired to a real `PdoImageProfileProvider` + `NativeImageMetadataReader` + `ImageProfileValidator`. Covers: valid JPEG/PNG/WebP happy paths; MIME + extension error collection (two errors at once); size limit violation; min-dimension violation on banner profile; inactive profile short-circuit; unknown profile short-circuit; missing file; non-image file; metadata populated on rule failure; JSON serialization; profile code preserved in result.
- `.gitignore` — standard library `.gitignore`: excludes `vendor/`, `build/`, PHPUnit and PHPStan caches, IDE/OS artefacts; `composer.lock` excluded per library convention.

### Architecture

- The CI matrix deliberately includes `prefer-lowest` to catch accidental dependency on APIs introduced after the declared minimum version constraint.
- The full-stack integration test uses no mocks — it is the definitive proof that all three layers (provider, reader, validator) compose correctly.

---

## [0.6.0] — 2026-04-17

### Added

- `phpstan.neon` — PHPStan level 10 (max + bleedingEdge) covering all five source trees: `src/`, `Application/`, `Infrastructure/`, `Adapter/`, `Storage/`; strict rules enabled; `checkMissingIterableValueType` and `checkGenericClassInNonGenericObjectType` enabled; `reportUnmatchedIgnoredErrors: true` to prevent stale suppressions.
- `tests/Contract/ValidationErrorCodeEnumStabilityTest` — 12 individual value assertions (one per enum case) + exact case-count assertion + `from()` round-trip data provider covering all 12 codes; any rename or addition is a breaking change caught here before release.
- `tests/Contract/ImageProfileProviderInterfaceContractTest` — runs the full provider contract against both `ArrayImageProfileProvider` and `PdoImageProfileProvider` (SQLite in-memory): `findByCode` returns the correct typed entity, returns `null` for missing codes, does NOT filter by `is_active`; `listAll()` and `listActive()` return `ImageProfileCollectionDTO`; `listActive()` excludes inactive profiles.
- `tests/Contract/ImageProfileValidatorInterfaceContractTest` — invariant assertions for `ImageProfileValidator`: always returns `ImageValidationResultDTO` (never throws); `errors` and `warnings` are always typed collection DTOs; `isValid=true` ⟹ empty errors; infra failures (missing profile, inactive profile, missing file) return invalid results, not exceptions; rule failures are collected exhaustively; metadata is present on rule failure; result is JSON-serializable.
- `phpunit.xml.dist` updated: added `Contract` test suite pointing to `tests/Contract/`.
- `EXTRACTION_CHECKLIST.md` — 14-section pre-release checklist covering: namespace purity, framework/cloud independence of `src/`, composer.json dependency declarations, no-array contract, immutability, contract stability, full test suite, static analysis, validator behaviour invariants, read/write separation, schema & infrastructure, exception hierarchy, adapters & storage, and CHANGELOG completeness.

### Architecture

- Contract tests are intentionally separated from Unit and Integration suites. They assert API surface stability — not implementation details — so they remain valid across refactors.
- `phpstan.neon` uses `bleedingEdge.neon` to catch issues that will become errors in future PHPStan releases before they land.
- `EXTRACTION_CHECKLIST.md` serves as the authoritative gate before any Packagist release or repository extraction.

---

## [0.5.0] — 2026-04-17

### Added

- `Adapter/SlimUploadedFileAdapter` — converts a PSR-7 `UploadedFileInterface` (Slim, Nyholm, Guzzle PSR-7, etc.) into `ImageFileInputDTO`; guards all `UPLOAD_ERR_*` codes before construction; throws `InvalidImageInputException` on error or empty stream URI.
- `Adapter/NativePhpUploadAdapter` — converts a native `$_FILES` entry into `ImageFileInputDTO` via `fromFilesEntry()` and `fromSuperGlobal()`; maps all PHP upload error codes to `InvalidImageInputException` with descriptive messages.
- `Storage/ImageStorageInterface` — write contract for image storage backends: `store(localPath, remotePath): StoredImageDTO` and `delete(remotePath): void`.
- `Storage/StoredImageDTO` — immutable result of a successful store operation; fields: `publicUrl`, `remotePath`, `disk`, `sizeBytes`, `mimeType`; implements `JsonSerializable`.
- `Storage/DoSpacesImageStorage` — DigitalOcean Spaces (S3-compatible) implementation; uses `aws/aws-sdk-php`; detects MIME via `finfo`; builds public URL from CDN base or bucket + endpoint; wraps `AwsException` as `ImageProfileException`.
- `InvalidImageInputException::uploadError()` — new named constructor added to the core exception to support adapter error reporting.
- `composer.json` updated: added autoload entries for `Adapter/` and `Storage/`; added `psr/http-message` to `require-dev`; added `suggest` entries for `psr/http-message` and `aws/aws-sdk-php`.
- `tests/Unit/Adapter/SlimUploadedFileAdapterTest` — happy path, null mime, null size, all 7 UPLOAD_ERR_* codes via data provider, empty stream URI.
- `tests/Unit/Adapter/NativePhpUploadAdapterTest` — valid entry, empty type → null mime, zero size, all 7 error codes, filename in exception message.
- `tests/Unit/Storage/StoredImageDTOTest` — field access, `jsonSerialize`, JSON encode round-trip.
- `tests/Unit/Storage/DoSpacesImageStorageTest` — mocked S3Client: `putObject` called once, DTO returned, CDN URL built correctly, direct URL without CDN, JPEG/PNG MIME detected, `AwsException` → `ImageProfileException` for both `store` and `delete`, correct bucket/key passed to `deleteObject`.

### Architecture

- Adapters live in `Adapter/` — outside `src/` — so the core library gains zero HTTP or superglobal dependencies.
- Storage lives in `Storage/` — outside `src/` — so the core library gains zero cloud SDK dependencies.
- `src/` contains only the reusable, framework-agnostic validation core. This boundary is now complete.
- External deps required by the project (not the library): `psr/http-message` for the Slim adapter, `aws/aws-sdk-php` for DO Spaces.

---

## [0.4.0] — 2026-04-17

### Added

- `Application/Contract/ImageProfileRepositoryInterface` — write-side contract (save, update, toggleActive, existsByCode); intentionally separate from the read-only `ImageProfileProviderInterface`.
- `Application/DTO/CreateImageProfileRequest` — immutable, typed input DTO for the create use case; implements `JsonSerializable`; no raw arrays.
- `Application/DTO/UpdateImageProfileRequest` — immutable, typed input DTO for the update use case; implements `JsonSerializable`; `code` is excluded (immutable identifier).
- `Application/Exception/DuplicateProfileCodeException` — thrown by `CreateImageProfileService` when the requested code is already in use; extends `ImageProfileException`.
- `Application/Service/CreateImageProfileService` — guards against duplicate codes then delegates to the repository; never calls the validator (separation of concerns).
- `Application/Service/UpdateImageProfileService` — delegates to the repository `update()`; propagates `ImageProfileNotFoundException` from the persistence layer.
- `Application/Service/ToggleImageProfileService` — exposes `enable()`, `disable()`, and `toggle()` as explicit named actions over `repository::toggleActive()`.
- `Infrastructure/Repository/PDO/PdoImageProfileRepository` — implements `ImageProfileRepositoryInterface` using PDO; re-fetches the row after every mutation so the returned `ImageProfile` reflects exact DB state; wraps all `PDOException` as `ImageProfileException`; serializes `AllowedExtensionCollection` / `AllowedMimeTypeCollection` as comma-delimited strings for storage.
- `composer.json` updated: added PSR-4 autoload entries for `Application/` (`Maatify\ImageProfile\Application\`) and `Infrastructure/` (`Maatify\ImageProfile\Infrastructure\`).
- `tests/Unit/Application/Service/CreateImageProfileServiceTest` — happy path, `save` called once, `DuplicateProfileCodeException` thrown and `save` never called when duplicate.
- `tests/Unit/Application/Service/UpdateImageProfileServiceTest` — delegates to repository, returns updated entity, propagates `ImageProfileNotFoundException`.
- `tests/Unit/Application/Service/ToggleImageProfileServiceTest` — `enable()` passes `true`, `disable()` passes `false`, `toggle()` explicit control, not-found propagation for both enable and disable.
- `tests/Integration/Infrastructure/Repository/PdoImageProfileRepositoryTest` — SQLite in-memory integration: `existsByCode`, `save` (all fields, null bounds, inactive), `update` (fields, code immutability, is_active untouched), `toggleActive` (enable, disable, not-found).

### Architecture

- Write side (`ImageProfileRepositoryInterface` / `PdoImageProfileRepository`) is completely decoupled from read side (`ImageProfileProviderInterface` / `PdoImageProfileProvider`).
- Services contain zero persistence logic — they only orchestrate contracts.
- The core library (`src/`) has no dependency on the Application or Infrastructure layers.
- `code` is treated as immutable: update operations do not change it.

---

## [0.3.0] — 2026-04-17

### Added

- `phpunit.xml.dist` — PHPUnit 10 configuration with Unit and Integration test suites, coverage report targets, and strict error reporting.
- `tests/Fixtures/ImageProfileFixtureFactory` — centralised factory for test `ImageProfile` entities covering: `standard`, `noTypeRestriction`, `unrestricted`, `inactive`, `strictMinDimensions`, `strictMaxDimensions`, `strictMaxSize`, and `webpOnly`.
- `tests/Fixtures/TestImageFactory` — runtime image-file factory using GD; creates real JPEG, PNG, WebP, and GIF files in the system temp directory; tracks created files for automatic cleanup; exposes `cleanup()`.
- `tests/Unit/ValueObject/AllowedExtensionCollectionTest` — 18 cases covering normalization, deduplication, leading-dot stripping, `has()`, `fromDelimitedString()` with multiple delimiters, iteration, and JSON serialization.
- `tests/Unit/ValueObject/AllowedMimeTypeCollectionTest` — 17 cases covering lowercase normalization, whitespace trimming, deduplication, `has()`, `fromDelimitedString()`, iteration, and JSON serialization.
- `tests/Unit/DTO/ImageFileInputDTOTest` — valid construction, null `clientMimeType`, zero `sizeBytes`, and three guard-clause throws (empty name, empty path, negative size).
- `tests/Unit/DTO/ImageValidationResultDTOTest` — `valid()` and `invalid()` factory semantics, contract invariant (`isValid === true` ⟹ errors empty), null/non-null metadata, and JSON encoding round-trip.
- `tests/Unit/DTO/ImageProfileCollectionDTOTest` — `empty()`, `with()` immutability, `first()`, `filterActive()` (including non-mutation proof), iteration, and JSON serialization.
- `tests/Unit/Provider/ArrayImageProfileProviderTest` — `findByCode` (hit, miss, inactive pass-through, empty provider, multi-profile), `listAll()`, and `listActive()`.
- `tests/Unit/Validator/ImageProfileValidatorTest` — full rule matrix: `profile_not_found`, `profile_inactive`, `file_not_found`, valid JPEG/PNG/WebP happy paths, `mime_not_allowed`, `extension_not_allowed`, `width_too_small`, `height_too_small`, `width_too_large`, `height_too_large`, `file_too_large`, multi-error collection, metadata presence on rule failure vs infra short-circuit.
- `tests/Unit/Reader/NativeImageMetadataReaderTest` — JPEG/PNG/WebP dimension and MIME reads, `sizeBytes` match, lowercase contracts, throws on non-image file.
- `tests/Integration/Infrastructure/PDO/PdoImageProfileProviderTest` — SQLite in-memory integration: `findByCode` (null on empty, null on miss, full field mapping, inactive pass-through, null bounds, extensions, mime types, notes), `listAll()`, and `listActive()` (SQL-level filter).

### Architecture

- Validation errors are always collected exhaustively for rule failures; short-circuit only on infra failures (missing profile, missing/unreadable file, unreadable metadata).
- `TestImageFactory::cleanup()` is called in `tearDown()` of every test class that creates real files — no temp file leaks.
- Integration tests require only `ext-pdo` and `ext-pdo_sqlite`; no external database or Docker is needed.
- Unit tests for the Validator and Reader require `ext-gd`.

---

## [0.2.0] — 2026-04-16

### Added

- `ImageProfileCollectionDTO` — immutable, ordered, iterable collection of `ImageProfile` entities; implements `IteratorAggregate` and `JsonSerializable`; no raw arrays exposed.
- `ImageProfileCollectionDTO::filterActive()` — returns a new collection containing only active profiles (pure, non-mutating).
- `ArrayImageProfileProvider` — in-memory provider backed by explicit `ImageProfile` objects; intended for unit tests and config-file bootstrapping; O(1) look-up by code via internal map; exposes `listAll()` and `listActive()`.
- `PdoImageProfileProvider` — database-backed provider using a plain PDO connection; maps DB rows to `ImageProfile` entities via `AllowedExtensionCollection::fromDelimitedString()` and `AllowedMimeTypeCollection::fromDelimitedString()`; wraps all `PDOException` as `ImageProfileException`; exposes `findByCode()`, `listAll()`, and `listActive()` (SQL-level filter).
- `src/Infrastructure/Schema/image_profiles.sql` — v1 schema for the `image_profiles` table with inline column comments and an optional commented-out seed block.

### Architecture

- `findByCode` on both providers does NOT filter by `is_active` — the validator owns that responsibility.
- `listActive` on `PdoImageProfileProvider` applies the filter at SQL level (`WHERE is_active = 1`) for efficiency.
- `listActive` on `ArrayImageProfileProvider` delegates to `ImageProfileCollectionDTO::filterActive()`.
- PDO infrastructure is isolated in `Infrastructure\Persistence\PDO\` — the core has no PDO dependency.
- All collection returns use typed `ImageProfileCollectionDTO` — no raw arrays on the public API.

---

## [0.1.0] — 2026-04-16

### Added

- `ImageProfile` — immutable core entity representing a reusable image rule set identified by a stable `code`.
- `ImageFileInputDTO` — neutral input DTO; no dependency on any framework upload object.
- `ImageMetadataDTO` — carries extracted width, height, detected mime type, detected extension, and size in bytes.
- `ImageValidationResultDTO` — typed outcome of a validation call with `isValid`, `profileCode`, `metadata`, `errors`, and `warnings`.
- `ImageValidationErrorDTO` — typed validation error carrying a stable `ValidationErrorCodeEnum` code and a human-readable message.
- `ImageValidationErrorCollectionDTO` — immutable, ordered, iterable collection of `ImageValidationErrorDTO`; no raw arrays exposed on the public API.
- `ImageValidationWarningDTO` — typed validation warning carrying a code and message.
- `ImageValidationWarningCollectionDTO` — immutable, ordered, iterable collection of `ImageValidationWarningDTO`.
- `ImageProfileProviderInterface` — contract for profile loading by code; decouples the core from any storage backend.
- `ImageMetadataReaderInterface` — contract for metadata extraction; isolates the reading concern from validation logic.
- `ImageProfileValidatorInterface` — contract for the validation entry point.
- `ImageProfileException` — base exception for all package-level errors.
- `ImageProfileNotFoundException` — thrown when a profile code cannot be resolved.
- `InvalidImageInputException` — thrown for misuse of the validation API.
- `ImageMetadataReadException` — thrown when metadata extraction fails for infrastructure reasons.
- `NativeImageMetadataReader` — concrete reader using PHP's `getimagesize` / `finfo`; produces `ImageMetadataDTO`.
- `ImageProfileValidator` — concrete validator; short-circuits only on infrastructure failures; collects all rule violations before returning.
- `AllowedExtensionCollection` — immutable value object for a normalized set of allowed file extensions.
- `AllowedMimeTypeCollection` — immutable value object for a normalized set of allowed MIME types.
- `ValidationErrorCodeEnum` — stable enum of all validation error codes (`profile_not_found`, `profile_inactive`, `file_not_found`, `file_not_readable`, `metadata_unreadable`, `mime_not_allowed`, `extension_not_allowed`, `width_too_small`, `height_too_small`, `width_too_large`, `height_too_large`, `file_too_large`).

### Architecture

- No array is returned anywhere on the public API — collections are typed DTOs.
- No dependency on `$_FILES`, Slim, Laravel, Symfony, or any framework upload abstraction.
- PSR-4 autoload under `Maatify\ImageProfile\` from `src/`.
- `declare(strict_types=1)` on every file.

[Unreleased]: https://github.com/Maatify/image-profile/compare/v0.9.0...HEAD
[0.9.0]: https://github.com/Maatify/image-profile/compare/v0.8.0...v0.9.0
[0.8.0]: https://github.com/Maatify/image-profile/compare/v0.7.0...v0.8.0
[0.7.0]: https://github.com/Maatify/image-profile/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/Maatify/image-profile/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/Maatify/image-profile/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/Maatify/image-profile/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/Maatify/image-profile/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/Maatify/image-profile/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/Maatify/image-profile/releases/tag/v0.1.0
