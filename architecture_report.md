# Cross-Module Independence Review Report & Traceability Matrix

## 1. الهيكلية المظلية والمراجع المعمارية (Umbrella vs Base Architectures)
تم في هذا التحديث إرجاع الوثيقة المونوليثية كـ PDF تاريخي ليصبح مرجعاً مظلياً (Umbrella/Composite Architecture) لـ **Catalog Package** بالكامل (والذي يشمل جميع الموديولات).
لضمان عدم تضارب مصادر الحقيقة، تم تعريف تسلسل هرمي واضح:
* **Base Module Architectures (`.md`):** هي الـ Source of Truth الوحيد للتصميم الداخلي للموديول.
* **Catalog Package Architecture (`CATALOG_PACKAGE_ARCHITECTURE.md`):** هو الـ Source of Truth الحالي والحصري للتنسيق (Composition)، دمج الكيانات، و الـ Cross-domain Derived Rules التي تعتمد على أكثر من موديول. أما الـ PDF المرفق فيُعد مجرد `Historical Composite Reference` للاستئناس ولا يعول عليه لتجاوز هذا الملف المعماري المكتوب.
* **Dependency Direction:** الـ Catalog Package يجمع ويعرف الموديولات، لكن الموديولات الأساسية لا تعرف الـ Package ولا تعرف بعضها البعض أبداً.

تم إنشاء/تعديل الملفات التالية وتم حذف أي نص قديم منافس لتفادي وجود Source of Truth متضارب:
* `Modules/Catalog/architecture/Catalog_V1_Architecture_Locked.pdf` (تم الاحتفاظ به دون تغيير - Retained unchanged - كمرجع للتنسيق العام للـ Package).
* `Modules/Catalog/architecture/CATALOG_PACKAGE_ARCHITECTURE.md` (جديد: يوضح دور الـ Package والـ PDF).
* `Modules/Catalog/architecture/CATALOG_V1_ARCHITECTURE.md` (مخصص لـ Taxonomy Base Module).
* `Modules/Product/architecture/PRODUCT_V1_ARCHITECTURE.md` (مستقل).
* `Modules/Pricing/architecture/PRICING_V1_ARCHITECTURE.md` (مستقل).
* `Modules/Inventory/architecture/INVENTORY_V1_ARCHITECTURE.md` (مستقل).
* `Modules/Cart/architecture/CART_V1_ARCHITECTURE.md` (مستقل).
* `Modules/Orders/architecture/ORDERS_V1_ARCHITECTURE.md` (مستقل).
* `architecture_report.md` (هذا التقرير نفسه).

## 2. Domain ownership النهائي لكل Base Module
* **Catalog:** يمتلك حصرياً التصنيف (Taxonomy)، الهرمية (Hierarchy)، وترجمات الفئات (Categories Translations).
* **Product:** يمتلك حصرياً المنتجات (Products)، الهويات (SKU/Barcode)، الخيارات (Options)، التشكلات (Variants/Composition)، والميديا (Media).
* **Pricing:** يمتلك حصرياً الأسعار الأساسية (Base Prices)، التعديلات (Adjustments)، والعمليات الحسابية المرتبطة بالعملات.
* **Inventory:** يمتلك حصرياً المخزون (Inventory)، الكميات (`quantity_on_hand`)، والحماية من المخزون السالب (Negative Stock Protection).

## 3. مصفوفة تتبع القرارات المعمارية المقفولة (Traceability Matrix)

فيما يلي تتبع دقيق وصريح لكل قرار في الوثيقة القديمة (Original Monolith) وكيف تم التعامل معه في المعماريات الجديدة.

