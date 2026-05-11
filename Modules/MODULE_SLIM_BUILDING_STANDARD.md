# 🏗️ Building Admin Slim Modules: Complete A-Z Standard

## 📖 What is a Slim Module?

A **Slim Module** is a thin Admin UI wrapper around a core module that:
- Does **NOT** duplicate core business logic
- Provides **API endpoints** for admin operations (list, get, update, etc.)
- Provides **UI pages** (Twig templates) for admin interface
- Integrates with **AdminKernel** for security, validation, and permissions
- Follows **exact patterns** from AdminKernel infrastructure

**Pattern**: `Modules/[CoreModule]Slim` wraps `Modules/[CoreModule]`

Example: `SettingsSlim` wraps `Settings`

---

## 📁 Directory Structure

```
Modules/SettingsSlim/
├── src/
│   ├── Admin/
│   │   ├── Security/
│   │   │   ├── SettingAdminPermissionMapProvider.php
│   │   │   └── SettingAdminPermissionPackage.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/
│   │   │   │   │   ├── SettingsListController.php
│   │   │   │   │   ├── SettingsDropdownController.php
│   │   │   │   │   ├── SettingsGetController.php
│   │   │   │   │   └── SettingsUpdateController.php
│   │   │   │   └── Ui/
│   │   │   │       └── SettingsListUiController.php
│   │   │   └── Routes/
│   │   │       ├── SettingsApiRoutes.php
│   │   │       └── SettingsUiRoutes.php
│   │   └── Domain/
│   │       ├── List/
│   │       │   └── SettingListCapabilities.php
│   │       └── Validation/
│   │           ├── SettingUpdateSchema.php
│   │           └── SettingGetSchema.php
│   └── Bootstrap/
│       └── (Optional bindings)
├── permissions_seed.sql
└── composer.json (optional)
```

---

## 🔥 Step-by-Step Building Process

### Step 1: Create Directory Structure

```bash
mkdir -p Modules/SettingsSlim/src/Admin/{Security,Http/{Controllers/{Api,Ui},Routes},Domain/{List,Validation}}
```

### Step 2: Define Permissions (Security Layer)

**SettingAdminPermissionMapProvider.php**: Maps route names to required permissions

```php
<?php
namespace Maatify\SettingsSlim\Admin\Security;

use Maatify\AdminKernel\Security\Permission\PermissionRequirementDefinition;

class SettingAdminPermissionMapProvider {
    public static function provide(): array {
        return [
            // UI Route
            'settings.list.ui'      => PermissionRequirementDefinition::single('settings.list'),
            
            // API Routes
            'settings.list.api'     => PermissionRequirementDefinition::single('settings.list'),
            'settings.get.api'      => PermissionRequirementDefinition::single('settings.view'),
            'settings.update.api'   => PermissionRequirementDefinition::single('settings.edit'),
            'settings.dropdown.api' => PermissionRequirementDefinition::single('settings.list'),
        ];
    }
}
```

**Rules for Permission Map**:
- **route name** (left side) = what's passed to `Route.setName()`
- **permission name** (right side) = what's in permissions database
- UI routes need same permission as their API counterpart
- Dropdown endpoint shares permission with list (used together)

**SettingAdminPermissionPackage.php**: Provides permissions to AdminKernel

```php
<?php
namespace Maatify\SettingsSlim\Admin\Security;

use Maatify\AdminKernel\Security\Permission\ProvidesPermissionMapsInterface;

class SettingAdminPermissionPackage implements ProvidesPermissionMapsInterface {
    public function getPermissionMaps(): array {
        return [
            SettingAdminPermissionMapProvider::class,
        ];
    }
}
```

**How It Works**:
1. Each package implements `ProvidesPermissionMapsInterface`
2. Returns array of provider classes
3. AdminKernel loads all packages from `public/index.php`
4. For each route, checks permission map
5. Compares route name with map to get required permission
6. Verifies admin has that permission via database

**permissions_seed.sql**: Seeds base permissions into database

**File Location**: `Modules/[Module]Slim/permissions_seed.sql`

**Purpose**: Create base permissions that can be assigned to roles

**Database Schema Expected**:
```sql
-- Existing table structure
CREATE TABLE `permissions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) UNIQUE NOT NULL,        -- e.g., 'settings.list'
    `display_name` VARCHAR(255) NOT NULL,       -- e.g., 'List Settings'
    `description` TEXT,                         -- e.g., 'Allows list settings'
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Example: permissions_seed.sql**

```sql
-- Settings Module Permissions
INSERT IGNORE INTO permissions (name, display_name, description)
VALUES
    ('settings.list', 'List Settings', 'Allows list settings'),
    ('settings.view', 'View Settings', 'Allows view settings'),
    ('settings.edit', 'Edit Settings', 'Allows edit settings');
```

**Key Points**:
- `INSERT IGNORE` → Doesn't error if permission already exists
- Permission `name` must match exactly what's in `SettingAdminPermissionMapProvider`
- `description` is for UI reference, explains what permission does
- Permissions are base building blocks; admins assign them to **roles**
- Roles are then assigned to **admins**

**Flow**:
```
Admin → Role → Permissions (e.g., settings.list, settings.edit)
```

**How to Run**:
1. **Manual**: Open MySQL client, run the SQL file
2. **Laravel Migration** (if applicable): Create migration that calls SQL
3. **Database Seeder**: If using Seeder classes

**Verification**:
```sql
SELECT * FROM permissions WHERE name LIKE 'settings.%';
```

Should return 3 rows with the permission names

### Step 3: Define Domain Classes & Update Core Repository

**SettingListCapabilities.php**: Defines filterable and searchable columns

```php
<?php
namespace Maatify\SettingsSlim\Admin\Domain\List;

use Maatify\AdminKernel\ListGenerator\Contracts\ProvidesListCapabilitiesInterface;

class SettingListCapabilities implements ProvidesListCapabilitiesInterface {
    public function searchableColumns(): array {
        return ['key', 'admin_note'];  // Searchable by global search
    }
    
    public function filterableColumns(): array {
        return ['key', 'admin_note', 'value_type'];  // Can filter individually
    }
}
```

**Searchable vs Filterable - Critical Difference**:

| Aspect | Searchable | Filterable |
|--------|-----------|-----------|
| **Used by** | Global search box | Column filter inputs |
| **How** | Searched with LIKE across all searchable | Each has dedicated input |
| **Operator** | OR (any column matches) | AND (all filters match) |
| **Example** | User types "curr" → searches key AND admin_note | User filters key="currency" AND value_type="string" |

**SearchableColumns**:
```php
searchableColumns: ['key', 'admin_note']
```
- Only these columns participate in global search
- If column not here, can't be found by global search
- Repository must search all searchable columns with OR logic

**FilterableColumns**:
```php
filterableColumns: ['key', 'admin_note', 'value_type']
```
- Only these columns can have dedicated filters
- Form inputs only appear for filterable columns
- ListFilterResolver only allows these names
- Repository receives only validated filter names

**Must Include in ListCapabilities**:
```php
// ✓ CORRECT: Include column in capabilities before repository uses it
class SettingListCapabilities {
    public function filterableColumns() {
        return ['key', 'admin_note', 'value_type'];  // ← value_type is listed
    }
}

// Repository THEN implements:
if (isset($columnFilters['value_type'])) {  // Safe because ListCapabilities allows it
    $where[] = '`value_type` = :value_type';
}

// ✗ WRONG: Repository adds filter ListCapabilities didn't declare
if (isset($columnFilters['created_at'])) {  // Unsafe! Never validated!
    $where[] = '`created_at` >= :date';      // Bypass ListFilterResolver
}
```

