# 02 - Domain Model

This chapter details the core entities, value objects, and data transfer objects (DTOs) used within the `ContentDocuments` module.

## 1. Entities

Entities are the core business objects with identity.

- **Document**: Represents a version of a document type.
  - `id`: Unique identifier.
  - `documentTypeId`: Foreign key to `DocumentType`.
  - `typeKey`: Immutable value object `DocumentTypeKey`.
  - `version`: Immutable value object `DocumentVersion`.
  - `isActive`: Boolean indicating current active status.
  - `requiresAcceptance`: Boolean indicating if acceptance is mandatory.
  - `publishedAt`: Timestamp when published (immutable thereafter).
  - `archivedAt`: Timestamp when archived (immutable).
  - `createdAt`, `updatedAt`: Timestamps.

- **DocumentTranslation**: Holds the localized content for a document version.
  - `id`: Unique identifier.
  - `documentId`: Foreign key to `Document`.
  - `languageId`: Foreign key to `Language`.
  - `title`, `metaTitle`, `metaDescription`, `content`: Localized text.

- **DocumentAcceptance**: Represents an immutable audit record of acceptance.
  - `id`: Unique identifier.
  - `actor`: `ActorIdentity` value object.
  - `documentId`: Foreign key to `Document`.
  - `version`: `DocumentVersion` accepted.
  - `acceptedAt`: Timestamp of acceptance.
  - `ipAddress`, `userAgent`: Audit metadata.

- **DocumentType**: Represents a logical document category.
  - `id`: Unique identifier.
  - `key`: `DocumentTypeKey` (e.g., `terms`).
  - `requiresAcceptanceDefault`: Default setting for new versions.
  - `isSystem`: Whether this type is system-defined.

## 2. Value Objects

Value objects encapsulate domain primitives and enforce validity.

- **DocumentTypeKey**:
  - Validates format: lowercase alphanumeric with hyphens (`/^[a-z0-9\-]+$/`).
  - Ensures non-empty and max length.
  - Represents stable keys like `privacy-policy`.

- **DocumentVersion**:
  - Validates format: string based, max length 32.
  - Represents semantic versions like `v1.0.0` or `2023-Q1`.

- **ActorIdentity**:
  - Composed of `actorType` (string) and `actorId` (int).
  - Validates `actorType` format (`/^[a-z0-9\-]+$/`).
  - Used to identify *who* accepted a document without coupling to specific tables.

## 3. Data Transfer Objects (DTOs)

DTOs are used to pass data across service boundaries.

- **DocumentDTO**: Full representation of a `Document` entity.
- **DocumentViewDTO**: Combined representation for UI/API responses, including `translation`.
- **DocumentTranslationDTO**: Represents translation data (title, content, etc.).
- **DocumentTypeDTO**: Represents a `DocumentType`.
- **DocumentVersionItemDTO**: Lightweight summary for listing versions.
- **DocumentVersionWithTranslationDTO**: `DocumentDTO` + `DocumentTranslationDTO`.
- **AcceptanceReceiptDTO**: Returns the result of an acceptance action.
- **EnforcementResultDTO**: Result of checking if an actor has pending acceptances.
- **AcceptanceDTO**: Generic acceptance data structure.
- **RequiredAcceptanceDTO**: Data structure for required acceptance checks.
- **AcceptDocumentDTO**: Command DTO for acceptance operations.
- **ActivateDocumentDTO**: Command DTO for activation operations.
- **CreateDocumentVersionDTO**: Command DTO for version creation.
- **PublishDocumentDTO**: Command DTO for publishing operations.

## 4. Exceptions

Specific exceptions are thrown for domain rule violations.

- **DocumentVersionImmutableException**: Thrown when attempting to modify a published/active/archived document.
- **DocumentNotFoundException**: Thrown when a requested document does not exist.
- **DocumentTypeNotFoundException**: Thrown when a document type key is invalid.
- **DocumentTranslationNotFoundException**: Thrown when a translation is missing.
- **DocumentTranslationAlreadyExistsException**: Thrown on duplicate translation creation.
- **DocumentAlreadyAcceptedException**: Thrown if an actor tries to accept the same version twice.
- **DocumentActivationConflictException**: Thrown if multiple documents try to be active simultaneously (race condition).
- **InvalidDocumentStateException**: Thrown for invalid lifecycle transitions (e.g., activating an unpublished doc).
- **InvalidActorIdentityException**: Thrown for malformed actor identity strings.
- **InvalidDocumentTypeKeyException**: Thrown for invalid type key format.
- **InvalidDocumentVersionException**: Thrown for invalid version string format.
