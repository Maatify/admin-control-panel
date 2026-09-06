# Catalog V1 Database Architecture — Candidate

**Host-Agnostic Catalog Engine**

هذه الوثيقة هي المرجع المعماري لـ **Catalog V1** بعد إعادة الهيكلة المستندة إلى الـ Domain Decomposition الحقيقي.
في هذه المعمارية، الـ Catalog Module هو المالك الحصري لدومين الـ Catalog والتنظيم الهرمي (Hierarchy)، ولا يمتلك أية تفاصيل عن الدومينات الأخرى (مثل Products, Pricing, Inventory).

---

# 1. الرؤية المعمارية والنطاق

## 1.1 الهدف

`Catalog V1` هو:

**Standalone, Extractable, Host-Agnostic Catalog Engine**

ويجب أن يظل قابلًا للاستخراج مستقبلًا كمكتبة مستقلة دون الاعتماد على تفاصيل الـHost أو موديولات أخرى.

### Catalog Domain

الوثيقة الأصلية ذكرت "Catalog Identity" ولم تحدد جدولًا منفصلًا باسم `maa_catalog_catalogs` أو ما شابه يمثل الـ Catalog ككيان مستقل (Entity)، بل اعتمدت على `Categories` كوحدة التنظيم الأساسية.
**وبما أنه لا يجوز أن نطلق على الموديول اسم Catalog بينما هو لا يحتوي على كيان Catalog حقيقي، فقد تم تسجيل هذا القرار كـ Unresolved Decision (انظر القسم الأخير).**

حتى يتم حسم ما إذا كان سيتم إضافة كيان Catalog مستقل يمتلك الـ Categories أم سيظل الـ Module عبارة عن Taxonomy/Categories فقط، تعتبر هذه الوثيقة **Candidate** وليست Locked.

---

## 1.2 النطاق المشمول

يشمل V1 (حتى الآن بناءً على الهيكلية المعروفة):

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
* Product ↔ Category Relations. (متروكة لـ Host/Integration Layer).
* Monetary values, Prices, Adjustments.
* Inventory, quantity_on_hand.
* Media Metadata.
* Customers, Orders, Payments, Shipping.
* Promotions, Discounts.

الـ Catalog يجب ألا يعرف أي شيء عنهم، وأي ربط خارجي يُدار من خلال الـ Host/Integration Layer.

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

العقد الكامل يشمل:
* Storage in UTC.
* Application-managed.
* `created_at` تُحدد عند الإنشاء.
* `updated_at` تُحدث عند أي mutation (تعديل).
* `deleted_at` تُحدد عند الـ soft delete.
* `updated_at` تُحدث أيضًا وقت الـ soft delete والـ restore.
* `deleted_at IS NULL` تعني السجل فعّال، ولا يتم استخدام runtime hard-delete للحذف العادي.

كل جدول يحتوي:

```text
created_at DATETIME NOT NULL
updated_at DATETIME NOT NULL
deleted_at DATETIME NULL
```

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
لا يتم توليد Identity جديدة؛ الاستعادة تُحيي نفس الهوية السابقة.

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
بما أن MySQL لا تسمح لـ `CHECK` بالرجوع إلى عمود يحمل `AUTO_INCREMENT`، يفرض
الـDB نفس invariant عبر triggerين مملوكين للموديول:

* Trigger على `INSERT` يرفض `parent_id = id` بعد تخصيص الـ`AUTO_INCREMENT` identity.
* Trigger على `UPDATE` يرفض أي حالة يصبح فيها `parent_id = id`.

تظل القاعدة الدلالية ثابتة: `parent_id IS NULL OR parent_id <> id`.
Domain تمنع العلاقات الدائرية (Cycle Prevention: A → B → C → A).

---

# 13. Complete Database Schema — 2 Tables


يجب أن تلتزم الجداول بالشروط الفيزيائية التالية:
```text
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci
```


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
Package-owned INSERT/UPDATE triggers reject parent_id = id
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
* Package-owned Category triggers enforce `parent_id <> id` on INSERT and UPDATE;
  MySQL cannot express this invariant as a CHECK while `id` is AUTO_INCREMENT.
* **Column Comments Requirement:** تطبيقًا للـ Package Building Standard، تلتزم كافة المخططات (Schemas) المذكورة هنا بتوفير تعليقات دلالية (Meaningful Comments) في مرحلة التنفيذ الفعلي (Implementation) توضح الغرض من كل عمود، خصوصًا الهويات الخارجية إن وجدت.

---

# 15. Domain / Transaction-Enforced Invariants

* Category Cycle Prevention.
* Category Child Delete Dependency: لا يمكن حذف تصنيف إذا كان لديه تصنيفات فرعية غير محذوفة (non-deleted children).

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

مصادر الحقيقة الوحيدة التي يملكها الموديول حالياً:
* Entity Status (Categories)
* deleted_at
* الـ `parent_id` (كمصدر الحقيقة الوحيد للهرمية).

**الـ Derived State:**
* الـ Hierarchy Path يعتبر مشتقاً (Derived State) من الـ `parent_id` ولا يعتبر مصدر حقيقة مستقل.

---

# 18. Unresolved Architectural Decisions

* **Catalog Entity Identity:** الوثيقة الأصلية للـ Monolith ذُكر فيها "Catalog Identity" كمفهوم، ولكن المخطط الفعلي لا يحتوي على جدول يمثل "الكتالوج" (مثلاً `maa_catalog_catalogs`) كحاوية عليا تملك الـ Categories. هل يجب إضافة كيان Catalog حقيقي لتبرير اسم الموديول؟ أم يجب إعادة تسمية الموديول لاحقاً ليعكس كونه مجرد Taxonomy / Categories engine؟
هذا القرار الداخلي غير محسوم، وبالتالي الموديول ككل لا يمكن اعتباره Locked.

# 19. Architecture Status

**Candidate**
بسبب وجود قرار معماري داخلي غير محسوم حول "Catalog Entity" وهويتها، لا يمكن اعتبار هذه البنية Locked.
