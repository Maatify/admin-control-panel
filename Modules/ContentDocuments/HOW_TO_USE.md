# How to Use: Content Documents Module

This guide demonstrates practical usage of the module via its primary entry point, the `ContentDocumentsFacade`.

## Dependencies

Before using the Facade, you must wire it up with its required dependencies (e.g., in your DI container):

- `DocumentRepositoryInterface` (e.g., `PdoDocumentRepository`)
- `DocumentTypeRepositoryInterface` (e.g., `PdoDocumentTypeRepository`)
- `DocumentTranslationRepositoryInterface` (e.g., `PdoDocumentTranslationRepository`)
- `DocumentAcceptanceRepositoryInterface` (e.g., `PdoDocumentAcceptanceRepository`)
- `TransactionManagerInterface` (e.g., `PdoTransactionManager`)
- `ClockInterface` (Shared Common Contract)

## 1. Manage Document Types

Create a new logical document type (e.g., "terms").

```php
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;

// Create a new type
$typeId = $facade->createDocumentType(
    key: new DocumentTypeKey('terms'),
    requiresAcceptanceDefault: true,
    isSystem: true
);

// List all types
$types = $facade->listDocumentTypes();
```

## 2. Create a Version (Draft)

Create a specific version of the document (e.g., "v1.0"). Initially, it is a draft.

```php
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;

$documentId = $facade->createVersion(
    typeKey: new DocumentTypeKey('terms'),
    version: new DocumentVersion('v1.0'),
    requiresAcceptance: true
);
```

## 3. Add Translations (Draft Only)

Add content for a specific language. **Note:** This is only allowed while the document is NOT published, active, or archived.

```php
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;

$facade->saveTranslation(new DocumentTranslationDTO(
    documentId: $documentId,
    languageId: 1, // e.g., English
    title: 'Terms of Service',
    metaTitle: 'Terms',
    metaDescription: 'Our terms...',
    content: '<h1>Terms</h1><p>...</p>'
));
```

## 4. Publish (Lock Content)

Publishing marks the version as complete and **locks it against further edits**.

```php
$facade->publish(
    documentId: $documentId,
    publishedAt: new DateTimeImmutable('now')
);
```

## 5. Activate (Make Current)

Make this version the single "active" version for the 'terms' type. This automatically deactivates any previously active version of the same type.

```php
$facade->activate($documentId);
```

## 6. Retrieve Active Document

Fetch the currently active document for a type, optionally with a translation.

```php
$view = $facade->getActiveDocument(
    typeKey: new DocumentTypeKey('terms'),
    languageId: 1 // Optional: pass null to get metadata only
);

if ($view) {
    echo "Active Version: " . $view->version;
    if ($view->translation) {
        echo "Title: " . $view->translation->title;
    }
}
```

## 7. Record Acceptance

Record that a specific actor (user, customer, admin) has accepted the **currently active** version.

```php
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;

// Define the actor (agnostic to user table)
$actor = new ActorIdentity('user', 123);

// Record acceptance
$receipt = $facade->acceptActive(
    actor: $actor,
    typeKey: new DocumentTypeKey('terms'),
    ipAddress: '192.168.1.1',
    userAgent: 'Mozilla/5.0...'
);

echo "Accepted Version: " . $receipt->version;
echo "Accepted At: " . $receipt->acceptedAt->format('Y-m-d H:i:s');
```

## 8. Enforcement Check

Check if an actor has pending acceptances for any active document types that require it.

```php
$result = $facade->enforcementResult($actor);

if ($result->hasPendingAcceptance) {
    foreach ($result->pendingDocuments as $doc) {
        echo "Please accept: " . $doc->typeKey;
    }
}
```
