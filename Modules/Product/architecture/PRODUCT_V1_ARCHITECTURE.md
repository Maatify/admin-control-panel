# Product V1 Database Architecture — Locked

**Standalone Product and Variant Engine**

هذه الوثيقة هي المرجع المعماري المقفول لـ **Product V1** بعد عملية إعادة الهيكلة وفصل الدومينات.
يملك الموديول دومين المنتجات، الـ Variants، الخيارات (Options)، الخصائص المجمعة (Composition)، والميديا التابعة لها.

---

# 1. الرؤية المعمارية والنطاق

## 1.1 الهدف

`Product V1` هو موديول مخصص لإدارة تعريف المنتجات التجارية وهوياتها وتشكيلاتها (Variants)، بحيث يكون مستقلاً بشكل كامل.

---

## 1.2 النطاق المشمول

يشمل V1:

* Product identity, code, slug.
* Product Translations.
* Lifecycle/status (active/inactive).
* `force_out_of_stock` flag.
* Product Options, Option Values, Option Translations.
* Variants, SKU, Barcode, Default variant flag.
* Variant Composition.
* Replacement flows لإضافة/إزالة الخيارات.
* Product & Variant Media metadata.
* Configuration Rules (Simple vs Configurable Products, Direct Resolution, Effective Selectability).

---

## 1.3 خارج النطاق صراحة

لا يحتوي V1 على:

* Categories, Catalog Hierarchy.
* الأسعار والعملات.
* المخزون، الكميات المتاحة (`quantity_on_hand`).
* Customers, Orders, Payments, Shipping.

لا توجد FKs تشير إلى أي موديول خارجي.

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
  * Product.status = inactive تعني مخفية عالمياً، لكن لا يُعدّل الـ Status للـ Variants.
  * الـ Delete لا يتأثر بموديولات أخرى، ولا يؤدي للـ Cascade الحذف المنطقي.

---

# 5. Logical Identity Immutability

هويات الربط لا تتغير بعد الإنشاء:
* `product_translation` (product_id, language_code)
* `product_option` (product_id)
* `option_translation` (option_id, language_code)
* `option_value` (option_id)
* `variant_option_value` (variant_id, option_id, option_value_id)

**Exceptions:** `product.slug` و `barcode` قابلان للتعديل (لا نحتفظ بتارييخهما هنا). الـ `code` والـ `SKU` ثابتان (Immutable).

---

# 6. Variant & Option Concepts

* **Variant Composition Immutable:** بعد الإنشاء، السجلات في `variant_option_values` لا تتغير. أي تغيير يتطلب Variant جديدة بـ SKU جديد.
* **Variant-Defining Options:** كل الخيارات إجبارية في سياق تشكيل الـ Variant. لا يوجد حقل `is_required`.
* **Full Active Option Coverage:** أي Variant فعّالة لمنتج Configurable يجب أن تغطي جميع الخيارات الفعّالة للمنتج (قيمة واحدة لكل Option فعال).
* **Effectively Selectable Variant:** كيان يُعتبر قابلاً للاختيار هيكلياً فقط إذا كان Product, Variant, Option, و Option Value كلها `active` وغير محذوفة وتمتلك Coverage كامل. (هذا لا يأخذ بالاعتبار المخزون أو السعر، بل هو تقييم لهيكلية الـ Product).
* **Customer-Selectable Values:** القيم المتاحة للعميل لاختيارها تُستمد فقط من الـ Variants التي تعد Effectively Selectable.
* **Simple Product:** منتج ليس لديه خيارات فعالة ويمتلك Exactly One Effectively Selectable Variant.
* **Configurable Product:** منتج لديه خيارات فعالة.
* **Direct Sellable Resolution:** النظام يمكنه تحديد Variant مباشرة بدون تدخل العميل (يتحقق في الـ Simple Product).
* **Replacement Flow:** عند إضافة/حذف Option من منتج قائم، يجب إنشاء Variants جديدة وعمل Cutover، مع إبقاء الـ Variants القديمة كهويات غير محذوفة (لأغراض تاريخية).
* **Default Variant:**
  * `is_default TINYINT(1) NOT NULL DEFAULT 0`
  * كحد أقصى توجد Default Variant واحدة غير محذوفة لكل منتج.
  * يجب أن تكون Active و Effectively Selectable.
  * في حال فَقَدت صلاحيتها، يُزال الـ Default أو يُنقل خلال نفس المعاملة (Transaction).

---

# 7. Media Ownership

يخزن الموديول مراجع الميديا (بدون ملفات حقيقية):
* إذا كان `variant_id IS NULL` فهي Product Media.
* إذا كان `variant_id IS NOT NULL` فهي Variant Media وتتبع نفس الـ `product_id`.
* بحد أقصى: Primary Product Media واحدة غير محذوفة لكل Product، و Primary Variant Media واحدة غير محذوفة لكل Variant.
* الـ Restore لأي Primary Media يجب أن يحل الـ Conflict داخلياً.

---

# 8. Complete Database Schema — 9 Tables

