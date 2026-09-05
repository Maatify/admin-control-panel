# Product V1 Database Architecture — Locked

**Standalone Product and Variant Engine**

هذه الوثيقة هي المرجع المعماري المقفول لـ **Product V1** بعد عملية إعادة الهيكلة وفصل الدومينات.
يملك الموديول دومين المنتجات، الـ Variants، الخيارات (Options)، الخصائص المجمعة (Composition)، والميديا التابعة لها.

---

# 1. الرؤية المعمارية والنطاق

## 1.1 الهدف

`Product V1` هو موديول مخصص لإدارة تعريف المنتجات التجارية وهوياتها وتشكيلاتها (Variants)، بحيث يكون مستقلاً بشكل كامل عن Catalog، Pricing، و Inventory.

---

## 1.2 النطاق المشمول

يشمل V1:

* Product identity, code, slug.
* Product Translations.
* Lifecycle/status (active/inactive).
* Product Options, Option Values, Option Translations.
* Variants, SKU, Barcode, Default variant flag.
* Variant Composition.
* Replacement flows لإضافة/إزالة الخيارات.
* Product & Variant Media metadata.

---

## 1.3 خارج النطاق صراحة

لا يحتوي V1 على:

* Categories, Catalog Hierarchy (مملوكة لـ Catalog).
* الأسعار (Base Price, Adjustments) والعملات (مملوكة لـ Pricing).
* المخزون، الكميات المتاحة (`quantity_on_hand`) (مملوكة لـ Inventory).
* Customers, Orders, Payments, Shipping.

لا توجد FKs تشير إلى أي موديول خارجي. العلاقة مع الـ Categories يتم إدارتها خارج الموديول (عبر Host/Integration).

---

# 2. Host-Agnostic Boundaries

* لا يتم استخدام FKs لجدول خارجي.
* `language_code VARCHAR(16) NOT NULL` للغات (BCP-47).

---

# 3. Timestamp Policy

التاريخ يُدار عبر التطبيق بنظام UTC:
* `created_at DATETIME NOT NULL`
* `updated_at DATETIME NOT NULL`
* `deleted_at DATETIME NULL`

---

# 4. Lifecycle and Status

المنتجات والـ Variants تستخدم `status` بدلاً من البوليان:
```text
CHECK (status IN ('active','inactive'))
```

* الـ Soft Delete يتم عبر `deleted_at`.
* **Visibility:**
  * Product.status = inactive تعني مخفية، لكن لا يُعدّل الـ Status للـ Variants.
  * الـ Delete لا يتأثر بموديولات أخرى، ولا يؤدي للـ Cascade الحذف المنطقي.

---

# 5. Logical Identity Immutability

هويات الربط لا تتغير بعد الإنشاء:
* `product_translation` (product_id, language_code)
* `product_option` (product_id)
* `option_translation` (option_id, language_code)
* `option_value` (option_id)
* `variant_option_value` (variant_id, option_id, option_value_id)

**Exceptions:** `product.slug` و `barcode` قابلان للتعديل. الـ `code` والـ `SKU` ثابتان (Immutable).

---

# 6. Variant & Option Concepts

* **Variant Composition Immutable:** بعد الإنشاء، السجلات في `variant_option_values` لا تتغير. أي تغيير يتطلب Variant جديدة بـ SKU جديد.
* **Full Active Option Coverage:** أي Variant فعّالة لمنتج Configurable يجب أن تغطي جميع الخيارات الفعّالة للمنتج (قيمة واحدة لكل Option).
* **Effectively Selectable Variant:** كيان قابل للاختيار إذا كان Product, Variant, Option, و Option Value كلها `active` وغير محذوفة.
* **Replacement Flow:** عند إضافة/حذف Option من منتج قائم، يجب إنشاء Variants جديدة وعمل Cutover، مع إبقاء الـ Variants القديمة كهويات غير محذوفة (لأغراض تاريخية).

---

# 7. Media Ownership

