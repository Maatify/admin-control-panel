# Data Table Documentation

`data_table.js` is a comprehensive component for rendering dynamic tables with server-side pagination, sorting, filtering, and export capabilities.

## Integration Example

### 1. HTML Structure
Ensure you have a container where the table will be rendered.
```html
<div class="container mt-6">
    <div id="table-container" class="w-full"></div>
</div>

<!-- Include Scripts -->
<script src="/assets/js/data_table.js"></script>
```

### 2. Javascript Initialization
```javascript
document.addEventListener('DOMContentLoaded', () => {
    // 1. Define Columns Headers (Display Names)
    const headers = [
        "User ID",
        "Full Name", 
        "Email Address",
        "Account Status"
    ];

    // 2. Define Row Keys (JSON keys from API response)
    const rows = [
        "id",
        "full_name",
        "email",
        "status"
    ];

    // 3. Define Initial Parameters
    const params = {
        per_page: 10,
        filters: {} 
    };

    // 4. Initialize Table
    // Fetches data from /api/users/list
    createTable("users/list", params, headers, rows);
});
```

## Global Functions

### `createTable(apiUrl, params, headers, rows)`
Initializes or refreshes the table data.
- **apiUrl**: Endpoint path (e.g., `"sessions/query"`).
- **params**: Object containing `per_page`, filters, etc.
- **headers**: Array of column display names.
- **rows**: Array of object keys corresponding to columns.
- **Behavior**: Fetches data from `/api/{apiUrl}` and calls `TableComponent`.

## `TableComponent(data, columns, rowNames, pagination)`
Renders the HTML table logic.
- **data**: Array of row objects.
- **columns**: Table header labels.
- **rowNames**: Keys to access data in row objects.
- **pagination**: Object `{ count, page, total }`.

### Features
1.  **Rendering**: Generates HTML for table structure, headers, and rows.
2.  **Badge Logic**: Automatically styles specific fields like "status" (active=green, draft=red, etc.).
3.  **Pagination**: Renders Next/Prev and numbered page buttons. Handles page changes via `updatePage`.
4.  **Sorting**: Clickable headers to sort data client-side.
5.  **Filtering**:
    - **Search Input**: simple client-side text filter.
    - **Status Filter**: buttons for "Active", "Draft", etc.
6.  **Export**:
    - **CSV**: Downloads client-side CSV.
    - **Excel**: Downloads client-side XLS.
    - **PDF**: Opens print view for PDF generation.

## Dependencies- **Axios/Fetch**: Uses `fetch` for API calls.
- **Tailwind/Bootstrap**: Relies on specific CSS classes for styling.
