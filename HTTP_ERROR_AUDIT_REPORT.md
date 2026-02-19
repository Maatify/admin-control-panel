# HTTP ERROR FLOW AUDIT REPORT

## 1️⃣ Current Error JSON Structures Found

1.  **Unified Standard (from `http.php`)**
    ```json
    {
        "success": false,
        "error": {
            "code": "STRING",
            "category": "STRING",
            "message": "STRING",
            "meta": [],
            "retryable": false
        }
    }
    ```
    - `app/Modules/AdminKernel/Bootstrap/http.php` (Global Error Handler)
    - `app/Modules/AdminKernel/Http/Response/JsonResponseFactory.php`

2.  **Simple Error Object**
    ```json
    {
        "error": "STRING"
    }
    ```
    - `app/Modules/AdminKernel/Http/Controllers/Api/Sessions/SessionBulkRevokeController.php` (401 Unauthorized)
    - `app/Modules/AdminKernel/Http/Middleware/SessionStateGuardMiddleware.php` (401 Unauthorized)
    - `app/Modules/AdminKernel/Http/Middleware/GuestGuardMiddleware.php` (403 Forbidden)
    - `app/Modules/AdminKernel/Http/Middleware/SessionGuardMiddleware.php` (401 Unauthorized)
    - `app/Modules/AdminKernel/Http/Middleware/ScopeGuardMiddleware.php` (401 Unauthorized)

3.  **Step-Up Challenge Object**
    ```json
    {
        "code": "STEP_UP_REQUIRED",
        "scope": "login"
    }
    ```
    - `app/Modules/AdminKernel/Http/Middleware/SessionStateGuardMiddleware.php` (403 Forbidden)

## 2️⃣ Boundary Analysis

- **Centralization:** The boundary is centralized in `app/Modules/AdminKernel/Bootstrap/http.php` via Slim's `addErrorMiddleware`.
- **Normalization:**
    - Most exceptions are caught and normalized to the Unified Standard.
    - `Throwable` is caught, preventing re-throws.
    - Specific handlers exist for `ValidationFailedException`, `Http*Exception`, `PermissionDeniedException`, etc.
- **Inconsistency:**
    - Middleware executes *before* the error handler for the application logic. Middleware that returns a `Response` directly (like `SessionGuardMiddleware`) bypasses the boundary logic entirely.
    - `SessionBulkRevokeController` manually constructs error responses, bypassing the boundary.
- **Rethrow:** None observed in `http.php`. The catch-all handler returns a formatted response.

## 3️⃣ Controller-Level Findings

**app/Modules/AdminKernel/Http/Controllers/Api/Sessions/SessionBulkRevokeController.php**
- **Violation:** Manually constructs JSON error response for 401 Unauthorized.
  ```php
  $response->getBody()->write(json_encode(['error' => 'Current session not found'], JSON_THROW_ON_ERROR));
  return $response->withStatus(401)...
  ```
- **Violation:** Manually constructs JSON error response for 400 Bad Request (catching `DomainException`).
  ```php
  $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR));
  return $response->withStatus(400)...
  ```

**app/Modules/AdminKernel/Http/Controllers/Api/I18n/Domains/I18nDomainCreateController.php**
- **Note:** Manually constructs success JSON.
  ```php
  $response->getBody()->write(json_encode(['id' => $id], JSON_THROW_ON_ERROR));
  ```
  (Consistent with "Unified Standard" not enforcing success shape, but noteworthy for manual JSON encoding).

**app/Modules/AdminKernel/Http/Controllers/Api/I18n/Languages/LanguagesCreateController.php**
- **Behavior:** Throws `\RuntimeException`.
  - Result: Caught by `http.php`, returns 500 Internal Server Error (System Error).
  - Production: Message masked as "Internal Server Error".
  - Development: Message "Invalid validated payload" exposed.

**General Findings:**
- Most controllers rely on throwing exceptions which are correctly handled by the boundary.
- `SessionRevokeController.php` correctly throws `Http*Exception` which are handled by the boundary.

## 4️⃣ JsonResponseFactory Findings

- **Structure:** `error()` produces the Unified Standard JSON.
- **Match:** Perfectly matches `http.php` error shape.
- **Usage:**
    - Used in `SessionBulkRevokeController` for *success* (`$this->json->data(...)`).
    - **Ignored** in `SessionBulkRevokeController` for *errors*.
- **Fields:** Includes `code`, `category`, `message`, `meta`, `retryable`.

## 5️⃣ Risk Assessment

- **Unstable Contract:** Clients must handle at least three distinct error formats depending on whether the error comes from Middleware (Auth), Controller (Logic), or the global handler (System/Validation).
- **Silent Divergence:** The presence of manual `json_encode` in `SessionBulkRevokeController` suggests a pattern that might be copied by developers, furthering inconsistency.
- **Bypass:** Auth Middleware completely bypasses the unified error formatting in `http.php`, leading to raw JSON responses for 401/403 errors.

## 6️⃣ Final Verdict

**Fragmented**

The application has a unified core error handler (`http.php`) and a matching factory (`JsonResponseFactory`), but the implementation in Middleware (Authentication/Authorization) and specific Controllers (`SessionBulkRevokeController`) actively violates this standard, resulting in multiple conflicting error response formats exposed to the client.
