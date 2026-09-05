# Catalog V1 Architecture (Candidate)

هذه الوثيقة تصف البنية المعمارية لـ Taxonomy / Categories (الـ Catalog Base Module).
الحالة: **Candidate** (لأن الكيان الأساسي Base Entity لم يحسم بعد).

لا يحتوي هذا الموديول على أي معلومات أو روابط بجداول المنتجات، الأسعار، أو المخزون.

## 1. الرؤية المعمارية والنطاق

`Catalog Base` هو الوحدة المسؤولة حصرياً عن إدارة شجرة التصنيفات (Categories).
يجب أن يظل:
* Standalone.
* Extractable.
* Host-agnostic.
* لا يحتوي على Cross-module FKs أو JOINs.

## 2. Category Model

* **Status Model:** يستخدم String enum `status VARCHAR(50) NOT NULL` ولا يستخدم boolean flags (مثل `is_active`).
* **Display Order:** حقل `display_order INT NOT NULL DEFAULT 0` مع الترتيب الحتمي `ORDER BY display_order, id`.
* **Hierarchy:** يتم تنظيم التصنيفات في شجرة هرمية باستخدام `parent_id`.
  * Root Category: `parent_id = NULL`.
* **Soft Delete:** يطبق من خلال `deleted_at DATETIME NULL`.
  * السجل النشط: `deleted_at IS NULL`.
* **Timestamps:** يتم إدارتها برمجياً بـ UTC: `created_at` و `updated_at`.

## 3. Database Schema

يجب أن تلتزم الجداول بالشروط الفيزيائية التالية:
```text
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci
```

### 3.1 `maa_catalog_categories` (Unresolved/Candidate Table Name Example)

| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `parent_id`     | `BIGINT UNSIGNED NULL`           |
| `status`        | `VARCHAR(50) NOT NULL`           |
| `display_order` | `INT NOT NULL DEFAULT 0`         |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
CHECK (status IN ('active', 'inactive'))
CHECK (parent_id <> id)
```

**FK:**
```text
parent_id → maa_catalog_categories.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

### 3.2 `maa_catalog_category_translations`

| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `category_id`   | `BIGINT UNSIGNED NOT NULL`       |
| `language_code` | `VARCHAR(16) NOT NULL`           |
| `name`          | `VARCHAR(255) NOT NULL`          |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(category_id, language_code)
```

**FK:**
```text
category_id → maa_catalog_categories.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

## 4. Index Strategy

الـ Indexes تكون Explicit:

```text
maa_catalog_categories
(parent_id, status, deleted_at, display_order, id)
```

## 5. Domain Invariants

يجب فرض:
1. **Category Cycle Prevention:** منع الحلقات الدائرية في الهرمية.
2. **Category Child Delete Dependency:** لا يمكن حذف تصنيف إذا كان لديه تصنيفات فرعية نشطة (أو يتم معالجته وفقًا لقواعد الـ Package، ولكن على مستوى الـ Base Module يجب الحفاظ على التكامل المرجعي الداخلي).

---
*هذه الوثيقة تحل محل بنية الـ Monolith القديمة الخاصة بـ Catalog V1.*
