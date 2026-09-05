# Catalog Package / Composite Architecture

**The Umbrella Architecture for the E-Commerce Catalog System**

هذه الوثيقة هي المرجع المعماري المظلي (Umbrella Architecture) لـ **Catalog Package**. وتُعرّف كيفية دمج وتنسيق الموديولات المستقلة (Taxonomy, Product, Pricing, Inventory) لتكوين منظومة الكتالوج الشاملة، مع الحفاظ على استقلالية كل موديول.

---

## 1. الغرض من الـ Catalog Package

الـ Catalog Package ليس Base Module بحد ذاته، بل هو Aggregation Layer. وظيفته هي تجميع الموديولات المستقلة لتقديم تجربة تسوق وبناء كتالوج إلكتروني متكامل.

---

## 2. اتجاه الاعتماد (Dependency Direction)

المبدأ الأساسي للحفاظ على استقلالية الموديولات هو:

> **Catalog Package knows and composes the modules.**
> **The modules do not know the Catalog Package and do not know each other.**

* الـ Package/Aggregator: يعرف Catalog, Product, Pricing, و Inventory، ويدير الربط بينها.
* الـ Base Modules: لا تعرف بعضها، ولا تعرف أنها جزء من Catalog Package.

---

## 3. التسلسل الهرمي لمصادر الحقيقة (Source of Truth Hierarchy)

لتفادي التناقضات بين التصميم المجمع والتصميم المستقل، يجب الالتزام بالتسلسل التالي:

### 3.1 Module Internal Architectures (أعلى أولوية للتصميم الداخلي)
أي ملف يمثل المرجعية الداخلية لموديول ويحمل اسمه (مثل `CATALOG_V1_ARCHITECTURE.md`, `PRODUCT_V1_ARCHITECTURE.md`, إلخ) داخل مسار `Modules/{Module}/architecture/` هو المرجع الحصري الوحيد (Source of Truth) للتصميم الداخلي للموديول، الـ Schema، الـ Invariants، والـ Lifecycles الخاصة به.
**(يُستثنى من هذه القاعدة المستند الحالي `CATALOG_PACKAGE_ARCHITECTURE.md` وملف الـ PDF `Catalog_V1_Architecture_Locked.pdf` حيث أنهما يمثلان الـ Package-level Umbrella ولا ينتميان للتصميم الداخلي لموديول الـ Taxonomy).**

### 3.2 Catalog Package Architecture (المرجع التنسيقي المظلي)
هذا الملف يعتبر المرجع (Source of Truth) فقط لـ:
* الـ Composition.
* الـ Domain Map.
* الـ Integration Ownership.
* الـ Cross-domain derived rules.
* مفاهيم الـ Package-level orchestration.

### 3.3 Historical Composite Reference (الـ PDF الأصلي)
الملف `Catalog_V1_Architecture_Locked.pdf` تم الاحتفاظ به كمرجع تاريخي شامل يعرض الصورة العامة والتنسيق والربط بين المجالات.
* **ممنوع** استخدام الـ PDF كمرجع داخلي لتجاوز قرارات الـ Base Modules (كإعادة إدخال FKs أو Cross-module dependencies داخل الـ Base Modules). في حال التعارض حول التفاصيل الداخلية لموديول، تكون الأولوية لـ Module Internal Architecture (`.md` file).

---

## 4. توزيع ملكية المجالات (Domain Map & Module Ownership)

يجمع الـ Catalog Package الموديولات التالية:

1. **Catalog Base Module (`Modules/Catalog`):** يملك الـ Taxonomy، الهرمية، والـ Categories.
2. **Product Base Module (`Modules/Product`):** يملك المنتجات، الـ Variants، الـ Options، والميديا المرتبطة بها.
3. **Pricing Base Module (`Modules/Pricing`):** يملك الأسعار الأساسية والتعديلات، والعمليات الحسابية للعملات.
4. **Inventory Base Module (`Modules/Inventory`):** يملك المخزون والعمليات الذرية لتغيير الكميات المتاحة.

