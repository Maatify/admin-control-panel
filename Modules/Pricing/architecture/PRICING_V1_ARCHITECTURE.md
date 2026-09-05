# Pricing V1 Database Architecture — Locked

**Standalone Pricing Engine**

هذه الوثيقة هي المرجع المعماري المقفول لـ **Pricing V1** بعد عملية إعادة الهيكلة وفصل الدومينات.
الـ Pricing Module هو المالك الحصري لدومين التسعير والعملات والمبالغ المالية.

---

# 1. الرؤية المعمارية والنطاق

## 1.1 الهدف

`Pricing V1` هو موديول مخصص لإدارة تعريفات الأسعار الأساسية والتعديلات (Adjustments) الخاصة بالمنتجات أو أجزائها. يجب أن يكون قادراً على العمل بشكل مستقل بدون الاعتماد على التفاصيل الداخلية لموديول المنتجات أو الكتالوج.

---

## 1.2 النطاق المشمول

يشمل V1:

* Multi-Currency Base Pricing.
* Price Adjustments.
* Price Calculation.
* Pricing Safety / Currency Consistency.
* Price Lifecycle.

---

## 1.3 خارج النطاق صراحة

لا يحتوي V1 على:

* Products, Variants, Options.
* Inventory, Orders, Customers.
* الضرائب (إلا إذا كانت جزءاً من سياسة تسعير مدمجة لاحقاً كعقد مستقل).
* Product Status. التسعير لا يعرف إن كان المنتج `active` أم `inactive`.

---

# 2. Host-Agnostic Boundaries

* **لا يوجد Foreign Keys أو جداول بأسماء تابعة لـ Product Module.**
* يمتلك Pricing V1 هويته الخاصة بربط الأسعار بالموارد الخارجية عبر "Subject Reference" مجهول النوع (Polymorphic-like أو Explicit Reference).

---

# 3. Currency and Values

* `currency_code CHAR(3) NOT NULL` للعملات (ISO 4217).
* جميع القيم المالية تستخدم `DECIMAL(20,6) NOT NULL`.
* لا يُجرى حفظ القيم بصيغة `FLOAT`.

---

# 4. Logical Reference Identity

بدلاً من ربط السعر بـ `product_id` (مما يسبب Cross-Module Dependency)، يتم استخدام نمط مرجعي (Subject Reference) أو الـ Host/Integration Layer يمتلك الربط. لكن في حال تقديم Pricing כخدمة، فإنه يُعرّف:
`subject_type` و `subject_id`.
لتسهيل المعمارية وحفاظاً على سياق الكتالوج القديم، سنعرّف الأسعار بناءً على مُعرفات عامة (Generic Identifiers).

* `base_price_subject_id`: يمثل هوية الشيء المُسعَّر (سابقاً Product).
* `adjustment_subject_id`: يمثل هوية مُعدِّل السعر (سابقاً Option Value).

---

# 5. Pricing Safety and Consistency

* **Final Price >= 0:** لأي حساب، يجب أن يكون المجموع النهائي للسعر والتعديلات أكبر من أو يساوي صفرًا للعملة المحددة.
* **Currency Consistency:** التعديلات يجب أن تطابق عملة السعر الأساسي (لا يمكن إضافة EGP إلى USD).
* **Missing Data:** في حال غياب Adjustment لعملة معينة، تعتبر `0`. في حال غياب Base Price لعملة، يعتبر الـ Subject `Not Sellable In This Currency`.

---

# 6. Complete Database Schema — 2 Tables

## 6.1 `maa_pricing_base_prices`
| العمود                 | النوع                            |
| ---------------------- | -------------------------------- |
| `id`                   | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `subject_id`           | `VARCHAR(255) NOT NULL`          |
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
*(ملاحظة: `subject_id` عبارة عن VARCHAR لدعم GUIDs أو Prefix-based IDs من الـ Host، ولا يمثل FK لأي موديول)*

---

## 6.2 `maa_pricing_adjustments`
| العمود                 | النوع                            |
| ---------------------- | -------------------------------- |
| `id`                   | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `subject_id`           | `VARCHAR(255) NOT NULL`          |
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
*(التعديل `price_adjustment` يمكن أن يكون سالباً، لكن المُحصلة النهائية تتم مراجعتها بالطبقة التطبيقية)*

---

# 7. Database-Enforced Invariants

* `base_price >= 0`.
* الـ Unique Constraints تمنع وجود سعرين أساسيين لنفس الـ Subject بنفس العملة.

---

# 8. Domain / Transaction-Enforced Invariants

* حساب `Final Price >= 0` يتم التحقق منه كـ Invariant داخل الـ Pricing Service عند طلب السعر النهائي لمجموعة Subjects (مثلاً Base Subject + Array of Adjustment Subjects).
* لا يتدخل الموديول لمعرفة ما إذا كان الـ Subject نشطاً أو محذوفاً (هذه مسؤولية المستهلك/الـ Host).

---

# 9. Index Strategy

```text
maa_pricing_base_prices:
UNIQUE(subject_id, currency_code)

maa_pricing_adjustments:
UNIQUE(subject_id, currency_code)
```

---

# 10. Unresolved Architectural Decisions

* **Host Integration:** الـ Host Coordinator سيكون مسؤولاً عن استدعاء الـ Pricing Module للحصول على الأسعار وحساب الـ Final Price للـ Product/Variant المحددة. الـ Pricing Module نفسه لا يحتفظ بـ "Pricing Safety Triggers" المرتبطة بتغيير حالات المنتجات (Activation Triggers) لأن ذلك يحتاج معرفة بدومين المنتجات؛ التنسيق سيتم عبر طبقة الـ Host أو Events.
