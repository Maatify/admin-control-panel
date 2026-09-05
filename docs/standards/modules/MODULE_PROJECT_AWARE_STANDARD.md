# MODULE_PROJECT_AWARE_STANDARD

**Maatify Project-Aware Slim Module Standard — v1**

This document extends `MODULE_BUILDING_STANDARD.md` and `MODULE_SLIM_BUILDING_STANDARD.md`.
Read both fully before reading this document.

---

## 1. What Is a Project-Aware Slim Module?

A **standard Slim module** wraps exactly one core module and never touches tables outside that core module.

A **project-aware Slim module** wraps cross-module concerns that only make sense within the host project.
It may JOIN tables from multiple core modules in a single query because the host project owns all of them.

| Aspect | Standard Slim | Project-Aware Slim |
|---|---|---|
| Core module dependency | Exactly one | Multiple (or none) |
| Cross-module JOINs | Never | Allowed — the project owns all tables |
| Namespace | `Maatify\{CoreModule}Slim\` | `Maatify\{Feature}AdminSlim\` (e.g. `OrderAdminSlim`) |
| Extractable as library? | Yes — ships with its core module | No — tied to the host project |
| When to use | Wrapping a single core module for admin UI | Enriching entities with context from other modules (e.g. images + orders) |

**Example**: `OrderAdminSlim` is project-aware because it JOINs `orders`, `order_subscriptions`, `arp_subscriptions`, `arp_catalogs`, and `arp_images` — tables that belong to different core modules.

---

## 2. Cross-Module JOIN Rules

### 2.1 Latest-Record Derived JOIN

When the relationship goes through an intermediate table that may have multiple rows per parent (even if currently constrained as unique), use a derived subquery to guarantee one row:

```sql
LEFT JOIN (
    SELECT
        los.`subscription_id`,
        MAX(los.`order_id`) AS `latest_order_id`
    FROM `order_subscriptions` los
    GROUP BY los.`subscription_id`
) lo ON lo.`subscription_id` = cat.`subscription_id`
LEFT JOIN `orders` o ON o.`id` = lo.`latest_order_id`
```

This is the **default** for list queries where no specific record is requested.

> **Note**: `MAX(order_id)` works here because `order_id` is monotonic and represents the latest order in this project. If your project uses a different ordering column (e.g. `created_at`), use that instead. The pattern is "latest-record derived join" — `MAX()` is just the implementation for monotonic IDs.

### 2.2 Conditional JOIN Pattern

When a filter targets a column from the joined table (e.g. `order_id`), the JOIN strategy must change to match:

```php
$filterOrderId = $columnFilters['order_id'] ?? null;
$hasOrderFilter = (is_int($filterOrderId) || is_string($filterOrderId))
    && is_numeric($filterOrderId) && (int) $filterOrderId > 0;

$orderJoinSql = $hasOrderFilter
    ? 'INNER JOIN `order_subscriptions` os ON os.`subscription_id` = cat.`subscription_id`
           AND os.`order_id` = :join_order_id
       INNER JOIN `orders` o ON o.`id` = os.`order_id`'
    : 'LEFT JOIN (
           SELECT los.`subscription_id`, MAX(los.`order_id`) AS `latest_order_id`
           FROM `order_subscriptions` los
           GROUP BY los.`subscription_id`
       ) lo ON lo.`subscription_id` = cat.`subscription_id`
       LEFT JOIN `orders` o ON o.`id` = lo.`latest_order_id`';

$joinParams = $hasOrderFilter ? ['join_order_id' => (int) $filterOrderId] : [];
```

**Why**: If the filter uses `EXISTS` or `WHERE` but the SELECT still uses `MAX()`, the displayed `order_id` may differ from the filtered value — a data correctness bug.

**Rule**: The filter column value and the displayed column value must always come from the same JOIN path.

### 2.3 Filtered Count Must Include the JOIN

The filtered count query must include the same JOIN as the data query:

```php
$filteredStatement = $this->prepareOrFail(
    "SELECT COUNT(*)
     FROM `arp_images` i
     INNER JOIN `arp_catalogs` cat ON cat.`id` = i.`catalog_id`
     {$orderJoinSql}
     {$whereSql}"
);
$this->bindParams($filteredStatement, array_merge($joinParams, $params));
```

The total count (unfiltered) stays on the base table only — no JOINs.

---

## 3. Endpoint Reuse

A project-aware module can build frontend URLs that call endpoints from other modules without creating new backend routes.

**Example**: AR Image detail page uses download/replace endpoints from ArPlatformSlim:

```javascript
// Existing endpoints — no new backend needed
const DOWNLOAD = '/api/subscriptions/{subscriptionId}/ar-media-assets/{assetKey}';
const REPLACE  = '/api/subscriptions/{subscriptionId}/ar-media-assets/{assetKey}/source-asset';
```

The JS builds asset keys from the entity context:

```javascript
// Image variants
'ar_image:' + imageId + ':original_image'
'ar_image:' + imageId + ':compressed_image'