**Template Reflects Capabilities**:
```twig
{# Filters appear only for filterable columns #}
<input id="filter-key" />           {# ✓ In filterableColumns #}
<input id="filter-admin-note" />   {# ✓ In filterableColumns #}
<input id="filter-value-type" />   {# ✓ In filterableColumns #}

{# NO input for created_at even if Repository supports it #}
{# created_at not in filterableColumns #}
```

**⚠️ CRITICAL**: Update Core Module's Repository

If the core module's repository (e.g., `PdoAdminSettingQueryRepository`) doesn't support the filters defined in `ListCapabilities`, add them:

```php
// In Modules/Settings/src/Admin/Setting/Infrastructure/Repository/PdoAdminSettingQueryRepository.php

if (isset($columnFilters['key'])) {
    $where[] = '`setting_key` LIKE :key';
    $params['key'] = '%' . $columnFilters['key'] . '%';
}

if (isset($columnFilters['admin_note'])) {
    $where[] = '`admin_note` LIKE :admin_note';
    $params['admin_note'] = '%' . $columnFilters['admin_note'] . '%';
}

if (isset($columnFilters['value_type'])) {
    $where[] = '`value_type` = :value_type';
    $params['value_type'] = $columnFilters['value_type'];
}
```

**Schema Classes**: Validate update/get requests
- Allow empty strings with: `v::stringType()->length(0, 255)`
- NOT `v::notEmpty()` (blocks valid values)
- This ensures settings can have empty string values if needed

### Step 4: Create API Controllers

**Pattern**: Controllers handle HTTP layer, inject Services for logic

**Controllers Required**:
- `SettingsListController` - Query with filters/search (returns paginated results)
- `SettingsGetController` - Get single setting by key
- `SettingsUpdateController` - Update setting value
- `SettingsDropdownController` - Get key-value pairs for dropdown lists

**What Each Controller Does**:

| Controller | Endpoint | Input | Output | Purpose |
|-----------|----------|-------|--------|---------|
| SettingsListController | POST /settings/query | ListQueryDTO (page, filters, search) | Paginated table data with pagination info | Admin list page table |
| SettingsGetController | POST /settings/get | `{setting_key: string}` | Single setting with all details | Fetch before edit modal |
| SettingsUpdateController | POST /settings/update | `{setting_key, value}` | Success/error response | Save setting changes |
| SettingsDropdownController | POST /settings/dropdown | Optional: `{search: string}` | Array of `{value, label}` | Populate select dropdowns |

**Use ValidationGuard**: Don't use try-catch

```php
$this->validationGuard->check(new SettingUpdateSchema(), $body);
```

**Example: SettingsListController Structure**

```php
class SettingsListController {
    public function __invoke(ServerRequestInterface $request): ResponseInterface {
        // 1. Extract body
        $body = $request->getParsedBody();
        
        // 2. Validate
        $this->validationGuard->check(new ListQuerySchema(), $body);
        
        // 3. Build DTO
        $dto = new ListQueryDTO($body['page'] ?? 1, $body['per_page'] ?? 25, $body['search'] ?? []);
        
        // 4. Call Service (NOT Repository directly)
        $result = $this->settingQueryService->list($dto);
        
        // 5. Return response
        return ApiHandler.json($response, [
            'data' => $result['data'],
            'pagination' => $result['pagination']
        ]);
    }
}
```

**⚠️ CRITICAL**: Controller NEVER calls Repository directly. Always use Service layer.

### Step 5: Create UI Controller

**Purpose**: Render Twig template with admin capabilities injected

**Process**:
1. Extract admin ID from request (via AdminContext)
2. Check admin's permissions for this feature
3. Build capabilities array
4. Pass to Twig template

**Example: SettingsListUiController**

```php
class SettingsListUiController {
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        AdminContext $adminContext,
        UiPermissionService $uiPermissionService
    ): ResponseInterface {
        // 1. Extract admin ID from context
        $adminId = $adminContext->getAdminId();
        if (!$adminId) {
            return ApiHandler.json($response, ['error' => 'Unauthorized'], 401);
        }
        
        // 2. Build capabilities based on admin's permissions
        $capabilities = [
            'can_view' => $uiPermissionService->hasPermission($adminId, 'settings.get.api'),
            'can_edit' => $uiPermissionService->hasPermission($adminId, 'settings.update.api'),
        ];
        
        // 3. Render template with capabilities
        return $this->twig->render($response, 'pages/settings/settings_list.twig', [
            'capabilities' => $capabilities,
        ]);
    }
}
```

**AdminContext**: Provides authenticated admin information
- `getAdminId()` - ID of logged-in admin
- `getAdmin()` - Full admin object (name, email, etc)

**UiPermissionService**: Checks if admin has permission
- `hasPermission($adminId, 'route.name.api')` - Returns boolean
- Only checks those permissions bound to that admin's roles

**Capabilities Array**: Lists what admin can do
- Must match what UI JavaScript checks
- Each key = one permission check
- Values = boolean true/false

### Step 6: Create Routes

**SettingsApiRoutes.php**: Register API endpoints

```php
<?php
namespace Maatify\SettingsSlim\Admin\Http\Routes;

use Slim\Routing\RouteCollectorProxy;
use Maatify\SettingsSlim\Admin\Http\Controllers\Api\{
    SettingsListController,
    SettingsGetController,
    SettingsUpdateController,
    SettingsDropdownController
};
use Maatify\AdminKernel\Security\Middleware\AuthorizationGuardMiddleware;

class SettingsApiRoutes {
    public static function register(RouteCollectorProxy $group): void {
        $group->post('/settings/query', SettingsListController::class)
            ->setName('settings.list.api')
            ->add(AuthorizationGuardMiddleware::class);
            
        $group->post('/settings/get', SettingsGetController::class)
            ->setName('settings.get.api')
            ->add(AuthorizationGuardMiddleware::class);
            
        $group->post('/settings/update', SettingsUpdateController::class)
            ->setName('settings.update.api')
            ->add(AuthorizationGuardMiddleware::class);
            
        $group->post('/settings/dropdown', SettingsDropdownController::class)
            ->setName('settings.dropdown.api')
            ->add(AuthorizationGuardMiddleware::class);
    }
}
```

**Key Rules**:
- POST endpoints (queries, mutations)
- `setName()` MUST match key in SettingAdminPermissionMapProvider
- `add(AuthorizationGuardMiddleware::class)` LAST (executes first)
- Route path matches what JS sends in ApiHandler calls

**SettingsUiRoutes.php**: Register UI page

```php
<?php
namespace Maatify\SettingsSlim\Admin\Http\Routes;

use Slim\Routing\RouteCollectorProxy;
use Maatify\SettingsSlim\Admin\Http\Controllers\Ui\SettingsListUiController;
use Maatify\AdminKernel\Security\Middleware\AuthorizationGuardMiddleware;

class SettingsUiRoutes {
    public static function register(RouteCollectorProxy $group): void {
        $group->get('/settings', SettingsListUiController::class)
            ->setName('settings.list.ui')
            ->add(AuthorizationGuardMiddleware::class);
    }
}
```

**UI Route vs API Route**:
| Aspect | UI Route | API Route |
|--------|----------|-----------|
| Method | GET | POST |
| Returns | HTML (Twig rendered) | JSON |
| Has middleware | Yes, AuthorizationGuard | Yes, AuthorizationGuard |
| Uses permission check | Yes | Yes |
| Example | GET /settings | POST /settings/query |

### Step 7: Create Twig Templates

**📍 Location**: `app/Modules/AdminKernel/Templates/pages/[module]/[page].twig`

