# Catalog V1 / Category Package Database Architecture — Candidate

**Host-Agnostic Category Package Reference**

هذه الوثيقة هي مرجع معماري مرَحلي لتكامل **Catalog V1** مع مكتبة الـ Category المستقلة.
المرجع الداخلي الحاكم لتنفيذ الـ Category وSchema وInvariants هو توثيق مكتبة `maatify/category`؛ أما `Modules/Catalog` في مشروع الـ Admin فليس مالكًا لتطبيق أو Persistence الـ Category.

---

# 1. الرؤية المعمارية والنطاق

## 1.1 الهدف

`maatify/category` هو:

**Standalone, Reusable, Host-Agnostic Category Package**

ويمكن استهلاكه بواسطة Catalog أو Navigation أو أي Host آخر دون الاعتماد على تفاصيل الـHost أو موديولات أخرى.

### Category Domain

الوثيقة الأصلية ذكرت "Catalog Identity" ولم تحدد جدولًا منفصلًا باسم `maa_catalog_catalogs` أو ما شابه يمثل الـ Catalog ككيان مستقل (Entity)، بل اعتمدت على `Categories` كوحدة التنظيم الأساسية.
**وبما أنه لا يجوز أن نطلق على الـ Category package اسم Catalog بينما هو لا يحتوي على كيان Catalog حقيقي، فقد تم تسجيل هذا القرار كـ Unresolved Decision (انظر القسم الأخير).**

حتى يتم حسم ما إذا كان سيتم إضافة كيان Catalog مستقل يمتلك الـ Categories في طبقة التكامل أم سيظل النظام يستخدم Taxonomy/Categories package فقط، تعتبر هذه الوثيقة **Candidate** وليست Locked.

---

## 1.2 النطاق المشمول

يشمل Category V1 (حتى الآن بناءً على الهيكلية المعروفة):

* Categories.
* Category Hierarchy (Parent-Child).
* Category Translations.
* Category Status & Visibility.
* Category Display Ordering.
* Category Lifecycle (Soft delete / restore).
* Internal Domain/Transaction Invariants (Cycle prevention).

---

## 1.3 خارج النطاق صراحة (Explicit Out-of-Scope)

لا تحتوي مكتبة Category V1 ولا يجب توسيع مخططها من أجل دعم ما يخص الـ Domains الأخرى:

* Products, Variants, SKU, Barcode.
* Product Options, Option Values.
* Product ↔ Category Relations. (متروكة لـ Host/Integration Layer).
* Monetary values, Prices, Adjustments.
* Inventory, quantity_on_hand.
* Media Metadata.
* Customers, Orders, Payments, Shipping.
* Promotions, Discounts.

مكتبة Category يجب ألا تعرف أي شيء عنهم، وأي ربط خارجي يُدار من خلال الـ Host/Integration Layer.

---

# 2. Host-Agnostic Boundaries

مكتبة Category لا تعتمد على جداول الـHost.
لا توجد Foreign Keys أو JOINs من Category إلى أي Module آخر.

---

## 2.1 اللغات

تخزن مكتبة Category:

```text
language_code VARCHAR(16) NOT NULL
```

وهو BCP-47 code.
الـHost مسؤول عن التحقق من صحة `language_code` والـ Fallback Chain.

---

# 3. Timestamp Policy

كل Timestamps تدار بواسطة **Category Application Layer**.

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

كل جداول مكتبة Category تستخدم Soft Delete.

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

كل Foreign Keys الداخلية في مكتبة Category تستخدم:

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

يتطلب هذا العقد MySQL `8.0.16+` لأن الإصدارات الأقدم من MySQL 8 كانت تقبل صياغة `CHECK` دون تنفيذها فعليًا.

---

# 10. Display Order

```text
display_order INT NOT NULL DEFAULT 0
```
تُستخدم للـ Categories ضمن نفس الـ `parent_id` داخل مكتبة Category.
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
لا تسمح MySQL بأن يشير `CHECK` إلى عمود `AUTO_INCREMENT` بهذه الصورة، لذلك لا تستخدم `CHECK` لهذا الشرط.
بدلًا من ذلك، تفرض مكتبة `maatify/category` invariant `parent_id <> id` عبر triggers مملوكة للحزمة: `AFTER INSERT` بعد توليد الـ identity، و`BEFORE UPDATE`.
Domain تمنع العلاقات الدائرية (Cycle Prevention: A → B → C → A).

---

# 13. Complete Category Package Database Schema — 2 Tables


يجب أن تلتزم الجداول بالشروط الفيزيائية التالية:
```text
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci
```


## 13.1 `maa_category_categories`

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
Package-owned INSERT/UPDATE triggers reject `parent_id = id`
```
FK:
```text
parent_id → maa_category_categories.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

---

## 13.2 `maa_category_category_translations`

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
category_id → maa_category_categories.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

---

# 14. Database-Enforced Invariants

* Primary Keys, Internal FKs (ON DELETE/UPDATE RESTRICT).
* Unique Stable Codes, Unique Translation Identities.
* Status constraints (active/inactive).
* Package-owned `AFTER INSERT` and `BEFORE UPDATE` triggers enforce `parent_id <> id` after generated identity allocation and on updates; this is not represented as a `CHECK` constraint.
* **Column Comments Requirement:** تطبيقًا للـ Package Building Standard، تلتزم كافة المخططات (Schemas) المذكورة هنا بتوفير تعليقات دلالية (Meaningful Comments) في مرحلة التنفيذ الفعلي (Implementation) توضح الغرض من كل عمود، خصوصًا الهويات الخارجية إن وجدت.

---

# 15. Domain / Transaction-Enforced Invariants

* Category Cycle Prevention.
* Category Child Delete Dependency: لا يمكن حذف تصنيف إذا كان لديه تصنيفات فرعية غير محذوفة (non-deleted children).

---

# 16. Index Strategy

## Categories
```text
maa_category_categories
(parent_id, status, deleted_at, display_order, id)
```

## Translations
```text
(category_id, language_code)
```

---

# 17. Sources of Truth

مصادر الحقيقة الوحيدة التي تملكها مكتبة Category حالياً:
* Entity Status (Categories)
* deleted_at
* الـ `parent_id` (كمصدر الحقيقة الوحيد للهرمية).

**الـ Derived State:**
* الـ Hierarchy Path يعتبر مشتقاً (Derived State) من الـ `parent_id` ولا يعتبر مصدر حقيقة مستقل.

---

# 18. Unresolved Architectural Decisions

* **Catalog Entity Identity:** الوثيقة الأصلية للـ Monolith ذُكر فيها "Catalog Identity" كمفهوم، ولكن المخطط الفعلي لا يحتوي على جدول يمثل "الكتالوج" (مثلاً `maa_catalog_catalogs`) كحاوية عليا تملك الـ Categories. هل يجب إضافة كيان Catalog حقيقي في طبقة الـ Catalog/Host؟ أم يجب الاكتفاء بمكتبة Category مستقلة مع طبقة تكامل؟
هذا القرار على مستوى تكامل الـ Catalog غير محسوم، ولذلك لا يمكن اعتبار هذه الوثيقة Locked.

# 19. Architecture Status

**Candidate**
بسبب وجود قرار معماري غير محسوم على مستوى "Catalog Entity" وهويتها، لا يمكن اعتبار هذه البنية Locked.