يخزن الموديول مراجع الميديا (بدون ملفات حقيقية):
* إذا كان `variant_id IS NULL` فهي Product Media.
* إذا كان `variant_id IS NOT NULL` فهي Variant Media وتتبع نفس الـ `product_id`.

---

# 8. Complete Database Schema — 10 Tables

## 8.1 `maa_products`
| العمود               | النوع                                     |
| -------------------- | ----------------------------------------- |
| `id`                 | `BIGINT UNSIGNED AUTO_INCREMENT`          |
| `code`               | `VARCHAR(100) NOT NULL`                   |
| `slug`               | `VARCHAR(255) NOT NULL`                   |
| `status`             | `VARCHAR(20) NOT NULL DEFAULT 'inactive'` |
| `display_order`      | `INT NOT NULL DEFAULT 0`                  |
| `created_at`         | `DATETIME NOT NULL`                       |
| `updated_at`         | `DATETIME NOT NULL`                       |
| `deleted_at`         | `DATETIME NULL`                           |

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(code)
UNIQUE(slug)
CHECK(status IN ('active','inactive'))
```

---

## 8.2 `maa_product_translations`
| العمود              | النوع                            |
| ------------------- | -------------------------------- |
| `id`                | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `product_id`        | `BIGINT UNSIGNED NOT NULL`       |
| `language_code`     | `VARCHAR(16) NOT NULL`           |
| `name`              | `VARCHAR(255) NOT NULL`          |
| `short_description` | `VARCHAR(500) NULL`              |
| `description`       | `TEXT NULL`                      |
| `created_at`        | `DATETIME NOT NULL`              |
| `updated_at`        | `DATETIME NOT NULL`              |
| `deleted_at`        | `DATETIME NULL`                  |

Constraints: `UNIQUE(product_id, language_code)`
FK: `product_id → maa_products.id`

---

## 8.3 `maa_product_options`
| العمود          | النوع                                     |
| --------------- | ----------------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT`          |
| `product_id`    | `BIGINT UNSIGNED NOT NULL`                |
| `code`          | `VARCHAR(100) NOT NULL`                   |
| `status`        | `VARCHAR(20) NOT NULL DEFAULT 'inactive'` |
| `display_order` | `INT NOT NULL DEFAULT 0`                  |
| `created_at`    | `DATETIME NOT NULL`                       |
| `updated_at`    | `DATETIME NOT NULL`                       |
| `deleted_at`    | `DATETIME NULL`                           |

Constraints: `UNIQUE(product_id, code)`
FK: `product_id → maa_products.id`

---

## 8.4 `maa_product_option_translations`
| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `option_id`     | `BIGINT UNSIGNED NOT NULL`       |
| `language_code` | `VARCHAR(16) NOT NULL`           |
| `name`          | `VARCHAR(255) NOT NULL`          |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

Constraints: `UNIQUE(option_id, language_code)`
FK: `option_id → maa_product_options.id`

---

