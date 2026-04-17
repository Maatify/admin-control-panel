# Roadmap — `maatify/image-profile`
## 1. Purpose

`maatify/image-profile` is a reusable image-profile and validation module designed to start as a **project module** and later become a **standalone library** without architectural rewrite.

The module is responsible for:

- defining reusable image profiles
- validating uploaded images against profile rules
- exposing a clean typed validation result
- keeping image rules configurable and extendable
- remaining framework-agnostic at the core

The module is **not** responsible in v1 for:

- storage engines
- CDN delivery
- queue processing
- image editing UI
- framework request lifecycle
- direct dependency on `$_FILES`
- hard coupling to Slim, Laravel, Symfony, or native PHP upload objects

Those concerns must remain outside the core package boundary.

---

## 2. Core Architectural Principle

The package must remain split into **three explicit responsibilities**:

### A. Profile Definition
Define a reusable image rule set such as:

- `category_app_image`
- `product_thumbnail`
- `homepage_banner`
- `gallery_item`

A profile describes what is allowed and what is rejected.

### B. Image Inspection
Read image metadata from a neutral input object:

- width
- height
- mime type
- extension
- file size

This responsibility must be isolated so metadata extraction can evolve independently.

### C. Validation
Validate an inspected image against a selected image profile and return a typed result.

This separation is mandatory because it gives:

- testability
- portability
- easier migration to a library
- controlled extension points
- predictable behavior across projects

The package must **not** mix all concerns into one giant upload service.

---

## 3. Execution Direction for v1

Version 1 should remain **validation-focused**.

That means:

- the core supports profile definition and rule validation
- image optimization is not part of the first stable scope
- resizing, recompression, thumbnail generation, and format conversion are postponed unless a real project need forces them

This roadmap is intentionally designed to avoid early complexity that could block delivery.

---

## 4. Source of Truth

The package contract must treat the internal image profile model as the source of truth.

This means:

- validation rules are defined by profile data, not scattered in controllers
- consuming projects only pass a profile code and an image input DTO
- profile meaning must remain stable across module and library forms
- profile rule changes must be reflected through the same profile provider boundary

The package should not depend on UI pages or business-specific entities as the rule source of truth.

---

## 5. Naming Direction

Because the package is intended to become reusable, naming must reflect the real business abstraction.

Recommended root package name:

```text
maatify/image-profile
```

Recommended internal naming:

- `ImageProfile`
- `ImageProfileProviderInterface`
- `ImageMetadataReaderInterface`
- `ImageProfileValidatorInterface`
- `ImageValidationResultDTO`
- `ImageFileInputDTO`

Avoid weak names such as:

- `SettingsImage`
- `UploadHelper`
- `ImageTools`
- `ValidationService` without scope

The package is about **profiles and validation policies**, not just file upload settings.

---

## 6. Recommended Namespace Strategy

### Root Namespace

```php
Maatify\ImageProfile
```

### Generic Core Namespaces

```php
Maatify\ImageProfile\Config
Maatify\ImageProfile\Contract
Maatify\ImageProfile\DTO
Maatify\ImageProfile\Entity
Maatify\ImageProfile\Enum
Maatify\ImageProfile\Exception
Maatify\ImageProfile\Provider
Maatify\ImageProfile\Reader
Maatify\ImageProfile\Validator
Maatify\ImageProfile\ValueObject
```

### Infrastructure Namespaces

```php
Maatify\ImageProfile\Infrastructure\Persistence\PDO
Maatify\ImageProfile\Infrastructure\Provider
Maatify\ImageProfile\Infrastructure\Reader
```

### Optional Project-Side Admin Layer

```php
App\Modules\ImageProfile\Http\Controllers
App\Modules\ImageProfile\Templates
App\Modules\ImageProfile\Application
```

This keeps the reusable core clean while allowing the project to host admin CRUD/UI separately.

---

## 7. Final Public Usage Goal

