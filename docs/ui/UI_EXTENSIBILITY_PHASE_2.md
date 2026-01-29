# UI Extensibility – Phase 2 Design Document

## 1. Executive Summary

This document outlines the architectural design for the second phase of the Admin Control Panel's UI system. The primary objective is to enable "Host Applications" to extend and customize the Admin UI without modifying the core "Kernel" code. This separation of concerns is critical for maintaining the security and integrity of the Admin Control Panel while allowing it to be white-labeled and integrated into diverse environments.

The design focuses on three key pillars: **Host Overrides** (allowing the host to replace specific UI elements), **Template Namespaces** (providing a safe mechanism for inheritance and conflict resolution), and **Theme Slots** (defining granular injection points within the layout). This proposal remains strictly design-focused and assumes no changes to the existing Phase 1 implementation have been made yet.

## 2. Phase 1 – Current UI Architecture (Facts Only)

The current system establishes a centralized and secure foundation for UI rendering. Key architectural facts include:

*   **Centralized Configuration**: All UI-related settings are encapsulated within a strongly typed Data Transfer Object (`UiConfigDTO`). This DTO holds configuration values such as the application name, logo URL, admin URL, and the base URL for assets.
*   **Global Injection**: The `UiConfigDTO` instance is injected globally into the Twig environment as a variable named `ui`. This ensures that all templates have uniform access to configuration data without requiring controller-level intervention.
*   **Kernel Ownership**: The core templates (including the master layout `base.twig`) are located within the kernel's directory structure. The Twig loader is currently configured with a single path pointing to these internal templates.
*   **Hardcoded Structure**: The master layout defines the document structure (HTML head, body, sidebar, main content area) with limited flexibility. Styles (Tailwind CSS) and scripts are loaded via fixed references, and the navigation menu is dynamically generated via a `NavigationProviderInterface`.

## 3. Design Goals for Extensibility

To successfully enable UI extensibility, the following goals must be met:

*   **Kernel Integrity**: The core Admin Panel must remain upgradeable. Host customizations should not require modifying files within the `vendor` or `app` directories of the kernel.
*   **Safe Customization**: Hosts should be able to override specific parts of the UI (e.g., the login page or the footer) without accidentally breaking the security features or critical flows of the application.
*   **Explicit Resolution**: The system must have a deterministic way to decide whether to load a Kernel template or a Host template. Ambiguity in template loading can lead to hard-to-debug errors.
*   **Granular Control**: Customization should not be "all or nothing." A host should be able to tweak a single section (like adding a script to the `<head>`) without being forced to copy-paste the entire page layout.

## 4. Host Overrides (Design Proposal)

The mechanism for Host Overrides defines how the hosting application can substitute its own assets or templates for the defaults provided by the Kernel.

**Conceptual Approach: Filesystem Stacking**

The design proposes a "Stacked Filesystem" approach. Instead of the Twig loader looking at a single directory, it will be configured with an ordered list of directories.

1.  **Host Directory (High Priority)**: A directory within the Host application (e.g., `templates/admin`).
2.  **Kernel Directory (Low Priority)**: The existing kernel template directory.

When the system requests a template (e.g., `login.twig`), the loader checks the Host Directory first. If a matching file is found, it is used immediately. If not, the loader falls back to the Kernel Directory. This allows the Host to "shadow" any specific template file by simply creating a file with the same name in its own directory.

**Configuration Integration**

To support this, the Container configuration will be updated to accept an optional "Host Template Path." This path will be validated during the container build process. If provided, it is prepended to the Twig loader's paths. This leverages the existing `UiConfigDTO` or a similar environment-based configuration mechanism to define the location of these overrides.

**Asset Overrides**

For static assets (images, CSS), the design leverages the existing `adminAssetBaseUrl` configuration. Hosts can point this URL to their own public directory, effectively replacing all default assets. For more specific overrides (like just the logo), the `UiConfigDTO` already supports specific properties (e.g., `logoUrl`), which take precedence over default asset paths.

## 5. Template Namespaces (Design Proposal)

While Filesystem Stacking allows for *replacement*, it creates a problem for *extension*. If a Host wants to customize `login.twig` but keep most of the original logic, creating a file named `login.twig` that tries to extend `login.twig` results in an infinite loop.

