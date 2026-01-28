# Application Use-Case Audit & Mapping

## 1. Executive Summary

This audit documents the current state of Authentication, Access, and UI use-cases within the `maatify/admin-control-panel` project.

**Key Findings:**
*   **Layering Inconsistency:** While newer UI controllers (e.g., `UiAdminsController`) leverage Application Services and Readers appropriately, core authentication controllers (`LoginController`, `LogoutController`) frequently bypass the Application Layer, interacting directly with Domain Services and Repositories.
*   **Orchestration:** Orchestration logic is split. In Auth flows, it resides heavily within Controllers. In Profile/Admin management flows, it is correctly pushed to Application Services.
*   **Navigation:** Navigation is static and defined in the Infrastructure layer, lacking dynamic authorization awareness.
*   **Telemetry:** Telemetry is injected directly into Controllers as a cross-cutting concern, often in a "best-effort" try-catch block.

The following sections map each identified use-case to its entry point, participating layers, and orchestration owner.

---

## 2. Use-Case Index

**Authentication & Access**
1.  Admin Login
2.  Admin Logout
3.  Password Change
4.  Email Verification (Verify)
5.  Email Verification (Resend)
6.  Connect Telegram
7.  2FA Setup (Enrollment)
8.  2FA Verification (Step-Up/Login)

**UI & Web Flows**
9.  Dashboard Access
10. Admin List View
11. Admin Profile View
12. Admin Profile Edit Form
13. Admin Profile Update
14. Admin Email List
15. Admin Session List
16. Roles Management View
17. Permissions View
18. Session List View
19. Navigation Menu Generation

---

## 3. Detailed Use-Case Sections

### 1. Admin Login
*   **Entry Point:** `App\Http\Controllers\Web\LoginController::login`
*   **Involved Layers:**
    *   **Controller:** `LoginController`
    *   **Domain Service:** `AdminAuthenticationService`
    *   **Domain Service:** `RememberMeService`
    *   **Repository:** `AdminSessionValidationRepositoryInterface`
    *   **Infrastructure:** `AdminIdentifierCryptoServiceInterface`
*   **Orchestration Location:** **Controller** (Mixed). The controller calculates blind indexes, calls the auth service, fetches session details from the repo, and manages cookies.
*   **Boundary Observations:**
    *   Controller calls Domain Services directly.
    *   Controller calls Repository directly (`sessionRepository->findSession`) to calculate cookie parameters.
    *   Controller handles Blind Index calculation.

### 2. Admin Logout
*   **Entry Point:** `App\Http\Controllers\Web\LogoutController::logout`
*   **Involved Layers:**
    *   **Controller:** `LogoutController`
    *   **Domain Service:** `AdminAuthenticationService`
    *   **Domain Service:** `RememberMeService`
    *   **Repository:** `AdminSessionValidationRepositoryInterface`
    *   **Application Service:** `DiagnosticsTelemetryService`
*   **Orchestration Location:** **Controller**. The controller manually retrieves cookies, checks session ownership via repository, invokes logout on domain service, and revokes remember-me tokens.
*   **Boundary Observations:**
    *   Controller calls Repository directly (`sessionRepository->findSession`).
    *   Controller calls Domain Services directly.
    *   Telemetry is explicitly handled in the controller.

### 3. Password Change
*   **Entry Point:** `App\Http\Controllers\Web\ChangePasswordController::change`
*   **Involved Layers:**
    *   **Controller:** `ChangePasswordController`
    *   **Infrastructure:** `AdminIdentifierCryptoServiceInterface`, `PDO`
    *   **Repository:** `AdminIdentifierLookupInterface`, `AdminPasswordRepositoryInterface`, `AuthoritativeSecurityAuditWriterInterface`
    *   **Domain Service:** `PasswordService`, `RecoveryStateService`