Example: `app/Modules/AdminKernel/Templates/pages/settings/settings_list.twig`

**📚 Template Inheritance Hierarchy**

```
layouts/base.twig (root layout)
    ↑ extends
    ↑
layouts/[something].twig (optional middle layer)
    ↑ extends
    ↑
pages/settings/settings_list.twig (your page)
```

**🏗️ Template Structure (Required Blocks)**

```twig
{% extends "layouts/base.twig" %}

{# 1. Page Title - appears in browser tab #}
{% block title %}
    Settings | {{ ui.appName }}
{% endblock %}

{# 2. Main Content #}
{% block content %}
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <h2>Settings Management</h2>
        
        <!-- Breadcrumb Navigation -->
        <nav>
            <ol class="flex gap-1">
                <li><a href="{{ ui.adminUrl }}dashboard">Home</a></li>
                <li>Settings</li>
            </ol>
        </nav>
    </div>

    <!-- JavaScript Capabilities Injection -->
    <script>
        window.settingsCapabilities = {
            can_view: {{ capabilities.can_view ?? false ? 'true' : 'false' }},
            can_edit: {{ capabilities.can_edit ?? false ? 'true' : 'false' }}
        };
        window.settingsApi = {
            query: 'settings/query',
            get: 'settings/get',
            update: 'settings/update'
        };
    </script>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg p-6 mb-6">
        <form id="settings-filter-form">
            <!-- Each filter input's ID must match what JS reads -->
            <input id="filter-key" placeholder="..." />
            <input id="filter-admin-note" placeholder="..." />
            
            <!-- Buttons -->
            <button type="submit">Search</button>
            <button type="button" id="settings-reset-filters">Reset</button>
        </form>
    </div>

    <!-- Global Search Bar -->
    <div class="bg-white rounded-lg p-4 mb-4">
        <input id="settings-search" type="text" 
               placeholder="Quick search by key or admin note..." />
    </div>

    <!-- Table Container - JS renders here -->
    <div id="settings-table-container" class="w-full"></div>
{% endblock %}

{# 3. Scripts Block - infrastructure + page-specific JS #}
{% block scripts %}
    <script src="{{ asset('assets/maatify/admin-kernel/js/api_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/data_table.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/settings_list.js') }}"></script>
{% endblock %}
```

**🔑 Key Twig Syntax & Features**

| Syntax | Purpose | Example |
|--------|---------|---------|
| `{{ variable }}` | Output variable | `{{ ui.appName }}` |
| `{{ var \|\| 'default' }}` | Default value | `{{ label \|\| '-' }}` |
| `{{ var ?? false ? 'true' : 'false' }}` | Null coalescing + ternary | `{{ capabilities.can_edit ?? false ? 'true' : 'false' }}` |
| `{% if condition %}...{% endif %}` | Conditional | `{% if can_create %}...{% endif %}` |
| `{% for item in items %}...{% endfor %}` | Loop | `{% for role in roles %}...{% endfor %}` |
| `{% block name %}...{% endblock %}` | Define block for child templates | See example above |
| `{% extends "template" %}` | Inherit from parent | `{% extends "layouts/base.twig" %}` |

**💡 Capabilities Injection - Critical Pattern**

```twig
<script>
    window.settingsCapabilities = {
        can_view: {{ capabilities.can_view ?? false ? 'true' : 'false' }},
        can_edit: {{ capabilities.can_edit ?? false ? 'true' : 'false' }}
    };
</script>
```

**⚠️ Do NOT use**: `{{ capabilities|json_encode|raw }}` — this is unsafe and doesn't provide null coalescing protection.

Then in JavaScript:
```javascript
if (window.settingsCapabilities.can_edit) {
    // Show edit button
}
```

**⚠️ Critical Rules for Filter Inputs**

| Rule | Why | Example |
|------|-----|---------|
| Input ID must match JS `getElementById()` | Otherwise JS can't find it | `id="filter-key"` → `getElementById('filter-key')` |
| Filter field name must match ListCapabilities | Otherwise API doesn't recognize it | `filterableColumns: ['key']` → JS sends `{ key: "value" }` |
| Placeholder must match API search scope | Users get confused otherwise | If API searches in `key` and `admin_note`, say "by key or admin note" |
| Use `ui.adminUrl` for internal links | Theme/routing aware | `href="{{ ui.adminUrl }}dashboard"` |
| Use `asset()` for static files | Cache busting + CDN support | `src="{{ asset('assets/.../file.js') }}"` |

**🎨 Tailwind CSS + Dark Mode**

All templates use Tailwind classes with dark mode support:
```twig
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
    Light: white bg, dark text
    Dark: gray-800 bg, light text
</div>
```

**📦 Available Template Variables**

Passed from controller to template:
```php
return $this->twig->render($response, 'pages/settings/settings_list.twig', [
    'capabilities' => $capabilities,  // Your app-specific capabilities
    'ui' => [/*...*/],               // Global UI helpers (appName, adminUrl, etc)
]);
```

### Step 8: Create JavaScript File

#### 8.1: Complete Role & Responsibilities

**JavaScript Role**: **THE ENTIRE USER EXPERIENCE** - من الـ click الأول لحد الـ save الأخير

**ما الـ JavaScript يتعامل معه بالضبط:**

```
┌─────────────────────────────────────────┐
│  USER INTERACTIONS (الـ User يعمل ايه)   │
├─────────────────────────────────────────┤
│ • Click Search/Filter buttons           │
│ • Type in filter inputs                 │
│ • Click pagination buttons              │
│ • Click Edit buttons                    │
│ • Type new value in modal               │
│ • Click Save/Cancel in modal            │
└─────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────┐
│  JAVASCRIPT LISTENERS (JavaScript يسمع) │
├─────────────────────────────────────────┤
│ • DOMContentLoaded (page loaded)        │
│ • Click events (all buttons)            │
│ • Keypress events (filter inputs)       │
│ • Submit events (filter form)           │
│ • Table pagination events               │
└─────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────┐
│  STATE MANAGEMENT (JavaScript يحفظ)      │
├─────────────────────────────────────────┤
│ • currentPage = 1                       │
│ • currentPerPage = 25                   │
│ • Filter values (key, admin_note, etc) │
│ • Search term                           │
└─────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────┐
│  BUILD REQUEST OBJECT (JavaScript يبني) │
├─────────────────────────────────────────┤
│ {                                       │
│   page: 1,                              │
│   per_page: 25,                         │
│   search: {                             │
│     global: "search term",              │
│     columns: { key: "value" }           │
│   }                                     │
│ }                                       │
└─────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────┐
│  API CALLS (JavaScript يستدعي)          │
├─────────────────────────────────────────┤
│ POST /settings/query → Get table data   │
│ POST /settings/get → Get single item    │
│ POST /settings/update → Save changes    │
│ POST /settings/dropdown → Get options   │
└─────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────┐
│  DOM MANIPULATION (JavaScript يرسم)     │
├─────────────────────────────────────────┤
│ • Render table rows                     │
│ • Update pagination info                │
│ • Show/hide edit modal                  │
│ • Show success/error alerts             │
│ • Highlight/disable buttons             │
└─────────────────────────────────────────┘
```

### JavaScript Structure in settings_list.js

