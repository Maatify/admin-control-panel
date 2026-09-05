# Cross-Module Independence Review Report & Traceability Matrix

## 1. المجلدات والملفات التي تم إنشاؤها أو تعديلها
تم إنشاء/تعديل الملفات التالية وتم حذف `original_catalog_arch.md` لضمان عدم وجود Source of Truth منافس:
* `Modules/Catalog/architecture/CATALOG_V1_ARCHITECTURE.md` (أصبح مخصصاً للـ Taxonomy فقط).
* `Modules/Product/architecture/PRODUCT_V1_ARCHITECTURE.md` (مستقل).
* `Modules/Pricing/architecture/PRICING_V1_ARCHITECTURE.md` (مستقل).
* `Modules/Inventory/architecture/INVENTORY_V1_ARCHITECTURE.md` (مستقل).

## 2. Domain ownership النهائي لكل Module
* **Catalog:** يمتلك حصرياً التصنيف (Taxonomy)، الهرمية (Hierarchy)، وترجمات الفئات (Categories Translations).
* **Product:** يمتلك حصرياً المنتجات (Products)، الهويات (SKU/Barcode)، الخيارات (Options)، التشكلات (Variants/Composition)، والميديا (Media).
* **Pricing:** يمتلك حصرياً الأسعار الأساسية (Base Prices)، التعديلات (Adjustments)، والعمليات الحسابية المرتبطة بالعملات.
* **Inventory:** يمتلك حصرياً المخزون (Inventory)، الكميات (`quantity_on_hand`)، والحماية من المخزون السالب (Negative Stock Protection).

## 3. مصفوفة تتبع القرارات المعمارية المقفولة (Traceability Matrix)

فيما يلي تتبع دقيق وصريح لكل قرار في الوثيقة القديمة (Original Monolith) وأين استقر في المعماريات الجديدة لضمان عدم سقوط أي قرار.

| القرار القديم (Original Rule) | الحالة (Status) | التوضيح |
| --- | --- | --- |
| **Host-Agnostic Boundaries** | **Preserved (All)** | مطبقة كقاعدة أساسية في كل الموديولات الأربعة بشكل مستقل. |
| **Timestamp Policy (UTC, Application-managed)** | **Preserved (All)** | مطبقة في كل جدول في الموديولات الأربعة. |
| **Soft Delete Policy (deleted_at IS NULL)** | **Preserved (All)** | مطبقة في كل الموديولات. |
| **Logical Identity Immutability** | **Preserved (Catalog, Product)** | هويات الكتالوج (Category Translation) بقيت في Catalog. هويات المنتج والـ Variants والـ Options بقيت في Product. |
| **Product Slug & Barcode Lifecycle** | **Preserved (Product)** | تم توثيق قواعد الـ Mutable current values بدون حفظ הـ History في הـ Product. |
| **Category Delete Dependency / Cycle Prevention** | **Preserved (Catalog)** | موجودة بالكامل في `CATALOG_V1_ARCHITECTURE.md`. |
| **Variant Composition Immutable** | **Preserved (Product)** | موجودة كقاعدة صريحة (Composition Immutable after Variant creation). |
| **Duplicate Variant Combination Prevention** | **Preserved (Product)** | Transactionally enforced داخل الـ Product Domain. |
| **Variant-Defining Options / No is_required** | **Preserved (Product)** | الخيارات تعتبر إجبارية دائمًا عند تشكيل הـ Variants. |
| **Full Active Option Coverage** | **Preserved (Product)** | مطبقة لتحديد صحة הـ Effectively Selectable Variants هيكلياً. |
| **Replacement Flows (Add/Remove Options)** | **Preserved (Product)** | موثقة بالخطوات الذرية كـ Domain Invariant داخل الـ Product. |
| **Simple / Configurable Product Rules** | **Preserved (Product)** | مبنية حصريًا على הـ Structural Options. |
| **Direct Sellable Resolution** | **Rewritten (Product)** | قاصرة الآن على تعريف הـ Simple Product هيكليًا (لا تعتمد على Stock/Price كما كان مُضمنًا سابقًا). |
| **Default Variant** | **Preserved (Product)** | `is_default` + `TINYINT(1)` مع قواعد حل التعارض (Conflict Handling) متواجدة بالكامل. |
| **Media Ownership & Primary Limits** | **Preserved (Product)** | جداول الـ Media والقيود الخاصة بالـ Primary وقواعد الـ Restore Conflict Handling موجودة. |
| **force_out_of_stock** | **Preserved (Product)** | Flag مملوك للمنتج وتم الإبقاء عليه مع توضيح صريح لكونه override إداري هيكلي لا يقرأ أو يحتاج حالة المخزون. |
| **Product Visibility based on Category** | **Intentionally Retired (Host Concern)** | لأن الكتالوج لم يعد يعرف المنتج، هذه القاعدة أصبحت مسؤولية الـ Host/Mapping Module ولا تعيش كشرط Transactional داخل الكتالوج أو المنتج. |
| **Sellable In Currency / In-Stock Variant** | **Intentionally Retired (Cross-Domain State)** | هذه القواعد كانت Derived States تجمع Product Status + Base Price + Inventory. تم سحبها لأنها تخالف الـ Independence. |
| **Product Activation Completeness (needs Price)** | **Intentionally Retired (Host Concern)** | المنتج لا يمكنه قراءة جدول الـ Pricing للتحقق من وجود السعر عند التفعيل. يجب التنسيق عبر Coordinator/Events. |
| **Final Price >= 0** | **Rewritten (Pricing - Read-Time)** | Pricing لا يمكنه التنبؤ أو الرفض عند تفعيل منتج. فُرِضت القاعدة في الـ Service كـ Read-time invariant عند الاستعلام عن الأسعار. |
| **Missing Price/Adjustment = 0** | **Preserved (Pricing)** | مطبقة داخل قواعد Pricing المستقلة. |
| **Atomic Quantity Increase/Decrease** | **Rewritten (Inventory)** | ذُكرت كقاعدة Generic داخل Inventory بمعزل عن مفاهيم Orders أو Reservations (والتي تم تصنيفها كـ Explicit Out-of-Scope). |