### Phase 1 usage

```php
$provider = new PdoImageProfileProvider($pdo);
$reader = new NativeImageMetadataReader();
$validator = new ImageProfileValidator($provider, $reader);

$input = new ImageFileInputDTO(
    originalName: 'banner.webp',
    temporaryPath: '/tmp/php123',
    clientMimeType: 'image/webp',
    sizeBytes: 524288,
);

$result = $validator->validateByCode('homepage_banner', $input);

if (!$result->isValid()) {
    // handle validation errors
}
```

### Later optional convenience layer

```php
$result = $imageValidationService->validate('homepage_banner', $input);
```

This convenience layer must remain a wrapper over:

- provider
- metadata reader
- validator

It must not hide the architectural boundaries.

---

## 8. Target Package Structure

```text
src/
  Contract/
    ImageProfileProviderInterface.php
    ImageMetadataReaderInterface.php
    ImageProfileValidatorInterface.php

  DTO/
    ImageFileInputDTO.php
    ImageMetadataDTO.php
    ImageValidationResultDTO.php
    ImageValidationErrorDTO.php

  Entity/
    ImageProfile.php

  Enum/
    ValidationErrorCodeEnum.php

  Exception/
    ImageProfileException.php
    ImageProfileNotFoundException.php
    InvalidImageInputException.php
    ImageMetadataReadException.php

  Provider/
    ArrayImageProfileProvider.php

  Reader/
    NativeImageMetadataReader.php

  Validator/
    ImageProfileValidator.php

  ValueObject/
    AllowedExtensionCollection.php
    AllowedMimeTypeCollection.php

  Infrastructure/
    Persistence/
      PDO/
        PdoImageProfileProvider.php
```

Later:

```text
src/
  Processor/
  Optimizer/
  Variant/
```

---

## 9. Database Schema Direction

The schema must be designed from day one with **library extraction in mind**.

### Core schema table

```sql
CREATE TABLE `image_profiles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(64) NOT NULL,
  `display_name` VARCHAR(128) DEFAULT NULL,
  `min_width` INT UNSIGNED DEFAULT NULL,
  `min_height` INT UNSIGNED DEFAULT NULL,
  `max_width` INT UNSIGNED DEFAULT NULL,
  `max_height` INT UNSIGNED DEFAULT NULL,
  `max_size_bytes` BIGINT UNSIGNED DEFAULT NULL,
  `allowed_extensions` VARCHAR(255) DEFAULT NULL,
  `allowed_mime_types` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_image_profiles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Schema principles

- `id` is the internal database identity
- `code` is the stable business identifier
- `display_name` is for admin readability
- `min_*` and `max_*` support real-world validation
- `max_size_bytes` is explicit and unambiguous
- `allowed_extensions` and `allowed_mime_types` are stored simply in v1 for delivery speed
- `is_active` allows soft control without deleting profile rows

### Important note

The database is **not** the only allowed source of profiles.

The package must support:

- PDO-backed provider
- array/config-backed provider

This requirement is mandatory for future library extraction.

---

## 10. Phase-by-Phase Roadmap

# Phase 1 — Foundation

## Goal

Build a small, framework-agnostic core that models image profiles and validates images without being tied to any specific project or HTTP framework.

## Deliverables

### 1. Composer package foundation

Create:

- package name: `maatify/image-profile`
- PSR-4 autoload
- strict types everywhere
- README
- CHANGELOG
- LICENSE
- VERSION policy

### 2. Core entity

Create:

- `ImageProfile`

Suggested fields:

- `id` optional
- `code`
- `displayName`
- `minWidth`
- `minHeight`
- `maxWidth`
- `maxHeight`
- `maxSizeBytes`
- `allowedExtensions`
- `allowedMimeTypes`
- `isActive`
- `notes`

### 3. Core input DTO

Create:

- `ImageFileInputDTO`

Suggested fields:

- `originalName`
- `temporaryPath` or `realPath`
- `clientMimeType` optional
- `sizeBytes`

This DTO must not depend on framework-specific upload classes.

### 4. Metadata DTO

Create:

- `ImageMetadataDTO`

Fields:

- `width`
- `height`
- `detectedMimeType`
- `detectedExtension`
- `sizeBytes`

### 5. Validation result DTO

Create:

- `ImageValidationResultDTO`

Suggested fields:

- `isValid`
- `profileCode`
- `metadata`
- `errors`
- `warnings`

### 6. Contracts

Create:

- `ImageProfileProviderInterface`
- `ImageMetadataReaderInterface`
- `ImageProfileValidatorInterface`

### 7. Base exceptions

Create:

- `ImageProfileException`
- `ImageProfileNotFoundException`
- `InvalidImageInputException`
- `ImageMetadataReadException`

### 8. Native metadata reader

Create:

- `NativeImageMetadataReader`

Responsibility:

- read dimensions
- detect mime
- infer extension
- produce `ImageMetadataDTO`

### 9. Core validator

Create:

- `ImageProfileValidator`

Responsibility:

- load profile by code
- inspect input through metadata reader
- validate all rules
- return a typed result
- never silently ignore failed rules

### 10. Operational boundary rule

The package must validate only through neutral DTOs and contracts.

It must not directly parse:

- `$_FILES`
- `Slim UploadedFile`
- `Symfony UploadedFile`
- `Laravel UploadedFile`

Adapters may exist outside the core.

---

# Phase 2 — Schema and Persistence Support

## Goal

Provide stable database-backed profile loading without forcing database dependence into the core.

## Deliverables

### 1. Database schema

Adopt the following v1 schema:

```sql
CREATE TABLE `image_profiles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(64) NOT NULL,
  `display_name` VARCHAR(128) DEFAULT NULL,
  `min_width` INT UNSIGNED DEFAULT NULL,
  `min_height` INT UNSIGNED DEFAULT NULL,
  `max_width` INT UNSIGNED DEFAULT NULL,
  `max_height` INT UNSIGNED DEFAULT NULL,
  `max_size_bytes` BIGINT UNSIGNED DEFAULT NULL,
  `allowed_extensions` VARCHAR(255) DEFAULT NULL,
  `allowed_mime_types` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_image_profiles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. PDO provider

Create:

- `PdoImageProfileProvider`

Responsibilities:

- find profile by code
- optionally list active profiles
- map database row to `ImageProfile`

### 3. Array provider

Create:

- `ArrayImageProfileProvider`

Responsibilities:

- support testing
- support config-based bootstrapping
- allow library usage without database

### 4. Mapping helpers

Create value objects or internal parsers for:

- extensions parsing
- mime parsing

These must normalize values consistently.

### 5. Unit tests

Test:

- DB row mapping
- empty/null allowed fields
- inactive profile handling
- profile not found handling

---

# Phase 3 — Validation Rules Hardening

## Goal

Make validation behavior stable, explicit, and safe for production use.

## Deliverables

### 1. Validation policy

The validator must check:

- profile exists
- profile is active
- file path exists
- file is readable
- metadata can be extracted
- mime type is allowed if defined
- extension is allowed if defined
- min width
- min height
- max width
- max height
- max file size

### 2. Error model

Create a stable validation error structure.

Suggested error codes:

- `profile_not_found`
- `profile_inactive`
- `file_not_found`
- `file_not_readable`
- `metadata_unreadable`
- `mime_not_allowed`
- `extension_not_allowed`
- `width_too_small`
- `height_too_small`
- `width_too_large`
- `height_too_large`
- `file_too_large`

### 3. Validation result discipline

The validator must return a result object for business validation failures.

It should throw exceptions only for:

- infrastructure errors
- impossible states
- misuse of the API

### 4. Contract fixtures

Create test fixtures for:

