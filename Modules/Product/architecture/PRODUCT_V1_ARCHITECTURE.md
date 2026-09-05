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

التاريخ يُدار عبر التطبيق بنظام UTC. العقد الكامل:
* Application-managed.
* `created_at` تُحدد عند الإنشاء.
* `updated_at` تُحدث عند أي mutation (تعديل).
* `deleted_at` تُحدد عند الـ soft delete.
* `updated_at` تُحدث أيضًا وقت الـ soft delete والـ restore.
* `deleted_at IS NULL` تعني السجل فعّال، ولا يتم استخدام runtime hard-delete.

```text
created_at DATETIME NOT NULL
updated_at DATETIME NOT NULL
deleted_at DATETIME NULL
```

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

# 5. Logical Identity Immutability & Reference Lifecycles

هويات الربط لا تتغير بعد الإنشاء:
* `product_translation` (product_id, language_code)
* `product_option` (product_id)
* `option_translation` (option_id, language_code)
* `option_value` (option_id)
* `variant_option_value` (variant_id, option_id, option_value_id)

**Variant Ownership Immutability:**
Variant ownership by `product_id` is immutable. لا يمكن نقل Variant من Product إلى Product آخر تحت أي ظرف.

**Stable Codes (Immutable):**
الـ `code` والـ `SKU` هي هويات ثابتة (Stable & Immutable) ولا يعاد استخدامها بعد الحذف المنطقي.

**Product Slug Lifecycle:**
* `slug` هي قيمة مسار (Routing Value) يمكن تغييرها (Mutable).
* لا يحتفظ V1 بتاريخ الـ Slugs القديمة.
* تظل الـ records المحذوفة منطقيًا تحتفظ بالـ Unique Constraint للـ `slug`.

**Barcode Lifecycle:**
* `barcode` هي قيمة فريدة، يمكن تغييرها (Mutable)، وتسمح بالـ `NULL`.
* لا يحتفظ V1 بتاريخ الـ Barcodes القديمة.
* تظل الـ records المحذوفة منطقيًا تحتفظ بالـ Unique Constraint للـ `barcode`.

---

# 6. Variant & Option Concepts

* **Variant Composition Immutable:** بعد الإنشاء، السجلات في `variant_option_values` لا تتغير. أي تغيير يتطلب Variant جديدة بـ SKU جديد.
* **Variant-Defining Options:** كل الخيارات إجبارية في سياق تشكيل الـ Variant. لا يوجد حقل `is_required`.
* **Full Active Option Coverage:** أي Variant فعّالة لمنتج Configurable يجب أن تغطي جميع الخيارات الفعّالة للمنتج (قيمة واحدة لكل Option فعال).
* **Effectively Selectable Variant:** كيان يُعتبر قابلاً للاختيار هيكلياً فقط إذا كان Product, Variant, Option, و Option Value كلها `active` وغير محذوفة وتمتلك Coverage كامل. (هذا لا يأخذ بالاعتبار المخزون أو السعر، بل هو تقييم لهيكلية الـ Product).
* **Customer-Selectable Values:** القيم المتاحة للعميل لاختيارها تُستمد فقط من الـ Variants التي تعد Effectively Selectable بناءً على الهيكلية فقط.
* **Simple Product:** منتج ليس لديه خيارات فعالة ويمتلك Exactly One Effectively Selectable Variant.
* **Configurable Product:** منتج لديه خيارات فعالة.
* **Direct Sellable Resolution:** المنتجات البسيطة (Simple Products) يتم حلها مباشرة إلى Exactly One Effectively Selectable Variant (ولا يشترط أن تكون is_default). المخزون والتسعير لا يشاركان في هذه الخطوة (Stock does not participate in Direct Resolution).

---

# 7. Lifecycles, Creation, and Replacement Flows

* **Product/Variant Creation Lifecycle:** المنتجات والـ Variants والـ Options الجديدة تبدأ بـ `status = 'inactive'`، ويجوز إنشاؤها وتكوين الـ Composition (Staged Composition) حتى لو كانت الخيارات المرتبطة لا تزال غير مفعلة. لا تصبح Variant `active` إلا بعد أن تكتمل البنية ويكون كل شيء صحيحًا (Full Coverage).
* **Staged Composition:** Variant جديدة Inactive يمكن أثناء التجهيز أن تحتوي Composition تشير إلى Option ما زالت Inactive. يتم التفعيل في Atomic Operation واحد عند الجاهزية.

**Replacement Flows (Add Option):**
1. Create new Option as inactive.
2. Create Option Values.
3. Create replacement Variants (with new SKUs, full combination) as inactive.
4. Validate replacements.
5. Atomic Cutover: deactivate old Variants, activate new Option, activate replacement Variants.
6. Preserve old Variant identities unchanged.

**Replacement Flows (Remove Option):**
1. Prepare replacement Variants without the Option (new SKUs).
2. Validate replacements.
3. Atomic Cutover: deactivate old Variants, deactivate removed Option, activate replacement Variants.

---

# 8. Override & Conflict Handling Concepts

* **force_out_of_stock:**
  * Flag إداري `TINYINT(1)` مملوك للمنتج.
  * معناه داخل دومين المنتج المستقل: "إشارة إدارية تقضي بحجب إتاحة المنتج بغض النظر عن حالته الفيزيائية". الـ Host هو الذي يدمج هذا الـ Flag مع مخزون الكيان الخارجي لإنتاج الـ Derived "Stock State".

