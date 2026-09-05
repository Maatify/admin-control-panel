# 🗺️ خارطة طريق بناء: Catalog V1 (Roadmap)

تستند خارطة الطريق هذه إلى الوثيقة المعمارية المغلقة `CATALOG_V1_ARCHITECTURE.md` وتتوافق تماماً مع المعايير الأساسية لبناء الوحدات (`MODULE_BUILDING_STANDARD.md` و `MODULE_SLIM_BUILDING_STANDARD.md`).

سيتم تقسيم العمل إلى مسارين رئيسيين:
1. **Core Module (`Modules/Catalog`)**: معزول، لا يعتمد على Host، يتعامل مع PDO مباشرة.
2. **Slim Module (`Modules/CatalogSlim`)**: واجهة لوحة التحكم (Admin UI & API) التي تتفاعل مع الـ Core.

---

## 🚀 المرحلة الأولى: تهيئة هيكل الـ Core Module (Catalog)

- [ ] إنشاء المجلدات الأساسية لـ `Modules/Catalog` (`src/`، `schema/`).
- [ ] إنشاء الملفات الإلزامية: `README.md`، `CHANGELOG.md`، `composer.json`، و `phpstan.neon` (بإعداد `level: max`).
- [ ] تهيئة مساحة الأسماء `Maatify\Catalog\`.
- [ ] إعداد هيكلة طبقات الـ Admin والـ Customer داخل `src/`.

---

## 🗄️ المرحلة الثانية: بناء قواعد البيانات (Schema & Invariants)

- [ ] إنشاء ملف المخطط `schema/catalog_v1.sql` بحيث يضم الجداول الـ 15 المذكورة في الوثيقة المعمارية.
- [ ] **تصنيف الجداول:**
  - `maa_catalog_categories` و `maa_catalog_category_translations`.
  - `maa_catalog_products` و `maa_catalog_product_translations` و `maa_catalog_product_categories`.
  - `maa_catalog_product_options` و `maa_catalog_product_option_translations`.
  - `maa_catalog_product_option_values` و `maa_catalog_product_option_value_translations`.
  - `maa_catalog_variants` و `maa_catalog_variant_option_values`.
  - الجداول المالية: `maa_catalog_product_prices` و `maa_catalog_option_value_price_adjustments`.
  - المخزون والوسائط: `maa_catalog_variant_inventory` و `maa_catalog_media`.
- [ ] تطبيق **Database-Enforced Invariants**:
  - `ON DELETE RESTRICT` و `ON UPDATE RESTRICT` لجميع الـ FKs.
  - تطبيق القيود المنطقية (`CHECK(base_price >= 0)`، `CHECK(quantity_on_hand >= 0)`، الخ).
  - الفهارس (Indexes) الصريحة المحددة لكل جدول.

---

## 🏗️ المرحلة الثالثة: بناء الـ DTOs والـ Repositories

- [ ] **DTOs (Data Transfer Objects):**
  - فصل الـ Admin DTOs (تعرض كافة البيانات حتى المحذوفة/غير النشطة) عن الـ Customer DTOs (تعرض النشط فقط).
  - جميع الحقول المالية (Prices) تكون `string` للتعامل مع الـ `bcmath` وعدم استخدام `float` مطلقًا.
  - استخدام `list<Type>` والمصفوفات المحددة النوع بشكل كامل.
- [ ] **Repositories (PDO):**
  - بناء مستودعات مبنية بالكامل على `PDO` بدون ORM.
  - استخدام `Named Placeholders` فريدة في الاستعلامات.
  - استخدام نمط الـ `Upsert` `ON DUPLICATE KEY UPDATE` للترجمات (`Translations`).
  - تطبيق `LEFT JOIN` لجلب الاسم الأساسي (Base Name) بجانب الترجمة في شاشات الإدارة كما ينص الـ `MODULE_BUILDING_STANDARD`.

---

## ⚙️ المرحلة الرابعة: بناء الـ Services وتطبيق الـ Business Rules

- [ ] **التحقق من Domain/Transaction Invariants (قواعد البزنس 58):**
  - منع الحلقات الدورية في التصنيفات (Category Cycle Prevention).
  - منع الحذف إذا كان هناك أبناء (Child Delete Dependency).
  - منع التداخل بين الـ Options/Values للـ Variants.
  - حماية الأسعار واستخدام دوال الـ `bcmath` للعمليات الحسابية المالية.
  - دورة حياة المخزون (Inventory 1:1 Lifecycle).
- [ ] **إدارة الترتيب والصور (Ordering & Images):**
  - تطبيق منطق الـ `display_order` عبر دوال منفصلة وعدم الاعتماد على الـ Create/Update في تحديثها.
  - تطبيق نفس القاعدة على الصور `updateImage()` وتعيين الـ `NULL` لحذف الصورة.
  - تطبيق منطق `is_primary` للوسائط (Media).
- [ ] **فصل المسؤوليات:**
  - عدم وضع استعلامات SQL داخل الـ Services، بل الاعتماد حصرياً على Repositories و Commands.

---

## 🖼️ المرحلة الخامسة: تهيئة الـ Slim Module (CatalogSlim)

- [ ] إنشاء المجلد `Modules/CatalogSlim/`.
- [ ] إنشاء هيكل الملفات التابع للوحة التحكم (`Admin/Security/`، `Admin/Http/`، `Admin/Domain/`).
- [ ] إنشاء ملف `permissions_seed.sql` لتحديد الصلاحيات المطلوبة للموديول.
- [ ] بناء الكلاسات الخاصة بالـ Security (مثل `PermissionMapProvider`).

---

## 🌐 المرحلة السادسة: طبقة العرض والـ API للـ Slim

- [ ] بناء وحدات التحكم (Controllers) مفصولة بين `API` و `UI`.
- [ ] بناء الـ `Routes` بناءً على المعايير المتبعة.
- [ ] بناء مخططات التحقق (Validation Schemas) للطلبات (مثل `ProductUpdateSchema`).
- [ ] الالتزام الصارم بعدم تكرار أو كتابة Core Logic في الـ Slim، بل الاكتفاء بقراءة البيانات ومعالجتها من الـ Core Module.

---

## 🧪 المرحلة السابعة: المراجعة النهائية والـ Bootstrap

- [ ] كتابة كلاس `CatalogBindings` (و `CatalogSlimBindings`) للـ Dependency Injection (DI) مع تحديد أنواع المتغيرات (Annotations) بوضوح.
- [ ] **PHPStan Max Level:**
  - ضمان خلو الكود من الأخطاء كلياً (0 Errors).
  - جميع متغيرات الاستعلام يجب أن يتم تحديد نوعها وتوضيح الـ Fetch Shape.
  - جميع حالات إرجاع المصفوفات محددة النوع بدقة.
  - استخدام أمر الفحص: `vendor/bin/phpstan analyse Modules/Catalog Modules/CatalogSlim --level=max`
- [ ] مراجعة أن كل الـ Services جاهزة وأنه تم تطبيق كافة القيود المعمارية.
- [ ] تحديث `CHANGELOG.md` لكل موديول بإصدار `[1.0.0]`.
