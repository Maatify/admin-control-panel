# UI Extensibility (Phase 2) — Design & Implementation

> **Status:** IMPLEMENTED
> **Scope:** Admin Control Panel UI
> **Reference:** `docs/adr/ADR-015-ui-extensibility.md`

## 1. Overview

The Admin Control Panel supports a **kernel-safe UI extensibility model**. This allows host applications to customize the UI (layout, branding, specific pages) without modifying the vendor (kernel) files.

The system relies on three core concepts:
1.  **Host Template Path:** An optional directory where the host application stores its custom templates.
2.  **Filesystem Stacking:** Host templates take precedence over Kernel templates.
3.  **Template Namespaces:** Explicit namespaces (`@admin` and `@host`) allow safe inheritance.

---

## 2. Configuration

To enable UI extensibility, the Host Application must define the `HOST_TEMPLATE_PATH` environment variable.

```dotenv
# .env
HOST_TEMPLATE_PATH=/path/to/your/host/templates
```

*   If this variable is **not set**, the system runs in **Kernel-only mode** (standard UI).
*   If set, Twig will look in this directory *first* before looking in the Kernel's `templates/` directory.

---

## 3. Template Namespaces

The system registers two Twig namespaces to facilitate safe inheritance:

| Namespace | Path | Description |
| :--- | :--- | :--- |
| **(default)** | `[HostPath, KernelPath]` | Used for automatic overrides. If a file exists in Host, it loads; otherwise Kernel. |
| **`@admin`** | `KernelPath` | Forces loading from the Kernel directory. |
| **`@host`** | `HostPath` | Forces loading from the Host directory. |

### Usage Rule
*   **To Override:** Create a file with the same path as the kernel file (e.g., `layouts/base.twig`).
*   **To Extend:** Use `{% extends "@admin/layouts/base.twig" %}` inside your override to inherit the kernel's structure.

---

## 4. Theme Slots (Layout Customization)

The main layout (`templates/layouts/base.twig`) is divided into granular **slots** (blocks). You can override these slots to inject custom content while preserving the rest of the layout.

### Available Slots

| Slot Name | Description | Recommended Usage |
| :--- | :--- | :--- |
| `head_meta` | `<meta>` tags and `<title>` | SEO, favicon, viewport settings. |
| `head_assets` | CSS links, Fonts, Scripts in `<head>` | Adding custom CSS or loading external fonts. |
| `page_header` | The top navigation bar | Customizing the top bar content (search, profile). |
| `sidebar_header` | Top of the sidebar (Logo area) | Changing the logo or branding. |
| `sidebar_footer` | Bottom of the sidebar | Adding version info or links at the sidebar bottom. |
| `content_footer` | The page footer | Customizing copyright or legal text. |
| `body_scripts` | Scripts at the end of `<body>` | Injecting custom JS or analytics. |

---

## 5. Examples

### Example A: Custom Branding (CSS)

**File:** `<HOST_TEMPLATE_PATH>/layouts/base.twig`

```twig
{% extends "@admin/layouts/base.twig" %}

{% block head_assets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('css/my-brand.css') }}">
{% endblock %}
```

### Example B: Custom Footer

**File:** `<HOST_TEMPLATE_PATH>/layouts/base.twig`

```twig
{% extends "@admin/layouts/base.twig" %}

{% block content_footer %}
    <footer class="bg-gray-100 p-4 text-center">
        &copy; 2024 My Host Application
    </footer>
{% endblock %}
```

### Example C: Replacing a Page Entirely

**File:** `<HOST_TEMPLATE_PATH>/pages/login.twig`

```twig
<!DOCTYPE html>
<html>
    <!-- Completely custom login page -->
    <body>
        <h1>Welcome to My App</h1>
        <!-- ... form ... -->
    </body>
</html>
```

---

## 6. Security Guidance

*   **Prefer Extending:** Always try to extend `@admin` templates rather than copying/replacing them. This ensures you receive upstream security updates (e.g., if the kernel adds a CSRF token to the layout).
*   **Use `{{ parent() }}`:** When overriding `head_assets` or `body_scripts`, calling `{{ parent() }}` ensures that kernel dependencies (like Tailwind or main JS files) are still loaded.
