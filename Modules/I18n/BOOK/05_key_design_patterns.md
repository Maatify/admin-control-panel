# 05. Key Design Patterns

This chapter documents the mandatory key structure and best practices.

## 1. The Structure

All keys **must** adhere to the `Scope . Domain . KeyPart` pattern.

### Rationale

Flat keys (e.g., `error_message`, `btn_submit`) are prohibited because they lead to:
1.  **Collision:** `btn_submit` used ambiguously across features.
2.  **Pollution:** Loading unrelated keys into memory.
3.  **Context Loss:** Unclear usage context.

### The Standard: Structured Metadata

Enforcing `Scope` and `Domain` ensures:

1.  **Uniqueness:**
    *   `client.auth.btn.submit` -> "Log In"
    *   `client.checkout.btn.submit` -> "Pay Now"
2.  **Efficiency:** Loading `client.auth` only fetches relevant keys.
3.  **Context:** `admin.users.table.header.email` is self-describing.

## 2. Best Practices for Key Parts

The `KeyPart` is the final segment of the key. Use dot-notation for clarity.

### Recommended Hierarchy

1.  **Component / Feature:** (e.g., `form`, `modal`, `table`)
2.  **Element:** (e.g., `email`, `password`, `delete_btn`)
3.  **Property:** (e.g., `label`, `placeholder`, `tooltip`, `error`)

#### Example: Login Form (`client.auth`)

| Key Part | Good Practice | Bad Practice |
| :--- | :--- | :--- |
| **Email Label** | `form.email.label` | `email` |
| **Email Placeholder** | `form.email.placeholder` | `email_text` |
| **Password Error** | `form.password.error.required` | `pass_req_err` |
| **Submit Button** | `form.submit.label` | `btn_login` |

### Redundancy Rule

Do not repeat the Scope or Domain in the Key Part.

*   **PROHIBITED:** `client.auth.client_auth_login_title`
*   **REQUIRED:** `client.auth.login.title`

## 3. Handling Dynamic Content

The library stores **static strings**. It does not provide a template engine.

### Implementation
Store placeholders in the string, but handle replacement in application logic.

*   **Stored Value:** `Welcome back, :name!`
*   **Usage:**
    ```php
    $text = $readService->getValue(..., 'welcome');
    echo str_replace(':name', $user->name, $text);
    ```
