# Analysis Report: Step-Up / Session / Login Flow

## 1️⃣ TRACE MAP (Web Login Request)

**Request:** `POST /login`
1.  **Controller:** `App\Http\Controllers\Ui\UiLoginController::login`
2.  **Service:** `App\Domain\Service\AdminAuthenticationService::login`
    *   Verifies credentials.
    *   **Creates Session:** `AdminSessionRepository::createSession`.
    *   Returns `AdminLoginResultDTO`.
3.  **Controller:** Sets `auth_token` cookie.
4.  **Redirect:** Returns `302 Found` → `/dashboard`.

**Request:** `GET /dashboard` (Protected Route)
*Middleware Chain (Execution Order - LIFO):*
1.  `App\Http\Middleware\UiRedirectNormalizationMiddleware` (Enters)
2.  `App\Http\Middleware\ScopeGuardMiddleware` (Enters)
3.  `App\Http\Middleware\SessionStateGuardMiddleware` (Enters)
4.  `App\Http\Middleware\AdminContextMiddleware` (Enters)
5.  `App\Http\Middleware\SessionGuardMiddleware` (Enters)
6.  `App\Http\Middleware\RememberMeMiddleware` (Enters)

*Execution Flow:*
1.  `RememberMeMiddleware` passes.
2.  `SessionGuardMiddleware`: Validates token, sets `admin_id` attribute.
3.  `AdminContextMiddleware`: Creates `AdminContext` from `admin_id`.
4.  **`SessionStateGuardMiddleware`**:
    *   Calls `StepUpService::getSessionState`.
    *   Result: `SessionState::PENDING_STEP_UP` (Grant missing).
    *   **DECISION POINT REACHED.**

## 2️⃣ EXACT DECISION POINT

The decision to redirect is made in `App\Http\Middleware\SessionStateGuardMiddleware::process`.

**Code:**
```php
        $state = $this->stepUpService->getSessionState($adminId, $sessionId, $context);

        if ($state !== SessionState::ACTIVE) {
            if ($isApi) {
                // ... returns JSON ['code' => 'STEP_UP_REQUIRED'] ...
            }

            // Web: Redirect to 2FA Setup or Verify
            if (!$this->totpSecretStore->exists($adminId)) {
                 // Redirect to /2fa/setup
            }

            // Redirect to /2fa/verify
        }
```

**Value Used:** `$isApi` (bool) AND `$this->totpSecretStore->exists($adminId)` (bool).
**Origin:**
*   `$isApi`: `App\Http\Auth\AuthSurface::isApi($request)`
*   `exists()`: `App\Infrastructure\Repository\AdminTotpSecretRepository` (via Store)

## 3️⃣ EXISTS() ROOT CAUSE

**The observed behavior (Redirect to `/2fa/verify` when DB is clean) is caused by `$isApi` evaluating to `true`.**

If `AdminTotpSecretStore::exists($adminId)` checks a clean database, it returns `false`.
If it returns `false`, the code explicitly redirects to `/2fa/setup`.
Since the redirection is to `/2fa/verify`, the `exists()` check **must have been bypassed**.

The **only** way to bypass the `exists()` check is if `$isApi` is `true`.

**Execution Path causing the Bug:**
1.  `SessionStateGuardMiddleware` determines `$isApi = true`.
2.  It returns a `403 Forbidden` response with JSON body: `{"code": "STEP_UP_REQUIRED", ...}`.
3.  The response bubbles up to `App\Http\Middleware\UiRedirectNormalizationMiddleware`.
4.  `UiRedirectNormalizationMiddleware` detects `Content-Type: application/json` and `code === STEP_UP_REQUIRED`.
5.  **It forces a redirect to `/2fa/verify`.**

```php
// App\Http\Middleware\UiRedirectNormalizationMiddleware.php
                if (isset($data['code']) && $data['code'] === 'STEP_UP_REQUIRED') {
                    return $response
                        ->withStatus(302)
                        ->withHeader('Location', '/2fa/verify');
                }
```

**This explains why:**
1.  User sees `/2fa/verify`.
2.  DB is clean (`exists` is actually `false`, but ignored).
3.  Admin ID is correct.

## 4️⃣ CONTAINER / BINDINGS AUDIT

Confirmed in `App\Bootstrap\Container::create`:

| Interface | Concrete Class | File |
| :--- | :--- | :--- |
| `AdminTotpSecretRepositoryInterface` | `App\Infrastructure\Repository\AdminTotpSecretRepository` | `app/Infrastructure/Repository/AdminTotpSecretRepository.php` |
| `AdminTotpSecretStoreInterface` | `App\Infrastructure\Service\AdminTotpSecretStore` | `app/Infrastructure/Service/AdminTotpSecretStore.php` |
| `StepUpService` | `App\Domain\Service\StepUpService` | `app/Domain/Service/StepUpService.php` |
| `SessionStateGuardMiddleware` | `App\Http\Middleware\SessionStateGuardMiddleware` | `app/Http/Middleware/SessionStateGuardMiddleware.php` |

The bindings are standard and correct; there are no mocks or alternate implementations interfering.

## 6️⃣ FINAL ANSWER

The redirect is caused by:
`App\Http\Middleware\UiRedirectNormalizationMiddleware::process` intercepting a JSON `STEP_UP_REQUIRED` response from `SessionStateGuardMiddleware`.

The incorrect behavior originates from:
`App\Http\Middleware\SessionStateGuardMiddleware::process` detecting the request as API (`$isApi = true`), which causes it to return JSON instead of performing the `exists()` check for Web redirection.
