# 05 - Immutability and Legal Audit

This module enforces strict immutability to ensure legal integrity. Once a document is published or active, its content **cannot** be modified.

## 1. The Immutability Rule

A document version transitions through these states:
1.  **Draft**: Editable (content, translations).
2.  **Published**: Locked. Content is immutable.
3.  **Active**: Locked. The currently enforceable version.
4.  **Archived**: Locked. Historical record.

### Why?
If a user accepts "Version 1.0" of the Terms of Service, that specific version must be preserved exactly as it was at the moment of acceptance. Modifying the text after acceptance would invalidate the legal agreement.

## 2. Enforcement

The enforcement happens in `DocumentTranslationService::save`.

```php
// Application/Service/DocumentTranslationService.php

public function save(DocumentTranslationDTO $translation): void
{
    $document = $this->documentRepository->findById($translation->documentId);

    // ... (check existence)

    // Enforce Immutability
    if (
        $document->isActive
        || $document->isPublished()
        || $document->isArchived()
    ) {
        throw new DocumentVersionImmutableException();
    }

    // ... (proceed with save)
}
```

This prevents any updates to the title, content, or metadata of a finalized document.

## 3. How to Update Content?

To change the text of a document (e.g., update Terms of Service):
1.  **Create a New Version**: Use `createVersion` to make a new draft (e.g., "v1.1").
2.  **Add Translations**: Use `saveTranslation` to add/edit content for the new draft.
3.  **Publish**: Lock the new version.
4.  **Activate**: Make the new version the active one.

Users will then be prompted to accept the new version (if `requiresAcceptance` is true).

## 4. Acceptance Audit Log

The `document_acceptance` table serves as an **insert-only** audit log.

- **Immutable**: Records are never updated or deleted by the application.
- **Redundant Versioning**: The table stores `version` (string) alongside `document_id`. This ensures that even if the `documents` table was theoretically compromised, the acceptance record explicitly states *which version string* was agreed to.
- **Actor-Agnostic**: By storing `actor_type` and `actor_id`, the log remains valid even if user tables are refactored or if different actor types (e.g., `admin`, `system`) need to accept documents.
