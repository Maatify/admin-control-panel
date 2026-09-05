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
* Canonical Generic Subject Identity Contract.

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
* يمتلك Inventory V1 هويته الخاصة بربط السجلات بـ "Stock Subject". لتجنب تضارب الهويات (Collisions)، وللتوافق مع معايير الـ Base Module، يتم استخدام `stock_subject_type` و `stock_subject_id`.
* **Canonical Subject Type Contract:**
  * `stock_subject_type` هو `VARCHAR(100) NOT NULL`.
  * **Datatype & Limits:** نص لا يتجاوز 100 حرف.
  * **Validation Pattern:** لضمان الـ Determinism بين طبقة الـ Application وقاعدة البيانات، يجب أن يطابق نمط Machine Key بأحرف صغيرة فقط `/^[a-z0-9_.-]+$/`.
  * **Case-Sensitivity & Normalization:** يُفرض استخدام الأحرف الصغيرة فقط (lowercase-only) لمنع الاختلاف بين الـ Application (الذي قد يفرق بين `Product` و `product`) وقاعدة البيانات (التي قد تعتبرهما متطابقين بناءً على الـ collation الافتراضية). ولا يجوز استخدام Aliases لاختلاف الـ Case.
  * **Empty Values:** غير مسموح بنصوص فارغة أو تحتوي على Whitespace.
  * **Immutability:** لا يمكن تعديل الـ Type أو الـ ID لأي سجل مخزون بعد إنشائه.
  * **Uniqueness Handling:** في حالات الـ Soft Delete والـ Restore، يتم ضمان عدم تكرار الـ Identity (النوع + المعرف).

---

# 3. Timestamp Policy & Soft Delete

التاريخ يُدار عبر التطبيق بنظام UTC. العقد الكامل:
* Application-managed.
* `created_at` تُحدد عند الإنشاء.
* `updated_at` تُحدث عند أي mutation (تعديل).
* `deleted_at` تُحدد عند الـ soft delete.
* `updated_at` تُحدث أيضًا وقت الـ soft delete والـ restore.
* `deleted_at IS NULL` تعني السجل فعّال، ولا يتم استخدام runtime hard-delete.

---

# 4. Stock Lifecycle & Identity

* يتم إنشاء سجل Inventory بمعرف النوع والرقم المعطى من قبل الـ Host.
* `quantity_on_hand = 0` عند الإنشاء الأولي (ما لم تُمرر كمية ابتدائية مختلفة صراحة).
* **Soft Delete & Restore:** الموديول يدعم الحذف المنطقي (`deleted_at`). عملية الحذف أو الاستعادة لا تصفر المخزون، بل تعتمد على السجل المحفوظ. السجل المحذوف لا يستقبل عمليات تعديل للكميات.
* لا يتم تحرير الـ Identity (النوع + الـ ID) لإعادة استخدامها كـ Identity جديدة عبر الحذف؛ الـ Restore يعيد نفس السجل بنفس الكمية.

---

# 5. Quantity Rules & Negative Stock Protection

* `quantity_on_hand >= 0` بشكل قاطع مدعوم بالـ Database CHECK Constraint.
* لا يوجد مخزون بالسالب (Negative Stock).
* **Insufficient Quantity Behavior:** أي عملية إنقاص تؤدي إلى كمية أقل من صفر يجب أن ترفض بالكامل.

---

# 6. Complete Database Schema — 1 Table

## 6.1 `maa_inventory_stocks`
| العمود               | النوع                            |
| -------------------- | -------------------------------- |
| `id`                 | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `stock_subject_type` | `VARCHAR(100) NOT NULL`          |
| `stock_subject_id`   | `BIGINT UNSIGNED NOT NULL COMMENT 'Host-provided ID. No FK.'` |
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

# 7. Database-Enforced Invariants