**Initialize (التهيئة)**:
```javascript
document.addEventListener('DOMContentLoaded', () => {
    // 1. Get all DOM elements (filter inputs, buttons, table container)
    const filterForm = document.getElementById('settings-filter-form');
    const globalSearchInput = document.getElementById('settings-search');
    const tableContainer = document.getElementById('settings-table-container');
    
    // 2. Initialize state
    let currentPage = 1;
    let currentPerPage = 25;
    
    // 3. Define table structure
    const headers = ['Key', 'Admin Note', 'Value', 'Type', 'Actions'];
    const rowKeys = ['setting_key', 'admin_note', 'setting_value', 'value_type', 'actions'];
    
    // 4. Define how to render each cell (escape HTML for security)
    const customRenderers = { ... };
    
    // 5. Load table for first time
    loadTable();
});
```

**Listen to Events (الاستماع للـ أحداث)**:
```javascript
// User submits filter form
filterForm.addEventListener('submit', (e) => {
    e.preventDefault();
    currentPage = 1;  // Reset to page 1
    loadTable();      // Reload with new filters
});

// User clicks search button
globalSearchBtn.addEventListener('click', () => {
    currentPage = 1;
    loadTable();
});

// User presses Enter in global search
globalSearchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        currentPage = 1;
        loadTable();
    }
});

// Table fires pagination event
document.addEventListener('tableAction', (e) => {
    if (e.detail.action === 'pageChange') {
        currentPage = e.detail.value;
        loadTable();
    }
});

// User clicks Edit button
document.addEventListener('click', (e) => {
    const editBtn = e.target.closest('.btn-edit-setting');
    if (editBtn) {
        const key = editBtn.dataset.key;
        openEditModal(key, ...);
    }
});
```

**Build & Send Request (بناء و إرسال الطلب)**:
```javascript
const loadTable = () => {
    // 1. Gather current filter values
    const filters = {
        key: document.getElementById('filter-key').value,
        admin_note: document.getElementById('filter-admin-note').value,
        value_type: document.getElementById('filter-value-type').value
    };
    
    // 2. Remove empty filters
    Object.keys(filters).forEach(k => filters[k] === "" && delete filters[k]);
    
    // 3. Gather search term
    const globalSearch = globalSearchInput.value.trim();
    
    // 4. Build request object
    const params = {
        page: currentPage,
        per_page: currentPerPage
    };
    
    const search = {};
    if (globalSearch) search.global = globalSearch;
    if (Object.keys(filters).length > 0) search.columns = filters;
    
    if (Object.keys(search).length > 0) {
        params.search = search;
    }
    
    // 5. Call API
    createTable(
        window.settingsApi.query,  // Endpoint: '/settings/query'
        params,                     // Request body
        headers,                    // Column headers
        rowKeys,                    // What properties to display
        false,                      // withSelection: false
        'setting_key',              // primaryKey
        null,                       // onSelectionChange
        customRenderers,            // How to render cells
        null,                       // selectableIds
        getPaginationInfo           // Pagination formatter
    );
};
```

**Handle Response (التعامل مع الرد)**:
```javascript
// createTable() internally:
// 1. Makes API call
// 2. Gets response: { data: [...], pagination: {...} }
// 3. Renders rows using customRenderers
// 4. Displays pagination info
// 5. Attaches event listeners to buttons
```

**Render Modal (فتح مودال)**:
```javascript
window.openEditModal = (key, value, type, adminNote) => {
    // 1. Escape values for HTML safety
    const safeKey = escapeHtml(key);
    const safeValue = escapeHtml(value);
    
    // 2. Build HTML modal with escaped values
    const modalContent = `
        <div class="p-6">
            <form id="edit-setting-form">
                <input type="text" value="${safeKey}" disabled />
                <textarea>${safeValue}</textarea>
            </form>
        </div>
    `;
    
    // 3. Insert modal into DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // 4. Listen to Save button
    submitBtn.addEventListener('click', async () => {
        const newValue = valueInput.value;  // NO trim()!
        
        // 5. Call update API
        const result = await ApiHandler.call(window.settingsApi.update, {
            setting_key: key,
            value: newValue
        });
        
        // 6. Handle response
        if (result.success) {
            ApiHandler.showAlert('success', 'Updated!');
            loadTable();  // Reload to show changes
            modalEl.remove();  // Close modal
        } else {
            ApiHandler.showAlert('danger', result.error);
        }
    });
};
```

**XSS Protection (الحماية من الـ XSS)**:
```javascript
const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
}[char]));

// APPLY escapeHtml EVERYWHERE:
// 1. In custom renderers (when building table cells)
// 2. In modal content (before inserting into DOM)
// 3. NOT on search input (search is text, not HTML)
```

### What JavaScript Does NOT Do

```
❌ JavaScript does NOT:
- Validate data (that's PHP/API's job)
- Check permissions (that's middleware's job)
- Decrypt data (that's backend's job)
- Connect to database directly
- Create users or change roles
- Run business logic
- Make decisions (just displays UI for user to decide)

✓ JavaScript ONLY:
- Listens to user clicks/typing
- Builds request objects
- Calls APIs with requests
- Formats and displays responses
- Shows modals and alerts
- Renders tables and pagination
```

### Full Event Flow (الـ Flow الكامل)

```
User loads page (/settings)
    ↓
Server renders Twig template
    ↓
Template includes settings_list.js
    ↓
JavaScript runs DOMContentLoaded
    ↓
loadTable() called with default page=1
    ↓
JavaScript builds: { page: 1, per_page: 25, search: {} }
    ↓
ApiHandler.call('/settings/query', params)
    ↓
Server returns: { data: [...], pagination: {...} }
    ↓
JavaScript renders table rows (escaping HTML)
    ↓
User sees table with settings
    ↓
User types in "key" filter input → value = "main"
    ↓
User clicks Search button
    ↓
JavaScript builds: { page: 1, per_page: 25, search: { columns: { key: "main" } } }
    ↓
ApiHandler.call('/settings/query', params)
    ↓
Server filters by key="main", returns filtered data
    ↓
JavaScript renders new table (only matching rows)
    ↓
User sees filtered results
    ↓
User clicks Edit on a row
    ↓
JavaScript extracts key/value from button's dataset
    ↓
openEditModal() renders modal with form
    ↓
User types new value in modal textarea
    ↓
User clicks Save button
    ↓
JavaScript gets textarea value: "new_value" (NO trim!)
    ↓
ApiHandler.call('/settings/update', { setting_key: "...", value: "new_value" })
    ↓
Server validates + updates database
    ↓
Server returns: { success: true }
    ↓
JavaScript shows "Updated successfully" alert
    ↓
loadTable() reloads data
    ↓
JavaScript renders table with new value
    ↓
Modal closes
    ↓
User sees updated table
```

### JavaScript Dependencies (الـ استدعيات التي تحتاجها)

**Must Have in Template**:
```twig
{# window.settingsCapabilities - passed from PHP #}
<script>
    window.settingsCapabilities = { can_edit: true, ... };
    window.settingsApi = {
        query: 'settings/query',
        get: 'settings/get',
        update: 'settings/update'
    };
</script>

{# Shared infrastructure #}
<script src="{{ asset('assets/maatify/admin-kernel/js/api_handler.js') }}"></script>
<script src="{{ asset('assets/maatify/admin-kernel/js/data_table.js') }}"></script>

{# Page-specific logic #}
<script src="{{ asset('assets/maatify/admin-kernel/js/pages/settings_list.js') }}"></script>
```

**Uses From ApiHandler**:
- `ApiHandler.call(endpoint, data)` - Make API request
- `ApiHandler.showAlert(type, message)` - Show notification

**Uses From DataTable**:
- `createTable(endpoint, params, ...)` - Render table with pagination

**Uses From AdminUIComponents**:
- `AdminUIComponents.buildModalTemplate()` - Create modal HTML
- `AdminUIComponents.buildActionButton()` - Create action buttons
- `AdminUIComponents.SVGIcons` - Icon SVGs