// Media variants (requires ar_image_asset_id)
'ar_image_asset:' + assetId + ':original_media'
'ar_image_asset:' + assetId + ':compressed_media'
```

**Rule**: The detail DTO must include all IDs needed to construct these URLs (`subscription_id`, `id`, `ar_image_asset_id`).

---

## 4. Detail Page Pattern

### 4.1 UI Controller

```php
final readonly class EntityDetailsUiController
{
    public function __construct(
        private Twig $twig,
        private QueryService $service,
        private UiPermissionService $uiPermissionService,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = ctype_digit($args['id']) ? (int) $args['id'] : 0;

        if ($id < 1) {
            throw new EntityNotFoundException('Entity', $args['id']);
        }

        $entity = $this->service->getEntity($id);

        $adminContext = $request->getAttribute(AdminContext::class);
        $adminId = $adminContext instanceof AdminContext ? $adminContext->adminId : 0;

        $capabilities = [
            'can_do_x' => $this->uiPermissionService->hasPermission($adminId, 'permission.name'),
            // ...
        ];

        return $this->twig->render($response, 'pages/module/entity_details.twig', [
            'entity' => $entity,
            'capabilities' => $capabilities,
        ]);
    }
}
```

### 4.2 Twig Template

**Capabilities** — always explicit extraction, never `json_encode|raw`:

```twig
window.entityCapabilities = {
    can_do_x: {{ capabilities.can_do_x ?? false ? 'true' : 'false' }},
};
```

**Entity context** — always use `JSON_HEX_*` flags to prevent `</script>` injection:

```twig
window.entityContext = {{ entity|json_encode(
    constant('JSON_HEX_TAG')
    b-or constant('JSON_HEX_AMP')
    b-or constant('JSON_HEX_APOS')
    b-or constant('JSON_HEX_QUOT')
)|raw }};
```

**Never** use bare `json_encode|raw` for entity objects — they contain user-provided strings.

**Template structure**:
- `{% block title %}` — entity type + ID
- Breadcrumb: Home > List Page > #ID
- Metadata section (rendered by JS)
- Feature sections (lazy-loaded or rendered by JS)
- Quick Links section

**Scripts block** — Pattern B order:

```twig
{% block scripts %}
    <script src="{{ asset('assets/maatify/admin-kernel/js/api_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/callback_handler.js') }}"></script>

    {# Module Scripts (Pattern B) #}
    <script src="{{ asset('assets/js/modules/feature/entity-details-helpers.js') }}"></script>
    <script src="{{ asset('assets/js/modules/feature/entity-details-actions.js') }}"></script>
    <script src="{{ asset('assets/js/modules/feature/entity-details.js') }}"></script>
{% endblock %}
```

### 4.3 JavaScript — Pattern B (3 Files)

**File 1: `entity-details-helpers.js`**

Contains everything that does NOT listen to events:

- `API_ROUTES` — endpoint templates with `{placeholders}`
- `escapeHtml()`, `escapeAttr()` — HTML safety
- `field(label, value)` — renders a labeled field card
- `badge(label, tone)` — renders a colored badge (primary/success/danger/neutral)
- `link(url, label, allowed)` — renders a link if capability allows, otherwise plain text
- `safeExternalLink(url)` — validates URL scheme (http/https only) before rendering as `<a>`
- `route(template, ctx)` — replaces placeholders with context values
- `renderMetadata(ctx, cap)` — renders the metadata grid
- `renderFeatureSection(ctx, cap)` — renders feature-specific sections
- Exposed as `window.EntityDetailsHelpers`

**File 2: `entity-details-actions.js`**

Contains all event listeners and async operations:

- Button click handlers (load, regenerate, etc.)
- File upload handlers (replace source asset)
- Confirm dialogs before destructive actions
- Success/error feedback via `ApiHandler.showAlert()`
- Exposed as `window.EntityDetailsActions` with `init()` method

**File 3: `entity-details.js`** (with-components)

Minimal init that wires helpers + actions:

```javascript
const EntityDetailsWithComponents = {
    init() {
        const ctx = window.entityContext;
        const cap = window.entityCapabilities;
        const h = window.EntityDetailsHelpers;

        if (!ctx || !cap || !h) return;

        h.renderMetadata(ctx, cap);
        h.renderFeatureSection(ctx, cap);

        if (window.EntityDetailsActions) {
            window.EntityDetailsActions.init();
        }
    }
};
window.EntityDetailsWithComponents = EntityDetailsWithComponents;
document.addEventListener('DOMContentLoaded', () => { window.EntityDetailsWithComponents.init(); });
```

### 4.4 Lazy-Load Sections

For data that requires a separate API call (e.g. encrypted QR tokens, protected details):

**Twig**: Section with load button + result container:

```twig
<section class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Section Title</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Description.</p>
        </div>
        <div id="section-actions" class="flex flex-wrap items-center gap-2"></div>
    </div>
    <div id="section-container" class="text-sm text-gray-500 dark:text-gray-400"></div>
</section>
```

**JS actions**: Button click → API call → render result → disable button:

```javascript
document.getElementById('btn-load-section')?.addEventListener('click', async function () {
    if (!cap.can_view_section || !window.ApiHandler) return;

    this.disabled = true;
    container.innerHTML = '<span class="text-xs text-gray-500">Loading...</span>';

    try {
        const response = await window.ApiHandler.call(route, null, 'Label', 'GET');
        // render result
    } catch (error) {
        container.innerHTML = '<span class="text-xs text-red-600">Failed to load.</span>';
    } finally {
        this.disabled = false;
    }
});
```

---

## 5. URL Safety in JavaScript

### 5.1 Three Contexts, Three Functions

| Context | Function | Example |
|---|---|---|
| HTML content | `escapeHtml(value)` | `<span>${escapeHtml(name)}</span>` |
| URL parameter/path | `encodeURIComponent(value)` | `href="/orders/${encodeURIComponent(id)}"` |
| External URL from DB | `safeExternalLink(url)` | Validates scheme before rendering as `<a>` |

### 5.2 `safeExternalLink` Pattern

External URLs from the database must be validated before rendering as clickable links:

```javascript
safeExternalLink(url) {
    let parsed;
    try {
        parsed = new URL(url);
    } catch {
        return '<span class="...">' + this.escapeHtml(url) + '</span>';
    }
    if (parsed.protocol !== 'https:' && parsed.protocol !== 'http:') {
        return '<span class="...">' + this.escapeHtml(url) + '</span>';
    }
    return '<a href="' + this.escapeHtml(parsed.href) + '" target="_blank" rel="noopener noreferrer" class="...">'
        + this.escapeHtml(url) + '</a>';
}
```

- `parsed.href` (normalized) in `href` attribute
- Original `url` (escaped) as display text
- Non-http(s) schemes render as plain text — prevents `javascript:` XSS

### 5.3 Link Rendering with Capability Check

Links to other admin pages must respect capabilities:

```javascript
link(url, label, allowed) {
    return allowed
        ? `<a href="${url}" class="text-blue-700 dark:text-blue-300 hover:underline">${this.escapeHtml(label)}</a>`
        : this.escapeHtml(label);
}
```

Used as: `h.link('/orders/' + encodeURIComponent(id), id, cap.can_view_orders)`

---

## 6. Permission Reuse

### 6.1 When to Reuse vs Create

| Scenario | Action |
|---|---|
| Endpoint shows the same data as an existing page | Reuse the existing permission |
| Endpoint shows enriched data but no new sensitive context | Reuse — order_id/number as display info is not sensitive |
| Endpoint exposes new sensitive context (protected details, PII) | Create a new permission |
| Link to another page | Use that page's view permission as capability check — no new permission |

### 6.2 Documenting Reused Permissions

In the module's `permissions_seed.sql`, document reused permissions as comments:

```sql
-- The following routes reuse permissions from Modules/ArPlatformSlim/permissions_seed.sql:
--   orders.ar_images_with_orders.list.api  -> ar_platform_ar_images.list
--   orders.catalogs_with_orders.list.api   -> ar_platform_catalogs.list
```

This ensures the permission linter stays clean and the dependency is explicit.

### 6.3 Capability for Links

Links in rendered HTML must be gated by the target page's view permission:

```php
// UI Controller
'can_view_orders' => $this->uiPermissionService->hasPermission($adminId, 'orders.view'),
```

```javascript
// JS renderer
order_id: (v) => capabilities.can_view_orders
    ? `<a href="/orders/${encodeURIComponent(v)}">${escapeHtml(v)}</a>`
    : escapeHtml(v),
```

---

## 7. List-to-Detail Navigation

### 7.1 List Table ID Column as Link

The ID column in list tables should link to the detail page:

```javascript
id: (v) => `<a href="/entity/${encodeURIComponent(v)}" class="font-mono text-blue-900 dark:text-blue-300 text-sm">#${escapeHtml(v)}</a>`,
```

### 7.2 URL Filter Auto-Prefill

List pages should read URL query parameters and populate filter fields on load.
The recommended pattern (from Catalogs):

```javascript
handleUrlFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.forEach((value, key) => {
        const idKey = key.replace(/_/g, '-');
        const element = document.getElementById(`filter-${idKey}`);
        if (element && value) {
            element.value = value;
        }
    });
}
```

This enables cross-page linking like `/ar-images?catalog_id=5` to auto-filter on load.

---

## 8. File Upload (Replace Source Asset)

### 8.1 Event Delegation Pattern

```javascript
document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches('[data-replace-file]')) {
        return;
    }
    this.replaceSourceAsset(input, ctx);
});
```

### 8.2 Upload Flow

```javascript
async replaceSourceAsset(input, ctx) {
    const assetKey = input.dataset.assetKey || '';
    const file = input.files && input.files[0] ? input.files[0] : null;
    if (!assetKey || !file) return;

    const formData = new FormData();
    formData.append('file', file);
    input.disabled = true;

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.success === false) {
            throw new Error(payload.error?.message || 'Replacement failed.');
        }

        ApiHandler.showAlert('success', 'Source asset replacement queued successfully.');
        window.location.reload();
    } catch (error) {
        ApiHandler.showAlert('danger', error.message || 'Replacement failed.');
    } finally {
        input.value = '';
        input.disabled = false;
    }
}
```

### 8.3 Replace Button (Original Variants Only)

```javascript
renderReplaceAction(asset, ctx, cap) {
    if (!cap.can_replace || asset.variant !== 'original') return '';

    const accepts = asset.assetType === 'media' ? 'video/*,audio/*' : 'image/*';
    const inputId = 'replace-' + escapeAttr(asset.assetKey);

    return `
        <label for="${inputId}" class="... cursor-pointer">Replace original</label>
        <input id="${inputId}" class="hidden" type="file" accept="${accepts}"
               data-replace-file data-asset-key="${escapeHtml(asset.assetKey)}">
    `;
}
```

---

## 9. Checklist: Project-Aware Slim Module

### Backend
- [ ] DTOs: List item (summary) + Detail (full) — both `final readonly`, `JsonSerializable`
- [ ] Interface: list + find methods with PHPDoc return shapes
- [ ] Repository: cross-module JOINs with latest-record derived pattern
- [ ] Repository: conditional JOIN when filter targets joined table column
- [ ] Repository: filtered count includes same JOIN as data query
- [ ] Service: delegates to repository, throws `EntityNotFoundException` for find
- [ ] API Controllers: GET for detail, POST for list query
- [ ] UI Controller: entity + capabilities → Twig
- [ ] Routes: API + UI registered with `AuthorizationGuardMiddleware`
- [ ] Permissions: mapped in provider, reused permissions documented in seed SQL
- [ ] Bindings: all controllers registered in DI
- [ ] PHPStan max: zero errors
- [ ] Permission linter: zero errors

### Frontend
- [ ] Twig: capabilities as explicit booleans, context with `JSON_HEX_*`
- [ ] JS Pattern B: helpers + actions + with-components (3 files)
- [ ] HTML escaping: `escapeHtml()` for display content
- [ ] URL encoding: `encodeURIComponent()` for URL parameters/paths
- [ ] External URLs: `safeExternalLink()` with scheme validation
- [ ] Links gated by capabilities
- [ ] List ID column links to detail page
- [ ] File upload: event delegation, FormData, disable during upload
- [ ] Lazy-load sections: button → API call → render → feedback

---

## 10. Project-Aware Aggregation Pattern

When a project-aware Slim module needs analytics that aggregate data from multiple core module tables (e.g. orders + order_items + order_payments + order_fulfillments), use the **Split INSERT + UPDATE** pattern.

### 10.1 The Problem

A single `INSERT...SELECT` with correlated subqueries on related tables fails under `ONLY_FULL_GROUP_BY`:

```sql
-- ❌ WRONG — o.id is not in GROUP BY, correlated subqueries pick arbitrary row
INSERT INTO order_daily_stats (stat_date, currency_id, order_status, item_count, ...)
SELECT
    DATE(o.created_at), o.currency_id, o.status,
    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id),  -- ← o.id not in GROUP BY
    ...