---

## 5. قواعد الحالات المتقاطعة (Cross-Domain Derived Rules)

القواعد التي تحتاج معلومات من أكثر من Domain لتقييمها لا يملكها أي موديول أساسي منفرداً، بل تُعتبر **Catalog Package / Aggregation Layer Derived Rules**.

### 5.1 Sellable In Currency
القابلية للبيع بعملة محددة (Sellability in Currency) تعتمد حصريًا على التسعير (Pricing-only rule) ولا تختلط مع حالة المخزون أو تقييمات الـ Product-local الهيكلية. الكيان يعتبر Sellable In Currency إذا تحقق الآتي:
* يمتلك تسعيرة أساسية (Base Price). إذا كانت Missing Base، فهو Not Sellable In This Currency.
* غياب التعديلات (Missing Adjustment) يحسب كـ `0`.
* السعر النهائي (Final Price = Base + Adjustments) يجب أن يكون غير سالب (`>= 0`).
* **توضيح صريح:** حالة المخزون (Stock State) و التقييمات الهيكلية (Product orderability) منفصلة تمامًا ولا تشارك في تقييم הـ Sellable In Currency.

### 5.2 Stock State / In-Stock Variant
المنتج يعتبر In-Stock إذا:
* `Product Base Module`: `force_out_of_stock` غير مفعل، وهناك Variants قابلة للاختيار هيكليًا.
* `Inventory Base Module`: الكمية المتاحة لتلك الـ Variants أكبر من صفر (`quantity_on_hand > 0`).

### 5.3 Product Activation Completeness
لتفعيل المنتج تجارياً بالكامل يجب على الـ Package Layer التأكد من توفر جميع شروط الإتاحة الهيكلية والتجارية في وقت واحد:
* `Product Base Module`: يوجد على الأقل Variant واحد غير محذوف، Active، و Structurally Valid.
* `Inventory Base Module`: يوجد سجل مخزون (Inventory identity/row) مرتبط بهذا الـ Variant. (ملاحظة: ليس شرطاً أن يكون `quantity_on_hand > 0` للتفعيل؛ المخزون الصفري يجعل المنتج Out of Stock ولكنه لا يمنع التفعيل).
* `Pricing Base Module`: توجد تسعيرة أساسية (Base Price) واحدة على الأقل غير محذوفة، ويتم التحقق من أن حسابات التسعير (Pricing Safety) تفي بشروط عدم وجود أسعار نهائية سالبة.

### 5.4 Variant Activation Validation
تفعيل الـ Variant بحد ذاتها يخضع لنفس مفهوم التنسيق المظلي (Package coordination):
لا يتم تفعيل Variant ما لم تتجاوز التقييم الهيكلي (Product-local Structural Validity) بالإضافة لوجود سجل Inventory ومرورها باختبار الـ Pricing Safety.

### 5.5 Option & Option Value Activation Safety
تفعيل Option (خيار) أو Option Value (قيمة) قد يؤثر على الأسعار النهائية للـ Variants التي تعتمد عليهما. لذلك، يجب على طبقة الـ Package التحقق من الـ Pricing Safety (عدم ظهور أسعار نهائية سالبة) قبل إتمام عملية التفعيل.

### 5.6 Package-Level Restore Safety
هناك فرق واضح بين الـ **Base Module restore validation** (الذي يتأكد من القيود الهيكلية للموديول) والـ **Package orchestration restore validation** (الذي ينسق الحالات المتقاطعة).
استعادة (Restore) أي كيان من الحذف المنطقي قد تجعله فعّالاً (Active) بشكل مباشر إذا كانت حالته السابقة Active. يفرض الـ Package Coordinator التحققات التالية:
* **استعادة Product:** يجب التأكد من وجود Active Valid Variant، سجل Inventory مرتبط، Base Price واحدة على الأقل، وعدم اختراق Pricing Safety.
* **استعادة Variant:** يجب التأكد من الصلاحية الهيكلية (Product structural validity)، توفر سجل Inventory، وسلامة التسعير (Pricing Safety)، بالإضافة إلى القيود المحلية للمنتج.
* **استعادة Option أو Option Value:** التأكد من التغطية الهيكلية (Product-local coverage/selectability) بالإضافة إلى الـ Pricing Safety في طبقة الـ Package لتجنب إنتاج أسعار نهائية سالبة.

