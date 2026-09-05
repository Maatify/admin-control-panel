# Catalog V1 Database Architecture — Locked

**Host-Agnostic Taxonomy Engine**

هذه الوثيقة هي المرجع المعماري لـ **Catalog V1** بعد إعادة الهيكلة المستندة إلى الـ Domain Decomposition الحقيقي.
في هذه المعمارية، الـ Catalog Module هو المالك الحصري لدومين الـ Taxonomy والتنظيم الهرمي (Hierarchy)، ولا يمتلك أية تفاصيل عن الدومينات الأخرى.

أي تغيير لاحق على قرار معماري مثبت هنا يعتبر **تغييرًا معماريًا جديدًا** وليس مجرد تفصيل تنفيذ.

---

# 1. الرؤية المعمارية والنطاق

## 1.1 الهدف

`Catalog V1` هو:

**Standalone, Extractable, Host-Agnostic Taxonomy Engine**

ويجب أن يظل قابلًا للاستخراج مستقبلًا كمكتبة مستقلة دون الاعتماد على تفاصيل الـHost أو موديولات أخرى.

### Taxonomy Domain

يمثل التصنيف الهرمي والتنظيمي للمحتويات أو الكيانات (سواء كانت منتجات أو مقالات أو غيرها، رغم أن تلك الكيانات نفسها ليست جزءًا من هذا الدومين).
*ملاحظة: لا توجد Entity مستقلة باسم Catalog في الـ schema الحالية؛ הـ Categories هي وحدة التنظيم الأساسية.*

---

## 1.2 النطاق المشمول

يشمل V1:

* Categories.
* Category Hierarchy (Parent-Child).
* Category Translations.
* Category Status & Visibility.
* Category Display Ordering.
* Category Lifecycle (Soft delete / restore).
* Internal Domain/Transaction Invariants (Cycle prevention).

---

## 1.3 خارج النطاق صراحة (Explicit Out-of-Scope)

لا يحتوي V1 ولا يجب توسيع مخططه من أجل دعم ما يخص الـ Domains الأخرى:

* Products, Variants, SKU, Barcode.
* Product Options, Option Values.
* Product ↔ Category Relations. (العلاقة متروكة لـ Host/Integration Layer).
* Monetary values, Prices, Adjustments.
* Inventory, quantity_on_hand.
* Media Metadata.
* Customers, Orders, Payments, Shipping.
* Promotions, Discounts.

الـ Catalog يجب ألا يعرف أي شيء عنهم، وأي ربط يتم إدارته بشكل Generic أو من خلال الـ Host/Integration Layer.

---

# 2. Host-Agnostic Boundaries

Catalog لا يعتمد على جداول الـHost.
لا توجد Foreign Keys أو JOINs من Catalog إلى أي Module آخر.

---

## 2.1 اللغات

يخزن Catalog:

```text
language_code VARCHAR(16) NOT NULL
```

وهو BCP-47 code.
الـHost مسؤول عن التحقق من صحة `language_code` والـ Fallback Chain.

---

# 3. Timestamp Policy

كل Timestamps تدار بواسطة **Catalog Application Layer**.

كل جدول يحتوي:

```text
created_at DATETIME NOT NULL
updated_at DATETIME NOT NULL
deleted_at DATETIME NULL
```

جميع القيم تخزن UTC، وتحديثها هو مسؤولية الـ Application.

---

# 4. Soft Delete Policy

كل جداول Catalog تستخدم Soft Delete.

```text
deleted_at IS NULL
```
تعني Record غير محذوفة.

## 4.1 Category Delete Dependency

لا يجوز Soft Delete لـCategory لديها Child Categories غير محذوفة.

---

# 5. Restore Policy

الـ Restore يجب أن يلتزم بنفس قواعد إنشاء الـ Identity ولا يستخدم إضافة `deleted_at` للـ Unique Constraints.

---

# 6. Logical Identity Immutability

