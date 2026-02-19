# JS RESPONSE CONSUMPTION AUDIT

## 1️⃣ Central API Wrappers

### `public/assets/maatify/admin-kernel/js/api_handler.js`
- **Behavior:** The modern, unified wrapper for `fetch` operations.
- **Assumptions:**
    - Expects JSON response or empty 200 OK.
    - Parses error messages from multiple potential keys (`error`, `message`, `errors`).
    - Does NOT handle redirects or auth flows automatically (leaves that to caller or generic error display).
    - Logs detailed request/response info to console.

### `public/assets/maatify/admin-kernel/js/callback_handler.js`
- **Behavior:** Legacy/Specialized wrapper (possibly for older parts of the system).
- **Assumptions:**
    - Expects a specific nested JSON structure: `{ data: { response: <code_int>, action: <string>, more_info: <any> } }`.
    - Handles redirects for session expiration (419, 498) and permission denied (403).
    - Uses custom integer error codes (e.g., `405000` for AuthRedirect).
    - Hard-coded alert messages based on integer codes (1000-9000).

### `public/assets/maatify/admin-kernel/js/data_table.js`
- **Behavior:** Standalone component with its own `fetch` implementation.
- **Assumptions:**
    - Expects `data.data` (array) for rows.
    - Expects `data.pagination` object.
    - Throws generic error on `!response.ok` (doesn't parse structured error objects deeply).

## 2️⃣ Error Shape Assumptions

The frontend code assumes at least **5 distinct error shapes**:

1.  **Unified Error Object (Standard):**
    ```json
    {
        "error": {
            "message": "Validation failed",
            "fields": { "field_name": "Error message" }
        }
    }
    ```
    *(Handled by `ApiHandler.extractErrorMessage`)*

2.  **Simple Error String:**
    ```json
    { "error": "Error message string" }
    ```
    *(Handled by `ApiHandler` and manual fetch calls)*

3.  **Simple Message String:**
    ```json
    { "message": "Error message string" }
    ```
    *(Handled by `ApiHandler` fallback)*

4.  **Legacy "Callback" Format:**
    ```json
    {
        "data": {
            "response": 40001,
            "action": "SomeAction",
            "more_info": "Details"
        }
    }
    ```
    *(Handled ONLY by `callback_handler.js`)*

5.  **Step-Up Challenge:**
    ```json
    {
        "code": "STEP_UP_REQUIRED",
        "scope": "roles.create"
    }
    ```
    *(Handled manually in page scripts)*

## 3️⃣ Status-Code Driven Logic

### 401 Unauthorized
- **`callback_handler.js`:** Implicitly handled via custom codes or default error.
- **`api_handler.js`:** Treated as generic error; caller must handle.
- **Page Scripts:** Generally not explicitly handled; falls through to generic error alert.

### 403 Forbidden
- **`callback_handler.js`:** Hard redirect to `/home`.
- **Page Scripts (e.g., `roles-create-rename-toggle.js`):** Checks for **Step-Up Challenge**.
    - Logic: `if (response.status === 403 && data.code === 'STEP_UP_REQUIRED') { ... }`
    - Action: Redirects to `/2fa/verify`.

### 409 Conflict
- **Page Scripts:** Explicitly checked in `roles-create-rename-toggle.js`.
    - Logic: `if (response.status === 409) { ... }`
    - Action: Shows specific "already exists" error message from `data.message`.

### 419 / 498 (Session Expired)
- **`callback_handler.js`:** Hard redirect to `/index.php?page=login`.
- **Other files:** No explicit handling found.

### 422 Unprocessable Entity
- **`api_handler.js`:** Parsed via `extractErrorMessage` to show field-level errors.

## 4️⃣ Step-Up / Special Flows

**Step-Up Challenge Detection**
- **Mechanism:** Decentralized / Manual.
- **Implementation:** Found repeatedly in page-specific scripts (e.g., `roles-create-rename-toggle.js`, `role-details-permissions.js`, `admin_emails.js`).
- **Code Pattern:**
    ```javascript
    if (response.status === 403) {
        const data = await response.json();
        if (data && data.code === 'STEP_UP_REQUIRED') {
             // Redirect to /2fa/verify with scope and return_to
        }
    }
    ```
- **Risk:** If the backend changes the error format (e.g., nesting `code` inside `error` object), **all** these checks will fail.

**Auth Redirect (Custom)**
- **Mechanism:** `callback_handler.js` custom code `405000`.
- **Action:** Redirects based on `action` string (Login, AuthRegister, EmailConfirm, etc.).

## 5️⃣ Risk Assessment

1.  **High Risk: Step-Up Challenge Fragility**
    - The Step-Up logic relies on `data.code` being at the top level of the JSON response.
    - If the unified error format wraps everything in `{ "error": { ... } }`, the check `data.code === 'STEP_UP_REQUIRED'` will become `undefined === 'STEP_UP_REQUIRED'`, **breaking the 2FA flow**. Users will see a generic "Forbidden" error instead of being prompted for 2FA.

2.  **Medium Risk: Legacy `callback_handler.js`**
    - This handler expects a completely different JSON structure (`data.data.response`).
    - If endpoints used by this handler are migrated to the unified error format, the UI will likely break or show "Unknown Error" alerts.

3.  **Low Risk: `ApiHandler` Robustness**
    - `ApiHandler.js` is relatively robust because `extractErrorMessage` tries multiple properties (`error`, `message`, `error.message`). It will likely adapt to the unified format without changes, provided the unified format populates `message`.

4.  **Inconsistent 403 Handling**
    - `callback_handler.js` redirects 403 to `/home`.
    - Page scripts check 403 for Step-Up.
    - `ApiHandler` treats 403 as a generic error.
    - **Result:** Inconsistent user experience depending on which part of the app triggers the 403.