### JavaScript Testing Checklist

- [ ] Page loads, table renders with data
- [ ] Typing in filter updates `currentPerPage` on blur
- [ ] Clicking Search button calls API with filters
- [ ] Pagination buttons call API with correct page
- [ ] Edit button extracts data correctly from dataset
- [ ] Modal shows with escaped values (no HTML tags visible)
- [ ] Save button sends request without trim()ing value
- [ ] Success alert shows, table reloads
- [ ] Error alert shows when API fails
- [ ] XSS: Input `<script>alert('xss')</script>` in value → stored as text, not executed

#### 8.2: XSS Protection

**🔒 XSS Prevention**: Escape all HTML values

```javascript
const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
}[char]));
```

**Apply escapeHtml in**:
1. **Custom Renderers**: Escape `setting_key`, `admin_note`, `setting_value`, `value_type`
   ```javascript
   setting_key: (value) => {
       const safeValue = escapeHtml(value);
       return `<span class="...">${safeValue}</span>`;
   }
   ```

2. **Modal Content**: Escape all data before inserting into HTML
   ```javascript
   const safeKey = escapeHtml(key);
   const safeValue = escapeHtml(value);
   const safeAdminNote = escapeHtml(adminNote);
   
   const modalContent = `
       <input type="text" value="${safeKey}" disabled>
       <textarea>${safeValue}</textarea>
   `;
   ```

**⚠️ Critical Details**:
- **NO `trim()` on values** → Preserves intentional whitespace in settings
- Use `trim()` ONLY on search input before sending to API
- Values get validated at API layer (Respect\Validation)
- Store raw values in database as-is

**Pattern**: 
- Use `createTable()` from shared infrastructure
- Handle filter/search events
- Modal using `AdminUIComponents.buildModalTemplate()`
- Listen to edit buttons with event delegation
- API calls via `ApiHandler.call()`

**Error Handling in JavaScript**

```javascript
// Bad: Bare try-catch (hides real errors)
try {
    const result = await ApiHandler.call(endpoint, data);
} catch (e) {
    console.log('Error');
}

// Good: Handle actual response
const result = await ApiHandler.call(window.settingsApi.update, {
    setting_key: key,
    value: newValue
});

if (result.success) {
    ApiHandler.showAlert('success', 'Setting updated successfully');
    loadTable();
} else {
    // result.error contains server error message
    ApiHandler.showAlert('danger', result.error || 'Failed to update');
}
```

**ApiHandler.call() Returns**:
- `{ success: true, data: {...} }` - Valid response
- `{ success: false, error: "message" }` - Server rejected
- Throws if network error
- Always check `result.success` before using `result.data`

**Common Errors to Handle**:
```javascript
// Validation error from server
{ success: false, error: "Value exceeds maximum length" }

// Permission error
{ success: false, error: "Unauthorized" }

// Database error
{ success: false, error: "Database error occurred" }
```

### Step 9: Register with Main App

**public/index.php**:
```php
use Maatify\SettingsSlim\Admin\Security\SettingAdminPermissionPackage;
$permissionPackages = [new SettingAdminPermissionPackage()];
```

**Route Files**:
- `ApiProtectedRoutes.php` → `SettingsApiRoutes::register($group);`
- `UiProtectedRoutes.php` → `SettingsUiRoutes::register($group);`

**Database**: Seed permissions

```bash
# Option 1: Direct MySQL
mysql -u [user] -p [database] < Modules/SettingsSlim/permissions_seed.sql

# Option 2: Through PHP
php -r "
  \$pdo = new PDO('mysql:host=localhost;dbname=athar', 'user', 'pass');
  \$sql = file_get_contents('Modules/SettingsSlim/permissions_seed.sql');
  \$pdo->exec(\$sql);
  echo 'Permissions seeded successfully';
"
```

**Verify Permissions Were Created**:
```sql
SELECT * FROM permissions WHERE name IN ('settings.list', 'settings.view', 'settings.edit');
```

Should return 3 rows. If empty → seed didn't run or had SQL error

---

## 🔐 Permissions & Roles System

### Permission Hierarchy

```
Super Admin
    ↓
(Has all permissions automatically)

Regular Admin
    ↓
Assigned to Roles
    ↓
Roles have specific Permissions
    ↓
Can access routes that match those permissions
```

### How Permission Check Works

1. **Admin tries to access route** (e.g., GET `/settings`)
2. **Route has middleware**: `AuthorizationGuardMiddleware`
3. **Middleware checks**: 
   - Is user authenticated? ✓
   - Does user's role have the required permission?
     - Look up route name: `settings.list.ui`
     - Look in `SettingAdminPermissionMapProvider`: `settings.list.ui` → `settings.list`
     - Check if admin's role has `settings.list` permission
4. **If permission exists**: ✓ Allow access
5. **If permission missing**: ✗ Return 403 Forbidden

### Without permissions_seed.sql

- Routes exist and are registered ✓
- Route names are mapped ✓
- But permission names don't exist in database
- Admin has no way to get that permission
- Result: Everyone gets 403 Forbidden

### After permissions_seed.sql

- Permission records exist in database ✓
- Admin role can be assigned these permissions ✓
- Admins with the role can access routes ✓

---

## 🔗 How It All Works Together

### Data Flow: Filter → API → Service → Repository

```
User Input (JS Filter Form)
    ↓
JavaScript builds: 
{
    page: 1,
    per_page: 25,
    search: {
        global: "search text",      (optional - searches key + admin_note)
        columns: {
            key: "main",            (optional - exact match on setting_key)
            admin_note: "text"      (optional - LIKE match on admin_note)
        }
    }
}
    ↓
API Endpoint receives: POST /settings/query
    ↓
Controller validates with ValidationGuard
    ↓
Controller builds ListQueryDTO from body
    ↓
ListFilterResolver processes using ListCapabilities:
  - **Critical**: Validates filter names AGAINST ListCapabilities
  - If 'key' in ListCapabilities.filterableColumns → PASS
  - If 'badcolumn' NOT in ListCapabilities → REJECT
  - Prevents SQL injection by only allowing defined columns
  - Passes validated columnFilters to Service
    ↓
Service calls Repository.list(columnFilters: { key: "value" })
    ↓
Repository.list() implementation:
  - Checks if columnFilters['key'] exists
  - If yes: adds WHERE `setting_key` LIKE '%value%'
  - If no: skips that filter
  - Builds final SQL with all active filters
  - Returns filtered + paginated results
    ↓
Response with filtered data
    ↓
JavaScript renders table with results
```

**Key Integration Points**:
1. **ListCapabilities defines what CAN be filtered**
2. **ListFilterResolver validates filter names against ListCapabilities** ← SECURITY LAYER
3. **Repository implements actual SQL filtering** ← DATA ACCESS LAYER
4. **If any step is missing or mismatched → filters fail silently**

### ListFilterResolver Deep Dive

**What It Does**:
- Acts as gatekeeper between frontend and database
- Validates that only defined filters are used
- Prevents arbitrary column names from reaching Repository

**Example Flow**:

```
ListCapabilities says: filterableColumns: ['key', 'admin_note', 'value_type']

Frontend sends: { columns: { key: "test", admin_note: "foo" } }
    ↓
ListFilterResolver checks:
    - Is 'key' in filterableColumns? YES ✓
    - Is 'admin_note' in filterableColumns? YES ✓
    ↓
ListFilterResolver passes: { key: "test", admin_note: "foo" }
    ↓
Repository receives validated filters only

---

Frontend sends: { columns: { key: "test", hacked_col: "drop table" } }
    ↓
ListFilterResolver checks:
    - Is 'key' in filterableColumns? YES ✓
    - Is 'hacked_col' in filterableColumns? NO ✗
    ↓
ListFilterResolver REJECTS or REMOVES unknown columns
    ↓
Repository receives: { key: "test" } (unsafe column removed)
```