*   **Orchestration Location:** **Controller**. The controller handles the entire transaction script: verifying recovery state, looking up admin, verifying old password, hashing new password, saving to repo, and writing audit logs.
*   **Boundary Observations:**
    *   Controller performs direct PDO transaction management (`beginTransaction`, `commit`).
    *   Controller interacts with multiple Repositories and Domain Services directly.
    *   No Application Service is used.

### 4. Email Verification (Verify)
*   **Entry Point:** `App\Http\Controllers\Web\EmailVerificationController::verify`
*   **Involved Layers:**
    *   **Controller:** `EmailVerificationController`
    *   **Infrastructure:** `AdminIdentifierCryptoServiceInterface`
    *   **Repository:** `AdminIdentifierLookupInterface`
    *   **Domain Service:** `AdminEmailVerificationService`
    *   **Domain Contract:** `VerificationCodeValidatorInterface`
*   **Orchestration Location:** **Controller**. The controller resolves the identity, validates the OTP using the validator, and then calls the verification service.
*   **Boundary Observations:**
    *   Controller calls Domain Contracts/Services directly.
    *   Controller handles identity resolution logic.

### 5. Email Verification (Resend)
*   **Entry Point:** `App\Http\Controllers\Web\EmailVerificationController::resend`
*   **Involved Layers:**
    *   **Controller:** `EmailVerificationController`
    *   **Infrastructure:** `AdminIdentifierCryptoServiceInterface`
    *   **Repository:** `AdminIdentifierLookupInterface`
    *   **Domain Contract:** `VerificationCodeGeneratorInterface`
    *   **Application Service:** `VerificationNotificationDispatcherInterface`
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Controller calls Domain Generator directly.
    *   Controller calls Application Notification Dispatcher (correct usage).

### 6. Connect Telegram
*   **Entry Point:** `App\Http\Controllers\Web\TelegramConnectController::index`
*   **Involved Layers:**
    *   **Controller:** `TelegramConnectController`
    *   **Domain Contract:** `VerificationCodeGeneratorInterface`
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Controller directly uses Domain Generator to create OTP.

### 7. 2FA Setup (Enrollment)
*   **Entry Point:** `App\Http\Controllers\Web\TwoFactorController::doSetup`
*   **Involved Layers:**
    *   **Controller:** `TwoFactorController`
    *   **Domain Service:** `StepUpService`
    *   **Application Service:** `DiagnosticsTelemetryService`
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Controller calls Domain Service (`StepUpService`) directly.
    *   Controller extracts session ID from cookies manually.

### 8. 2FA Verification (Step-Up/Login)
*   **Entry Point:** `App\Http\Controllers\Web\TwoFactorController::doVerify`
*   **Involved Layers:**
    *   **Controller:** `TwoFactorController`
    *   **Domain Service:** `StepUpService`
    *   **Application Service:** `DiagnosticsTelemetryService`
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Controller calls Domain Service (`StepUpService`) directly.
    *   Controller logic includes resolving requested scopes and return-to paths.

### 9. Dashboard Access
*   **Entry Point:** `App\Http\Controllers\Ui\UiDashboardController::index` → `App\Http\Controllers\Web\DashboardController::index`
*   **Involved Layers:**
    *   **Controller:** `UiDashboardController`, `DashboardController`
    *   **View:** Twig
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Pure UI delegation. `UiDashboardController` acts as a proxy to `Web\DashboardController`.

### 10. Admin List View
*   **Entry Point:** `App\Http\Controllers\Ui\UiAdminsController::index`
*   **Involved Layers:**
    *   **Controller:** `UiAdminsController`
    *   **View:** Twig
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Renders static template (data likely loaded via API/JS).

### 11. Admin Profile View
*   **Entry Point:** `App\Http\Controllers\Ui\UiAdminsController::profile`
*   **Involved Layers:**
    *   **Controller:** `UiAdminsController`
    *   **Domain Reader:** `AdminProfileReaderInterface`
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Controller calls Domain Reader directly to fetch view data.

