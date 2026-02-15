# How To Use: Maatify/ContentDocuments

This guide provides practical integration examples for the `Maatify/ContentDocuments` library.

---

## 1. Setup & Wiring

The library requires `PDO` for database access and `ClockInterface` for time management. You must instantiate the repositories and inject them into the services.

```php
<?php

use Maatify\ContentDocuments\Application\Service\DocumentAcceptanceService;
use Maatify\ContentDocuments\Application\Service\DocumentEnforcementService;
use Maatify\ContentDocuments\Application\Service\DocumentLifecycleService;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentAcceptanceRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTypeRepository;
use Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoTransactionManager;
use Maatify\SharedCommon\Infrastructure\SystemClock; // Assuming standard clock implementation

// 1. Dependencies
$pdo = new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
$clock = new SystemClock();

// 2. Repositories
$docRepo        = new PdoDocumentRepository($pdo);
$typeRepo       = new PdoDocumentTypeRepository($pdo);
$acceptanceRepo = new PdoDocumentAcceptanceRepository($pdo);
$txManager      = new PdoTransactionManager($pdo);

// 3. Services

// For Managing Documents (Back-office)
$lifecycleService = new DocumentLifecycleService(
    $docRepo,
    $typeRepo,
    $txManager
);

// For Checking Requirements (Front-end/API)
$enforcementService = new DocumentEnforcementService(
    $docRepo,
    $acceptanceRepo
);

// For Recording Acceptance (Front-end/API)
$acceptanceService = new DocumentAcceptanceService(
    $docRepo,
    $acceptanceRepo,
    $txManager,
    $clock
);
```

---

## 2. Managing Document Lifecycle

### Create a New Version
Create a draft version of a document type (e.g., `terms`).

```php
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;

$docId = $lifecycleService->createVersion(
    typeKey: new DocumentTypeKey('terms'),
    version: new DocumentVersion('v2.0'),
    requiresAcceptance: true
);
```

### Publish
Mark the document as ready (Published). It is not yet active/enforced.

```php
$lifecycleService->publish(
    documentId: $docId,
    publishedAt: new DateTimeImmutable()
);
```

### Activate
Set this version as the **Active** version for the 'terms' type. This automatically deactivates any previous 'terms' version.

```php
$lifecycleService->activate($docId);
```

---

## 3. Enforcement Logic

Check if a specific user needs to accept any documents.

```php
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;

// specific user
$actor = new ActorIdentity(actorType: 'user', actorId: 123);

// Check Requirement
$result = $enforcementService->enforcementResult($actor);

if ($result->requiresAcceptance) {
    foreach ($result->requiredDocuments as $req) {
        echo "You must accept: " . $req->typeKey . " version " . $req->version;
    }
}
```

---

## 4. Recording Acceptance

When a user accepts a document (e.g., clicks "I Agree").

```php
try {
    $acceptedAt = $acceptanceService->accept(
        actor: $actor,
        documentId: $docId, // The ID of the document version they accepted
        ipAddress: '192.168.1.1',
        userAgent: 'Mozilla/5.0...'
    );

    echo "Accepted at: " . $acceptedAt->format('Y-m-d H:i:s');

} catch (InvalidDocumentStateException $e) {
    // Document might have been deactivated or un-published
    echo "Error: " . $e->getMessage();
}
```
