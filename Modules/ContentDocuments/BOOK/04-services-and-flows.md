# 04 - Services and Flows

This chapter details the primary services in the `Application` layer and the workflows they orchestrate.

## 1. DocumentLifecycleService

Manages the state transitions of a document version.

### Create Version
- **Input**: `DocumentTypeKey`, `DocumentVersion`, `requiresAcceptance` (bool).
- **Process**:
  1.  Validates `DocumentType` exists.
  2.  Creates a new `Document` entity in `draft` state (`publishedAt` = null, `isActive` = false).
- **Output**: `int` (new Document ID).

### Publish
- **Input**: `documentId`, `publishedAt` (DateTimeImmutable).
- **Process**:
  1.  Validates document exists and is not archived.
  2.  If already published, no-op.
  3.  Sets `publishedAt` timestamp.
- **Output**: `void`.

### Activate
- **Input**: `documentId`.
- **Process**:
  1.  Validates document is **Published**.
  2.  Starts Transaction.
  3.  Deactivates **all other** documents of the same `DocumentType` (`deactivateAllByTypeId`).
  4.  Sets `isActive = true` for the target document.
  5.  Commits Transaction.
- **Output**: `void`.

### Archive
- **Input**: `documentId`, `archivedAt`.
- **Process**:
  1.  Validates document exists.
  2.  If active, deactivates it first.
  3.  Sets `archivedAt`.
- **Output**: `void`.

## 2. DocumentTranslationService

Manages localized content for document versions.

### Save (Create or Update)
- **Input**: `DocumentTranslationDTO`.
- **Process**:
  1.  **Immutability Check**: Throws `DocumentVersionImmutableException` if the target document is Published, Active, or Archived.
  2.  Checks if translation exists for (DocumentID + LanguageID).
  3.  **Update**: If exists, updates title/content.
  4.  **Create**: If not exists, inserts new record.
- **Output**: `void`.

## 3. DocumentQueryService

Handles read operations, returning DTOs.

### getActiveDocument / getActiveTranslation
- Fetches the single active document for a type key.
- Optionally joins translation for a specific language ID.
- Returns `DocumentViewDTO` or `DocumentTranslationDTO`.

### listVersions
- Returns a lightweight list (`DocumentVersionItemDTO`) of all versions for a type.

### getVersionsWithLanguage
- **N+1 Prevention**:
  1.  Fetches all documents for a type.
  2.  Collects IDs.
  3.  Fetches all translations for those IDs and the target language in one query (`findByDocumentIdsAndLanguage`).
  4.  Maps to `DocumentVersionWithTranslationDTO`.

## 4. DocumentAcceptanceService

Records legal acceptance of documents.

### acceptActive
- **Input**: `ActorIdentity`, `DocumentTypeKey`, metadata (IP, UserAgent).
- **Process**:
  1.  Finds the currently **Active** document for the type.
  2.  Validates it is published and requires acceptance.
  3.  Inserts `DocumentAcceptance` record.
  4.  **Idempotency**: If a record already exists for (Actor + Document + Version), returns the existing timestamp (no error).
- **Output**: `DateTimeImmutable` (acceptedAt timestamp).
