# Inventory V1 Database Architecture — Locked

**Standalone Inventory Engine**

هذه الوثيقة هي المرجع المعماري المقفول لـ **Inventory V1** بعد عملية إعادة الهيكلة وفصل الدومينات.
الـ Inventory Module هو المالك الحصري لدومين المخزون وإدارة الكميات المجردة.

---

# 1. الرؤية المعمارية والنطاق

## 1.1 الهدف

`Inventory V1` هو موديول مستقل مسؤول بالكامل عن تتبع المخزون وتحديث الكميات بشكل ذري (Atomic)، ويُستخدم لتحديد إتاحة المواد/العناصر بمعزل تام عن الكيان التجاري الذي يُمثله هذا المخزون.

---

## 1.2 النطاق المشمول

يشمل V1:

* Stock identity (Inventory row per reference).
* Initial quantity.
* Quantity tracking (`quantity_on_hand`).
* Atomic increase/decrease semantics.
* Negative stock protection.
* Insufficient quantity behavior.
* Lifecycle (Soft delete/restore).

---

## 1.3 خارج النطاق صراحة

لا يحتوي V1 على:

* Variants, Products, SKUs (المخزون لا يعلم ما هي الكيانات التي يُديرها).
* Reservations tracking (حجوزات المخزون المؤقتة).
* دلالات الطلبات أو المبيعات (لا يوجد ربط بموديولات الطلبات Orders).
* Warehouses, Backorders, أو Movement History Ledger.
* Status flags الخاصة بالكيان التجاري (مثل `In-Stock`).

---

# 2. Host-Agnostic Boundaries & Logical Reference Identity

* **لا يوجد Foreign Keys لأي جداول خارجية.**
* يمتلك Inventory V1 هويته الخاصة بربط السجلات بـ "Stock Subject". لتجنب تضارب الهويات (Collisions) عند استخدام أرقام هويات متكررة لأنواع مختلفة من الكيانات، وللتوافق مع معايير הـ Base Module (Canonical Positive ID Contract)، يتم استخدام:
  * `stock_subject_type VARCHAR(100) NOT NULL`: نوع الكيان المرتبط بالمخزون.
  * `stock_subject_id BIGINT UNSIGNED NOT NULL`: رقم هوية الكيان.

---

# 3. Stock Lifecycle & Identity

* يتم إنشاء سجل Inventory بمعرف النوع والرقم المعطى من قبل الـ Host.
* `quantity_on_hand = 0` عند الإنشاء الأولي (ما لم تُمرر كمية ابتدائية مختلفة صراحة).
* **Soft Delete & Restore:** الموديول يدعم الحذف المنطقي (`deleted_at`). عملية الحذف أو الاستعادة لا تصفر المخزون، بل تعتمد على السجل المحفوظ. السجل المحذوف لا يستقبل عمليات تعديل للكميات.
* لا يتم تحرير الـ Identity (النوع + الـ ID) لإعادة استخدامها كـ Identity جديدة عبر الحذف؛ الـ Restore يعيد نفس السجل بنفس الكمية.

---

# 4. Quantity Rules & Negative Stock Protection

* `quantity_on_hand >= 0` بشكل قاطع مدعوم بالـ Database CHECK Constraint.
* لا يوجد مخزون بالسالب (Negative Stock).
* **Insufficient Quantity Behavior:** أي عملية إنقاص تؤدي إلى كمية أقل من صفر يجب أن ترفض بالكامل.

---

# 5. Complete Database Schema — 1 Table

## 5.1 `maa_inventory_stocks`
| العمود               | النوع                            |
| -------------------- | -------------------------------- |
| `id`                 | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `stock_subject_type` | `VARCHAR(100) NOT NULL`          |
| `stock_subject_id`   | `BIGINT UNSIGNED NOT NULL`       |
| `quantity_on_hand`   | `INT NOT NULL DEFAULT 0`         |
| `created_at`         | `DATETIME NOT NULL`              |
| `updated_at`         | `DATETIME NOT NULL`              |
| `deleted_at`         | `DATETIME NULL`                  |

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(stock_subject_type, stock_subject_id)
CHECK(quantity_on_hand >= 0)
```

---

# 6. Database-Enforced Invariants

* حماية المخزون السالب مدعومة بالقيد `CHECK(quantity_on_hand >= 0)`.
* فرادة المخزون لكل كيان مدعومة بالقيد `UNIQUE(stock_subject_type, stock_subject_id)`.

---

# 7. Domain / Transaction-Enforced Invariants

* **Atomic Increase/Decrease:** التحديثات يجب أن تُنفذ باستخدام عمليات نسبية لضمان التزامن وحل الـ Concurrency issues.
  مثال لعملية الإنقاص: `UPDATE maa_inventory_stocks SET quantity_on_hand = quantity_on_hand - X WHERE quantity_on_hand >= X`. إذا أرجع الاستعلام `0` صفوف معدلة، ترفض العملية (Insufficient stock).
* الموديول يوفر عمليات "الزيادة" و "النقصان" الذرية فقط ولا يسمي عملياته بـ "Reserve" أو "Cancel" لأن هذه دلالات تجارية تخص موديولات أخرى.
* السجلات المحذوفة منطقيًا (`deleted_at IS NOT NULL`) تُمنع من أي تحديث للكميات.

---

# 8. Index Strategy

```text
UNIQUE(stock_subject_type, stock_subject_id)
```
يُعد هذا الفهرس كافيًا لمتطلبات الاستعلام والتحديث استنادًا إلى نوع ومعرف الکيان.

---

# 9. Sources of Truth

مصادر الحقيقة الوحيدة هنا هي:
* السجل وكميته المتوفرة `quantity_on_hand`.
* `deleted_at`.

---

# 10. Architecture Status

**Locked**
القرارات المعمارية الداخلية الخاصة بتتبع المخزون والعمليات الذرية عليه محسومة بشكل كامل. الهوية الخارجية مدعومة بـ `subject_type` لمنع الـ Collisions. وتعمل هذه البنية دون أي معرفة بتفاصيل الدومينات الأخرى.
