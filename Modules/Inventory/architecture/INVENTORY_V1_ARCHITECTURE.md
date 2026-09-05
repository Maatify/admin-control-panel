# Inventory V1 Database Architecture — Locked

**Standalone Inventory Engine**

هذه الوثيقة هي المرجع المعماري المقفول لـ **Inventory V1** بعد عملية إعادة الهيكلة وفصل الدومينات.
الـ Inventory Module هو المالك الحصري لدومين المخزون وإدارة الكميات.

---

# 1. الرؤية المعمارية والنطاق

## 1.1 الهدف

`Inventory V1` هو موديول مستقل مسؤول بالكامل عن تتبع المخزون وتحديث الكميات بشكل ذري (Atomic)، ويُستخدم لتحديد إتاحة المواد/العناصر. يعمل بشكل معزول تماماً عن Products أو Orders.

---

## 1.2 النطاق المشمول

يشمل V1:

* Stock identity (Inventory row per reference).
* Quantity tracking (`quantity_on_hand`).
* Atomic increase/decrease semantics.
* Negative stock protection.
* Sources of truth for inventory.

---

## 1.3 خارج النطاق صراحة

لا يحتوي V1 على:

* Variants, Products, SKUs (لا يوجد `variant_id` كـ FK).
* Reservations, Order IDs. الموديول يقوم بالإنقاص أو الزيادة، لكنه لا يدير دورة حياة الحجز نفسه.
* Warehouses, Backorders, Inventory Movement History Ledger (خارج نطاق V1 كلياً).
* Status flags (مفهوم الـ In-Stock / Out-Of-Stock تُحسب عبر الاستعلام، الموديول لا يملك `status` بوليانية).

---

# 2. Host-Agnostic Boundaries

* **لا يوجد Foreign Keys إلى Product Module أو Orders Module.**
* يمتلك Inventory V1 هويته الخاصة بربط السجلات بـ "Stock Subject" مجهول النوع للـ Host.

---

# 3. Stock Lifecycle & Identity

* يتم إنشاء سجل Inventory للكيان المراد تتبع مخزونه (مثلاً `stock_subject_id`).
* `quantity_on_hand = 0` عند الإنشاء الأولي (في حال لم تُعطى كمية مبدئية).
* Soft Delete للـ Subject من قِبل الـ Host لا يستوجب حذف سجل الـ Inventory، ما لم يقم الـ Host بذلك صراحة، والموديول يدعم الـ `deleted_at`.

---

# 4. Quantity Rules

* `quantity_on_hand >= 0` بشكل قاطع.
* لا يوجد مخزون بالسالب (Negative Stock).
* عمليات التحديث يجب أن تكون Atomic (تزيد أو تنقص بناءً على الكمية الحالية، وليس بعملية تعيين صريحة للكمية المطلقة إذا كان هناك Concurrency).

---

# 5. Complete Database Schema — 1 Table

## 5.1 `maa_inventory_stocks`
| العمود               | النوع                            |
| -------------------- | -------------------------------- |
| `id`                 | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `stock_subject_id`   | `VARCHAR(255) NOT NULL`          |
| `quantity_on_hand`   | `INT NOT NULL DEFAULT 0`         |
| `created_at`         | `DATETIME NOT NULL`              |
| `updated_at`         | `DATETIME NOT NULL`              |
| `deleted_at`         | `DATETIME NULL`                  |

Constraints:
```text
PRIMARY KEY(id)
UNIQUE(stock_subject_id)
CHECK(quantity_on_hand >= 0)
```
*(ملاحظة: `stock_subject_id` هو المعرف العام الذي يقدمه الـ Host، والذي كان سابقاً يمثل `variant_id`، ولا يعتمد على موديول Product هيكلياً)*

---

# 6. Database-Enforced Invariants

* `quantity_on_hand >= 0`. (حماية المخزون السالب مدعومة من محرك قاعدة البيانات).
* `UNIQUE(stock_subject_id)`.

---

# 7. Domain / Transaction-Enforced Invariants

* التحديثات يجب أن تُنفذ باستخدام استعلامات نسبية `UPDATE maa_inventory_stocks SET quantity_on_hand = quantity_on_hand - X WHERE quantity_on_hand >= X` لضمان صحة التزامن (Concurrency).
* الموديول لا يهتم بدلالات الحجز؛ عملية الاستهلاك (Consume) تنقص الرقم فوراً، وعملية الإلغاء (Release) تزيده فوراً.

---

# 8. Index Strategy

```text
UNIQUE(stock_subject_id)
```
يُعد هذا الفهرس كافياً للاستعلام وتحديث المخزون بناءً على الكيان المرتبط.

---

# 9. Unresolved Architectural Decisions

* **Reservations Tracking:** هل ينبغي للـ Inventory تتبع عمليات החجز (Reservation) مؤقتاً قبل الاستهلاك الفعلي (لتجنب الاعتماد على موديول Orders لبيانات الحجز)؟
  * *القرار الحالي:* V1 لا يمتلك Reservations. أي إنقاص يعتبر نهائياً بالنسبة لـ Inventory، وعلى الـ Host/Coordinator (مثلاً موديول Orders) معالجة إرجاع الكميات في حال انتهاء مدة الحجز أو الإلغاء.
