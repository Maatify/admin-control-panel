# UI Extensibility Examples

**⚠️ IMPORTANT: These files are NOT used by the runtime.**

This directory contains educational examples demonstrating how a Host Application should structure its templates to override or extend the Admin Kernel.

## Concepts

### 1. Host Overrides (Replacement)
If you place a file named `login.twig` in your Host Template Directory, it will completely replace the Kernel's `login.twig`.

### 2. Template Namespaces (Extension)
To *extend* a Kernel template instead of replacing it, you must use the `@admin` namespace.
*   `@admin`: Refers to the Kernel's templates.
*   `@host`: Refers to your Host templates.

### 3. Theme Slots (Injection)
The Kernel's `base.twig` exposes specific "Blocks" (Slots) that you can override to inject content.

## Example File
See `example_override.twig` for a code example of extending the layout and injecting a script.
