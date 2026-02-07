# 05. Key Design Patterns

This chapter explains the strict key structure enforced by the library and best practices for creating manageable translation keys.

## 1. The Structure

All keys MUST follow the `Scope . Domain . KeyPart` pattern.

### Why not flat keys?

In many legacy systems, keys like `error_invalid_email` or `btn_submit` are common. However, as the application grows:
1.  **Collision:** `btn_submit` might be used on a login form (say "Log In") and a payment form (say "Pay Now").
2.  **Pollution:** Loading all keys into memory wastes resources.
3.  **Context Loss:** It's unclear where `error_system_failure` is actually used.

### The Solution: Structured Metadata

By enforcing `Scope` and `Domain`, we solve these problems:

1.  **No Collisions:**
    *   `client.auth.btn.submit` -> "Log In"
    *   `client.checkout.btn.submit` -> "Pay Now"
2.  **Efficient Loading:**
    *   When the user visits `/login`, we only load `client.auth.*`.
    *   We do NOT load `admin.*` or `system.*`.
3.  **Clear Context:**
    *   `admin.users.table.header.email` -> Clearly for the Admin User Management table.

## 2. Best Practices for Key Parts

The `KeyPart` is the final segment of the key (after scope and domain). While it is a single string field in the database, you should use dot-notation for clarity.

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

### Avoiding Redundancy

Do not repeat the Scope or Domain in the Key Part.

*   **BAD:** `client.auth.client_auth_login_title`
*   **GOOD:** `client.auth.login.title`

## 3. Handling Dynamic Content

The library stores **static strings**. It does not have a built-in template engine (like `sprintf` or `{{name}}`).

### Recommendation
Store placeholders in the string, but handle replacement in your application logic or a higher-level wrapper.

*   **Stored Value:** `Welcome back, :name!`
*   **Usage:**
    ```php
    $text = $readService->getValue(..., 'welcome');
    echo str_replace(':name', $user->name, $text);
    ```

This keeps the core library focused on storage and retrieval, not string manipulation.