الأعمدة التي تحدد **Logical Identity** لسجل لا تتغير بعد الإنشاء:
```text
category_translation:
(category_id, language_code)
```

استثناء مقصود:
`category.parent_id` قابلة للتغيير لدعم نقل الـ Category.

---

# 7. Stable Codes

الآتي Stable وImmutable ولا يعاد استخدامه بعد Soft Delete:
* Category code

---

# 8. Foreign Key Policy

كل Foreign Keys الداخلية تستخدم:

```text
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

---

# 9. Status Model

المصدر الوحيد للحالة هو العمود:

```text
status VARCHAR(20) NOT NULL
```

مع:

```text
CHECK (status IN ('active','inactive'))
```

---

# 10. Display Order

```text
display_order INT NOT NULL DEFAULT 0
```
تُستخدم للـ Categories ضمن نفس الـ `parent_id`.
الترتيب الحتمي هو `ORDER BY display_order, id`.

---

# 11. Category Visibility

Category ذات:
```text
status = inactive
```
لا تظهر للمستهلك. وإذا كان Parent inactive، فالـ Descendants لا تظهر عبر هذا المسار. هذه القاعدة تطبق فقط على هيكل الـ Categories نفسه، وليس على الكيانات المربوطة به خارجيًا.

---

# 12. Category Hierarchy

Root:
```text
parent_id = NULL
```
DB تمنع: `CHECK (parent_id IS NULL OR parent_id <> id)`
Domain تمنع العلاقات الدائرية (Cycle Prevention: A → B → C → A).

---

# 13. Complete Database Schema — 2 Tables

## 13.1 `maa_catalog_categories`

| العمود          | النوع                                   |
| --------------- | --------------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT`        |
| `parent_id`     | `BIGINT UNSIGNED NULL`                  |
| `code`          | `VARCHAR(100) NOT NULL`                 |
| `status`        | `VARCHAR(20) NOT NULL DEFAULT 'active'` |
| `display_order` | `INT NOT NULL DEFAULT 0`                |
| `created_at`    | `DATETIME NOT NULL`                     |
| `updated_at`    | `DATETIME NOT NULL`                     |
| `deleted_at`    | `DATETIME NULL`                         |

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(code)
CHECK(status IN ('active','inactive'))
CHECK(parent_id IS NULL OR parent_id <> id)
```
FK:
```text
parent_id → maa_catalog_categories.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

---

## 13.2 `maa_catalog_category_translations`

| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `category_id`   | `BIGINT UNSIGNED NOT NULL`       |
| `language_code` | `VARCHAR(16) NOT NULL`           |
| `name`          | `VARCHAR(255) NOT NULL`          |
| `description`   | `TEXT NULL`                      |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(category_id, language_code)
```
FK:
```text
category_id → maa_catalog_categories.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

---

# 14. Database-Enforced Invariants

* Primary Keys, Internal FKs (ON DELETE/UPDATE RESTRICT).
* Unique Stable Codes, Unique Translation Identities.
* Status constraints (active/inactive).
* `parent_id <> id`.

---

# 15. Domain / Transaction-Enforced Invariants

* Category Cycle Prevention.
* Category Child Delete Dependency.

---

# 16. Index Strategy

## Categories
```text
maa_catalog_categories
(parent_id, status, deleted_at, display_order, id)
```

## Translations
```text
(category_id, language_code)
```

---

# 17. Sources of Truth

مصادر الحقيقة الوحيدة التي يملكها الكتالوج:
* Entity Status
* deleted_at
* Hierarchy Path

---

# 18. Architecture Status

**Locked**
جميع القرارات المعمارية الداخلية الخاصة بتصنيف الفئات وهيكليتها محسومة بشكل كامل في هذه الوثيقة. آليات التكامل والربط مع الكيانات الأخرى (مثل المنتجات) محددة صراحة كمسؤولية للـ Host/Integration Layer ولن تُعالج داخل هذا الموديول.
