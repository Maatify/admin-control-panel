# WYSIWYG Manager Documentation (`AdminKernel.Wysiwyg`)

## 1. Purpose

The `AdminKernel.Wysiwyg` manager is a shared, scalable registry for instantiating and managing multiple Jodit WYSIWYG editors across the application frontend. It abstracts away complex lifecycle management (like global dark mode toggling, RTL directionality binding, and state synchronization) to provide developers with a simple, idempotent, and unified interface.

## 2. Multi-instance Behavior

The system is explicitly designed for **multi-instance support**:
- **Multiple editors** can safely exist on a single page.
- **No Shared State:** Each editor operates completely independently with its own internal DOM and state, mapped by a unique `data-field` key.
- **Independent Lifecycle:** Editors are initialized, reinitialized, and destroyed safely without affecting other editors, except during global theme toggles which synchronize all instances simultaneously.

## 3. Internal Architecture

Understanding the internal architecture is crucial for avoiding duplicate logic:
- **Registry (`Map<data-field, { editor, element }>`)**: The manager stores active editor instances keyed strictly by the `<textarea>`'s `data-field` attribute. This acts as the primary key for all data extraction and state tracking.
- **Singleton `MutationObserver`**: Instead of having every editor watch the DOM, the manager initializes a **single** `MutationObserver` on the `<html>` tag to watch for `class="dark"` changes. When triggered, it idempotently re-initializes all registered editors to apply the correct theme while preserving their current content and dirty states.
- **Lifecycle Flow (`init → change → reinit`)**:
  1. `initAll` scans the DOM and initializes untracked textareas.
  2. Editors fire native Jodit `change` events, which the manager intercepts to track dirty fields and dispatch a custom, bubbled `wysiwygChange` event.
  3. `reinitTheme` automatically destroys and recreates editors smoothly when the global UI theme changes.

## 4. API & Wrapper Clarification

The system exposes two layers:
1.  **Global Developer Wrappers (Recommended):** Lightweight functions (`initWysiwyg`, `getWysiwygData`, `isWysiwygDirty`) designed to be used in standard page scripts (`*-with-components.js`, `*-helpers.js`).
2.  **Core Manager (`AdminKernel.Wysiwyg`):** The internal engine. Direct interaction from standard page scripts is **strongly discouraged**, except for specific administrative commands like `resetDirtyState()`.

## 5. HTML Contract (The "What")

To opt into the system, a field **MUST** be a `<textarea>` and include both the `js-wysiwyg-editor` class and a unique `data-field` attribute.

```html
<!-- Single WYSIWYG Field -->
<div>
    <label for="trans-details">Details</label>
    <!-- MUST include 'js-wysiwyg-editor' and 'data-field' -->
    <textarea id="trans-details"
              class="js-wysiwyg-editor"
              data-field="details"
              name="details">{{ translation.details|raw }}</textarea>
</div>

<!-- Multiple WYSIWYG Fields -->
<div>
    <label for="trans-log-desc">Internal Logs</label>
    <textarea id="trans-log-desc"
              class="js-wysiwyg-editor"
              data-field="log_description"
              name="log_description">{{ translation.log_description|raw }}</textarea>
</div>
```

## 6. Initialization Flow (The "How")

Initialize the manager globally inside your `*-with-components.js` file using the `initWysiwyg` wrapper.

```javascript
// Initialize ALL .js-wysiwyg-editor textareas on the page
if (typeof window.initWysiwyg === 'function') {
    // Context handles RTL/LTR directionality dynamically
    window.initWysiwyg({
        languageCode: window.context.languageCode,
        languageDirection: window.context.languageDirection
    });

    // Listen to generic WYSIWYG changes to update UI state
    document.addEventListener('wysiwygChange', (e) => {
        // e.detail.field contains the specific data-field that changed
        console.log(`Editor for field ${e.detail.field} was changed.`);
        const saveBtn = document.getElementById('btn-save');
        if (saveBtn) saveBtn.classList.add('ring-2', 'ring-blue-400');
    });
}
```

## 7. Data Extraction & Lifecycle (The "When")

Data extraction MUST be done using `getWysiwygData()`, mapped against the `data-field` keys. Upon successful submission, the system's dirty tracking must be cleared explicitly using `window.AdminKernel.Wysiwyg.resetDirtyState()`.

```javascript
// Inside helper payload extraction (*-helpers.js)
extractFormData: function() {
    const payload = { name: document.getElementById('trans-name').value.trim() };

    // Extract ALL WYSIWYG data automatically via the global wrapper
    if (typeof window.getWysiwygData === 'function') {
        const wysiwygData = window.getWysiwygData();

        // Keys match the 'data-field' attribute from HTML
        if (wysiwygData.details && wysiwygData.details.trim()) {
            payload.details = wysiwygData.details.trim();
        }
        if (wysiwygData.log_description && wysiwygData.log_description.trim()) {
            payload.log_description = wysiwygData.log_description.trim();
        }
    }
    return payload;
}

// Inside core submission (*-with-components.js)
saveTranslation: async function(payload) {
    const response = await ApiHandler.call(endpoint, payload, 'Save Translation');
    if (response.success) {
        // Clear global dirty state formally through the core API
        if (window.AdminKernel && window.AdminKernel.Wysiwyg) {
            window.AdminKernel.Wysiwyg.resetDirtyState();
        }
        ApiHandler.showAlert('success', 'Saved successfully!');
    }
}
```

## 8. Strict Enforcement Rules

**DO:**
- **DO** use `<textarea class="js-wysiwyg-editor" data-field="...">` as the sole mechanism for defining an editor.
- **DO** use the wrapper `initWysiwyg(context)` globally in the `*-with-components.js` file.
- **DO** extract data using the wrapper `getWysiwygData()`.
- **DO** use `AdminKernel.Wysiwyg.resetDirtyState()` to clear state flags after a successful data save.

**DO NOT:**
- **DO NOT** invoke `Jodit.make(...)` directly on any page. All initialization must route through the global wrappers.
- **DO NOT** attempt to apply inline `dir` attributes to the textarea. RTL/LTR is fully managed by the global initialization context.
- **DO NOT** write custom `MutationObserver` logic to handle Dark Mode toggling for editors. The manager implements a singleton observer that handles this for all registered instances automatically.
- **DO NOT** extract values using `document.getElementById('xyz').value` if the field is a WYSIWYG editor. You MUST pull from `getWysiwygData()`.