### 12. Admin Profile Edit Form
*   **Entry Point:** `App\Http\Controllers\Ui\UiAdminsController::editProfile`
*   **Involved Layers:**
    *   **Controller:** `UiAdminsController`
    *   **Domain Reader:** `AdminProfileReaderInterface`
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Reuses Domain Reader for form data population.

### 13. Admin Profile Update
*   **Entry Point:** `App\Http\Controllers\Ui\UiAdminsController::updateProfile`
*   **Involved Layers:**
    *   **Controller:** `UiAdminsController`
    *   **Application Service:** `AdminProfileUpdateService`
*   **Orchestration Location:** **Application Service**.
*   **Boundary Observations:**
    *   **Correct Boundary Usage:** The controller delegates the entire update operation to an Application Service (`AdminProfileUpdateService`).

### 14. Admin Email List
*   **Entry Point:** `App\Http\Controllers\Ui\UiAdminsController::emails`
*   **Involved Layers:**
    *   **Controller:** `UiAdminsController`
    *   **Domain Reader:** `AdminBasicInfoReaderInterface`
    *   **Domain Reader:** `AdminEmailReaderInterface`
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Controller coordinates multiple readers to build the view model.

### 15. Admin Session List
*   **Entry Point:** `App\Http\Controllers\Ui\UiAdminsController::sessions`
*   **Involved Layers:**
    *   **Controller:** `UiAdminsController`
    *   **Domain Reader:** `AdminBasicInfoReaderInterface`
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Fetches display name via Reader, renders template (list likely loaded via API).

### 16. Roles Management View
*   **Entry Point:** `App\Http\Controllers\Ui\UiRolesController::index`
*   **Involved Layers:**
    *   **Controller:** `UiRolesController`
    *   **Domain Service:** `AuthorizationService`
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Controller explicitly calls `AuthorizationService` to build a capability map (`can_create`, `can_rename`, etc.) for the view.

### 17. Permissions View
*   **Entry Point:** `App\Http\Controllers\Ui\UiPermissionsController::index`
*   **Involved Layers:**
    *   **Controller:** `UiPermissionsController`
    *   **View:** Twig
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Simple view rendering.

### 18. Session List View
*   **Entry Point:** `App\Http\Controllers\Ui\SessionListController::__invoke`
*   **Involved Layers:**
    *   **Controller:** `SessionListController`
    *   **View:** Twig
*   **Orchestration Location:** **Controller**.
*   **Boundary Observations:**
    *   Simple view rendering.

### 19. Navigation Menu Generation
*   **Entry Point:** `App\Infrastructure\Ui\DefaultNavigationProvider::getNavigationItems`
*   **Involved Layers:**
    *   **Infrastructure:** `DefaultNavigationProvider`
    *   **DTO:** `NavigationItemDTO` (Domain DTO)
*   **Orchestration Location:** **Infrastructure**.
*   **Boundary Observations:**
    *   Navigation is static.
    *   No authorization checks are performed during menu generation.
    *   Uses a Domain DTO (`NavigationItemDTO`).

---

## 4. Cross-Cutting Observations

1.  **Auth Controller Heavy-Lifting:** The controllers in `App\Http\Controllers\Web\` (Login, Logout, ChangePassword) perform significant orchestration, direct repository access, and transaction management. They effectively act as "Transaction Scripts" rather than delegates to an Application Service.
2.  **UI Controller Separation:** There is a clear separation between `Ui` controllers (View rendering) and `Web` controllers (Action processing), though some `Ui` controllers (like `UiAdminsController`) handle both view rendering and redirection logic after updates.
3.  **Application Service Adoption:** The "Admin Profile Update" use-case demonstrates the target architecture (Controller → Application Service). However, most other flows do not yet follow this pattern.
4.  **Reader Pattern Usage:** Read-only views frequently utilize specialized "Reader" interfaces, bypassing Application Services. This is a consistent pattern in the `Ui` namespace.
5.  **Direct Repo Access:** Direct Repository access is prevalent in the legacy `Web` controllers but absent in the newer `Ui` controllers (except via Readers).
