# Cross-Module Independence Review Report & Traceability Matrix

## 1. المجلدات والملفات التي تم إنشاؤها أو تعديلها
تم إنشاء/تعديل الملفات التالية وتم حذف النسخة المونوليثية السابقة بالكامل لضمان عدم وجود Source of Truth منافس:
* `Modules/Catalog/architecture/CATALOG_V1_ARCHITECTURE.md` (مخصص للـ Taxonomy).
* `Modules/Product/architecture/PRODUCT_V1_ARCHITECTURE.md` (مستقل).
* `Modules/Pricing/architecture/PRICING_V1_ARCHITECTURE.md` (مستقل).
* `Modules/Inventory/architecture/INVENTORY_V1_ARCHITECTURE.md` (مستقل).

## 2. Domain ownership النهائي لكل Module
* **Catalog:** يمتلك حصرياً التصنيف (Taxonomy)، الهرمية (Hierarchy)، وترجمات الفئات (Categories Translations).
* **Product:** يمتلك حصرياً المنتجات (Products)، الهويات (SKU/Barcode)، الخيارات (Options)، التشكلات (Variants/Composition)، والميديا (Media).
* **Pricing:** يمتلك حصرياً الأسعار الأساسية (Base Prices)، التعديلات (Adjustments)، والعمليات الحسابية المرتبطة بالعملات.
* **Inventory:** يمتلك حصرياً المخزون (Inventory)، الكميات (`quantity_on_hand`)، والحماية من المخزون السالب (Negative Stock Protection).

## 3. مصفوفة تتبع القرارات المعمارية المقفولة (Traceability Matrix)

فيما يلي تتبع دقيق وصريح لكل قرار في الوثيقة القديمة (Original Monolith) وكيف تم التعامل معه في المعماريات الجديدة.

| القرار القديم (Original Rule) | الحالة (Status) | التوضيح |
| --- | --- | --- |
| **Host-Agnostic Boundaries** | **Preserved (All)** | مطبقة كقاعدة أساسية في كل الموديولات بشكل مستقل. لا توجد External FKs أو JOINs. |
| **Timestamp Policy (UTC)** | **Preserved (All)** | مطبقة في كل جدول في الموديولات. |
| **Soft Delete Policy** | **Preserved (All)** | مطبقة باستخدام `deleted_at IS NULL` في كل الموديولات. |
| **Logical Identity Immutability** | **Preserved (Catalog, Product)** | هويات الكتالوج (Translations) والمنتجات (Variants, Options) ثابتة ومحددة. |
| **Product Slug & Barcode Lifecycle** | **Preserved (Product)** | تم توثيق قواعد الـ Mutable current values بدون حفظ الـ History ضمن الـ Product. |
| **Category Cycle Prevention** | **Preserved (Catalog)** | موجودة بالكامل في `CATALOG_V1_ARCHITECTURE.md`. |
| **Variant Composition Immutable** | **Preserved (Product)** | التركيبة تعتبر Immutable بعد إنشاء الـ Variant. |
| **Duplicate Variant Prevention** | **Preserved (Product)** | يتم منع التكرار Transactionally لـ الـ Composition. |
| **Variant-Defining Options** | **Preserved (Product)** | الخيارات تعتبر إجبارية دائمًا (No `is_required`). |
| **Full Active Option Coverage** | **Preserved (Product)** | تحدد صحة الـ Effectively Selectable Variants هيكلياً. |
| **Replacement Flows (Options)** | **Preserved (Product)** | موثقة بالخطوات الذرية كـ Domain Invariant داخل المنتج. |
| **Simple/Configurable Product** | **Preserved (Product)** | مبنية حصريًا على البنية الهيكلية للخيارات الفعالة. |
| **Direct Sellable Resolution** | **Rewritten (Product)** | قاصرة الآن على دلالة Simple Product هيكليًا (لا يتم تقييم Stock/Price هنا كما كان ضمنياً في الماضي). |
| **Default Variant Conflict Handling**| **Preserved (Product)** | قاعدة الـ Maximum 1 وحل التعارض عند الـ Restore موجودة. |
| **Primary Media Limits** | **Preserved (Product)** | القيود الخاصة بالـ Primary وحل تعارض الـ Restore موجودة بالكامل. |
| **force_out_of_stock** | **Preserved (Product)** | Flag إداري هيكلي محفوظ في المنتج، ولا يتدخل لتقييم أو قراءة المخزون الفعلي بل يفرض كـ Override هيكلي. |
| **Product Visibility by Category** | **Intentionally Retired (Host)** | لأن المنتج والكتالوج منفصلان، الرؤية القائمة على الفئات أصبحت مهمة تنسيق خارجي ولا يمكن فرضها داخلياً. |
| **Sellable In Currency State** | **Intentionally Retired (Cross-Domain)**| كانت تجمع Product Status + Base Price + Inventory. تم إزالتها من الدومينات الداخلية لأنها تخالف الـ Independence. |
| **Product Activation (Price check)**| **Intentionally Retired (Host)** | المنتج لا يملك قدرة على قراءة الـ Pricing. ضمان التسعير قبل التفعيل يجب أن يدار عبر Coordinator/Events خارجية. |
| **Final Price >= 0** | **Rewritten (Pricing)** | أُعيدت صياغتها لتصبح Read-time invariant داخل Service التسعير عند حساب السعر المركب بدلاً من Write-time rejection للكيان. |
| **Missing Adjustment = 0** | **Preserved (Pricing)** | أي غياب لـ Adjustment في حساب السعر المركب يُعتبر `0`. |
| **Missing Base Price = Not Sellable**| **Preserved (Pricing)** | غياب Base Price يعني أن الـ Subject المعني غير مسعر بهذه العملة. |
| **Atomic Quantity Increments** | **Rewritten (Inventory)** | ذُكرت كقاعدة Generic داخل Inventory بمعزل عن دلالات الـ Orders والـ Reservations. |