## 8.1 `maa_products`
| العمود               | النوع                                     |
| -------------------- | ----------------------------------------- |
| `id`                 | `BIGINT UNSIGNED AUTO_INCREMENT`          |
| `code`               | `VARCHAR(100) NOT NULL`                   |
| `slug`               | `VARCHAR(255) NOT NULL`                   |
| `status`             | `VARCHAR(20) NOT NULL DEFAULT 'inactive'` |
| `force_out_of_stock` | `TINYINT(1) NOT NULL DEFAULT 0`           |
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
CHECK(force_out_of_stock IN (0,1))
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

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(product_id, language_code)
```
FK:
```text
product_id → maa_products.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

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

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(product_id, code)
CHECK(status IN ('active','inactive'))
```
FK:
```text
product_id → maa_products.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

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

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(option_id, language_code)
```
FK:
```text
option_id → maa_product_options.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

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

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(option_id, code)
CHECK(status IN ('active','inactive'))
```
FK:
```text
option_id → maa_product_options.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

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

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(option_value_id, language_code)
```
FK:
```text
option_value_id → maa_product_option_values.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

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
PRIMARY KEY(id)
UNIQUE(sku)
UNIQUE(barcode)
CHECK(is_default IN (0,1))
CHECK(status IN ('active','inactive'))
```
FK:
```text
product_id → maa_products.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

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

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(variant_id, option_id)
```
FKs:
```text
variant_id → maa_product_variants.id
ON DELETE RESTRICT
ON UPDATE RESTRICT

option_id → maa_product_options.id
ON DELETE RESTRICT
ON UPDATE RESTRICT

option_value_id → maa_product_option_values.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```
*Composition is Immutable after Variant creation.*

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

Constraints:
```text
PRIMARY KEY(id)
CHECK(is_primary IN (0,1))
```
FKs:
```text
product_id → maa_products.id
ON DELETE RESTRICT
ON UPDATE RESTRICT

variant_id → maa_product_variants.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```
*Cross-validation (variant.product_id = media.product_id) enforced by domain.*

---

# 9. Index Strategy

الـIndexes تكون Explicit ولا تضاف Redundant Indexes بلا Query Requirement.

## Products
```text
maa_products
(status, deleted_at, display_order, id)
```

## Options & Values
```text
maa_product_options
(product_id, status, deleted_at, display_order, id)

maa_product_option_values
(option_id, status, deleted_at, display_order, id)
```

## Variants
```text
maa_product_variants
(product_id, status, deleted_at, display_order, id)
```

## Variant Composition
بالإضافة للـ `UNIQUE(variant_id, option_id)`، يُضاف:
```text
(option_value_id, deleted_at, variant_id)
(option_id, deleted_at, variant_id)
```

## Media
```text
maa_product_media
(product_id, variant_id, deleted_at, is_primary, display_order, id)
(variant_id, deleted_at, is_primary, display_order, id)
```

## Translations
```text
UNIQUE(product_id, language_code)
UNIQUE(option_id, language_code)
UNIQUE(option_value_id, language_code)
```

---

# 10. Database-Enforced Invariants

* Primary Keys, Internal FKs.
* `ON DELETE RESTRICT` و `ON UPDATE RESTRICT`.
* Unique Stable Codes, Unique Slug, Unique SKU, Unique Barcode.
* Unique Translation Identities.
* Unique Option Code per Product, Unique Value Code per Option.
* Unique `(variant_id, option_id)`.
* Status Constraints.
* Boolean Constraints.

---

# 11. Domain / Transaction-Enforced Invariants

* **Option/Value Delete Dependency:** منع Soft Delete إذا كانت مرتبطة بـ Variant غير محذوفة.
* **Option/Value Integrity & Cross-Product Composition Prevention:** التأكد من تبعية القيم والخيارات لنفس المنتج الخاص بالـ Variant.
* **Duplicate Variant Combination Prevention:** منع إنشاء Variants مختلفة بنفس تركيبة الخيارات.
* **Immutable Variant Composition:** لا يجوز التعديل على سجلات التركيبة لـ Variant قائمة.
* **Full Active Option Coverage:** التفعيل يتطلب تغطية تامة للخيارات.
* **Replacement Flows:** عند التعديل الهيكلي على خيارات المنتج.
* **Restore Safety:** ضمان الـ Invariants عند الـ Restore.
* **Max one valid Default Variant & Conflict Handling.**
* **Media Ownership & Primary Media Limits / Conflict Handling.**

---

# 12. Sources of Truth و Prohibited Duplicated Fields

لا يجوز إضافة مصادر حقيقة مكررة.
مصادر الحقيقة المدعومة هنا حصراً:
* Entity Status
* deleted_at
* force_out_of_stock
* Variant Composition

لا يتم تخزين `is_selectable` أو `stock` أو `final_price`، فهذه تعتبر Cross-domain derived states ولا تخص الـ Product منفردًا.

---

# 13. Architecture Status

**Locked**
تم حسم جميع القرارات المتعلقة بهيكل المنتجات وتشكيلاتها داخليًا.
لا يُسمح بإضافة أية اشتراطات تعتمد على موديولات أخرى للتحقق من صحة الكيانات (مثل التحقق من السعر للتفعيل). التكامل الأوسع بين المنتجات والكيانات الأخرى متروك لطبقة الـ Host Coordinator أو عبر الأحداث.
