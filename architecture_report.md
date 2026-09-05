# Architecture Report: Catalog Domain Decomposition

تمت إعادة بناء البنية المعمارية الخاصة بنطاق الـ Catalog لفك الارتباط وتحويله من Monolith (Catalog V1) إلى مجموعة من Base Modules المستقلة تماماً بالإضافة إلى Catalog Package (Umbrella/Aggregator).

## 1. Branch & Commit Information
- **Branch:** `arch/catalog-domain-decomposition`
- **Commit SHA:** 875ad79c1865a6364f4339f7a0d221e898ea7f7c

## 2. Files Modified / Created
تم إضافة وتعديل الملفات التالية:
*   `Modules/Catalog/architecture/CATALOG_PACKAGE_ARCHITECTURE.md` (تم إنشاؤه)
*   `Modules/Catalog/architecture/CATALOG_V1_ARCHITECTURE.md` (تم تحديثه)
*   `Modules/Product/architecture/PRODUCT_V1_ARCHITECTURE.md` (تم إنشاؤه)
*   `Modules/Pricing/architecture/PRICING_V1_ARCHITECTURE.md` (تم إنشاؤه)
*   `Modules/Inventory/architecture/INVENTORY_V1_ARCHITECTURE.md` (تم إنشاؤه)
*   `Modules/Orders/architecture/ORDERS_V1_ARCHITECTURE.md` (تم تحديثه لتصحيح المصطلحات)
*   `Modules/Cart/architecture/CART_V1_ARCHITECTURE.md` (تم تحديثه لتصحيح المصطلحات)
*   `architecture_report.md` (هذا الملف)

*(ملف `Modules/Catalog/architecture/Catalog_V1_Architecture_Locked.pdf` يظل كما هو كمرجع تاريخي).*

## 3. Resolution of Issues

### A. Variant ↔ Inventory Lifecycle
**الحل:** تم توثيق قواعد الـ Lifecycle الخاصة بالـ Variant في `CATALOG_PACKAGE_ARCHITECTURE.md` بدلاً من Inventory Base Module. تم تفصيل أن Inventory Base يعتمد على مراجع Generic (`stock_subject_type`, `stock_subject_id`)، بينما المنسق (Catalog Package) يمتلك التنسيق الخاص بتحديثات دورة الحياة للـ Variant (مثل الإنشاء، الحذف المؤقت، الاستعادة، وإنشاء البديل Replacement Variant).

### B. Final Price Equation
**الحل:** أُضيفت معادلة السعر النهائي بوضوح كقاعدة (Source of Truth) داخل `CATALOG_PACKAGE_ARCHITECTURE.md`.
*   Final Price = Base Price + SUM(Selected Option Value Price Adjustments) لنفس العملة.
*   تم تحديد قواعد الفقدان (Missing Adjustment = 0, Missing Base Price = Not Sellable).
*   تم التوضيح بأن السعر يحسب أثناء التشغيل ولا يتم تخزينه، والمخزون لا يدخل في السعر.

### C. Pricing Logical Identity
**الحل:** في ملف `PRICING_V1_ARCHITECTURE.md`، تم تثبيت الهوية المنطقية (Logical Identity) بشكل واضح وهي: `(subject_type, subject_id, currency_code)`. تم النص على أن هذه الأعمدة الثلاثة جميعها Immutable (غير قابلة للتعديل)، وأي تغيير في العملة يعني هوية جديدة.

### D. Product ↔ Category Contract
**الحل:** تم نقل الـ Business Semantics وقواعد الارتباط بين Product و Category إلى `CATALOG_PACKAGE_ARCHITECTURE.md`. مع ترك آلية الـ Persistence كقرار Package-level unresolved. تم إثبات الـ Logical identity `(product_id, category_id)` والـ Display Order Scope و Ordering Rule، وتوضيح أن حذف أو تغيير الـ Category لا يؤثر على الـ Product نفسه، فقط على رؤيته في المسار.

### E. Product Display Order Contracts
**الحل:** تمت إضافة الـ Scopes الخاصة بـ Display Order في `PRODUCT_V1_ARCHITECTURE.md` (مثلاً: Global للـ Products، Per Product للـ Options و Variants و Product Media، و Per Option للـ Option Values، الخ). وتم إدراج استراتيجية الفهارس (Index Strategy) لتدعم هذا الترتيب.

### F. Physical Database Contract
**الحل:** تم تضمين Physical DB contract (`ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`) في جميع الـ Modules الأساسية (`PRODUCT_V1_ARCHITECTURE.md`, `PRICING_V1_ARCHITECTURE.md`, `INVENTORY_V1_ARCHITECTURE.md`, و `CATALOG_V1_ARCHITECTURE.md`) كـ Source of Truth الحالي.

### G. Architecture Report Updates
**الحل:** تم تصنيف التغييرات بعناية حسب متطلبات المهمة:
*   **Inventory Lifecycle:** Rewritten inside Inventory Base / Catalog Package Rule.
*   **Direct Sellable Resolution:** Preserved inside Product Base.
*   **Sellable In Currency:** تم الفصل الكامل في الوثائق بين Stock State و Currency Sellability.
*   **Traceability:** تم تأكيد أن التقارير والمستندات أصبحت تغطي بشكل مباشر كافة النقاط المذكورة.

### H. Orders / Cart Terminology Sweep
**الحل:** تم فحص ملفات الهندسة الخاصة بـ Orders و Cart. تم استبدال الكلمات القديمة مثل "Catalog owns translations" أو "Catalog quantity_on_hand" بالكلمات الصحيحة مثل "Product Domain", "Inventory Domain", "Pricing Domain", و "Catalog Package Coordinator" بحيث لا يظل هناك لبس في ملكية النطاقات.

## 4. Module Statuses

| Module / Package | Status | Notes |
| :--- | :--- | :--- |
| **Catalog Package** | N/A (Umbrella) | Coordinates Base Modules, Unresolved Category mapping persistence. |
| **Catalog Base** | **Candidate** | Base Entity itself (Categories) is not fully resolved. |
| **Product** | **Locked** | Base product management rules are established and locked. |
| **Pricing** | **Locked** | Generic Pricing rules and logical identity are locked. |
| **Inventory** | **Locked** | Generic Inventory mutations and constraints are locked. |

## 5. Independence Check
لا توجد أي اعتمادية متبادلة (No cross-module dependencies, No FK, No JOIN) داخل ملفات الهندسة المعمارية لـ Base Modules. كل وحدة تعتمد فقط على الـ Generic References لتعريف الكيانات.
