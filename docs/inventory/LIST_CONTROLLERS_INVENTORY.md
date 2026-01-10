# LIST INVENTORY REPORT

## 1) Fully Compliant (SessionQueryController pattern)

### **Sessions**
*   **Resource Name:** Sessions
*   **UI Route:** `GET /sessions`
*   **API Route:** `POST /api/sessions/query`
*   **Controller(s):**
    *   UI: `App\Http\Controllers\Ui\SessionListController`
    *   API: `App\Http\Controllers\Api\SessionQueryController`
*   **Uses SessionQueryController pattern?** YES
*   **Input Keys Observed:** `page`, `per_page`, `search`, `date` (Canonical `ListQueryDTO`)
*   **Pagination Style:** Canonical (page/per_page)
*   **Legacy Handling?** NO
*   **Documented in docs/API_PHASE1.md?** YES
*   **Notes:** This is the reference implementation.

### **Admins**
*   **Resource Name:** Admins
*   **UI Route:** `GET /admins`
*   **API Route:** `POST /api/admins/query`
*   **Controller(s):**
    *   UI: `App\Http\Controllers\Ui\UiAdminsController`
    *   API: `App\Http\Controllers\Api\AdminQueryController`
*   **Uses SessionQueryController pattern?** YES
*   **Input Keys Observed:** `page`, `per_page`, `search`, `date` (Canonical `ListQueryDTO`)
*   **Pagination Style:** Canonical (page/per_page)
*   **Legacy Handling?** NO
*   **Documented in docs/API_PHASE1.md?** YES
*   **Notes:** Fully compliant API implementation.

## 2) Partially Compliant

### **Roles**
*   **Resource Name:** Roles
*   **UI Route:** `GET /roles`
*   **API Route:** NONE
*   **Controller(s):** `App\Http\Controllers\Ui\UiRolesController`
*   **Uses SessionQueryController pattern?** NO
*   **Input Keys Observed:** None (Static View)
*   **Pagination Style:** None
*   **Legacy Handling?** NO
*   **Documented in docs/API_PHASE1.md?** NO
*   **Notes:** Placeholder UI page. Missing API endpoint.

### **Permissions**
*   **Resource Name:** Permissions
*   **UI Route:** `GET /permissions`
*   **API Route:** NONE
*   **Controller(s):** `App\Http\Controllers\Ui\UiPermissionsController`
*   **Uses SessionQueryController pattern?** NO
*   **Input Keys Observed:** None (Static View)
*   **Pagination Style:** None
*   **Legacy Handling?** NO
*   **Documented in docs/API_PHASE1.md?** NO
*   **Notes:** Placeholder UI page. Missing API endpoint.

## 3) Non-Compliant / Legacy

### **Admin Notification History**
*   **Resource Name:** Admin Notifications
*   **UI Route:** NONE
*   **API Route:** `GET /admins/{admin_id}/notifications`
*   **Controller(s):** `App\Http\Controllers\AdminNotificationHistoryController`
*   **Uses SessionQueryController pattern?** NO
*   **Input Keys Observed:** `page`, `limit`, `notification_type`, `is_read`, `from_date`, `to_date`, `admin_id` (route/query)
*   **Pagination Style:** Legacy (limit/page)
*   **Legacy Handling?** YES (Explicit mapping in controller)
*   **Documented in docs/API_PHASE1.md?** YES (Marked as Legacy)
*   **Notes:** Uses `AdminNotificationHistorySchema` and flat query parameters. Explicitly distinct from canonical pattern.

### **Global Notifications**
*   **Resource Name:** Notifications (System)
*   **UI Route:** NONE
*   **API Route:** `GET /notifications`
*   **Controller(s):** `App\Http\Controllers\NotificationQueryController`
*   **Uses SessionQueryController pattern?** NO
*   **Input Keys Observed:** `status`, `channel`, `from`, `to`, `admin_id`
*   **Pagination Style:** None (Returns full array or repository-limited result)
*   **Legacy Handling?** YES
*   **Documented in docs/API_PHASE1.md?** YES (Legacy / System)
*   **Notes:** Uses `NotificationQuerySchema`. Priority-based filtering logic.

## 4) Undocumented LIST Endpoints

*   None identified in `routes/web.php` that are missing from `docs/API_PHASE1.md`.

## 5) Blocking Conflicts

*   None. `UiAdminsController` and `AdminQueryController` coexist correctly following the View/API separation pattern.

This report is AS-IS only. No implementation or refactoring was performed.
