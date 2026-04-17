# Image Profile Module Reference

## Purpose
`maatify/image-profile` provides a clean schema-first module for managing reusable image profile records and validating image metadata against those profiles.

## Source of Truth
- Structural and layering style: `Modules/currency`
- Data model: `Modules/ImageProfile/schema.sql`
- Primary table: `maa_image_profiles`

## Structure
- `src/Command` — write commands (`CreateImageProfileCommand`, `UpdateImageProfileCommand`, `UpdateImageProfileStatusCommand`)
- `src/Contract` — repository/query/validation contracts
- `src/DTO` — read models and validation DTOs
- `src/Exception` — module exception family
- `src/Infrastructure/Repository` — PDO implementations
- `src/Service` — command/query/validation services
- `src/Bootstrap` — DI bindings (`ImageProfileBindings`)

## Public Entry Points
- `ImageProfileCommandService`
- `ImageProfileQueryService`
- `ImageProfileValidationServiceInterface` (bound to `ImageProfileValidationService`)

## In Scope
- CRUD over `maa_image_profiles`
- DTO-based reads/writes
- PDO query and command repositories
- Simple metadata validation against persisted profile rules

## Out of Scope
- Upload orchestration
- File storage (local/S3/Spaces)
- Base64 ingestion
- Image processing pipelines
- Variant generation engines
- Media abstraction layers
