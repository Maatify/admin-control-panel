# Pricing V1 Database Architecture — Locked

**Standalone Pricing Engine**

هذه الوثيقة هي المرجع المعماري المقفول لـ **Pricing V1** بعد عملية إعادة الهيكلة وفصل الدومينات.
الـ Pricing Module هو المالك الحصري لدومين التسعير وإدارة المبالغ المالية.

---

# 1. الرؤية المعمارية والنطاق

## 1.1 الهدف

`Pricing V1` هو موديول مخصص لإدارة تعريفات الأسعار الأساسية والتعديلات (Adjustments) لأي كيان. يجب أن يكون قادرًا على العمل بشكل مستقل وبدون أية افتراضات حول الكيانات المُسعَّرة.

---

## 1.2 النطاق المشمول

يشمل V1:

* Multi-Currency Base Pricing.
* Price Adjustments.
* Price Calculation Rules (Same-currency consistency).
* Pricing Invariants (Final Price >= 0).
* Price Lifecycle (Soft delete/restore).

---

## 1.3 خارج النطاق صراحة

لا يحتوي V1 على:

* الكيانات التجارية كمنتجات أو مقالات أو رحلات.
* جداول أو هويات للعملات (تُدار رموز العملات فقط كنص).
* الضرائب أو الخصومات أو العروض.
* معرفة بحالة الكيان المُسعّر (لا يعرف Pricing إذا كان الكيان فعالًا أم لا).

---

# 2. Host-Agnostic Boundaries & Logical Reference Identity

* **لا يوجد Foreign Keys إلى أي موديول آخر.**
* الربط يتم باستخدام معرّفات عامة (Generic Identifiers). يتوافق الموديول مع معايير הـ Base Module من خلال الالتزام بعقد الـ Canonical Positive ID:
  * `subject_id BIGINT UNSIGNED NOT NULL`: يمثل هوية الشيء المُسعَّر أو المُعدِّل.

---

# 3. Currency and Values

* لا يمتلك الموديول جداول للعملات. يخزن فقط الرمز:
  * `currency_code CHAR(3) NOT NULL` (مخصص لرموز ISO 4217).
* جميع القيم المالية تستخدم `DECIMAL(20,6) NOT NULL`.
* يُمنع التخزين أو الحساب باستخدام `FLOAT`.

---

# 4. Pricing Calculation Rules & Missing Data

* **Currency Consistency:** تُحسب جميع العمليات داخل نفس العملة (لا يجمع تعديل بعملة مختلفة).
* **Missing Adjustment:** التعديل المفقود يُعتبر `0`.
* **Missing Base Price:** غياب السعر الأساسي للكيان يعني "غير مُسعّر بهذه العملة" ولا يوجد افتراض للسعر.

---

# 5. Complete Database Schema — 2 Tables

## 5.1 `maa_pricing_base_prices`
| العمود                 | النوع                            |
| ---------------------- | -------------------------------- |
| `id`                   | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `subject_id`           | `BIGINT UNSIGNED NOT NULL`       |
| `currency_code`        | `CHAR(3) NOT NULL`               |
| `base_price`           | `DECIMAL(20,6) NOT NULL`         |
| `created_at`           | `DATETIME NOT NULL`              |
| `updated_at`           | `DATETIME NOT NULL`              |
| `deleted_at`           | `DATETIME NULL`                  |

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(subject_id, currency_code)
CHECK(base_price >= 0)
```
*(ملاحظة: `subject_id` لا يمثل FK لأي جدول، بل هو مرجع خارجي)*

---

## 5.2 `maa_pricing_adjustments`
| العمود                 | النوع                            |
| ---------------------- | -------------------------------- |
| `id`                   | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `subject_id`           | `BIGINT UNSIGNED NOT NULL`       |
| `currency_code`        | `CHAR(3) NOT NULL`               |
| `price_adjustment`     | `DECIMAL(20,6) NOT NULL`         |
| `created_at`           | `DATETIME NOT NULL`              |
| `updated_at`           | `DATETIME NOT NULL`              |
| `deleted_at`           | `DATETIME NULL`                  |

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(subject_id, currency_code)
```
*(التعديل يمكن أن يكون سالبًا أو موجبًا أو صفرًا)*

---

# 6. Database-Enforced Invariants

* Primary Keys & `UNIQUE(subject_id, currency_code)` لكل جدول لمنع تكرار السعر لنفس العملة.
* `base_price >= 0`.

---

# 7. Domain / Transaction-Enforced Invariants

* **Final Price Calculation:** يتم تطبيق قاعدة `Final Price >= 0` عند وقت القراءة (Read-time calculation) بواسطة الـ Service حين يُطلب تسعير مركب (Base + Adjustments). لم يعد Pricing مسؤولًا عن فرض الـ Pricing Safety Triggers عند وقت الحفظ (Write-time mutation rejection) لكيانات خارجية لا يعلم عنها شيئًا.

---

# 8. Index Strategy

```text
maa_pricing_base_prices:
UNIQUE(subject_id, currency_code)

maa_pricing_adjustments:
UNIQUE(subject_id, currency_code)
```
يُعد هذا الفهرس كافيًا لمتطلبات الاستعلام والتحديث داخل الموديول.

---

# 9. Sources of Truth

مصادر الحقيقة الوحيدة هنا:
* السعر الأساسي (`base_price`) للـ `subject_id` والعملة.
* التعديل (`price_adjustment`) للـ `subject_id` والعملة.
* حالة الحذف المنطقي (`deleted_at`).

لا يُخزّن السعر النهائي، بل يُحسب.

---

# 10. Architecture Status

**Locked**
القرارات المعمارية الداخلية الخاصة بهيكل التسعير محسومة. إسناد مسؤولية ضمان عدم بيع كيان بسعر سالب أثناء تفعيل الكيان نفسه أصبحت خارج نطاق الموديول وهي الآن مسؤولية الـ Host/Coordinator.
