# Inventory V1 Architecture

هذه الوثيقة تصف البنية المعمارية لـ Inventory Domain.
الحالة: **Locked**

## 1. الرؤية المعمارية والنطاق

`Inventory` هو الموديول المسؤول عن إدارة المخزون (`quantity_on_hand`) لأي كيان (Generic Reference).
يجب أن يظل:
* Standalone.
* Extractable.
* Host-agnostic.
* لا يحتوي على Cross-module FKs لأي جداول أخرى، ولا يعرف عن `Variant` أو `Product`.

## 2. Logical Identity

يعتمد المخزون على `(stock_subject_type, stock_subject_id)` لربط السجل بالكيانات الخارجية دون استخدام Foreign Keys. هذه الأعمدة ثابتة (Immutable).

## 3. Database Schema

يجب أن تلتزم الجداول بالشروط الفيزيائية التالية:
```text
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci
```
*جميع الجداول تحتوي على تعليقات (COMMENT) للأعمدة بحسب معايير البناء.*

### 3.1 `maa_inventory_stock`

| العمود               | النوع                            |
| -------------------- | -------------------------------- |
| `id`                 | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `stock_subject_type` | `VARCHAR(50) NOT NULL`           |
| `stock_subject_id`   | `BIGINT UNSIGNED NOT NULL`       |
| `quantity_on_hand`   | `INT NOT NULL DEFAULT 0`         |
| `created_at`         | `DATETIME NOT NULL`              |
| `updated_at`         | `DATETIME NOT NULL`              |
| `deleted_at`         | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(stock_subject_type, stock_subject_id)
CHECK (quantity_on_hand >= 0)
```
*(لا توجد FKs خارجية)*

## 4. Atomic Operations & Concurrency

* الطفرات على المخزون (زيادة أو نقصان `quantity_on_hand`) يجب أن تتم كعمليات ذرية (Atomic) باستخدام `UPDATE` مباشر في قاعدة البيانات مع الـ `updated_at`.
* يجب الفصل بوضوح في الـ Semantics بين:
  * المخزون غير موجود (Not Found).
  * المخزون محذوف (Soft-Deleted).

## 5. Lifecycle Rules within Base Module

الموديول لا يعلم بتفاصيل التبعية أو دورة الحياة للكيان الأصلي (Variant/Product):
* **Soft Delete:** يتم من خلال `deleted_at`. الكيان الخارجي (أو الـ Package Coordinator) هو المسؤول عن فرض قيود عدم حذف المخزون أثناء بقاء الكيان الأصلي نشطاً.
* **Restore:** عند الاستعادة، يتم إزالة `deleted_at`.

