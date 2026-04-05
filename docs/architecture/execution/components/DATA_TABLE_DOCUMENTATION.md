# DATA_TABLE_DOCUMENTATION.md
## data_table.js — Complete Reference
> Extracted from the actual data_table.js source code.

---

## Two Functions, One Constraint

Both functions write exclusively to `document.querySelector("#table-container")`.
The HTML **must** contain `<div id="table-container"></div>` — no other id works.

---

## 1. createTable() — All-in-One

Fetches data from the API and renders the table in a single call.

```javascript
createTable(
    apiUrl,             // string  — endpoint without /api/ prefix (e.g. 'sessions/query')
    params,             // object  — { page, per_page, search? }
    headers,            // array   — display column names  ['ID', 'Name', 'Status']
    rows,               // array   — data field keys       ['id', 'name', 'status']
    withSelection,      // boolean — false (default)
    primaryKey,         // string  — 'id' (default)
    onSelectionChange,  // null or function(Set)
    customRenderers,    // object  — { fieldKey: (value, row) => html }
    selectableIds,      // null or Set/Array of selectable IDs
    getPaginationInfo   // null or function(pagination) => { total, info }
)
```

**What it does internally:**
1. Sends a POST request to `/api/{apiUrl}` using `fetch()`
2. Expects response: `{ data: [...], pagination: { page, per_page, total, filtered? } }`
3. Stores state in module-level variables
4. Calls `TableComponent()` automatically

**Hard constraints:**
- POST only — cannot change the HTTP method
- Hardcoded to `#table-container`
- No error customization — uses built-in error HTML

---

## 2. TableComponent() — Manual Renderer

Renders data that you have already fetched yourself.

```javascript
TableComponent(
    data,               // array   — rows you fetched via ApiHandler.call()
    headers,            // array   — display column names
    rows,               // array   — data field keys
    paginationData,     // object  — { page, per_page, total, filtered? }
    '',                 // string  — legacy param, always pass empty string
    false,              // boolean — withSelection
    'id',               // string  — primaryKey
    null,               // null or function — onSelectionChange
    customRenderers,    // object  — { fieldKey: (value, row) => html }
    null,               // null or Set — selectableIds
    getPaginationInfo   // null or function
)
```

**Hard constraints:**
- Also hardcoded to `#table-container`
- Does not fetch data — you must fetch first

---

## 3. Decision: createTable() vs TableComponent()

| Scenario | Use |
|----------|-----|
| POST endpoint with standard pagination | `createTable()` |
| POST endpoint + custom error handling needed | `ApiHandler.call()` → `TableComponent()` |
| GET endpoint | `ApiHandler.call(..., 'GET')` + manual HTML render — do not use either function |
| Need to show raw error HTML on failure | `ApiHandler.call()` → `TableComponent()` |

---

## 4. Pagination Events

`data_table.js` does **not** call `window.changePage()` or `window.changePerPage()`.
It dispatches a `CustomEvent` named `tableAction`:

```javascript
// Fired by data_table.js on page number click
document.dispatchEvent(new CustomEvent('tableAction', {
    detail: { action: 'pageChange', value: pageNumber, currentParams: { ... } }
}));

// Fired by data_table.js on per-page select change
document.dispatchEvent(new CustomEvent('tableAction', {
    detail: { action: 'perPageChange', value: perPageValue, currentParams: { ... } }
}));
```

**Required in your feature module:**
```javascript
document.addEventListener('tableAction', (e) => {
    const { action, value } = e.detail;
    if (action === 'pageChange')    { currentPage = value;    loadData(); }
    if (action === 'perPageChange') { currentPerPage = value; currentPage = 1; loadData(); }
});
```

---

## 5. getPaginationInfo Callback

Controls what appears in the pagination footer. Required when your API returns a `filtered` count.

```javascript
function getPaginationInfo(pagination) {
    const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;

    const displayCount = filtered || total;
    const startItem    = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
    const endItem      = Math.min(page * per_page, displayCount);

    let info = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;

    if (filtered && filtered !== total) {
        info += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
    }

    return {
        total: displayCount,  // used for pagination button count calculation
        info:  info           // HTML string displayed in the footer
    };
}
```

Pass it as the last argument:
```javascript
createTable(apiUrl, params, headers, rows, false, 'id', null, renderers, null, getPaginationInfo);
// or
TableComponent(data, headers, rows, pagination, '', false, 'id', null, renderers, null, getPaginationInfo);
```

---

## 6. Custom Renderers

```javascript
const customRenderers = {
    // key must match the corresponding entry in the rows array
    is_active: (value, row) => {
        // value = the field value for this row
        // row   = the full row object
        const active = value === true || value === 1 || value === '1';
        return active
            ? `<span class="px-2.5 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Active</span>`
            : `<span class="px-2.5 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">Inactive</span>`;
    },

    actions: (value, row) => {
        // Use AdminUIComponents — do not build raw HTML strings for buttons
        return AdminUIComponents.buildActionButton({
            cssClass:       'edit-btn',
            icon:           AdminUIComponents.SVGIcons.edit,
            text:           'Edit',
            color:          'blue',
            entityId:       row.id,
            dataAttributes: { '{feature}-id': row.id }
        });
    }
};
```

---

## 7. Selection Feature

```javascript
createTable(
    apiUrl,
    params,
    headers,
    rows,
    true,                          // withSelection = true
    'id',
    (selectedSet) => {             // onSelectionChange callback
        const count = selectedSet.size;
        document.getElementById('selected-count').textContent = count;
        document.getElementById('btn-bulk-action').disabled = (count === 0);
    },
    customRenderers,
    new Set([1, 2, 5])             // only IDs 1, 2, 5 can be selected
);

// Helpers available globally after createTable() is called
const selectedIds  = getSelectedItems();      // array of selected IDs
const count        = getSelectedCount();      // number
const isSelected   = isItemSelected(id);      // boolean
clearSelectedItems();                         // deselect all
selectItems([1, 2, 3]);                       // programmatically select
```

---

## 8. Expected API Response Format

```json
{
    "data": [ { "id": 1, "name": "...", ... }, ... ],
    "pagination": {
        "page": 1,
        "per_page": 25,
        "total": 100,
        "filtered": 50
    }
}
```

- `filtered` is optional — used when a server-side filter is active
- If `pagination` is absent, the table falls back to `data.length` as the total

---

## 9. showAlert in data_table.js

`data_table.js` has its own internal `showAlert()` that accepts both the old short format and the full word format:

```javascript
// Both work inside data_table.js error handlers
showAlert('d',       'Error message');   // old format
showAlert('danger',  'Error message');   // new format — mapped internally
```

This is an internal function. Use the alert system that matches your pattern (see `JS_PATTERNS_REFERENCE.md`).

---

## 10. Exported Helper Functions

These are available globally after `data_table.js` loads:

| Function | Returns | Purpose |
|----------|---------|---------|
| `getSelectedItems()` | `array` | IDs of all selected rows |
| `getSelectedCount()` | `number` | Count of selected rows |
| `isItemSelected(id)` | `boolean` | Check if a specific ID is selected |
| `clearSelectedItems()` | `void` | Deselect all rows |
| `selectItems(ids)` | `void` | Programmatically select rows |
| `refreshTable()` | `Promise` | Re-runs last `createTable()` call with same params |
