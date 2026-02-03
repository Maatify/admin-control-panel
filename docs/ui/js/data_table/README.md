# Generic Data Table Component

A completely agnostic, reusable JavaScript data table component that handles API data fetching, rendering, pagination, selection, and export without knowing any business logic.

## Features
- 🚀 **Zero Business Logic**: Just displays what the API sends.
- 📡 **Built-in API Fetching**: Handles `POST` requests with parameters.
- 📄 **Pagination**: Supports server-side pagination with custom per-page options.
- ✅ **Selection**: Optional row selection with "Select All" capability.
- 🎨 **Custom Rendering**: Inject custom HTML for specific columns.
- 📤 **Export**: Built-in CSV, Excel, and PDF export (client-side).
- 🔔 **Event System**: Emits custom events for parent interaction.
- 🧩 **Sorting**: Client-side sorting support.

## Dependencies
- Tailwind CSS (for styling)
- FontAwesome / HeroIcons (SVGs included inline)

## Quick Start

```html
<div id="table-container"></div>

<script src="/path/to/data_table.js"></script>
<script>
    const headers = ["ID", "Name", "Email", "Status", "Actions"];
    const rowKeys = ["id", "name", "email", "status", "actions"];
    
    // Initialize and load data
    createTable(
        '/api/users/list',  // API Endpoint
        { page: 1 },        // Initial Params
        headers,            // Header Labels
        rowKeys,            // Keys in API response object
        true                // Enable Selection
    );
</script>
```

## API Reference

### `createTable(apiUrl, params, headers, rowKeys, withSelection, primaryKey, onSelectionChange, customRenderers, selectableIds, getPaginationInfo)`

Main entry point. Fetches data from the API and renders the table.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `apiUrl` | String | | The API endpoint to fetch data from (POST). |
| `params` | Object | | Payload sending to the API (e.g., `{ page: 1, search: 'foo' }`). |
| `headers` | Array | | Information text to display in the table header. |
| `rowKeys` | Array | | Keys matching the data object properties. |
| `withSelection` | Boolean | `false` | Enable checkbox selection column. |
| `primaryKey` | String | `'id'` | Unique key for tracking selection. |
| `onSelectionChange` | Function | `null` | Callback `(selectedIds) => {}`. |
| `customRenderers` | Object | `{}` | Functions to render custom column HTML. |
| `selectableIds` | Set/Array | `null` | Whitelist of IDs allowed to be selected. |
| `getPaginationInfo` | Function | `null` | Callback to return custom `{ total, info }` text. |

### `TableComponent(data, columns, rowNames, paginationData, ...)`

Renders the UI. Usually called internally by `createTable`, but can be used directly if you have static data.

## Custom Renderers

You can format specific columns (e.g., adding buttons, badges, or links).

```javascript
const renderers = {
    status: (value, row) => {
        const color = value === 'Active' ? 'green' : 'red';
        return `<span class="text-${color}-600 font-bold">${value}</span>`;
    },
    actions: (value, row) => {
        return `<button onclick="editUser(${row.id})" class="btn-primary">Edit</button>`;
    }
};

createTable(api, params, headers, keys, true, 'id', null, renderers);
```

## Events

The table emits a `tableAction` event on the `document` for interactions.

```javascript
document.addEventListener('tableAction', (e) => {
    const { action, value, currentParams } = e.detail;

    if (action === 'pageChange') {
        console.log(`Switched to page ${value}`);
        // You usually want to reload the table here
        currentParams.page = value;
        createTable(apiUrl, currentParams, ...);
    }
    
    if (action === 'perPageChange') {
        console.log(`Per page changed to ${value}`);
    }
});
```

## Exporting Data
Three buttons are automatically generated at the top of the table:
- **CSV**: Exports visible data to CSV.
- **Excel**: Exports visible data to `.xls`.
- **PDF**: Opens a print-friendly view.

## Error Handling
If the API fails:
1. Validates input arrays.
2. Shows a loading spinner.
3. Catches fetch errors and displays a user-friendly error UI with a "Retry" button.