## 4. إصلاحات الـ External Reference Identity

بناءً على معايير الـ Base Module (التي تفرض `int|string` كـ canonical id)، ولمنع الـ Collisions عند تسعير أو جرد أنواع كيانات متعددة، تم تطبيق الـ Generic Subject Identity Contract التالي:
* في **Pricing**: `subject_type` (VARCHAR) و `subject_id` (BIGINT UNSIGNED).
* في **Inventory**: `stock_subject_type` (VARCHAR) و `stock_subject_id` (BIGINT UNSIGNED).
* **Canonical Type Contract:** الـ Type يخضع لقواعد صارمة: نص لا يتجاوز 100 حرف، يتوافق مع Regex `/^[a-zA-Z0-9_.-]+$/`، الـ Whitespace مرفوض تمامًا، المطابقة Case-Sensitive حرفية (بدون Aliases)، وهو Immutable بعد الإنشاء.

## 5. حالة الـ Unresolved Decisions والـ Architecture Status

القرار الفعلي والنهائي لحالة كل موديول بناءً على اكتمال الـ schema والـ lifecycles:

* **Catalog:** **[Candidate]** يوجد قرار داخلي جوهري غير محسوم؛ وهو هل يحتاج الموديول إلى Entity حقيقية لتمثيل الـ `Catalog` ليكون حاوية للـ Categories (مما يبرر اسمه)، أم سيكتفي بكونه Taxonomy engine؟ ولعدم حسم هذه الـ Entity داخلياً، لا تعتبر البنية Locked.
* **Product:** **[Locked]** Schema كاملة (9 جداول بـ PKs/FKs)، Lifecycles (بما في ذلك Staged Composition، Slug/Barcode) و Restore semantics محددة، ولا توجد افتراضات مخفية.
* **Pricing:** **[Locked]** Generic Identity محددة بدقة لتفادي الـ Collisions، وقواعد الحساب مطبقة داخلياً بمعزل عن الكيانات الأخرى.
* **Inventory:** **[Locked]** Generic Identity محددة بدقة، والعمليات الذرية للحفاظ على الـ bounds واضحة ولا تعتمد على أي Domains خارجية (كالحجوزات أو الطلبات).

## 6. تأكيد الـ Extraction Test (اختبار الاستخراج المستقل)

اجتازت كل البنى (Architectures) بنجاح اختبار الاستخراج:
* *ملاحظة للاختبار:* لا يعتمد تقييم الـ Extraction Test على مجرد غياب الكلمات مفتاحية من المستند (فذكر كلمات كـ Products أو Orders في الـ `Explicit Out-of-Scope` هو لتوضيح حدود الموديول).
* **الاعتمادية المعمارية (Architectural Dependency):** لا يوجد أي Cross-module FK، أو JOIN، أو Derived state يحتاج قراءة جداول من मوديول آخر، ولا توجد Transaction Workflow تتخطى حاجز الموديول.
* يتم تطبيق الـ Generic IDs دون معرفة مسبقة بأسماء الجداول أو كلاسات الـ Host/Siblings.

## 7. الخاتمة
تم حذف الوثيقة الأصلية للـ Monolith بشكل كامل لتفادي وجود مصادر حقيقة (Sources of Truth) متنافسة. تم صياغة المعماريات الأربعة لتكون حقاً Domain Decomposed Architectures تحترم استقلالية الدومين، وتم فرز الحالات المتقاطعة إلى مهام التنسيق في طبقة الـ Host. لا يدعي هذا التقرير أن جميع المعماريات انتهت؛ الـ Catalog ما زال يحمل حالة `Candidate` لحين حسم الـ Catalog Entity. لا يوجد كود برمجي تم إنتاجه ضمن هذا الـ Pull Request.