FROM orders o
GROUP BY DATE(o.created_at), o.currency_id, o.status
```

Even if MySQL allows it (with `ONLY_FULL_GROUP_BY` disabled), it counts items for one arbitrary order from the group — **wrong data**.

### 10.2 The Solution: Split INSERT + UPDATE

**Step 1 — INSERT base stats** from the primary table only (no JOINs):

```php
private function insertBaseStats(string $dateFrom, string $dateTo): void
{
    $sql = <<<'SQL'
        INSERT INTO `order_daily_stats`
            (`stat_date`, `currency_id`, `order_status`,
             `order_count`, `grand_total`, `avg_order_value`, ...)
        SELECT
            DATE(o.`created_at`), o.`currency_id`, o.`status`,
            COUNT(*), SUM(o.`grand_total`), AVG(o.`grand_total`), ...
        FROM `orders` o
        WHERE DATE(o.`created_at`) BETWEEN :date_from AND :date_to
        GROUP BY DATE(o.`created_at`), o.`currency_id`, o.`status`
        SQL;
    $this->pdo->prepare($sql)->execute([...]);
}
```

**Step 2 — UPDATE with JOINed counts** from each related table, one UPDATE per table:

```php
private function updateItemCounts(string $dateFrom, string $dateTo): void
{
    $sql = <<<'SQL'
        UPDATE `order_daily_stats` s
        INNER JOIN (
            SELECT
                DATE(o.`created_at`)   AS `stat_date`,
                o.`currency_id`,
                o.`status`             AS `order_status`,
                COUNT(oi.`id`)         AS `item_count`,
                COUNT(CASE WHEN oi.`item_type` = 'plan' THEN 1 END) AS `plan_count`,
                ...
            FROM `orders` o
            INNER JOIN `order_items` oi ON oi.`order_id` = o.`id`
            WHERE DATE(o.`created_at`) BETWEEN :date_from AND :date_to
            GROUP BY `stat_date`, o.`currency_id`, o.`status`
        ) agg ON agg.`stat_date`    = s.`stat_date`
             AND agg.`currency_id`  = s.`currency_id`
             AND agg.`order_status` = s.`order_status`
        SET
            s.`item_count` = agg.`item_count`,
            s.`plan_count` = agg.`plan_count`,
            ...
        WHERE s.`stat_date` BETWEEN :date_from2 AND :date_to2
        SQL;
    $this->pdo->prepare($sql)->execute([...]);
}
```

**Step 3 — Repeat** for each related table (payments, fulfillments, etc.)

### 10.3 Why This Works

- Each query JOINs the primary table with **one** related table and GROUP BY is clean
- The derived subquery aggregates correctly across all orders in the bucket
- `INNER JOIN` on the derived subquery means buckets with no related rows keep their default `0` values
- All steps run inside the same DELETE + INSERT + UPDATE transaction

### 10.4 Execution Order

```php
public function aggregateDateRange(string $dateFrom, string $dateTo): int
{
    $this->pdo->beginTransaction();
    try {
        // 1. Purge stale buckets
        $this->deleteRange($dateFrom, $dateTo);
        // 2. Base stats (primary table only)
        $this->insertBaseStats($dateFrom, $dateTo);
        // 3. Related table counts (one UPDATE per table)
        $this->updateItemCounts($dateFrom, $dateTo);
        $this->updatePaymentCounts($dateFrom, $dateTo);
        $this->updateFulfillmentCounts($dateFrom, $dateTo);

        // 4. Count inserted rows
        $countStmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `order_daily_stats` WHERE `stat_date` BETWEEN :from AND :to'
        );
        $countStmt->execute(['from' => $dateFrom, 'to' => $dateTo]);
        $inserted = (int) $countStmt->fetchColumn();

        $this->pdo->commit();
        return $inserted;
    } catch (\Throwable $e) {
        if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
        throw $e;
    }
}
```

### 10.5 When to Use

| Scenario | Pattern |
|---|---|
| Single core module table (e.g. `maa_tap_charges`) | Simple DELETE + INSERT (see `MODULE_BUILDING_STANDARD.md` §12) |
| Multiple tables from different modules (project-aware) | **Split INSERT + UPDATE** (this section) |

### 10.6 Checklist

- [ ] INSERT uses only the primary table — no JOINs, no correlated subqueries
- [ ] Each UPDATE targets one related table with a derived subquery JOIN
- [ ] All steps in a single transaction with rollback on failure
- [ ] `ONLY_FULL_GROUP_BY` safe — every non-aggregated column is in GROUP BY
- [ ] Buckets with no related rows retain default `0` (INNER JOIN on derived = no match = no update)
- [ ] Cron script registers all required module bindings via `$builderHook`
