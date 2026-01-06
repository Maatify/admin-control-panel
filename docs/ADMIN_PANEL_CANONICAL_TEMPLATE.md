# 📘 ADMIN_PANEL_CANONICAL_TEMPLATE.md

## Admin Control Panel — Unified Page & API Work Template

> **Status:** CANONICAL / LOCKED
> **Scope:** All Admin Panel Pages & APIs
> **Applies From:** Phase 14+
> **Audience:** Backend, Frontend, UI/UX, Reviewers

---

## 🔒 القاعدة الذهبية (غير قابلة للنقاش)

> **أي صفحة أو Endpoint في النظام
> لازم تمشي على القالب ده
> ولازم تكون موثّقة في `docs/API_PHASE1.md` بالتفصيل.**

❌ أي Endpoint غير موثّق
❌ أي UI مش ماشي على القالب
= **مرفوض**

---

# 👑 0️⃣ System Ownership & Script-Driven Bootstrap

## System Ownership Model

* **Explicit Ownership**: Ownership is assigned strictly via the `system_ownership` table (one row max).
* **Script-Driven Only**: Ownership is assigned **ONLY** by the first-admin creation script (`scripts/bootstrap_admin.php`).
* **Immutable**: Once assigned, ownership cannot be transferred or reassigned via the script.
* **Global Bypass**: The system owner bypasses all authorization checks (`checkPermission` returns immediately).

## Why This Model?

* **No Auto-Detection**: We do not detect "admin count = 1" or "admin ID = 1".
* **No Database Flags**: We do not pollute the `admins` table with `is_super_admin` flags.
* **Deterministic**: The bootstrap script is the single authority for creating the owner.
* **Strict Authorization**: Authorization remains enabled and strict for everyone else.

---

# 🧱 1️⃣ Page Operation Types (ثابتة)

أي شغل في النظام لازم يكون واحد (أو أكتر) من الأنواع دي:

| Type   | الوصف                    |
| ------ | ------------------------ |
| LIST   | عرض قائمة (DataTable)    |
| CREATE | إضافة عنصر               |
| EDIT   | تعديل عنصر               |
| VIEW   | عرض تفاصيل               |
| DELETE | حذف / إلغاء (Action فقط) |

❗ مفيش Page خارج التصنيف ده

---

# 🧭 2️⃣ Canonical Routing Pattern (ثابت)

## UI Routes (HTML فقط)

```http
GET /{resource}
GET /{resource}/create
GET /{resource}/{id}
GET /{resource}/{id}/edit
```

### قواعد UI Routes

* UI / Twig فقط
* ❌ مفيش DB access
* ❌ مفيش Business Logic
* ❌ مفيش Security Decisions
* ✔️ كل الداتا بتيجي من API

---

## API Routes (JSON فقط)

```http
POST /api/{resource}/query
POST /api/{resource}/create
POST /api/{resource}/{id}/update
POST /api/{resource}/{id}/delete
```

### قواعد API Routes

* JSON فقط
* Operation واحدة واضحة
* Authorization إجباري
* مفيش HTML
* مفيش سلوك خفي

---

# 🔐 3️⃣ Permissions Template (إجباري)

كل Operation لها Permission واضح:

| Operation | Permission          |
| --------- | ------------------- |
| LIST      | `{resource}.list`   |
| CREATE    | `{resource}.create` |
| EDIT      | `{resource}.edit`   |
| DELETE    | `{resource}.delete` |

⚠️

* UI لا يقرر
* Backend فقط هو صاحب القرار
* UI يعرض / يخفي بناءً على permission response فقط

---

# 🖥️ 4️⃣ Page Composition Template (UI)

أي صفحة تتكوّن من **3 أجزاء ثابتة**:

## 🔹 Header

* Page Title
* Action Buttons (Create / Save / Delete)
* Visibility حسب permission فقط

## 🔹 Content

* DataTable (LIST)
* Form (CREATE / EDIT)
* Read-only blocks (VIEW)
* Any JS required by the page must be injected via the scripts block in the base layout.

## 🔹 Footer

* Pagination
* Submit actions
* Generic messages فقط

❌ مفيش Logic
❌ مفيش قرارات

---

# 📊 5️⃣ DataTable Template (LIST Pages)

## Page Route

```http
GET /{resource}
```

* يفتح الصفحة فقط
* لا يجلب داتا

---

## API Route

```http
POST /api/{resource}/query
```

---

## 📥 Request Template (ثابت)

```json
{
  "page": 1,
  "per_page": 20,
  "filters": {}
}
```

### قواعد

* Pagination Server-side فقط
* Filters لازم تكون موثّقة
* أي Filter غير موثّق = مرفوض

---

## 📤 Response Template (ثابت)

```json
{
  "data": [],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 0
  }
}
```

❌ No client-side search
❌ No client-side pagination

---

# 📝 6️⃣ Forms Template (CREATE / EDIT)

## Routes

```http
POST /api/{resource}/create
POST /api/{resource}/{id}/update
```

## قواعد

* نفس Form UI
* الفرق الوحيد:

    * Endpoint
    * Initial data
* Validation في Backend فقط
* Errors generic في UI

---

# 🧾 7️⃣ التوثيق الإجباري (MANDATORY)

> **أي Endpoint يتم إنشاؤه أو استخدامه
> لازم يتوثّق في `docs/API_PHASE1.md`**

❌ بدون توثيق = Endpoint غير موجود رسميًا

---

## 📘 توثيق كل Endpoint لازم يحتوي:

### ✔️ Endpoint Info

* Method
* URL
* Description
* Required Permission

### ✔️ Request Model

```json
{
  "...": "..."
}
```

### ✔️ Response Model

```json
{
  "...": "..."
}
```

### ✔️ Notes

* Pagination behavior
* Filters behavior
* Edge cases

All JS-driven pages require a scripts block in the base layout.

---

# 🧩 8️⃣ مثال تطبيقي — Sessions

## Page

```http
GET /sessions
```

Type: `LIST`

## API

```http
POST /api/sessions/query
```

## Permissions

```
sessions.list
```

## Documented in

```
docs/API_PHASE1.md
```

---

# 🚨 9️⃣ Enforcement Rule (نهائي)

> ❌ أي شغل:

* خارج القالب ده
* أو Endpoint غير موثّق
* أو Permission غير واضح

= **Bug معماري**
وليس Feature

---

# ✅ الخلاصة

* ده **المرجع الوحيد**
* Backend + Frontend + UI يمشوا عليه
* أي Page جديدة = تطبيق مباشر للقالب
* أي API جديدة = توثيق إجباري في `API_PHASE1.md`

---