- valid jpeg
- valid png
- valid webp
- too small image
- too large image
- invalid extension
- invalid mime type
- file size overflow

### 5. Stability rule

The meaning of each error code must remain stable once released.

This is important for future admin UI and API clients.

---

# Phase 4 — Project Module Integration

## Goal

Integrate the reusable core into the project as a self-contained admin-manageable module.

## Deliverables

### 1. Admin CRUD scope

Add project-side support for:

- create profile
- update profile
- enable profile
- disable profile
- list profiles
- view profile details

### 2. Separation rule

Admin pages, API endpoints, Twig, JS, and controller code must stay **outside** the reusable package.

They belong to the host project module, not the library core.

### 3. Application services

Create project-side services such as:

- `CreateImageProfileService`
- `UpdateImageProfileService`
- `ToggleImageProfileService`

These may rely on project repository abstractions if needed.

### 4. API contract

Project-side APIs should exchange clean payloads based on the profile model without leaking persistence internals.

### 5. First real use case integration

Choose at least one real production use case, for example:

- category app image
- product thumbnail
- homepage banner

Then validate real uploaded files through the module.

This phase is critical because it proves the abstraction works in practice.

---

# Phase 5 — Framework Adapters and Input Normalization

## Goal

Make it easy for real applications to convert framework upload objects into the core neutral DTO.

## Deliverables

### 1. Adapter boundary

Create adapters outside the core package for converting framework-specific file objects into `ImageFileInputDTO`.

Examples:

- Slim adapter
- Native PHP adapter

### 2. Input normalizer

Create a small helper layer if needed to normalize:

- file name
- temp path
- client mime type
- file size

### 3. Keep adapter scope limited

Adapters must not contain validation logic.

They only translate external input into core DTOs.

### 4. Documentation

Document clearly how consuming projects should bridge their upload object to the core.

---

# Phase 6 — Package Hardening

## Goal

Make the package stable and safe for extraction as a standalone reusable library.

## Deliverables

### 1. Full README

Cover:

- install
- profile concept
- schema
- array provider usage
- PDO provider usage
- validation flow
- error handling
- extension strategy
- framework adapter note

### 2. Semantic versioning discipline

Use:

- Major → breaking contract changes
- Minor → new profile capabilities or non-breaking features
- Patch → bug fixes and internal improvements

### 3. Static analysis

Add:

- PHPStan max
- strict typing
- PSR-12 formatting
- PHPUnit coverage for core flows

### 4. Contract tests

Have tests that ensure:

- profile codes are resolved consistently
- validation result error codes stay stable
- metadata reading contract remains predictable

### 5. Project extraction checklist

Before extraction, verify:

- no project-specific namespaces in core
- no framework-specific upload types in core
- no Twig/JS/controllers inside core package
- no direct database assumptions beyond provider contract
- no hidden dependency on project globals

---

# Phase 7 — First Stable Release Boundary

## Goal

Define an honest and stable v1.0.0 scope.

## Deliverables

### v1.0.0 should include

#### Core
- image profile entity
- input DTO
- metadata DTO
- validation result DTO
- provider contracts
- metadata reader contract
- validator contract
- validator implementation
- stable exceptions

#### Providers
- array provider
- PDO provider

#### Validation
- extension validation
- mime validation
- dimensions validation
- size validation
- stable error codes

#### Docs and tests
- README
- examples
- schema documentation
- PHPUnit
- PHPStan

### v1.0.0 should not include

- image optimizer
- format conversion
- thumbnail generator
- storage engines
- CDN delivery abstraction
- queue jobs
- admin UI package
- framework-locked internals

That keeps the first stable release focused and credible.

---

# Phase 8 — Optional Processing Layer

## Goal

Add image processing only after validation is proven stable in real projects.

## Deliverables

Potential future components:

- `ImageProcessorInterface`
- `ResizeOptionsDTO`
- `OptimizationOptionsDTO`
- `GeneratedVariantDTO`

