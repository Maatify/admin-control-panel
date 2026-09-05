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
* Canonical Generic Subject Identity Contract.

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
* الربط يتم باستخدام معرّفات عامة (Generic Identifiers). يتوافق الموديول مع معايير الـ Base Module من خلال الالتزام بعقد الـ Canonical Positive ID للهويات، مع إضافة `subject_type` لضمان عدم حدوث Collisions بين أنواع الكيانات المختلفة.
* **Canonical Subject Type Contract:**
  * `subject_type` هو `VARCHAR(100) NOT NULL`.
  * **Datatype & Limits:** نص لا يتجاوز 100 حرف.
  * **Validation Pattern:** لضمان الـ Determinism بين طبقة الـ Application وقاعدة البيانات، يجب أن يطابق نمط Machine Key بأحرف صغيرة فقط `/^[a-z0-9_.-]+$/`.
  * **Case-Sensitivity & Normalization:** يُفرض استخدام الأحرف الصغيرة فقط (lowercase-only) لمنع الاختلاف بين الـ Application (الذي قد يفرق بين `Product` و `product`) وقاعدة البيانات (التي قد تعتبرهما متطابقين بناءً على الـ collation الافتراضية).
  * **Empty Values:** غير مسموح بنصوص فارغة أو تحتوي على Whitespace.
  * **Immutability:** لا يمكن تعديل `subject_type`، `subject_id`، أو `currency_code` لأي سجل تسعير بعد إنشائه. الهوية المنطقية (Logical Identity) تتكون من هذه الأعمدة الثلاثة (subject_type, subject_id, currency_code) وهي Immutable. تغيير العملة يعني إنشاء سجل جديد بهوية جديدة وليس Update.
  * **Uniqueness Handling:** في حالات الـ Soft Delete والـ Restore، يجب التحقق من عدم تعارض الـ Identity (النوع + المعرف + العملة) مع سجل نشط آخر. لا تُنشأ هويات بديلة للالتفاف على ذلك.

---

# 3. Currency and Values

* لا يمتلك الموديول جداول للعملات. يخزن فقط الرمز:
  * `currency_code CHAR(3) NOT NULL` (مخصص لرموز ISO 4217).
* جميع القيم المالية تستخدم `DECIMAL(20,6) NOT NULL`.
* يُمنع التخزين أو الحساب باستخدام `FLOAT`.

---

# 4. Timestamp Policy & Soft Delete

التاريخ يُدار عبر التطبيق بنظام UTC. العقد الكامل:
* Application-managed.
* `created_at` تُحدد عند الإنشاء.
* `updated_at` تُحدث عند أي mutation (تعديل).
* `deleted_at` تُحدد عند الـ soft delete.
* `updated_at` تُحدث أيضًا وقت الـ soft delete والـ restore.
* `deleted_at IS NULL` تعني السجل فعّال، ولا يتم استخدام runtime hard-delete.

---

# 5. Pricing Calculation Rules & Missing Data

* **Currency Consistency:** تُحسب جميع العمليات داخل نفس العملة (لا يجمع تعديل بعملة مختلفة).
* **Missing Adjustment:** التعديل المفقود يُعتبر `0`.
* **Missing Base Price:** غياب السعر الأساسي للكيان يعني "غير مُسعّر بهذه العملة" ولا يوجد افتراض للسعر.

---

# 6. Complete Database Schema — 2 Tables

## 6.1 `maa_pricing_base_prices`
| العمود                 | النوع                            |
| ---------------------- | -------------------------------- |
| `id`                   | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `subject_type`         | `VARCHAR(100) NOT NULL`          |
| `subject_id`           | `BIGINT UNSIGNED NOT NULL COMMENT 'Host-provided ID. No FK.'` |
| `currency_code`        | `CHAR(3) NOT NULL`               |
| `base_price`           | `DECIMAL(20,6) NOT NULL`         |
| `created_at`           | `DATETIME NOT NULL`              |
| `updated_at`           | `DATETIME NOT NULL`              |
| `deleted_at`           | `DATETIME NULL`                  |

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(subject_type, subject_id, currency_code)
CHECK(base_price >= 0)
```

---

## 6.2 `maa_pricing_adjustments`
| العمود                 | النوع                            |
| ---------------------- | -------------------------------- |
| `id`                   | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `subject_type`         | `VARCHAR(100) NOT NULL`          |
| `subject_id`           | `BIGINT UNSIGNED NOT NULL COMMENT 'Host-provided ID. No FK.'` |
| `currency_code`        | `CHAR(3) NOT NULL`               |
| `price_adjustment`     | `DECIMAL(20,6) NOT NULL`         |
| `created_at`           | `DATETIME NOT NULL`              |
| `updated_at`           | `DATETIME NOT NULL`              |
| `deleted_at`           | `DATETIME NULL`                  |

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(subject_type, subject_id, currency_code)
```

---

# 7. Database-Enforced Invariants

* Primary Keys & `UNIQUE(subject_type, subject_id, currency_code)` لكل جدول لمنع تكرار السعر لنفس العملة والكيان.
* `base_price >= 0`.
* **Column Comments Requirement:** تطبيقًا للـ Package Building Standard، تلتزم كافة المخططات (Schemas) المذكورة هنا بتوفير تعليقات دلالية (Meaningful Comments) في مرحلة التنفيذ الفعلي (Implementation) توضح الغرض من كل عمود، خصوصًا الهويات الخارجية مثل `COMMENT 'Host-provided ID. No FK.'`.

---

# 8. Domain / Transaction-Enforced Invariants

* **Final Price Calculation:** يتم تطبيق قاعدة `Final Price >= 0` عند وقت القراءة (Read-time calculation) بواسطة الـ Service حين يُطلب تسعير مركب (Base + Adjustments). لم يعد Pricing مسؤولًا عن فرض الـ Pricing Safety Triggers عند وقت الحفظ (Write-time mutation rejection) لكيانات خارجية لا يعلم عنها شيئًا.
* **Identity Reuse / Restore:** الـ Restore لنفس الـ `subject_type` و `subject_id` يجب أن يلتزم بنفس قيود الـ Unique constraints.

---

# 9. Index Strategy

```text
maa_pricing_base_prices:
UNIQUE(subject_type, subject_id, currency_code)

maa_pricing_adjustments:
UNIQUE(subject_type, subject_id, currency_code)
```

---

# 10. Sources of Truth

مصادر الحقيقة الوحيدة هنا:
* السعر الأساسي (`base_price`) لهوية الـ Subject (Type + ID) والعملة.
* التعديل (`price_adjustment`) لهوية الـ Subject (Type + ID) والعملة.
* حالة الحذف المنطقي (`deleted_at`).

لا يُخزّن السعر النهائي، بل يُحسب.

---

# 11. Architecture Status

**Locked**
القرارات المعمارية الداخلية الخاصة بهيكل التسعير محسومة. الـ External Reference Identity صُممت بحيث لا يحدث لها Collision بفضل العقد الصارم لـ `subject_type`. إسناد مسؤولية ضمان عدم بيع كيان بسعر سالب أثناء تفعيل الكيان نفسه أصبحت خارج نطاق الموديول وهي الآن مسؤولية الـ Host/Coordinator.