| القرار القديم (Original Rule) | الحالة (Status) | التوضيح |
| --- | --- | --- |
| **Host-Agnostic Boundaries** | **Preserved (All)** | مطبقة كقاعدة أساسية في كل الموديولات بشكل مستقل. لا توجد External FKs أو JOINs. |
| **Timestamp Policy (UTC)** | **Preserved (All)** | مطبقة بالكامل في كل الموديولات: Application-managed، UTC، `created_at` عند الإنشاء، `updated_at` عند أي mutation (بما فيها soft delete/restore). |
| **Soft Delete Policy** | **Preserved (All)** | مطبقة باستخدام `deleted_at IS NULL` لتمثيل السجلات الفعالة. لا يوجد runtime hard-delete للحذف العادي. |
| **Logical Identity Immutability** | **Preserved inside Base Module (Catalog, Product)** | هويات الكتالوج (Translations) والمنتجات (Variants, Options) ثابتة ومحددة. تم تأكيد أن ارتباط الـ Variant بـ `product_id` يعتبر Immutable داخل Product Base Module. |
| **Product Slug & Barcode Lifecycle** | **Preserved inside Base Module (Product)** | تم توثيق قواعد الـ Mutable current values بدون حفظ الـ History ضمن الـ Product. |
| **Category Cycle Prevention** | **Preserved inside Base Module (Catalog)** | موجودة بالكامل وموثقة في `CATALOG_V1_ARCHITECTURE.md`. |
| **Category Child Delete Dependency** | **Preserved inside Base Module (Catalog)** | موجودة بالكامل لمنع الـ Soft Delete للفئة إذا كان لديها أبناء غير محذوفين. |
| **Variant Composition Immutable** | **Preserved inside Base Module (Product)** | التركيبة تعتبر Immutable بعد إنشاء الـ Variant. |
| **Duplicate Variant Prevention** | **Preserved inside Base Module (Product)** | يتم منع التكرار Transactionally لـ الـ Composition. |
| **Product-local Structural Validity** | **Preserved inside Base Module (Product)** | تم الحفاظ على التعريف الهيكلي لصحة الـ Variant (التبعية، اكتمال الخيارات، وعدم التكرار) بشكل مستقل تماماً عن الـ Stock والـ Pricing كقاعدة محلية للمنتج. |
| **Variant-Defining Options** | **Preserved inside Base Module (Product)** | الخيارات تعتبر إجبارية دائمًا (No `is_required`). |
| **Full Active Option Coverage** | **Preserved inside Base Module (Product)** | تحدد صحة الـ Effectively Selectable Variants هيكلياً. |
| **Effectively Selectable Composition Rules** | **Preserved inside Base Module (Product)** | مُعرفة داخلياً بالكامل استناداً لـ Active Status لكل من Product, Variant, Option, و Value. |
| **Replacement Flows (Options)** | **Rewritten inside Base Module / Catalog Package Rule** | الجزء الهيكلي preserved داخل Product Base. أما الـ Orchestration والتأكد من Inventory Identity والـ Pricing Safety فهي Catalog Package Rule. |
| **Option/Value Delete Dependency** | **Preserved inside Base Module (Product)** | يمنع الـ Soft Delete لخيارات أو قيم مرتبطة بـ Variant غير محذوفة. |
| **Option/Value Integrity** | **Preserved inside Base Module (Product)** | موجودة لضمان سلامة وارتباط القيم والخيارات. |
| **Cross-Product Composition Prevention** | **Preserved inside Base Module (Product)** | Transactionally enforced لضمان التبعية لنفس المنتج. |
| **Simple/Configurable Product** | **Preserved inside Base Module (Product)** | مبنية حصريًا على البنية الهيكلية للخيارات الفعالة. |
| **Direct Sellable Resolution** | **Preserved inside Base Module (Product)** | تم الحفاظ على القاعدة الهيكلية: No Active Options + Exactly One Effectively Selectable Variant (لا يشترط أن تكون default). Stock و Pricing لا يشاركان في خطوة الـ Resolution المباشرة. |
| **Default Variant Conflict Handling**| **Preserved inside Base Module (Product)** | قاعدة الـ Maximum 1 وحل التعارض الداخلي عند الـ Restore موجودة. |
| **Media Ownership** | **Preserved inside Base Module (Product)** | مُعرفة بوضوح للـ Product والـ Variant (Product Media vs Variant Media). |
| **Primary Media Limits** | **Preserved inside Base Module (Product)** | القيود الخاصة بالـ Primary وحل تعارض الـ Restore موجودة بالكامل. |
| **force_out_of_stock** | **Preserved inside Base Module (Product)** | Flag إداري هيكلي محفوظ في المنتج، ولا يتدخل لتقييم أو قراءة المخزون الفعلي، بل يفرض كـ Override هيكلي. |
| **Product Visibility by Category** | **Catalog Package / Composite Rule** | لأن المنتج والكتالوج Base Modules منفصلان، الرؤية القائمة على الفئات أصبحت مهمة تنسيق في طبقة الـ Catalog Package ولا يمكن فرضها داخلياً. (وتم توضيحها كعلاقة غير محسومة الـ persistence على مستوى الـ Package). |
| **Sellable In Currency State** | **Catalog Package / Composite Rule** | تم توضيح أن Sellable In Currency تعتمد فقط على الـ Base Price و Pricing rules منفصلة تماماً عن الـ Stock State، وتدار بواسطة الـ Package Coordinator. |
| **Product Activation Completeness** | **Catalog Package / Composite Rule** | المنتج لا يمكنه قراءة الـ Pricing ولا الـ Inventory مباشرةً لضمان التفعيل. توفر السعر وسجل المخزون تُدار كـ Orchestrated Operation في طبقة الـ Package. |
| **Variant Activation Validation** | **Catalog Package / Composite Rule** | تفعيل الـ Variant يستلزم تغطية خيارات، وسجل Inventory، وشروط تسعير، لذا التفعيل النهائي يتم تنسيقه من الـ Package. |
| **Active Product Base Price Continuity**| **Catalog Package / Composite Rule** | قاعدة منع حذف آخر Base Price لمنتج مفعل. نُقلت كـ Orchestrated Rule لعدم تداخل الدومينات. |
| **Option/Value Activation Safety** | **Catalog Package / Composite Rule** | تفعيل الخيارات أو القيم يستوجب تحقق Pricing Safety، ما يفرض تنسيقًا بين Product و Pricing في طبقة الـ Package. |
| **Restore Safety (Product/Variant)** | **Rewritten inside Base Module / Catalog Package Rule** | الـ Restore Safety الهيكلية (Coverage و Unique constraints) محفوظة داخل Product Base. أما الـ Restore Safety المرتبطة بالتسعير فتم توثيقها بوضوح كـ Catalog Package Rule. |
| **Pricing Safety Triggers (Write-Time)**| **Catalog Package / Composite Rule** | مسؤولية منع السعر النهائي السالب أثناء عمليات الحفظ/الاستعادة للـ Pricing أو الـ Products تقع على الـ Package Coordinator. |
| **Pricing Safety (Final Price >= 0)**| **Rewritten inside Base Module (Pricing)** | أُعيدت صياغتها لتصبح Read-time invariant داخل Service التسعير عند حساب السعر المركب. |
| **Same-Currency Pricing** | **Preserved inside Base Module (Pricing)** | مطبقة بالكامل كقاعدة أساسية لا يجمع تعديل بعملة مختلفة. |
| **Missing Adjustment = 0** | **Preserved inside Base Module (Pricing)** | أي غياب لـ Adjustment في حساب السعر المركب يُعتبر `0`. |
| **Missing Base Price = Not Sellable**| **Preserved inside Base Module (Pricing)** | غياب Base Price يعني أن الـ Subject المعني غير مسعر بهذه العملة. |
| **Inventory 1:1 Identity** | **Rewritten inside Inventory Base / Catalog Package Rule** | تم نقل التنسيق الخاص بالـ Variant 1:1 Lifecycle إلى الـ Catalog Package Coordinator، بينما الـ Inventory Base يحتفظ بالـ Identity المجردة. |
| **Inventory Lifecycle** | **Rewritten inside Inventory Base / Catalog Package Rule** | دورة حياة مخزون الـ Variant (Soft delete, Restore, Replacement) محكومة ومنسقة بالكامل من Catalog Package Coordinator لمنع الحذف العشوائي وتوفير الـ Safety. |
| **Product Display Order Contracts** | **Preserved inside Base Module (Product)** | تم تحديد النطاقات بدقة (Global للـ Products، per-Product، per-Option، الخ) وتأكيد الحتمية بـ `ORDER BY display_order, id`. |
| **Physical DB Contract** | **Preserved (All)** | تم تثبيت Physical DB Contract (`ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`) في جميع الـ Architectures الأساسية. |
| **Pricing Currency Immutability** | **Rewritten inside Base Module (Pricing)** | أصبحت الـ Logical Identity لـ Pricing تتضمن الـ `currency_code` وهي Immutable بالكامل، فتغيير العملة يعني إنشاء سجل جديد. |
| **Variant ↔ Inventory Orchestration** | **Catalog Package / Composite Rule** | منسق الحزمة (Package Coordinator) يضمن أن كل Variant تملك مخزوناً، ويتحكم في إنشاء وحذف المخزون بناءً على استدامة الـ Variant. |
| **Atomic Quantity Increments** | **Rewritten inside Base Module (Inventory)** | تم فرضها كقواعد Generic داخل Inventory باستخدام استعلامات صريحة تضمن `deleted_at IS NULL` وتمنع النزول تحت الصفر (Concurrency safe) مع تحديث `updated_at`، بمعزل تام عن دلالات الـ Orders والـ Reservations. |