## 4. إصلاحات الـ External Reference Identity بما يوافق الـ Standards

* بناءً على معايير الـ Base Module (التي تفرض `int|string` وتفضل الهويات الرقمية الإيجابية)، ولمنع تضارب الهويات (Collisions) بين كيانات من مصادر متعددة، تم تصميم הـ Generic Identity كالتالي:
  * في **Pricing**: إضافة `subject_type VARCHAR(100)` مع `subject_id BIGINT UNSIGNED`.
  * في **Inventory**: إضافة `stock_subject_type VARCHAR(100)` مع `stock_subject_id BIGINT UNSIGNED`.
* القيود (Constraints) والفهارس (Indexes) تم تحديثها لضمان הـ Uniqueness استنادًا إلى الـ Type + ID.

## 5. حالة הـ Unresolved Decisions والـ Architecture Status

تمت مراجعة حالة كل موديول وتقييمها بناءً على اكتمال الـ schema والـ lifecycles:
* **Catalog:** **[Candidate]** يوجد قرار داخلي غير محسوم حول ما إذا كان يجب وجود جدول يمثل "الكتالوج" ككيان يحمل הـ Categories، أم سيكتفي الموديول بالـ Taxonomy.
* **Product:** **[Locked]** هويات داخلية محسومة، schema كاملة، lifecycles וـ restore semantics محددة، لا افتراضات مخفية عن الـ host.
* **Pricing:** **[Locked]** הـ Generic identity محسومة، قواعد הـ Invariants مطبقة بشكل داخلي سليم (Read-time).
* **Inventory:** **[Locked]** הـ Generic identity محسومة، العمليات الذرية للحفاظ على הـ bounds واضحة ولا تعتمد على أي Domains خارجية.

## 6. تأكيد الـ Extraction Test (اختبار الاستخراج المستقل)

كل موديول اجتاز بنجاح الـ Extraction Test الفعلي.
*ملاحظة: ظهور كلمات مثل "Products" أو "Orders" في ملف الكتالوج أو المخزون هو محصور حصرياً داخل قسم `Explicit Out-of-Scope` كشرح نصي صريح لحدود الموديول، ولا يُشكّل أي Dependency معمارية أو Table/FK Coupling.*

1. **Catalog:** Schema مكتملة (Categories + Translations). لا يوجد FKs أو جداول لـ Products.
2. **Product:** 9 جداول كاملة بقيود `ON DELETE RESTRICT`، الفهارس الكاملة. الـ lifecycles لا تتطلب قراءة Pricing أو Inventory.
3. **Pricing:** جدولان مجهزان بـ `subject_type` و `subject_id` لتوفير Generic Identity آمنة من הـ Collisions.
4. **Inventory:** جدول وحيد بـ `stock_subject_type` و `stock_subject_id`. العمليات ذريّة مجردة.

كل موديول الآن قابل للعمل كـ Package منفصل تماماً (Microservice/Standalone Library). لا توجد استعلامات، ولا دورات حياة، ولا transactions تعبر Boundaries هذه الموديولات.

## 7. الخاتمة
الوثيقة القديمة لم تعد Source of Truth وتم حذفها، والملفات الجديدة الأربعة تعكس بأمانة الـ Domain Decomposed Architectures بدون فقدان للقواعد الأساسية التي تقع ضمن مسؤولية الدومين. الحالات المتقاطعة (Cross-Domain States) صُنفت صراحة كمهام تنسيق (Host Concerns). لا يوجد كود برمجي تشغيلي أو Roadmaps تم إنشاؤها ضمن هذه المهمة.