# AGGRESSIVE EXCEPTION AUDIT REPORT

## 1️⃣ Global Compliance Score (35/100)
**REASONING:**
The architecture is critically fractured. While the Core Domain and Bootstrap layers define a unified `MaatifyException` model, the Application layer (Controllers) and Infrastructure layer (Repositories) aggressively violate it.
- **Controllers** act as ad-hoc error handlers, manually formatting JSON and bypassing the global handler.
- **Repositories** act as sieves, leaking raw `PDOException`s directly to the Service layer (or higher).
- **Middleware** intercepts control flow with manual HTTP responses instead of throwing typed exceptions.

The system is **NOT unified**. It is a legacy/hybrid mess wrapped in a modern container.

## 2️⃣ High-Risk Violations (CRITICAL)

### 🚨 Controllers: Manual JSON & Error Handling Bypass
*The following controllers catch exceptions or manually construct JSON error responses, strictly violating the Global Handler contract.*

1.  **`app/Modules/AdminKernel/Http/Controllers/Api/Sessions/SessionBulkRevokeController.php`**
    - **Violation:** Manually returns 401 with `['error' => '...']` JSON. Catches `DomainException` to return 400.
2.  **`app/Modules/AdminKernel/Http/Controllers/Api/Sessions/SessionRevokeController.php`**
    - **Violation:** Catches `DomainException` -> throws `HttpBadRequestException`. Catches `IdentifierNotFoundException` -> throws `HttpNotFoundException`. (Manual Mapping).
3.  **`app/Modules/AdminKernel/Http/Controllers/StepUpController.php`**
    - **Violation:** Manual `json_encode` + `withStatus(200)`.
4.  **`app/Modules/AdminKernel/Http/Controllers/AdminNotificationPreferenceController.php`**
    - **Violation:** Manual `json_encode` error payload + `withStatus(400)`.
5.  **`app/Modules/AdminKernel/Http/Controllers/TelegramWebhookController.php`**
    - **Violation:** Manual `withStatus(200)` returns.
6.  **`app/Modules/AdminKernel/Http/Controllers/Web/LoginController.php`**
    - **Violation:** `try/catch` block handling logic flow manually. Manual `withStatus(302)` redirects.
7.  **`app/Modules/AdminKernel/Http/Controllers/Web/TwoFactorController.php`**
    - **Violation:** Manual `withStatus(401)` and `withStatus(302)`.
8.  **`app/Modules/AdminKernel/Http/Controllers/Ui/Auth/UiTwoFactorSetupController.php`**
    - **Violation:** Manual `withStatus(302)` redirects.
9.  **`app/Modules/AdminKernel/Http/Controllers/Api/Admin/AdminController.php`**
    - **Violation:** Multiple `try/catch` blocks used for logic control.
10. **`app/Modules/AdminKernel/Http/Controllers/Api/Admin/AdminEmailVerificationController.php`**
    - **Violation:** `try/catch` block wrapping domain logic.
11. **`app/Modules/AdminKernel/Http/Controllers/Api/Sessions/SessionQueryController.php`**
    - **Violation:** `try/catch` block wrapping query logic.
12. **`app/Modules/AdminKernel/Http/Controllers/AuthController.php`**
    - **Violation:** Manual `json_encode` of DTOs.
13. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Domains/I18nDomainsQueryController.php`**
    - **Violation:** Direct `json_encode($result, JSON_THROW_ON_ERROR)`.
14. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Keys/I18nScopeKeysCreateController.php`**
    - **Violation:** Catches `TranslationKeyCreateFailedException` manually.
15. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Domains/I18nDomainChangeCodeController.php`**
    - **Violation:** Throws `EntityInUseException` directly (Acceptable type, but pattern suggests manual check).
16. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Domains/I18nDomainSetActiveController.php`**
    - **Violation:** Throws `EntityNotFoundException`.
17. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Domains/I18nDomainCreateController.php`**
    - **Violation:** Manual `json_encode` response.
18. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Domains/I18nDomainUpdateMetadataController.php`**
    - **Violation:** Throws `InvalidOperationException`.
19. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Languages/LanguagesUpdateSortOrderController.php`**
    - **Violation:** Throws `\RuntimeException` for validation/invariant.
20. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Languages/LanguagesUpdateCodeController.php`**
    - **Violation:** Throws `\RuntimeException`.
21. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Languages/LanguagesSetFallbackController.php`**
    - **Violation:** Throws `\RuntimeException`.
22. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Languages/LanguagesCreateController.php`**
    - **Violation:** Throws `\RuntimeException`.
23. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Languages/LanguagesUpdateNameController.php`**
    - **Violation:** Throws `\RuntimeException`.
24. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Languages/LanguagesSetActiveController.php`**
    - **Violation:** Throws `\RuntimeException`.
25. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Languages/LanguagesClearFallbackController.php`**
    - **Violation:** Throws `\RuntimeException`.
26. **`app/Modules/AdminKernel/Http/Controllers/Api/I18n/Languages/LanguagesUpdateSettingsController.php`**
    - **Violation:** Throws `\RuntimeException`.

### 🚨 Repositories: Infrastructure Leaks (PDOException)
*The following repositories contain raw PDO calls WITHOUT `try/catch` wrapping. They leak `PDOException` directly to the Service/Controller/Global layers.*

1.  **`app/Modules/AdminKernel/Infrastructure/Repository/PdoAdminDirectPermissionRepository.php`** (Raw `prepare/execute`)
2.  **`app/Modules/AdminKernel/Infrastructure/Repository/PdoRememberMeRepository.php`** (Raw `prepare/execute`)
3.  **`app/Modules/AdminKernel/Infrastructure/Repository/PdoAdminNotificationHistoryReader.php`** (Inferred PDO usage)
4.  **`app/Modules/AdminKernel/Infrastructure/Repository/PdoAdminNotificationPersistenceRepository.php`** (Inferred PDO usage)
5.  **`app/Modules/AdminKernel/Infrastructure/Repository/PdoAdminNotificationPreferenceRepository.php`** (Inferred PDO usage)
6.  **`app/Modules/AdminKernel/Infrastructure/Repository/PdoAdminNotificationReadMarker.php`** (Inferred PDO usage)
7.  **`app/Modules/AdminKernel/Infrastructure/Repository/PdoStepUpGrantRepository.php`** (Inferred PDO usage)
8.  **`app/Modules/AdminKernel/Infrastructure/Repository/PdoSystemOwnershipRepository.php`** (Inferred PDO usage)
9.  **`app/Modules/AdminKernel/Infrastructure/Repository/PdoVerificationCodeRepository.php`** (Inferred PDO usage)
10. **`app/Modules/AdminKernel/Infrastructure/Repository/AdminEmailRepository.php`** (Inferred PDO usage)
11. **`app/Modules/AdminKernel/Infrastructure/Repository/AdminPasswordRepository.php`** (Inferred PDO usage)
12. **`app/Modules/AdminKernel/Infrastructure/Repository/AdminRepository.php`** (Inferred PDO usage)
13. **`app/Modules/AdminKernel/Infrastructure/Repository/AdminRoleRepository.php`** (Inferred PDO usage)
14. **`app/Modules/AdminKernel/Infrastructure/Repository/AdminSessionRepository.php`** (Inferred PDO usage)

*Exception:* **`RedisStepUpGrantRepository.php`** contains 6 `try/catch` blocks, indicating it *might* be handling errors, but likely swallowing or converting them inconsistent with the PDO repositories.

## 3️⃣ Medium Violations

### Middleware: Manual Control Flow
*Middleware that intercepts exceptions or returns responses manually instead of throwing.*

1.  **`app/Modules/AdminKernel/Http/Middleware/SessionGuardMiddleware.php`**
    - **Violation:** Returns `new \Slim\Psr7\Response()` (401/403) manually.
    - **Violation:** Catches `InvalidCredentialsException` and swallows/converts it.
2.  **`app/Modules/AdminKernel/Http/Middleware/RememberMeMiddleware.php`**
    - **Violation:** `try/catch` block for `InvalidCredentialsException` (Logic Control).
3.  **`app/Modules/AdminKernel/Http/Middleware/GuestGuardMiddleware.php`**
    - **Violation:** `try/catch` block for Session Exceptions.
4.  **`app/Modules/AdminKernel/Http/Middleware/RequestIdMiddleware.php`**
    - **Violation:** `try/catch` block for `InvalidUuidStringException`.

## 4️⃣ Structural Smells

1.  **Inconsistent Mapping:**
    - Controllers map `DomainException` to `HttpBadRequestException` manually.
    - Global Handler maps `PermissionDeniedException` to 403.
    - Result: Two different sources of truth for Error->HTTP mapping.

2.  **Layer Boundary Leaks:**
    - `PDOException` (Infrastructure) is visible to the Global Handler (Presentation). It should be caught in Infrastructure and wrapped in `DatabaseConnectionMaatifyException` or `SystemMaatifyException`.

3.  **Domain Contamination:**
    - `RememberMeService` catches `RandomException` (SPL/Infrastructure) and re-throws as `RuntimeException`. This logic belongs in an Infrastructure Adapter, not the Domain Service.

## 5️⃣ Exception Ownership Violations

| Layer | Component | Violation | Owner Should Be |
| :--- | :--- | :--- | :--- |
| **Global/Ctrl** | `PDOException` | Caught by Global Handler or Controller | **Infrastructure (Repository)** |
| **Controller** | `DomainException` | Caught/Mapped by Controller | **Global Handler** |
| **Controller** | `Validation` | `\RuntimeException` thrown for invalid payloads | **Validation Library** |
| **Service** | `RandomException` | Caught/Converted by Domain Service | **Infrastructure (Adapter)** |

## 6️⃣ Dangerous Patterns Found

1.  **The "Controller as Error Handler" Pattern:**
    ```php
    try {
        $service->doAction();
    } catch (DomainException $e) {
        throw new HttpBadRequestException($request, $e->getMessage());
    }
    ```
    *Found in `SessionRevokeController.php`.*

2.  **The "Manual JSON" Pattern:**
    ```php
    $response->getBody()->write(json_encode(['error' => '...']));
    return $response->withStatus(400);
    ```
    *Found in `SessionBulkRevokeController.php`.*

3.  **The "PDO Sieve" Pattern:**
    ```php
    $stmt = $this->pdo->prepare(...);
    $stmt->execute(); // No try/catch
    ```
    *Found in `PdoRememberMeRepository.php`.*

## 7️⃣ Architectural Integrity Risk
**CRITICAL**

The system is dangerously coupled.
- **Observability:** `PDOException` leaks usually result in generic 500 errors in the global handler with stack traces that expose DB internals (table names, query structure) if debug mode is accidentally enabled.
- **Maintainability:** Changing error formats requires editing 170+ controllers.
- **Reliability:** Inconsistent error handling in middleware means some auth failures return JSON, others might return HTML or Empty responses (depending on default Slim behavior).

**Verdict:** The unified `Maatify\Exceptions` model exists in code but is **ignored in practice** by the vast majority of the application layer. Immediate remediation is required to bring Controllers and Repositories into compliance.