* **Default Variant:**
  * `is_default TINYINT(1) NOT NULL DEFAULT 0`
  * كحد أقصى توجد Default Variant واحدة غير محذوفة لكل منتج.
  * يجب أن تكون Active و Effectively Selectable هيكليًا.
  * **Conflict Handling:** في حال فَقَدت صلاحيتها أو عند استعادة Variant محذوفة (Restore)، يتم حل الـ Conflict داخلياً بإزالة الـ Default القديم أو نقله في نفس الـ Transaction لضمان بقاء 1 بحد أقصى.

* **Restore Safety:**
  * الـ Restore ليس مجرد `deleted_at = NULL`. يجب أن يتم تقييم الـ Invariants الداخلية للمنتج والـ Variant (مثل Unique constraints و Composition Coverage و Default limits). إذا كانت الحالة المحفوظة لا تستوفي القيود الهيكلية الحالية، ترفض العملية ولا يُعدل التركيب تلقائيًا. لا تتولد هوية جديدة بل يُستعاد السجل السابق بالكامل.

---

# 9. Media Ownership & Primary Limits

يخزن الموديول مراجع الميديا (بدون ملفات حقيقية):
* إذا كان `variant_id IS NULL` فهي Product Media.
* إذا كان `variant_id IS NOT NULL` فهي Variant Media وتتبع نفس الـ `product_id`.
* **Primary Limits:** بحد أقصى Primary Product Media واحدة غير محذوفة لكل Product، و Primary Variant Media واحدة غير محذوفة لكل Variant.
* **Primary Restore Conflict Handling:** الـ Restore لأي Primary Media يجب أن يحل الـ Conflict داخلياً بتعطيل Primary السابقة إذا وُجدت في نفس الـ Transaction.

---



# 10. Display Order Scopes

كل Business-controlled list يجب أن يكون ترتيبها deterministic ويكون Scope-based:
* **Products:** Global.
* **Product Options:** per Product.
* **Option Values:** per Option.
* **Variants:** per Product.
* **Product Media:** per Product.
* **Variant Media:** per Variant.

الترتيب الحتمي يُطبق عن طريق:
```sql
ORDER BY display_order, id
```

# 11. Complete Database Schema — 9 Tables


يجب أن تلتزم الجداول بالشروط الفيزيائية التالية:
```text
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci
```


## 11.1 `maa_product_products`
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

## 11.2 `maa_product_translations`
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
product_id → maa_product_products.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

---

## 11.3 `maa_product_options`
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
product_id → maa_product_products.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

---

## 11.4 `maa_product_option_translations`
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

## 11.5 `maa_product_option_values`
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

## 11.6 `maa_product_option_value_translations`
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

## 11.7 `maa_product_variants`
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
product_id → maa_product_products.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

---

## 11.8 `maa_product_variant_option_values`
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

---

## 11.9 `maa_product_media`
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
product_id → maa_product_products.id
ON DELETE RESTRICT
ON UPDATE RESTRICT

variant_id → maa_product_variants.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```
*Cross-validation (variant.product_id = media.product_id) enforced by domain.*

---

# 12. Index Strategy

الـIndexes تكون Explicit ولا تضاف Redundant Indexes بلا Query Requirement.

## Products
```text
maa_product_products
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

# 12. Database-Enforced Invariants

* Primary Keys, Internal FKs.
* `ON DELETE RESTRICT` و `ON UPDATE RESTRICT`.
* Unique Stable Codes, Unique Slug, Unique SKU, Unique Barcode.
* Unique Translation Identities.
* Unique Option Code per Product, Unique Value Code per Option.
* Unique `(variant_id, option_id)`.
* Status Constraints.
* Boolean Constraints.
* **Column Comments Requirement:** تطبيقًا للـ Package Building Standard، تلتزم كافة المخططات (Schemas) المذكورة هنا بتوفير تعليقات دلالية (Meaningful Comments) في مرحلة التنفيذ الفعلي (Implementation) توضح الغرض من كل عمود، خصوصًا الهويات الخارجية.

---

# 13. Domain / Transaction-Enforced Invariants

* **Option/Value Delete Dependency:** منع Soft Delete إذا كانت مرتبطة بـ Variant غير محذوفة.
* **Option/Value Integrity & Cross-Product Composition Prevention:** التأكد من تبعية القيم والخيارات لنفس المنتج الخاص بالـ Variant.
* **Duplicate Variant Combination Prevention:** منع إنشاء Variants مختلفة بنفس تركيبة الخيارات Transactionally.
* **Immutable Variant Composition.**
* **Full Active Option Coverage.**
* **Replacement Flows Atomic Steps.**
* **Restore Safety Validation.**
* **Max one valid Default Variant & Conflict Handling.**
* **Primary Media Limits & Primary Restore Conflict Handling.**

---

# 14. Sources of Truth و Prohibited Duplicated Fields

لا يجوز إضافة مصادر حقيقة مكررة.
مصادر الحقيقة المدعومة هنا حصراً:
* Entity Status
* deleted_at
* force_out_of_stock
* Variant Composition

لا يتم تخزين `is_selectable` أو `stock` أو `final_price`، فهذه تعتبر Cross-domain derived states ولا تخص الـ Product منفردًا.

---

# 15. Architecture Status

**Locked**
تم حسم جميع القرارات المتعلقة بهيكل المنتجات وتشكيلاتها داخليًا، بما فيها دورة حياة الـ slug والـ barcode والـ staged composition والـ timestamp contracts. لا يُسمح بإضافة أية اشتراطات تعتمد على موديولات أخرى للتحقق من صحة الكيانات. التكامل الأوسع بين المنتجات والكيانات الأخرى متروك لطبقة الـ Host Coordinator أو عبر الأحداث.