### 5.7 Active Product Base Price Continuity
طالما أن المنتج في حالة التفعيل (Active)، يجب على طبقة الـ Package ضمان استمرارية وجود Base Price واحد غير محذوف على الأقل. لا يُسمح بعمل Soft Delete لآخر Base Price متبقي لمنتج Active، وهذا يتطلب تنسيقًا من الـ Package Coordinator.

### 5.8 Pricing Safety Write-Time Guarantees
موديول Pricing الأساسي لا يعلم بحالة Product ولا الـ lifecycle الخاصة به. لذلك، مسؤولية منع الأسعار النهائية السالبة عند التعديل (Write-time validation) تقع على الـ Catalog Package Coordinator.
إذا أدى أي من الإجراءات التالية إلى جعل السعر النهائي (Final Price) أقل من صفر لتركيبة (Combination) فعّالة (Active)، **يجب رفض العملية (Mutation Rejected)** بواسطة الـ Package Coordinator:
* إنشاء أو تعديل أو استعادة Base Price.
* إنشاء أو تعديل أو حذف منطقي (Soft Delete) أو استعادة Adjustment.
* تفعيل أو استعادة Product, Variant, Option, Option Value.
لا يُسمح للمنتج الفعّال (Active) بالدخول في حالة تسعير غير صالحة (Negative Final Price).

---

## 6. هوية الربط الخارجي (Integration Identity Mapping)

يعرّف الـ Catalog Package بشكل حتمي (Deterministic) خريطة هويات الربط (Mapping Contract) بين موديول Product وموديولات Pricing و Inventory لمنع الـ Collisions. يتم تمرير هذه الهويات للـ Base Modules المجردة:

* **Product إلى Pricing Base:**
  يتم تعيين `subject_type = 'product'` ويتم تمرير `subject_id = product.id`.
* **Option Value إلى Pricing Base (للتعديلات Adjustments):**
  يتم تعيين `subject_type = 'option_value'` ويتم تمرير `subject_id = option_value.id`.
* **Variant إلى Inventory Base:**
  يتم تعيين `stock_subject_type = 'variant'` ويتم تمرير `stock_subject_id = variant.id`.

يُعد هذا الـ Mapping ثابتاً (Immutable) وأي تغيير فيه يُعتبر تغييراً معمارياً (Architecture Change).

---



## 7. الربط بين الفئات والمنتجات (Product ↔ Category Mapping Ownership)

حيث أن Catalog Base لا يعرف Product، و Product لا يعرف Catalog، فإن ملكية الربط (Visibility / Mapping) تقع على عاتق الـ **Catalog Package / Host Application**.

* **الجهة المالكة:** Catalog Package / Host Application.
* **الهوية المنطقية:** علاقة (Mapping) بين `category_id` و `product_id`.
* **الـ Uniqueness:** يجب ألا تتكرر العلاقة لنفس الـ `(category_id, product_id)`.
* **الـ Soft Delete:** يتبع الـ Lifecycle الخاص بالـ Package.
* **Display Order Scope:** خاص بكل `category_id`.
* **Ordering Rule:** `ORDER BY display_order, id`.
* **Visibility Semantics:**
  * إذا كان Category A = inactive و Category B = active، والـ Product مرتبط بهما: تعطيل A يعطل مسار العرض (Classification Path) الخاص بـ A فقط. الـ Product تظل قابلة للظهور عبر B.
  * حالة الـ Category لا تقوم بتعديل `Product.status`.
  * حذف الـ Category أو تغيير حالته لا يعدل سجلات Product/Variant.
