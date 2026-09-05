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
المنتج يعتبر قابلًا للبيع بعملة محددة إذا تحقق الآتي:
* `Product Base Module`: الكيان مهيكليًا قابل للطلب.
* `Pricing Base Module`: يمتلك تسعيرة نهائية غير سالبة بهذه العملة.

### 5.2 Stock State / In-Stock Variant
المنتج يعتبر In-Stock إذا:
* `Product Base Module`: `force_out_of_stock` غير مفعل، وهناك Variants قابلة للاختيار هيكليًا.
* `Inventory Base Module`: الكمية المتاحة لتلك الـ Variants أكبر من صفر (`quantity_on_hand > 0`).

### 5.3 Product Activation Completeness
لتفعيل المنتج تجارياً بالكامل يجب على الـ Package Layer التأكد من توفر جميع شروط الإتاحة الهيكلية والتجارية في وقت واحد:
* `Product Base Module`: يوجد على الأقل Variant واحد غير محذوف، Active، و Structurally Valid.
* `Inventory Base Module`: يوجد سجل مخزون (Inventory identity/row) مرتبط بهذا الـ Variant. (ملاحظة: ليس شرطاً أن يكون `quantity_on_hand > 0` للتفعيل؛ المخزون الصفري يجعل المنتج Out of Stock ولكنه لا يمنع التفعيل).
* `Pricing Base Module`: توجد تسعيرة أساسية (Base Price) واحدة على الأقل غير محذوفة، ويتم التحقق من أن حسابات التسعير (Pricing Safety) تفي بشروط عدم وجود أسعار نهائية سالبة.

لأن الـ Product Base Module لا يمكنه قراءة جداول الـ Pricing أو الـ Inventory داخلياً، يتم التفعيل كعملية منسقة (Orchestrated Operation).

### 5.4 Active Product Base Price Continuity
طالما أن المنتج في حالة التفعيل (Active)، يجب على طبقة الـ Package ضمان استمرارية وجود Base Price واحد غير محذوف على الأقل. لا يُسمح بعمل Soft Delete لآخر Base Price متبقي لمنتج Active، وهذا يتطلب تنسيقًا من الـ Package Coordinator.

### 5.5 Category to Product Visibility
ظهور المنتج ضمن الـ Categories هو عملية تنسيق في طبقة الـ Package. يتم الربط (Mapping) خارج الـ Base Modules، وإذا توقف مسار الـ Category، فلا يؤثر ذلك داخلياً على الـ Product.
