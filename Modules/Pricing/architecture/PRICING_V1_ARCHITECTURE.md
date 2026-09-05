# Pricing V1 Architecture

هذه الوثيقة تصف البنية المعمارية لـ Pricing Domain.
الحالة: **Locked**

## 1. الرؤية المعمارية والنطاق

`Pricing` هو الموديول المسؤول عن إدارة الأسعار للكيانات المختلفة في النظام (مثل Products و Option Values).
يجب أن يظل:
* Standalone.
* Extractable.
* Host-agnostic.
* لا يحتوي على Cross-module FKs للـ Product أو Catalog. يتم استخدام `subject_type` و `subject_id` لتحديد الكيان المرتبط (Generic References).

## 2. Logical Identity & Immutability

الهوية المنطقية (Logical Identity) لأي سجل تسعير تتكون من ثلاثة أعمدة، **جميعها غير قابلة للتعديل (Immutable)** بعد الإنشاء:
1. `subject_type` (مثل `product`, `option_value`)
2. `subject_id` (رقم الكيان المرتبط)
3. `currency_code` (رمز العملة ISO 4217, `CHAR(3)`)

**التعديلات:**
* القيم التي يمكن تعديلها فقط هي القيمة المالية نفسها (`base_price` أو `price_adjustment`).
* تغيير العملة يعني إنشاء سجل جديد بهوية جديدة، وليس تعديلًا (Update) لنفس السجل.

## 3. Database Schema

يجب أن تلتزم الجداول بالشروط الفيزيائية التالية:
```text
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci
```
*جميع الجداول تحتوي على تعليقات (COMMENT) للأعمدة بحسب معايير البناء.*

### 3.1 `maa_pricing_base_prices`

| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `subject_type`  | `VARCHAR(50) NOT NULL`           |
| `subject_id`    | `BIGINT UNSIGNED NOT NULL`       |
| `currency_code` | `CHAR(3) NOT NULL`               |
| `base_price`    | `DECIMAL(20,6) NOT NULL`         |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(subject_type, subject_id, currency_code)
CHECK (base_price >= 0)
```
*(لا توجد FKs خارجية)*

### 3.2 `maa_pricing_adjustments`

| العمود             | النوع                            |
| ------------------ | -------------------------------- |
| `id`               | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `subject_type`     | `VARCHAR(50) NOT NULL`           |
| `subject_id`       | `BIGINT UNSIGNED NOT NULL`       |
| `currency_code`    | `CHAR(3) NOT NULL`               |
| `price_adjustment` | `DECIMAL(20,6) NOT NULL`         |
| `created_at`       | `DATETIME NOT NULL`              |
| `updated_at`       | `DATETIME NOT NULL`              |
| `deleted_at`       | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(subject_type, subject_id, currency_code)
```
*(لا توجد FKs خارجية)*

## 4. Restore Rules

* عند عمل Restore للكيان الأصلي (مثلاً عبر Catalog Package Coordinator)، الأسعار تستعاد أو يتم الحفاظ عليها بناءً على الـ Logical Identity: `(subject_type, subject_id, currency_code)`.
* السجل المحذوف عبر Soft Delete يظل يحتفظ بالـ Identity ولا يحررها، لمنع تكرار نفس الهوية.

## 5. Currency Sellability

* **Base Price:** تحدد إمكانية البيع بعملة معينة. إذا لم يكن هناك سجل لسعر أساسي بعملة C (أو كان السجل محذوفاً `deleted_at IS NOT NULL`)، فالكيان **غير قابل للبيع بهذه العملة (Not Sellable In This Currency)**.
* **Adjustment:** إذا لم يكن هناك تسعير (Adjustment) اختياري لعملة C، يُعتبر الـ Adjustment بقيمة `0`.
