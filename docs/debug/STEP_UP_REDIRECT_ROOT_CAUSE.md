
# STEP_UP_REDIRECT_ROOT_CAUSE.md

## 1. Request Execution Timeline

1.  **GET /admins**
    - **Middleware Chain**:
        - `UiRedirectNormalizationMiddleware`
        - `SessionGuardMiddleware` (Session Found)
        - `AdminContextMiddleware`
        - `SessionStateGuardMiddleware` (State: ACTIVE)
        - **`ScopeGuardMiddleware`**:
            - Checks required scope for `/admins` (e.g., `security`).
            - `StepUpService` denies access (missing grant).
            - **Action**: Stops chain, returns `302 Redirect` to `/2fa/verify?scope=security&return_to=/admins`.

2.  **GET /2fa/verify?scope=security&return_to=/admins**
    - **Middleware Chain**: `SessionGuardMiddleware` -> `AdminContextMiddleware`.
    - **Controller**: `UiStepUpController::verify`
        - Extracts `scope` and `return_to` from query parameters.
        - Sets request attributes.
    - **Delegate**: `TwoFactorController::verify`
        - Resolves `scope` ("security") and `return_to` ("/admins") from request query params.
        - **View**: Renders `templates/pages/2fa_verify.twig` passing these values.
    - **Response**: 200 OK (HTML Form). **Critical**: The rendered form action is `/2fa/verify` (no query params) and lacks hidden inputs for `scope` and `return_to`.

3.  **POST /2fa/verify**
    - **Request Payload**: `code=123456` (Missing `scope`, Missing `return_to`).
    - **Middleware Chain**: Same as GET.
    - **Controller**: `UiStepUpController::doVerify`
        - Reads parsed body. Only `code` is present.
    - **Delegate**: `TwoFactorController::doVerify`
        - `resolveRequestedScope($request)`: Checks POST body (missing), then Query string (missing). **Defaults to `Scope::LOGIN`**.
        - `resolveReturnTo($request)`: Checks POST body (missing), then Query string (missing). **Defaults to `null`**.
        - `StepUpService::verifyTotp`: Verifies code for `Scope::LOGIN`. **Success**.
        - **Redirect Logic**: Checks `$returnTo`. Since it is `null`, defaults to `/dashboard`.
    - **Response**: `302 Redirect` to `/dashboard`.

4.  **GET /dashboard**
    - User lands on dashboard.

5.  **GET /admins (Loop)**
    - `ScopeGuardMiddleware` checks `security` scope.
    - User only has `login` scope (from step 3).
    - Redirects to `/2fa/verify` again.

## 2. scope / return_to Data Flow

| Step | Location | `scope` Value | `return_to` Value | Status |
| :--- | :--- | :--- | :--- | :--- |
| **GET /admins** | `ScopeGuardMiddleware` | `security` | `/admins` | **Origin** |
| **Redirect** | Location Header | `security` | `/admins` | **Preserved** |
| **GET /2fa/verify** | Query Params | `security` | `/admins` | **Preserved** |
| **Controller** | `TwoFactorController` | `security` | `/admins` | **Preserved** |
| **Template Render** | Twig Context | `security` | `/admins` | **Preserved** |
| **HTML Output** | `<form>` | **MISSING** | **MISSING** | **LOST** |
| **POST /2fa/verify** | Body / Query | **MISSING** | **MISSING** | **LOST** |
| **Controller** | `TwoFactorController` | `Scope::LOGIN` (Default) | `null` (Default) | **Incorrect** |
| **Final Redirect** | Location Header | N/A | `/dashboard` | **Incorrect** |

## 3. Final Response Ownership

-   **Component**: `App\Http\Controllers\Web\TwoFactorController::doVerify`
-   **Type**: Controller Logic
-   **Mechanism**: The controller explicitly checks for `return_to`. Finding it `null` (due to data loss), it executes the fallback: `return $response->withHeader('Location', '/dashboard')->withStatus(302);`.
-   **Middleware**: No middleware overwrites this response; it is the legitimate result of the controller execution based on the incomplete input it received.

## 4. Root Cause (Single Cause)

The redirect to /dashboard happens because the `templates/pages/2fa_verify.twig` template fails to include hidden input fields for `scope` and `return_to`, causing these parameters to be discarded when the form is submitted via POST.

## 5. Confirmed Non-Causes

-   **ScopeGuardMiddleware**: Correctly constructs the initial redirect with valid query parameters.
-   **UiStepUpController / TwoFactorController**: Correctly implement logic to read `scope` and `return_to` from both Query Params and Request Body, and correctly pass them to the view.
-   **UiRedirectNormalizationMiddleware**: Does not interfere with the parameters or the redirect in this flow.
-   **SessionStateGuardMiddleware**: Not involved in the scope verification loop (as the session is already ACTIVE).
-   **StepUpService**: Correctly validates the code it is given; it simply receives the wrong scope (`LOGIN`) to validate against.

## 6. Verification Notes

-   **No Code Written**: No files were modified in the `src` or `templates` directories.
-   **No Fixes Proposed**: This document strictly analyzes the root cause.
-   **Analysis Method**: Static analysis of middleware execution order, controller logic, and template structure, combined with trace reconstruction of the reported behavior.