**If ListCapabilities Missing a Column**:
1. Frontend can't filter by it
2. JS doesn't know column name
3. ListCapabilities must define it first
4. THEN Repository implements the filter

**If Repository Missing Filter**:
1. ListCapabilities allows it
2. ListFilterResolver validates it
3. But Repository doesn't implement it
4. Filter gets silently ignored (data returns unfiltered)

### ListQueryDTO Structure

**What is it?**: Data Transfer Object that holds pagination + filter info

**Created From Request Body**:
```javascript
// Frontend sends this to POST /settings/query
{
    page: 1,
    per_page: 25,
    search: {
        global: "search text",
        columns: {
            key: "value"
        }
    }
}
```

**Controller Builds DTO**:
```php
$dto = new ListQueryDTO(
    page: 1,
    perPage: 25,
    search: [
        'global' => 'search text',
        'columns' => ['key' => 'value']
    ]
);
```

**DTO Properties**:
- `page` - Current page number (1-indexed)
- `perPage` - Results per page (25, 50, 100, etc)
- `search` - Array with `global` and `columns`
  - `global` - Text searched in all searchable columns
  - `columns` - Filtered values for specific columns

**Used By Service**:
```php
public function list(ListQueryDTO $dto): array {
    $columnFilters = $dto->search['columns'] ?? [];
    $globalSearch = $dto->search['global'] ?? '';
    
    // Validate filters against ListCapabilities
    $filters = $this->filterResolver->resolve($columnFilters);
    
    // Pass to repository
    return $this->repository->list(
        page: $dto->page,
        perPage: $dto->perPage,
        columnFilters: $filters,
        globalSearch: $globalSearch
    );
}
```

---

## ✅ Checklist: Complete Slim Module

### File Creation
- [ ] `Modules/SettingsSlim/src/Admin/Security/SettingAdminPermissionMapProvider.php`
- [ ] `Modules/SettingsSlim/src/Admin/Security/SettingAdminPermissionPackage.php`
- [ ] `Modules/SettingsSlim/permissions_seed.sql`
- [ ] `Modules/SettingsSlim/src/Admin/Domain/List/SettingListCapabilities.php`
- [ ] `Modules/SettingsSlim/src/Admin/Domain/Validation/SettingUpdateSchema.php`
- [ ] `Modules/SettingsSlim/src/Admin/Domain/Validation/SettingGetSchema.php`
- [ ] `Modules/SettingsSlim/src/Admin/Http/Controllers/Api/SettingsListController.php`
- [ ] `Modules/SettingsSlim/src/Admin/Http/Controllers/Api/SettingsGetController.php`
- [ ] `Modules/SettingsSlim/src/Admin/Http/Controllers/Api/SettingsUpdateController.php`
- [ ] `Modules/SettingsSlim/src/Admin/Http/Controllers/Api/SettingsDropdownController.php`
- [ ] `Modules/SettingsSlim/src/Admin/Http/Controllers/Ui/SettingsListUiController.php`
- [ ] `Modules/SettingsSlim/src/Admin/Http/Routes/SettingsApiRoutes.php`
- [ ] `Modules/SettingsSlim/src/Admin/Http/Routes/SettingsUiRoutes.php`
- [ ] `app/Modules/AdminKernel/Templates/pages/settings/settings_list.twig`
- [ ] `public/assets/maatify/admin-kernel/js/pages/settings_list.js`

### Core Module Updates
- [ ] Updated Repository to support ListCapabilities filters
- [ ] Validation schemas use correct rules (allow empty strings)

### Database Seeds
- [ ] Created `permissions_seed.sql` in module root
- [ ] Permission names match exactly with PermissionMapProvider
- [ ] Used `INSERT IGNORE` to prevent duplicate key errors
- [ ] Ran seed file: `mysql [db] < Modules/[Module]Slim/permissions_seed.sql`
- [ ] Verified permissions in DB: `SELECT * FROM permissions WHERE name LIKE '[module].%'`

### Integration
- [ ] Registered PermissionPackage in `public/index.php`
- [ ] Registered API routes in `ApiProtectedRoutes.php`
- [ ] Registered UI routes in `UiProtectedRoutes.php`
- [ ] Base permissions exist in database

### Testing & Verification

**Basic Operations**:
- [ ] All CRUD operations working (List, Get, Update)
- [ ] Filters working correctly (key, admin_note, value_type)
- [ ] Global search working
- [ ] Empty string values allowed on update
- [ ] XSS protection verified (no HTML injection)
- [ ] Placeholder text matches actual search scope

**Permission Testing**:

```sql
-- 1. Verify permissions exist in database
SELECT * FROM permissions WHERE name IN ('settings.list', 'settings.view', 'settings.edit');
-- Should return 3 rows

-- 2. Create test role for settings
INSERT INTO roles (name, display_name) VALUES ('settings_admin', 'Settings Admin');

-- 3. Assign permissions to role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'settings_admin' AND p.name IN ('settings.list', 'settings.view', 'settings.edit');

-- 4. Assign role to test admin
INSERT INTO admin_roles (admin_id, role_id)
SELECT [test_admin_id], id FROM roles WHERE name = 'settings_admin';
```

**Browser Testing**:
1. **With permissions**: Log in as admin with settings permissions
   - Can see settings page ✓
   - Can filter/search ✓
   - Can edit settings ✓
   
2. **Without permissions**: Log in as admin without settings
   - GET /settings → 403 Forbidden
   - POST /settings/query → 403 Forbidden
   - Page not accessible

3. **Partial permissions** (only view, not edit):
   - Can see page ✓
   - Filter/search works ✓
   - Edit button hidden (JS checks `can_edit`) ✓
   - POST /settings/update → 403 Forbidden

---

## 🚨 Critical Mistakes to Avoid

| ❌ Mistake | ✅ Fix |
|-----------|--------|
| Using `{{ capabilities\|json_encode\|raw }}` | Use explicit extraction: `{{ capabilities.can_edit ?? false ? 'true' : 'false' }}` for each capability |
| Seed file with wrong columns: `created_at, updated_at` | Use correct columns: `name, display_name, description` |
| Duplicating core logic | Inject the Service from core module |
| `v::notEmpty()` for empty-allowed values | Use `v::stringType()->length(0, 255)` |
| Using `trim()` on stored values | Only trim search input, NOT value being saved |
| Not escaping HTML in JS | Use `escapeHtml()` function everywhere |
| Business logic in controllers | Controllers = HTTP layer only |
| Forgetting to register routes | Check API + UI route registration |
| Wrong column names in filters | Must match database schema exactly |
| Missing `setName()` on routes | Names must match permission map |
| Using try-catch instead of ValidationGuard | Use `$this->validationGuard->check()` |
| ListCapabilities not matching repository | Update core repo to support all filters defined |
| HTML injection in modal content | Always escape via `escapeHtml()` before rendering |
| Filter/search not working | Check: (1) ListCapabilities columns (2) Repository supports them (3) Column names match DB schema |
| Placeholder text misleading | Placeholder must reflect actual search scope (e.g., "key or admin note", NOT "value") |
| Forgetting to run permissions_seed.sql | Routes work but no admin can access them (permission check fails silently) |
| Permission names in seed don't match PermissionMapProvider | Route has permission "settings.list" but seed creates "setting.list" → mismatch |
| Seed display_name too verbose | Use concise format: "List Settings", "View Settings", "Edit Settings" |
| Not implementing global search in repository | Global search searches multiple columns at once, not defined in ListCapabilities |
| Confusing global search with column filters | Global searches all searchable columns; column filters search specific column |

