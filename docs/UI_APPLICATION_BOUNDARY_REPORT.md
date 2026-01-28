# UI / Application Boundary Audit Report

## 1. Executive Summary
The UI layer (Controllers & Infrastructure) generally adheres to a separated architecture but exhibits inconsistent boundary discipline. While newer components (e.g., `UiAdminsController`) correctly utilize Application Services and Readers, legacy components (e.g., `Web\LoginController`) bypass the Application layer, communicating directly with Domain Services and Repositories. Navigation logic is isolated but lacks authorization awareness, leading to a "dumb" UI that may expose unreachable paths. DTO usage is present but leaked from the Domain layer rather than being UI-specific.

## 2. Confirmed Safe Areas
*   **Application Service Usage:** `UiAdminsController` correctly delegates mutation logic to `App\Application\Admin\AdminProfileUpdateService`.
*   **View Delegation:** `UiDashboardController` and `UiLoginController` correctly act as proxies to their `Web` counterparts, maintaining a clear "UI" vs "Web" distinction.
*   **Template Isolation:** Twig templates (`templates/`) are passive and do not contain business logic, consuming only passed variables.
*   **Infrastructure Location:** `DefaultNavigationProvider` is correctly placed in `App\Infrastructure\Ui`.

## 3. Confirmed Boundary Violations (if any)

### UI → Repository Leak
- **File path:** `app/Http/Controllers/Web/LoginController.php`
- **Approximate line range:** 35, 72
- **What boundary is violated:** Controller calls Repository directly.
- **Why this is a violation:** The Controller injects and calls `AdminSessionValidationRepositoryInterface` directly to check session details. Repositories should never be accessed by Controllers; this is the responsibility of an Application Service.

### UI → Repository Leak
- **File path:** `app/Http/Controllers/Web/EmailVerificationController.php`
- **Approximate line range:** 165
- **What boundary is violated:** Controller calls Repository directly.
- **Why this is a violation:** Direct usage of `AdminEmailRepository` to fetch or verify emails bypasses the Application layer.

### UI → Domain Service Bypass
- **File path:** `app/Http/Controllers/Web/LoginController.php`
- **Approximate line range:** 33, 67
- **What boundary is violated:** Controller calls Domain Service directly.
- **Why this is a violation:** Calls `AdminAuthenticationService` (Domain Service) directly. Strictly speaking, an Application Service (e.g., `UserLoginService`) should orchestrate the login transaction to handle side effects, logging, and DTO conversion.

### UI → Domain Service Bypass
- **File path:** `app/Http/Controllers/Ui/UiRolesController.php`
- **Approximate line range:** 16
- **What boundary is violated:** Controller calls Domain Service directly.
- **Why this is a violation:** Calls `AuthorizationService` (Domain Service) directly to build the capabilities array.

## 4. Ambiguous Areas (Need Decision)
*   **Domain DTOs in UI:** `NavigationItemDTO` and `UiConfigDTO` reside in `App\Domain\DTO\Ui`. While categorized as "Ui" DTOs, they live in the `Domain` namespace. Decision is needed on whether these should move to `App\Application\DTO\Ui` or `App\Infrastructure\Ui\DTO`.
*   **Controller → Reader Pattern:** `UiAdminsController` injects `AdminProfileReaderInterface` (Domain Contract) and passes the result (array) to the View. Decision is needed on whether "Read Model" access from Controller is permitted or if it must go through an Application Service.

## 5. DTO Usage Analysis
- **DTOs consumed:** `NavigationItemDTO`, `UiConfigDTO`.
- **Layer:** Domain (`App\Domain\DTO\Ui`).
- **Acceptability:** Acceptable for now, but technically a leakage of Domain into UI. Ideally, UI-specific DTOs should reside in the Application or Infrastructure layer to decouple the Domain from presentation concerns.

## 6. Navigation & Authorization Ownership
- **Definition:** Navigation is defined in `app/Infrastructure/Ui/DefaultNavigationProvider.php` as a static list.
- **Authorization Inference:** The current navigation provider does **not** infer permissions. It returns all items regardless of the user's role.
- **UI Logic:** `UiRolesController` explicitly checks permissions using `AuthorizationService` to toggle UI elements, which is a correct placement for view-logic but relies on a Domain Service.

## 7. Explicit Non-Issues
*   **UiAdminsController -> AdminProfileReaderInterface:** While it skips the Application Service, accessing a dedicated "Reader" interface for read-only views is a common and acceptable CQRS-lite pattern, provided the Reader doesn't contain business rules.
*   **UiRolesController -> Capabilities Array:** Generating a "capabilities" map for the frontend is a valid UI concern.

## 8. Proposed Directions (NO CODE)
*   **Refactor Login/Auth Flow:** Introduce `App\Application\Auth\LoginService` and `App\Application\Auth\SessionService`. Move all repository calls and Domain Service orchestration from `Web\LoginController` and `Web\LogoutController` into these new Application Services.
*   **Encapsulate Navigation:** Update `NavigationProviderInterface` to accept an `ActorContext` or `AdminId`. Refactor `DefaultNavigationProvider` to filter items based on `AuthorizationService` (injected) so the menu reflects actual privileges.
*   **Standardize DTOs:** Move `App\Domain\DTO\Ui\*` to `App\Application\DTO\Ui\*` to signify that UI contracts are an Application concern, not a Core Domain concern.

## 9. Final Recommendation
**APPROVE WITH CONDITIONS**
Proceed with remediation of the Auth flow violations immediately. The Navigation and DTO issues are lower priority but should be addressed to ensure strict layering.