Potential features:

- resize
- recompress
- convert to webp
- strip metadata
- generate thumbnail variants

### Critical rule

Processing must remain separate from validation.

Do not merge validation and processing into one class.

---

# Phase 9 — Advanced Profile Model

## Goal

Expand profile capability only when real use cases justify it.

## Possible future additions

- exact aspect ratio
- minimum aspect ratio
- maximum aspect ratio
- required transparency support
- auto-convert target format
- quality settings
- multiple named variants per profile

These belong to later versions only if real project demand exists.

---

## 11. Suggested Public API Design

### Core Explicit Flow

```php
$provider = new PdoImageProfileProvider($pdo);
$reader = new NativeImageMetadataReader();
$validator = new ImageProfileValidator($provider, $reader);

$input = new ImageFileInputDTO(
    originalName: 'thumb.png',
    temporaryPath: '/tmp/php123',
    clientMimeType: 'image/png',
    sizeBytes: 150000,
);

$result = $validator->validateByCode('product_thumbnail', $input);
```

### Optional Convenience Flow

```php
$imageValidationService = new ImageValidationService($validator);
$result = $imageValidationService->validate('product_thumbnail', $input);
```

The explicit path remains the preferred architectural reference.

---

## 12. Non-Goals for v1

The package must **not** include:

- storage backends
- direct upload handlers
- CDN logic
- image gallery management
- admin UI rendering
- framework HTTP controllers
- queue-based optimization
- watermarking
- AI image processing
- business-specific category/product logic

---

## 13. Design Rules

### Rule 1
The package must remain **profile and validation focused**.

### Rule 2
The core must remain **framework-agnostic**.

### Rule 3
The database must be **optional**, not mandatory.

### Rule 4
The validator must work with a **neutral input DTO**, not framework upload objects.

### Rule 5
Validation and processing must stay **separate**.

### Rule 6
Stable business identifiers must use `code`, not fragile display labels.

### Rule 7
The admin/project layer must remain outside the reusable package boundary.

### Rule 8
No hidden file-system side effects.

### Rule 9
Validation errors must be typed and stable.

### Rule 10
Any future optimization support must be added as a separate capability, not forced into v1.

---

## 14. Recommended Initial Milestone

The best first stable milestone is:

**v1.0.0 as a stable image-profile validation package**

### Core
- profile entity
- provider interfaces
- metadata reader
- validator
- result DTOs
- exceptions

### Persistence
- PDO provider
- array provider
- schema

### Validation
- dimensions
- size
- mime types
- extensions

### Docs and tests
- README
- API usage examples
- PHPUnit
- PHPStan

This gives a clean and honest first stable boundary.

---

## 15. Recommended Version Plan

### v0.1.0
- package skeleton
- entity
- DTOs
- contracts
- validator interface
- metadata reader interface

### v0.2.0
- native metadata reader
- array provider
- core validator
- base exceptions

### v0.3.0
- PDO provider
- schema documentation
- validation hardening
- test fixtures

### v0.4.0
- project module integration
- first real use case validation
- admin CRUD in host project

### v1.0.0
- stable validation package release
- extraction-ready architecture
- contract-locked error model

### v1.1.x+
- convenience service wrappers
- more adapters
- better observability hooks

### v2.x
- processing layer
- variants
- advanced profile rules

---

## 16. Final Recommendation

Build `maatify/image-profile` as a **reusable image profile and validation package** with:

- one stable profile model
- one neutral image input DTO
- one metadata reader contract
- one validator contract
- multiple profile providers
- project-side admin integration outside the core

Start with **validation** as the first implemented responsibility, while keeping the architecture ready for future expansion into processing only when the validation model is stable and proven.

This gives:

- correct package scope
- honest naming
- future extensibility
- strong separation of concerns
- minimal rewrite risk
- a realistic path from module to standalone library