### Global Search vs Column Filters

**Global Search**:
- Searches across multiple columns (defined in ListCapabilities.searchableColumns)
- User types once, searches `key` AND `admin_note` simultaneously
- Example: searching "curr" finds both setting_key="currency_code" AND admin_note="Current Rate"

**Column Filters**:
- Each filter targets one specific column
- User filters by exact column
- Example: filter key="currency" finds only setting_key="currency_code"

**Repository Implementation**:
```php
// In Repository.list()
if (!empty($globalSearch)) {
    // Global search: search in BOTH searchable columns
    $where[] = '(`setting_key` LIKE :global_key OR `admin_note` LIKE :global_admin_note)';
    $params['global_key'] = '%' . $globalSearch . '%';
    $params['global_admin_note'] = '%' . $globalSearch . '%';
}

// Column filters: search specific columns only
if (isset($columnFilters['key'])) {
    $where[] = '`setting_key` LIKE :key';
    $params['key'] = '%' . $columnFilters['key'] . '%';
}
```

**JavaScript Usage**:
```javascript
const params = {
    page: 1,
    per_page: 25
};

const search = {};

// Global search (global input)
if (globalSearchInput.value.trim()) {
    search.global = globalSearchInput.value.trim();
}

// Column filters (filter form)
if (filterKey.value) {
    search.columns = search.columns || {};
    search.columns.key = filterKey.value;
}

if (Object.keys(search).length > 0) {
    params.search = search;
}

// Final request: POST /settings/query with params
```

---

## 📚 Real Examples: SettingsSlim vs CurrencySlim

### SettingsSlim: Simple Wrapper

**Scope**: Read-only + Update operations only

```
API Endpoints:
  - POST /settings/query        (list with filters)
  - POST /settings/get          (get single)
  - POST /settings/update       (update value)
  - POST /settings/dropdown     (key-value pairs)

Controllers: 4
Validation Schemas: 2
UI Pages: 1
Capabilities: 2 (can_view, can_edit)
```

**Use When**: Core module only needs admin list/get/update wrappers

### CurrencySlim: Complex Module

**Scope**: Full CRUD + Extra features + Nested resources

```
API Endpoints:
  - POST /currencies/query              (list)
  - POST /currencies/create             (create)
  - POST /currencies/update             (update)
  - POST /currencies/set-active         (toggle active)
  - POST /currencies/update-sort-order  (reorder)
  - POST /currencies/dropdown           (for dropdowns)
  - POST /currencies/translations/query (nested: list translations)
  - POST /currencies/translations/upsert
  - POST /currencies/translations/delete

Controllers: 9
Validation Schemas: 8
UI Pages: 2 (main list + translations)
Capabilities: 5 (can_create, can_update, can_active, can_update_sort, can_view_translations)
```

**Key Differences**:
| Feature | SettingsSlim | CurrencySlim |
|---------|--------------|--------------|
| Create operation | ❌ No | ✅ Yes |
| Update sorting | ❌ No | ✅ Yes |
| Toggle active status | ❌ No | ✅ Yes |
| Nested resources | ❌ No | ✅ Yes (Translations) |
| Multiple UI pages | ❌ One | ✅ Two |
| Validation complexity | Low | High |
| Dropdown endpoint | ✅ Yes | ✅ Yes |

**Dropdown vs Query Endpoints**:

| Aspect | `/settings/query` | `/settings/dropdown` |
|--------|------------------|---------------------|
| **Purpose** | Admin list page table | Select/autocomplete inputs |
| **Returns** | Full paginated table data | Simple key-value pairs |
| **Fields** | All: key, value, type, note | Just: value, label |
| **Pagination** | Yes (pages/limits) | No (all items or search results) |
| **Filters** | Full ListCapabilities | Usually just search |
| **Example** | `{ data: [{key, value, type, admin_note}], pagination: {...} }` | `{ data: [{value: "app_version", label: "App Version"}] }` |
| **Use Case** | DataTables with pagination | `<select>`, autocomplete fields |

**Dropdown Implementation**:
```php
class SettingsDropdownController {
    public function __invoke(ServerRequestInterface $request): ResponseInterface {
        $body = $request->getParsedBody();
        $search = $body['search'] ?? '';
        
        // Get key-value pairs, optionally filtered by search
        $results = $this->settingService->dropdown($search);
        
        return ApiHandler.json($response, [
            'data' => $results  // [{ value: 'key', label: 'Admin Note or Key' }, ...]
        ]);
    }
}
```

**When to Use Each**:
- **Query**: Admin dashboard table with full details
- **Dropdown**: Multi-select forms, dependent fields, autocomplete
- Both use same core service but return different formats

**When Building Your Module**:
- If simple like Settings → Use SettingsSlim as template
- If complex like Currencies → Use CurrencySlim as template
- **Always check**: Does core module support all operations you need?

### Study Both

```bash
# Compare structures
diff -r Modules/SettingsSlim/src/ Modules/CurrencySlim/src/

# Read both templates
cat app/Modules/AdminKernel/Templates/pages/settings/settings_list.twig
cat app/Modules/AdminKernel/Templates/pages/currencies/currencies_list.twig

# Read both JS files
cat public/assets/maatify/admin-kernel/js/pages/settings_list.js
ls public/assets/maatify/admin-kernel/js/pages/currencies/
```

---

## 🔧 Service Injection & Dependency Binding

**How Controllers Get Services**:

Controllers don't create services themselves. They receive them via constructor injection:

```php
class SettingsListController {
    public function __construct(
        private SettingQueryService $settingService,
        private ValidationGuard $validationGuard
    ) {}
    
    public function __invoke(ServerRequestInterface $request): ResponseInterface {
        // Service already injected, just use it
        $result = $this->settingService->list($dto);
        return ApiHandler.json($response, $result);
    }
}
```

**Where Services Come From**:

**Option 1**: Slim's Container (automatic via class name)
```php
// Slim finds SettingQueryService in container and injects it
// Container configured in Bootstrap files
```

**Option 2**: Explicit binding in Bootstrap (if needed)
```php
// Modules/[Module]Slim/src/Bootstrap/bootstrap.php
use Psr\Container\ContainerInterface;

return function (ContainerInterface $container) {
    // Bind only if needed; let Slim auto-wire when possible
    $container->set(SettingQueryService::class, function ($c) {
        return new SettingQueryService(
            repository: $c->get(PdoAdminSettingQueryRepository::class),
            listCapabilities: $c->get(SettingListCapabilities::class)
        );
    });
};
```

**Core Module Services - Inject, Don't Duplicate**:

```php
// ✓ CORRECT: Inject core module's service
class SettingsUpdateController {
    public function __construct(
        private SettingCommandService $settingService  // From core Modules/Settings
    ) {}
}

// ✗ WRONG: Create new service (duplicates business logic)
class SettingsUpdateController {
    public function __invoke(...) {
        $service = new SettingCommandService(...);  // NEVER!
    }
}
```

**When Building SettingsSlim**:
1. Identify needed services (SettingQueryService, SettingCommandService)
2. Check Modules/Settings for them
3. If they exist: Inject them into Slim controllers
4. If missing: Add to core module FIRST, then use in Slim
5. Never re-implement business logic

---

## 🎯 Golden Rule

> A Slim Module = **NOT a new feature**
>
> It's a **thin admin UI wrapper** around existing core logic
>
> Pattern: **Core Module (Business Logic) + Slim Module (Admin UI Layer)**