**Conceptual Approach: Explicit Namespaces**

The design introduces two explicit namespaces to the template loader:

*   **@admin**: Strictly maps to the Kernel's template directory.
*   **@host**: Strictly maps to the Host's custom template directory (if it exists).

**Inheritance Strategy**

This namespacing enables the "Decorator Pattern" in templates. A Host template named `login.twig` can explicitly extend the Kernel's version by using the namespaced path:

*   *Host `login.twig`*: Extends `@admin/login.twig`.

This tells the renderer to load the Kernel's file as the parent, allowing the Host file to override specific blocks (like a "form_header" or "footer") while inheriting the complex form logic and security tokens defined in the parent. This mechanism is crucial for safe extensibility, ensuring that security patches in the Kernel's logic are automatically applied to the Host's custom views, provided the Host has not completely replaced the file.

## 6. Theme Slots (Design Proposal)

"Slots" refer to specific, named areas within the master layout (`base.twig`) where content can be injected. Currently, the layout is monolithic. To support extensibility, the layout must be refactored to expose these slots as Granular Twig Blocks.

**Strategic Injection Points**

The design identifies the following critical slots:

*   **Head Meta Slot**: Located in the `<head>` section. Allows Hosts to add custom meta tags, favicons, or external font references.
*   **Head Scripts/Styles Slot**: Located before the closing `</head>`. Allows injection of custom CSS or tracking scripts.
*   **Sidebar Header/Footer Slots**: Located above and below the navigation menu. Useful for adding branding elements or secondary actions (e.g., "Back to Main Site").
*   **Page Header Slot**: Located in the top bar. Allows injection of additional user controls or notifications.
*   **Content Footer Slot**: Located at the bottom of the main content area.
*   **Body Scripts Slot**: Located before the closing `</body>`. Used for custom JavaScript initialization code.

**Behavior**

By default, these slots will be populated with the standard Admin Panel content (e.g., the default Tailwind CDN link in the Head Slot). Because they are defined as Blocks, a Host template extending the layout can override any specific slot while leaving the others untouched. This eliminates the need to copy the entire `base.twig` just to add a single CSS file.

## 7. Explicit Non-Goals

To maintain scope and strictly adhere to the project's architectural principles, the following are explicitly **NOT** part of this design:

*   **Plugin System**: There is no design for a database-driven plugin architecture or a "Marketplace" for themes. Extensibility is strictly file-based.
*   **Dynamic Theme Engine**: We are not building a system to switch themes at runtime via the UI. The theme is determined at deployment time.
*   **Frontend Framework Agnosticism**: The Admin Panel is built on Tailwind CSS. This design does not attempt to abstract the CSS framework or support switching to Bootstrap/Bulma. Overrides must act within the context of the existing Tailwind environment or completely replace it.
*   **Database-Stored Templates**: Templates will remain on the filesystem. Storing views in the database is out of scope.

## 8. Readiness Assessment

**Current State**:
The Phase 1 architecture is robust and ready for this extension. The `UiConfigDTO` provides the necessary configuration vector, and the Container's `builderHook` allows for the seamless injection of the Host Template Path. The rigidity of the current `base.twig` is the primary blocker, which this design explicitly addresses via Theme Slots.

**Blockers**:
There are no architectural blockers. The dependency on `slim/twig-view` and the underlying `Twig` library fully supports the proposed Namespacing and Loader Chaining features without requiring custom extensions.

**Security Implications**:
The proposed design maintains security boundaries. By encouraging inheritance via Namespaces (`@admin`), we reduce the risk of Hosts accidentally removing security tokens or verification logic present in the core templates.

## 9. Phase 2 Conclusion

The proposed design leverages standard, proven patterns in template rendering to achieve high flexibility with low risk. By implementing **Filesystem Stacking** for replacement, **Namespaces** for inheritance, and **Theme Slots** for granular injection, the Admin Control Panel can support a wide range of host integrations. This approach respects the "Kernel vs. Host" ownership model and ensures that the system remains maintainable and upgradeable.

The system is fully prepared to move from Design to Implementation.
