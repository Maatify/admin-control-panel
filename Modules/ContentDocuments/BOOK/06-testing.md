# 06 - Testing

The Content Documents module is tested with a combination of Unit and Integration tests to ensure correctness of business logic and database interactions.

## 1. Test Locations

- **Unit Tests**: `tests/Modules/ContentDocuments/Unit`
- **Integration Tests**: `tests/Modules/ContentDocuments/Integration`

## 2. Unit Tests

These tests cover the core logic in isolation, mocking dependencies.

### Domain / Value Objects
- `DocumentTypeKeyTest`: Validates key format (lowercase, alphanumeric, etc.).
- `DocumentVersionTest`: Validates version string format.
- `ActorIdentityTest`: Validates actor type/id constraints.

### Application Services
- `DocumentTranslationServiceTest`:
  - **Immutability**: Verifies that `DocumentVersionImmutableException` is thrown when trying to save a translation for a published/active/archived document.
  - Verifies correct creation vs update logic.
- `DocumentLifecycleServiceTest`: Verifies state transitions (e.g., cannot activate unpublished document).
- `DocumentEnforcementServiceTest`: Verifies logic for checking pending acceptances.
- `DocumentTypeServiceTest`: Verifies CRUD operations for document types.
- `ContentDocumentsFacadeTypesTest`: Verifies Facade methods for types.

## 3. Integration Tests

These tests use a real database (via Docker/MySQL) to verify persistence and complex queries.

### Persistence (Repositories)
- `PdoDocumentRepositoryTest`: Verifies CRUD, `findActiveByType`, uniqueness constraints.
- `PdoDocumentTranslationRepositoryTest`: Verifies saving translations, handling duplicates.
- `PdoDocumentTypeRepositoryTest`: Verifies type creation/retrieval.
- `PdoDocumentAcceptanceRepositoryTest`: Verifies saving acceptance records.

### Service Integration
- `DocumentLifecycleServiceIntegrationTest`: End-to-end flow of creating a version, publishing, and activating it.
- `DocumentEnforcementServiceIntegrationTest`: Verifies enforcement checks against real data.
- `DocumentQueryServiceVersionsWithLanguageIntegrationTest`: specifically tests the N+1 optimized `getVersionsWithLanguage` method.

## 4. Running Tests

To run the tests for this module, execute:

```bash
vendor/bin/phpunit tests/Modules/ContentDocuments
```
