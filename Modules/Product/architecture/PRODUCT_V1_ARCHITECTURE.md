# Product V1 Architecture

هذه الوثيقة تصف البنية المعمارية لـ Product Domain.
الحالة: **Locked**

## 1. الرؤية المعمارية والنطاق

`Product` هو الموديول المسؤول عن المنتجات، المتغيرات (Variants)، الخيارات (Options)، القيم (Option Values)، والوسائط (Media).
يجب أن يظل:
* Standalone.
* Extractable.
* Host-agnostic.
* لا يحتوي على Cross-module FKs للـ Pricing, Inventory, أو Catalog (Taxonomy).

## 2. Product Lifecycle & Resolution

* **Product Slug Lifecycle:** `product.code` هي الهوية الثابتة في الـ URL، وتتغير مع تغير لغة الـ Host أو تعديل الـ SEO. الـ Logical Identity الحقيقية هي `id`.
* **SKU & Barcode:** `SKU` ثابت ولا يعاد استخدامه. `barcode` قابل للتعديل.
* **Direct Sellable Resolution:** المنتجات البسيطة (Simple Products) يتم حلها إلى الـ Variant الافتراضي الوحيد، حيث لا توجد Active Options. المخزون (Stock) والتسعير (Pricing) لا يشاركان في خطوة الـ Resolution نفسها، فهي تعتمد فقط على الـ Composition داخل هذا الموديول.

## 3. Display Order Scopes

الـ Display Order حتمي `ORDER BY display_order, id` ويتم تطبيقه بنطاقات مختلفة:
* **Products:** Global (نطاق عام).
* **Product Options:** Per Product (نطاق المنتج).
* **Option Values:** Per Option (نطاق الخيار).
* **Variants:** Per Product (نطاق المنتج).
* **Product Media:** Per Product (نطاق المنتج).
* **Variant Media:** Per Variant (نطاق المتغير).

## 4. Variant Identity & Composition

* **Identity:** الـ Variant هي وحدة البيع الأساسية، ولها هوية ثابتة.
* **Immutable Composition:** بعد إنشاء الـ Variant، سجلات التكوين الخاصة بها `variant_option_values` تصبح Immutable. لا يمكن تغيير الـ Option Values المرتبطة بها.

## 5. Media Ownership

الـ Product Domain يحتفظ فقط بمراجع (References) وملفات Metadata الخاصة بالوسائط، ولا يمتلك ملفات فيزيائية:
* Product Media تعود لـ `product_id`.
* Variant Media تعود لـ `variant_id`.
* بحد أقصى وسيط أساسي واحد (Primary) لكل منتج وكل متغير.

## 6. Database Schema

يجب أن تلتزم الجداول بالشروط الفيزيائية التالية:
```text
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci
```
*جميع الجداول تحتوي على تعليقات (COMMENT) للأعمدة بحسب معايير البناء.*

### 6.1 `maa_product_products`

| العمود               | النوع                            |
| -------------------- | -------------------------------- |
| `id`                 | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `code`               | `VARCHAR(255) NOT NULL`          |
| `status`             | `VARCHAR(50) NOT NULL`           |
| `force_out_of_stock` | `TINYINT(1) NOT NULL DEFAULT 0`  |
| `display_order`      | `INT NOT NULL DEFAULT 0`         |
| `created_at`         | `DATETIME NOT NULL`              |
| `updated_at`         | `DATETIME NOT NULL`              |
| `deleted_at`         | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(code)
CHECK (status IN ('active', 'inactive'))
CHECK (force_out_of_stock IN (0,1))
```

### 6.2 `maa_product_variants`

| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `product_id`    | `BIGINT UNSIGNED NOT NULL`       |
| `sku`           | `VARCHAR(100) NOT NULL`          |
| `barcode`       | `VARCHAR(255) NULL`              |
| `status`        | `VARCHAR(50) NOT NULL`           |
| `is_default`    | `TINYINT(1) NOT NULL DEFAULT 0`  |
| `display_order` | `INT NOT NULL DEFAULT 0`         |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(sku)
UNIQUE(barcode)
CHECK (status IN ('active', 'inactive'))
CHECK (is_default IN (0,1))
```

**FK:**
```text
product_id → maa_product_products.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

*(تستمر الجداول Options, Option Values, Composition, و Media مع نفس المبادئ، والـ FKs الداخلية فقط `ON DELETE RESTRICT` و `ON UPDATE RESTRICT`)*

## 7. Index Strategy

```text
maa_product_products
(status, deleted_at, display_order, id)

maa_product_product_options
(product_id, status, deleted_at, display_order, id)

maa_product_product_option_values
(option_id, status, deleted_at, display_order, id)

maa_product_variants
(product_id, status, deleted_at, display_order, id)

maa_product_media (Product scope)
(product_id, variant_id, deleted_at, is_primary, display_order, id)

maa_product_media (Variant scope)
(variant_id, deleted_at, is_primary, display_order, id)
```