**Slim Module = Read from Core, Don't Rewrite**
- Core module has all business logic
- Slim module has only UI/API layer
- If core is missing something, fix core (don't work around in Slim)

---

## 📊 Slim Module Patterns: Read-Only vs Full CRUD

### Pattern 1: Read-Only Slim (SettingsSlim)

**When to Use**:
- Core module is read-only or update-only
- No creation/deletion in admin UI
- Simple data display + basic edits

**Endpoints**:
```
GET  /settings              → UI page (render Twig)
POST /settings/query        → List with filters
POST /settings/get          → Get single item
POST /settings/update       → Update item
POST /settings/dropdown     → Key-value pairs
```

**No Endpoints For**:
- Create (create in DB directly, core handles)
- Delete (archive in DB directly, core handles)
- Bulk actions

**Use When Core Has**:
- Only read methods
- Update (set) method
- No delete/archive in API

### Pattern 2: Full CRUD Slim (CurrencySlim)

**When to Use**:
- Core module supports complete CRUD
- Admin needs to create/modify/delete
- Complex workflows (toggle, reorder, etc)

**Additional Endpoints**:
```
POST /currencies/create             → Create new
POST /currencies/delete             → Delete/archive
POST /currencies/set-active         → Toggle status
POST /currencies/update-sort-order  → Reorder
```

**UI Features**:
- Create button + form
- Delete buttons with confirmation
- Toggle switches
- Drag-to-reorder

**Use When Core Has**:
- Create method
- Delete/archive method
- Status toggle
- All business logic exposed via service

### How to Choose

**Check Core Module First**:
```php
// In Modules/[CoreModule]/src/Admin/...

// If only has:
// - list()
// - get()
// - update()
// → Use Pattern 1 (Read-only Slim)

// If has:
// - create()
// - list()
// - get()
// - update()
// - delete()
// - setActive()
// → Use Pattern 2 (Full CRUD Slim)
```

**Don't Fight the Core**:
```
Core says:           Slim should do:
✓ Can create    →    Add create UI
✗ Can't create  →    Don't add create button
✓ Can delete    →    Add delete UI
✗ Can't delete  →    Don't add delete button
```

If core needs a feature, add to core. Don't simulate in Slim.

---

## 🛡️ Edge Cases & Error Handling

### Edge Case 1: Empty Search Results

**Scenario**: User searches for something that doesn't exist

```javascript
// Frontend sends: { search: { global: "nonexistent" } }
// Repository returns: { data: [], pagination: { total: 0, page: 1 } }
// JavaScript should:
// - Not crash with "no items" message
// - Show empty table or "no results" placeholder
```

**Correct JavaScript**:
```javascript
if (result.data.length === 0) {
    // Show: "No settings found matching your search"
} else {
    // Render table normally
}
```

### Edge Case 2: Setting Value is Empty String

**Scenario**: Admin saves a setting with empty value `""`

```
Current value: "example"
Admin clears it: ""
Saves: {}

Expected: Store "" in database
NOT: Store NULL or reject as invalid
```

**Validation Schema Must Allow**:
```php
'value' => [
    v::stringType()->length(0, 255),  // ✓ Allows empty string
    // NOT v::notEmpty()              // ✗ Rejects empty
]
```

**Repository Must Support**:
```php
// Repository inserts/updates with empty string value
$query->execute([
    'setting_key' => 'some_key',
    'value' => '',  // Empty string is valid
]);
```

### Edge Case 3: Filter with Special Characters

**Scenario**: User filters by key with `%` or `'` characters

```
Filter: key = "app_%_version"
SQL: WHERE `setting_key` LIKE '%app_%_version%'

Problem: % is wildcard in LIKE
Solution: Use parameterized queries (already done in repository)
```

**Already Safe In**:
```php
$where[] = '`setting_key` LIKE :key';
$params['key'] = '%' . $columnFilters['key'] . '%';  // Parameterized; safe from SQL injection.
// Note: % and _ still behave as LIKE wildcards unless explicitly escaped.
// For literal search with special chars, use ESCAPE clause:
```

**If You Need Literal Search (with % or _ in value)**:
```php
$needle = strtr($columnFilters['key'], [
    '\\' => '\\\\',
    '%' => '\%',
    '_' => '\_',
]);
$where[] = '`setting_key` LIKE :key ESCAPE \'\\\'';
$params['key'] = '%' . $needle . '%';
```

### Edge Case 4: Permission Cache

**Scenario**: Admin loses permission, but still sees button (cached result)

```
Admin has permission at 10:00 AM
Button renders with can_edit: true
Admin loses permission at 10:05 AM
Button still shows (was cached from 10:00)
Admin clicks button → API returns 403
```

**Solution**:
- Don't rely on initial capability check alone
- API endpoint ALSO checks permission (AuthorizationGuardMiddleware)
- Button might show but API rejects (safe, not ideal)
- Better: Don't cache, check on every page load

### Edge Case 5: ListCapabilities Missing Column

**Scenario**: Repository supports filter, but ListCapabilities doesn't declare it

```php
// ListCapabilities (missing created_at)
filterableColumns: ['key', 'admin_note', 'value_type']

// Frontend tries to filter (because form has input)
{ columns: { created_at: "2026-01-01" } }

// ListFilterResolver rejects (not in capabilities)
// Repository never receives the filter
// Data returns unfiltered (silently!)
```

**Fix**: Add column to ListCapabilities FIRST, then Repository

### Edge Case 6: Pagination Out of Range

**Scenario**: User requests page 999 but only 5 pages exist

```
Total results: 100
Per page: 25
Pages: 4 (pages 1-4)

User requests: page=999
Repository returns: { data: [], pagination: { total: 100, page: 999 } }
```

**Repository Should Handle**:
```php
if ($page > $totalPages) {
    $page = $totalPages;  // Clamp to valid range
}
```

**JavaScript Should Handle**:
```javascript
if (result.data.length === 0 && result.pagination.total > 0) {
    // Out of range - reset to page 1
    currentPage = 1;
    loadTable();
}
```

### Edge Case 7: Concurrent Updates

**Scenario**: Two admins edit same setting simultaneously

```
Admin 1: Loads setting, value = "old"
Admin 2: Loads setting, value = "old"
Admin 1: Changes to "new1", saves
Admin 2: Changes to "new2", saves
Result: "new2" overwrites "new1"
```

**Current Implementation**: Last-write-wins (no conflict)

**If Needed**: Add version/timestamp check:
```php
// Before update, verify value hasn't changed:
SELECT value FROM settings WHERE key = ? AND value = ?
IF found: update
ELSE: reject (changed by someone else)
```

### Edge Case 8: Database Connection Lost During Update

**Scenario**: Connection drops mid-update

```php
try {
    ApiHandler.call('/settings/update', {...})
    // Network fails after request sent
    // Update may or may not have completed
} 

// JavaScript doesn't know if update happened
// Shows "error" but setting might be changed
```

**Partial Solution**:
```javascript
// On network error, reload to verify
const result = await ApiHandler.call(...);
if (result.error === 'network') {
    const getResult = await ApiHandler.call('/settings/get', {key});
    // Check if update actually happened
}
```

### Testing Edge Cases

**Checklist**:
- [ ] Empty search results display correctly
- [ ] Empty string values are saved and retrieved
- [ ] Special characters in filters don't break SQL
- [ ] Out-of-range page numbers handled gracefully
- [ ] Missing ListCapabilities column stops filter chain
- [ ] Concurrent edits produce last-write-wins
- [ ] Network errors show proper messages