## 8.5 `maa_product_option_values`
| العمود          | النوع                                   |
| --------------- | --------------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT`        |
| `option_id`     | `BIGINT UNSIGNED NOT NULL`              |
| `code`          | `VARCHAR(100) NOT NULL`                 |
| `status`        | `VARCHAR(20) NOT NULL DEFAULT 'active'` |
| `display_order` | `INT NOT NULL DEFAULT 0`                |
| `created_at`    | `DATETIME NOT NULL`                     |
| `updated_at`    | `DATETIME NOT NULL`                     |
| `deleted_at`    | `DATETIME NULL`                         |

Constraints: `UNIQUE(option_id, code)`
FK: `option_id → maa_product_options.id`

---

## 8.6 `maa_product_option_value_translations`
| العمود            | النوع                            |
| ----------------- | -------------------------------- |
| `id`              | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `option_value_id` | `BIGINT UNSIGNED NOT NULL`       |
| `language_code`   | `VARCHAR(16) NOT NULL`           |
| `name`            | `VARCHAR(255) NOT NULL`          |
| `created_at`      | `DATETIME NOT NULL`              |
| `updated_at`      | `DATETIME NOT NULL`              |
| `deleted_at`      | `DATETIME NULL`                  |

Constraints: `UNIQUE(option_value_id, language_code)`
FK: `option_value_id → maa_product_option_values.id`

---

## 8.7 `maa_product_variants`
| العمود          | النوع                                     |
| --------------- | ----------------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT`          |
| `product_id`    | `BIGINT UNSIGNED NOT NULL`                |
| `sku`           | `VARCHAR(100) NOT NULL`                   |
| `barcode`       | `VARCHAR(100) NULL`                       |
| `is_default`    | `TINYINT(1) NOT NULL DEFAULT 0`           |
| `status`        | `VARCHAR(20) NOT NULL DEFAULT 'inactive'` |
| `display_order` | `INT NOT NULL DEFAULT 0`                  |
| `created_at`    | `DATETIME NOT NULL`                       |
| `updated_at`    | `DATETIME NOT NULL`                       |
| `deleted_at`    | `DATETIME NULL`                           |

Constraints:
```text
UNIQUE(sku)
UNIQUE(barcode)
CHECK(is_default IN (0,1))
CHECK(status IN ('active','inactive'))
```
FK: `product_id → maa_products.id`

---

## 8.8 `maa_product_variant_option_values`
| العمود            | النوع                            |
| ----------------- | -------------------------------- |
| `id`              | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `variant_id`      | `BIGINT UNSIGNED NOT NULL`       |
| `option_id`       | `BIGINT UNSIGNED NOT NULL`       |
| `option_value_id` | `BIGINT UNSIGNED NOT NULL`       |
| `created_at`      | `DATETIME NOT NULL`              |
| `updated_at`      | `DATETIME NOT NULL`              |
| `deleted_at`      | `DATETIME NULL`                  |

Constraints: `UNIQUE(variant_id, option_id)`
FKs:
`variant_id → maa_product_variants.id`
`option_id → maa_product_options.id`
`option_value_id → maa_product_option_values.id`

---

## 8.9 `maa_product_media`
| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `product_id`    | `BIGINT UNSIGNED NOT NULL`       |
| `variant_id`    | `BIGINT UNSIGNED NULL`           |
| `relative_path` | `VARCHAR(500) NOT NULL`          |
| `mime_type`     | `VARCHAR(100) NULL`              |
| `size_bytes`    | `BIGINT UNSIGNED NULL`           |
| `width`         | `INT UNSIGNED NULL`              |
| `height`        | `INT UNSIGNED NULL`              |
| `is_primary`    | `TINYINT(1) NOT NULL DEFAULT 0`  |
| `display_order` | `INT NOT NULL DEFAULT 0`         |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

Constraints: `CHECK(is_primary IN (0,1))`
FKs:
`product_id → maa_products.id`
`variant_id → maa_product_variants.id`

---

# 9. Domain-Enforced Invariants

* Duplicate Variant Combinations: لا يُسمح بتكرار نفس التركيبة (Combination) لمنتج واحد في `maa_product_variant_option_values` لـ Variants غير محذوفة.
* Option/Value Delete Dependency: لا يمكن عمل Soft Delete لـ Option أو Option Value مرتبطة بـ Variant غير محذوفة.
* Default Variant: خيار اختياري، واحد كحد أقصى لكل منتج.

# 10. Derived States

الـ derived state لـ "Effectively Selectable Variant" تُحسب استناداً لحالة الكيانات الداخلية هنا فقط.

---

# 11. Unresolved Architectural Decisions

* كيفية التنسيق لضمان أن المنتج لن يصبح `active` بالكامل تجارياً إلا إذا كان مسعراً. هذه القاعدة تُركت لـ Host/Integration Layer (أو Event Subscriptions)، حيث أن Product Domain لا يرى Pricing Domain.