## 4. إصلاحات الـ External Reference Identity

لمنع الـ Collisions عند تسعير أو جرد كيانات من مصادر متعددة، تم تطبيق الـ Generic Subject Identity Contract التالي:
* في **Pricing**: `subject_type` (VARCHAR) و `subject_id` (BIGINT UNSIGNED).
* في **Inventory**: `stock_subject_type` (VARCHAR) و `stock_subject_id` (BIGINT UNSIGNED).
* **Canonical Type Contract:** الـ Type يخضع لقواعد صارمة (Canonical lowercase-only machine key) لتحقيق Determinism تام بين التطبيق وقاعدة البيانات:
  * نص لا يتجاوز 100 حرف.
  * يتوافق حصرياً مع النمط: `/^[a-z0-9_.-]+$/`.
  * الـ Whitespace مرفوض تمامًا.
  * لا يجوز استخدام Aliases أو الاعتماد على الـ Collation لتحقيق الـ Case-insensitivity (النمط مفروض كأحرف صغيرة فقط).
  * الـ Type والـ ID ثابتان (Immutable) بعد الإنشاء.

## 5. حالة الـ Unresolved Decisions والـ Architecture Status

القرار الفعلي والنهائي لحالة كل موديول بناءً على اكتمال الـ schema والـ lifecycles:

* **Catalog:** **[Candidate]** الـ Catalog Package كطبقة عليا واضحة، لكن الـ Base Module الداخلي (`Modules/Catalog`) كـ Taxonomy engine لا يحتوي على Entity حقيقية لتمثيل "الكتالوج" كحاوية عليا تملك الـ Categories. ولعدم حسم هذه الـ Entity داخلياً في المصادر الحالية، تُعتبر البنية الداخلية للـ Catalog Module غير مكتملة (Candidate) ولا يُدعى بأنها Locked. بالإضافة إلى ذلك، فإن آلية الـ Persistence لربط الـ Products بالـ Categories (هل هي عبر Generic IDs أم FKs صريحة) لا تزال Unresolved Package Decision.
* **Product:** **[Locked]** Schema كاملة (9 جداول بـ PKs/FKs)، Lifecycles (بما في ذلك Staged Composition، Slug/Barcode) و Restore semantics محددة، ولا توجد افتراضات مخفية.
* **Pricing:** **[Locked]** Generic Identity محددة بدقة لتفادي الـ Collisions (عقد Canonical lowercase-only machine key)، وقواعد الحساب مطبقة داخلياً بمعزل عن الكيانات الأخرى.
* **Inventory:** **[Locked]** Generic Identity محددة بدقة، والعمليات الذرية للحفاظ على الـ bounds واضحة في الـ Queries (بما فيها فحص الـ `deleted_at`)، ولا تعتمد على أي Domains خارجية.

## 6. تأكيد الـ Extraction Test (اختبار الاستخراج المستقل)

اجتازت كل الـ Base Architectures بنجاح اختبار الاستخراج:
* **الاعتمادية المعمارية (Architectural Dependency):** لا يوجد أي Cross-module FK، أو JOIN، أو Derived state يحتاج قراءة جداول من موديول آخر، ولا توجد Transaction Workflow تتخطى حاجز الموديول الداخلي.
* يتم تطبيق الـ Generic IDs دون معرفة مسبقة بأسماء الجداول أو كلاسات الـ Host/Siblings (باستخدام عقود الهويات الصارمة `subject_type` و `subject_id`).
* تم استبدال الادعاءات المطلقة وتوضيح أن كل ذكر للموديولات الأخرى محصور في توضيح الحدود، وأن Integration Definitions أصبحت صراحة تحت تصرف الـ Catalog Package المظلي.
* تم توضيح الفرق في الـ Catalog Base بين الـ Source of Truth (مثل `parent_id`) والـ Derived State (مثل الـ Hierarchy Path).

## 7. الخاتمة
تم إرجاع الـ PDF المونوليثي كمرجع مظلي (Umbrella Architecture) للـ Catalog Package بأكمله، بينما تم تخصيص الـ Markdown files كـ Source of Truth للموديولات الداخلية، لضمان استقلالية كل موديول وإمكانية استخراجه كـ Package منفصل. الحالات المتقاطعة (Cross-Domain States) وقواعد التفعيل المتقاطعة التي أُزيلت من الـ Base Modules صُنفت صراحة كمهام تنسيق في طبقة الـ Package. لا يدعي هذا التقرير إكمال جميع الـ Architectures بنجاح كامل؛ الـ Catalog Base Module لا يزال يحمل حالة `Candidate` لحين حسم الـ Catalog Entity. لا يوجد كود برمجي تم إنتاجه ضمن هذه المهمة.