* حماية المخزون السالب مدعومة بالقيد `CHECK(quantity_on_hand >= 0)`.
* فرادة المخزون لكل كيان مدعومة بالقيد `UNIQUE(stock_subject_type, stock_subject_id)`.
* **Column Comments Requirement:** تطبيقًا للـ Package Building Standard، تلتزم كافة المخططات (Schemas) المذكورة هنا بتوفير تعليقات دلالية (Meaningful Comments) في مرحلة التنفيذ الفعلي (Implementation) توضح الغرض من كل عمود، خصوصًا الهويات الخارجية مثل `COMMENT 'Host-provided ID. No FK.'`.

---

# 8. Domain / Transaction-Enforced Invariants

* **Atomic Increase/Decrease:** التحديثات يجب أن تُنفذ باستخدام عمليات نسبية لضمان التزامن وحل الـ Concurrency issues، ويجب دائمًا تحديث `updated_at` في نفس الاستعلام الذري.
* **Quantity Input Contract:**
  قيمة الـ Mutation (`X`) يجب أن تخضع لقواعد صارمة على مستوى الـ Application قبل الوصول لقاعدة البيانات:
  * يجب أن تكون عددًا صحيحًا موجبًا أكبر قطعًا من الصفر (`X > 0`).
  * الأرقام السالبة، الصفر، أو القيم العشرية (Float) مرفوضة تمامًا (لا يُسمح بتمرير `decrease(-5)` لتعمل كزيادة).
* **Atomic Decrease Contract:**
  `UPDATE maa_inventory_stocks SET quantity_on_hand = quantity_on_hand - :quantity, updated_at = :updated_at WHERE stock_subject_type = :stock_subject_type AND stock_subject_id = :stock_subject_id AND quantity_on_hand >= :quantity_check AND deleted_at IS NULL`.
  (تُمرر قيمة الـ Mutation لكل من `:quantity` و `:quantity_check` بشكل منفصل كـ Unique named placeholders).
* **Atomic Increase Contract:**
  `UPDATE maa_inventory_stocks SET quantity_on_hand = quantity_on_hand + :quantity, updated_at = :updated_at WHERE stock_subject_type = :stock_subject_type AND stock_subject_id = :stock_subject_id AND deleted_at IS NULL`.
* **Result Semantics:**
  الموديول يُفرق بوضوح بين نتائج الـ Mutation المختلفة:
  * `Success`: العملية تمت وتم تحديث صف واحد.
  * `Insufficient Stock`: عند الـ Decrease، السجل موجود وفعّال (Not soft-deleted) لكن الـ `quantity_on_hand` المتاح أقل من الكمية المطلوبة.
  * `Soft-Deleted`: السجل موجود في قاعدة البيانات لكنه يحمل `deleted_at IS NOT NULL` (لا يمكن التعديل عليه).
  * `Not Found`: لا يوجد سجل يطابق الهوية (`stock_subject_type` و `stock_subject_id`) في قاعدة البيانات.
  يجب عدم دمج هذه الحالات تحت رسالة خطأ واحدة إذا كان العقد العام يحتاج للتفريق بينها.
* الموديول يوفر عمليات "الزيادة" و "النقصان" الذرية فقط ولا يسمي عملياته بـ "Reserve" أو "Cancel" لأن هذه دلالات تجارية تخص موديولات أخرى.

---

# 9. Index Strategy

```text
UNIQUE(stock_subject_type, stock_subject_id)
```
يُعد هذا الفهرس كافيًا لمتطلبات الاستعلام والتحديث استنادًا إلى نوع ومعرف الكيان.

---

# 10. Sources of Truth

مصادر الحقيقة الوحيدة هنا هي:
* السجل وكميته المتوفرة `quantity_on_hand` لهوية الـ Subject (Type + ID).
* `deleted_at`.

---

# 11. Architecture Status

**Locked**
القرارات المعمارية الداخلية الخاصة بتتبع المخزون والعمليات الذرية عليه محسومة بشكل كامل. الهوية الخارجية مدعومة بعقد صارم لـ `stock_subject_type` لمنع الـ Collisions والغموض. وتعمل هذه البنية دون أي معرفة بتفاصيل الدومينات الأخرى.
