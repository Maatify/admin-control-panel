# UI Extensibility – Phase 2 Design Document

## 1. Executive Summary

This document defines the architectural strategy for "Phase 2: UI Extensibility" of the Admin Control Panel. It addresses the requirement for Host Applications (consumers of this kernel) to customize branding, modify layout structures, and override specific templates without compromising the upgradeability or security of the core kernel.

The design relies on three core mechanisms: **Filesystem Stacking** for overrides, **Template Namespaces** (`@admin`, `@host`) for safe inheritance, and **Theme Slots** for granular injection. This document serves as the blueprint for future implementation.

## 2. Phase 1 – Current UI Architecture (Facts)

The existing system (Phase 1) establishes the following baseline:

*   **Configuration**: UI settings are strictly typed in `App\Domain\DTO\Ui\UiConfigDTO`.
    *   `adminAssetBaseUrl`: Controls the root path for CSS/JS/Images.
    *   `appName`, `logoUrl`: Control basic branding.
    *   `adminUrl`: Controls internal linking base.
*   **Global Injection**: An instance of `UiConfigDTO` is injected into every Twig template via the global variable `ui`.
*   **Template Loading**: The Twig loader is configured with a single path pointing to the kernel's `templates/` directory.
*   **Rendering**: Templates like `layouts/base.twig` are monolithic and rely on the global `ui` object for dynamic content.
*   **Assets**: Frontend assets are referenced relative to `{{ ui.adminAssetBaseUrl }}`.

## 3. Design Goals for Extensibility

The Extensibility system must achieve:

1.  **Zero-Touch Kernel**: Hosts must not modify files in `vendor/` or the kernel's `src/`.
2.  **Safe Inheritance**: A host must be able to extend a kernel template (e.g., wrap the login form) without copying the entire file and losing future security updates.
3.  **Deterministic Resolution**: It must be unambiguous whether a template is loaded from the Host or the Kernel.
4.  **Granular Customization**: Hosts should be able to inject scripts or styles into the `<head>` or `<footer>` without redefining the entire HTML structure.

## 4. Host Overrides (Design Proposal)

### Strategy: Filesystem Path Stacking

To enable overrides, the Twig Loader configuration in the DI Container must be updated to accept an optional **Host Template Path**.

*   **Logic**: The loader will be configured with an array of paths: `[HOST_PATH, KERNEL_PATH]`.
*   **Behavior**: When a template is requested (e.g., `login.twig`), Twig looks in the `HOST_PATH` first.
    *   If found: The Host's version is rendered.
    *   If not found: The Kernel's version is rendered.
*   **Configuration**: The Host Template Path should be injected via the `Container::create($builderHook)` mechanism or a specific environment variable, allowing the Host Application to define where its overrides reside.

### Asset Overrides

Static assets are already decoupled via `UiConfigDTO`.
*   **Mechanism**: The Host configures `adminAssetBaseUrl` (via ENV) to point to its own public asset directory.
*   **Result**: The kernel templates use `{{ ui.adminAssetBaseUrl }}/css/style.css`. The Host can serve a completely different CSS file at that path, effectively "skinning" the application without changing template code.

## 5. Template Namespaces (Design Proposal)

To solve the "Infinite Recursion" problem (where `login.twig` cannot extend `login.twig`), explicit namespaces are required.

### Definitions

1.  **`@admin`**: Maps strictly to the Kernel's `templates/` directory.
2.  **`@host`**: Maps strictly to the Host's custom template directory (if configured).
3.  **`(root)`**: The default namespace, searching `[@host, @admin]`.

### Implementation Guide

*   **Kernel Templates**: Should refer to internal partials using the default namespace (e.g., `{% include 'partials/header.twig' %}`) to allow Hosts to override those partials.
*   **Host Overrides**: When a Host wants to *extend* a Kernel file, it must use the explicit namespace:
    ```twig
    {# Host's templates/login.twig #}
    {% extends "@admin/login.twig" %}
    ```
    This ensures the parent is explicitly the Kernel file, not the Host file itself.

## 6. Theme Slots (Design Proposal)

"Slots" are standardized Twig Blocks added to the master layout to allow content injection without structural replacement.

### Required Slots

Future implementation must refactor `layouts/base.twig` to include:

| Slot Name | Location | Purpose |
| :--- | :--- | :--- |
| `head_meta` | `<head>` | Favicons, Meta tags, SEO. |
| `head_css` | `<head>` (end) | Custom CSS, Fonts. |
| `body_start` | `<body>` (top) | Analytics, non-visual hooks. |
| `header_actions` | Top Header | Additional buttons/links. |
| `sidebar_nav` | Sidebar | (Existing loop over `nav_items`, wrap in block). |
| `footer_content` | Footer | Copyright overrides. |
| `scripts_end` | `<body>` (end) | Custom JS initialization. |

### Usage

A Host override of `base.twig` (or a child template) can define these blocks to append content:
```twig
{% block head_css %}
    {{ parent() }}
    <link rel="stylesheet" href="custom-theme.css">
{% endblock %}
```

## 7. Explicit Non-Goals

*   **Database Templates**: Templates are filesystem-only.
*   **Runtime Theming**: Theme selection is deployment-configuration, not user-preference.
*   **Plugin Engine**: Extensibility is monolithic (Host App), not modular (Plugins).
*   **CSS Abstraction**: Tailwind CSS is the mandated framework.

## 8. Readiness Assessment

The system is architecturally ready for this change.
*   **Blocking Factors**: None.
*   **Security**: Use of `UiConfigDTO` and Namespaces preserves security boundaries.
*   **Next Step**: Implementation of `Container` loader logic and refactoring of `base.twig`.

## 9. Phase 2 Conclusion

This design provides a robust, standard-compliant method for UI extensibility. By combining **Filesystem Stacking** (for replacement) with **Namespaces** (for extension) and **Slots** (for injection), the Admin Control Panel can support diverse Host requirements while maintaining a locked, secure Kernel.