* **Unresolved Package Decision:** بناءً على القيود الحالية لمنع الـ Cross-Module FKs، لم يتم الحسم معمارياً ما إذا كان سيتم فرض Foreign Keys في جدول الـ Mapping المظلي (Package-owned persistence) نحو الـ Base Modules، أم سيكتفي بهويات مجردة (Generic IDs). لذا تُعتبر تفاصيل الـ Persistence الدقيقة لهذا الربط Unresolved Decision على مستوى الـ Package.

---


## 8. Variant ↔ Inventory Lifecycle Orchestration

الـ Inventory Base Module مستقل ولا يعرف الـ Variant. الـ Catalog Package يفرض التنسيق الخاص بالـ Variant كالتالي:

* **Variant Inventory Entity:** `stock_subject_type = 'variant'`, `stock_subject_id = variant.id`
* **Lifecycle Rule:** Every non-deleted Variant participating in Catalog Package has exactly one Inventory identity.
* **Creation (Atomicity):** إنشاء الـ Variant و سجل المخزون المرتبط بها يجب أن يتم كعملية "كل شيء أو لا شيء" (All-or-nothing Transaction) يتحكم بها الـ Package Coordinator. الـ Product والـ Inventory يشاركان في نفس الـ Coordinator-owned Transaction بدون Commits أو Rollbacks مستقلة، لضمان عدم وجود Variant محفوط (Committed) بدون Inventory Identity. تبدأ كمية المخزون بـ `0`.
* **Restore:** عند عمل Restore لـ Variant، يعود نفس سجل الـ Inventory identity. لا تنشأ identity جديدة.
* **Soft Delete:** عمل Soft Delete للـ Variant لا يمحو المخزون بشكل مستقل، ولكن المنسق يمنع حذف الـ Inventory بشكل مستقل طالما أن الـ Variant موجودة وغير محذوفة نهائياً.

---

## 9. Final Price Equation

الـ Pricing Base Module مستقل ولا يعرف الـ Product أو الـ Options. الـ Catalog Package يحدد طريقة تركيب السعر النهائي للـ Variant.

* **Product Mapping:** `subject_type = 'product'`, `subject_id = product.id`
* **Option Value Mapping:** `subject_type = 'option_value'`, `subject_id = option_value.id`

**المعادلة المقفولة:**
```text
Final Price for Variant/Combination in Currency C
=
Base Price(product.id, C)
+
SUM(
    Adjustment(option_value.id, C)
    for selected option values in that Variant composition
)
```

**قواعد السعر النهائي:**
* يتم الحساب بناءً على الـ Option Values الموجودة فعلياً في تكوين الـ Variant.
* Missing adjustment لنفس العملة = `0`.
* Adjustment بعملة مختلفة لا يدخل في الحساب.
* Missing base price = Not Sellable In This Currency.
* Final Price يجب أن يكون `>= 0`.
* السعر النهائي (Final Price) يحسب أثناء التشغيل ولا يخزن في قواعد البيانات.
* حالة المخزون (Stock) لا تؤثر في السعر النهائي.

---

## 10. تدفقات الاستبدال عبر الـ Package (Package-Level Replacement Flows)

عند إجراء تدفق استبدال (Replacement Flow) كإضافة أو إزالة Option، يُنفذ الـ Product Base Module التعديلات الهيكلية بإنشاء الـ Variants الجديدة محلياً. لكن الـ Package Coordinator هو المسؤول عن الـ Orchestration المتقاطع:
* **Product:** ينشئ الـ Variants البديلة هيكلياً بشكل ذري (Atomic).
* **Inventory:** يجب على الـ Package Coordinator تجهيز سجلات المخزون (Inventory identities) للـ Variants الجديدة المتولدة.
* **Pricing:** يجب على الـ Package Coordinator إعادة التحقق من الـ Pricing Safety.
* **الاستبدال (Cutover):** لا تكتمل عملية الاستبدال ولا يُسمح بترك الـ Product مفعلًا (Active) إذا كانت الـ Package Invariants غير متحققة. الـ Product Base لا يستدعي الـ Inventory مباشرة أبداً.
