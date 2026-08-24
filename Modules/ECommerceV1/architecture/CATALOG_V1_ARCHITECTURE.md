دي النسخة المصححة بعد إدخال الملاحظات الأخيرة بالكامل، خصوصًا **Restore Safety، إضافة/إزالة الـOptions مع Immutable Variants، الفصل النهائي بين Stock وPricing، Barcode lifecycle، Logical Identity immutability، والـBase Price invariant**. وهي مبنية على آخر Final Review Candidate.

# Catalog V1 Database Architecture — Locked

**Host-Agnostic Product Catalog Engine**

هذه الوثيقة هي المرجع المعماري المقفول لـ **Catalog V1**.

تحدد نطاق الموديول، النموذج العلائقي، دورة حياة الكيانات، قواعد الهوية، الـVariants، التسعير، المخزون، الوسائط، العلاقات، وقواعد الظهور والاختيار والمخزون والتسعير والبيع.

أي تغيير لاحق على قرار معماري مثبت هنا يعتبر **تغييرًا معماريًا جديدًا** وليس مجرد تفصيل تنفيذ.

---

# 1. الرؤية المعمارية والنطاق

## 1.1 الهدف

`Catalog V1` هو:

**Standalone, Extractable, Host-Agnostic Product Catalog Engine**

ويجب أن يظل قابلًا للاستخراج مستقبلًا كمكتبة مستقلة دون الاعتماد على تفاصيل الـHost.

### Product

هي الهوية المفاهيمية والتجارية للمنتج داخل Catalog.

### Variant

هي الهوية الداخلية المستقرة لوحدة البيع الفعلية.

---

## 1.2 النطاق المشمول

يشمل V1:

* Categories.
* Category Hierarchy.
* Category Translations.
* Products.
* Product Translations.
* Product ↔ Category Relations.
* Product Options.
* Option Values.
* Option/Value Translations.
* Variants.
* Variant Combinations.
* Multi-Currency Product Base Pricing.
* Option Value Price Adjustments.
* Basic Variant Inventory.
* Product Media.
* Variant Media.

---

## 1.3 خارج النطاق صراحة

لا يحتوي V1 ولا يجب توسيع مخططه من أجل دعم:

* Customers.
* Customer Authentication.
* PII.
* Addresses.
* Cart.
* Checkout.
* Orders.
* Payments.
* Shipping.
* Promotions.
* Discounts.
* Warehouses.
* Stock Reservations.
* Inventory Movement History.
* Backorders.
* Unlimited Inventory.
* Analytics.
* Reports.
* Comparisons.
* Reviews.
* Ratings.
* Wishlists.
* Addons.
* Customizations.
* Optional non-variant modifiers.

هذه Domains مستقلة مستقبلًا عند وجود Requirement فعلي لها.

---

# 2. Host-Agnostic Boundaries

Catalog لا يعتمد على جداول الـHost.

لا توجد Foreign Keys أو JOINs من Catalog إلى:

* AdminKernel.
* LanguageCore.
* Currency.
* ExchangeRates.
* Storage.
* أي Module آخر تابع للـHost.

العلاقات بين جداول Catalog نفسها تستخدم Foreign Keys عادية.

---

## 2.1 اللغات

يخزن Catalog:

```text
language_code VARCHAR(16) NOT NULL
```

وهو BCP-47 code.

أمثلة:

```text
en
en-US
ar
ar-EG
```

الـHost مسؤول عن:

* التحقق من صحة `language_code`.
* اللغة المطلوبة.
* Language Fallback Chain.
* Translation Completeness Policy.

Catalog لا يفترض تلقائيًا:

```text
ar-EG → ar → en
```

---

## 2.2 العملات

يخزن Catalog:

```text
currency_code CHAR(3) NOT NULL
```

وفق ISO 4217.

أمثلة:

```text
EGP
USD
QAR
```

Catalog:

* لا يخزن Exchange Rates.
* لا يحول العملات.
* لا يعتمد على Currency table خارجية.

الـHost مسؤول عن التحقق من صحة `currency_code`.

---

# 3. Timestamp Policy

كل Timestamps تدار بواسطة **Catalog Application Layer**.

كل جدول يحتوي:

```text
created_at DATETIME NOT NULL
updated_at DATETIME NOT NULL
deleted_at DATETIME NULL
```

جميع القيم تخزن UTC.

لا يعتمد Catalog على:

```text
DEFAULT CURRENT_TIMESTAMP
ON UPDATE CURRENT_TIMESTAMP
```

كمصدر للحقيقة الزمنية.

الـApplication مسؤولة عن:

* `created_at` عند الإنشاء.
* `updated_at` عند Mutation.
* `deleted_at` عند Soft Delete.
* تحديث `updated_at` عند Soft Delete وRestore.

---

# 4. Soft Delete Policy

كل جداول Catalog تستخدم Soft Delete.

```text
deleted_at IS NULL
```

تعني Record غير محذوفة.

```text
deleted_at IS NOT NULL
```

تعني Record Soft Deleted.

لا يوجد Runtime Hard Delete ضمن العمليات الطبيعية.

---

## 4.1 لا يوجد Cascade Soft Delete

Soft Delete للـParent لا يحذف أبناءه منطقيًا ولا يغير Status لهم.

مثال: Soft Delete لـProduct لا يغير:

```text
variant.deleted_at
option.deleted_at
price.deleted_at
inventory.deleted_at
media.deleted_at
```

ولا يغير Status الأبناء.

Customer Queries تعتبر أبناء Parent المحذوف غير متاحين.

---

# 5. Restore Policy

Soft Delete لا يحرر Logical Identity لإعادة الاستخدام.

إذا كانت الهوية المطلوبة موجودة ولكن Record Soft Deleted:

> يتم Restore للـRecord الأصلية بدل إنشاء Replacement Identity.

ولا تستخدم إضافة `deleted_at` إلى Unique Constraints للتحايل على ذلك.

---

## 5.1 Restore ليس مجرد `deleted_at = NULL`

أي Restore يجب أن يعيد تقييم الـInvariants الحالية.

إذا كانت Record ستصبح فعالة فور Restore بسبب Status أو Flags محفوظة، فلا يجوز إتمام Restore إلا إذا استوفت القواعد الحالية.

### Variant Restore

يجب التحقق من:

* Original Immutable Combination.
* عدم وجود Duplicate Combination.
* Inventory Identity.
* Option/Value compatibility.
* Effective Selectability إن كانت Status = active.
* Default Variant rules.
* Pricing Safety.

### Product Restore

إذا كان Status المحفوظ:

```text
active
```

فيجب إعادة التحقق من:

* Product Activation Completeness.
* وجود Active valid Variant.
* وجود Base Price.
* Pricing Safety.

### Option / Option Value Restore

إذا كانت Status المحفوظة `active`:

* Full Coverage.
* Variant Selectability.
* Default Variant integrity.
* Pricing Safety.

إذا كانت الـInvariants غير متحققة:

> يتم رفض Restore بهذه الحالة.

ولا يتم تعديل الهوية أو الـCombination تلقائيًا لحل المشكلة.

---

# 6. Logical Identity Immutability

الأعمدة التي تحدد **Logical Identity** لسجل لا تتغير بعد الإنشاء.

تشمل:

```text
category_translation:
(category_id, language_code)

product_translation:
(product_id, language_code)

product_category:
(product_id, category_id)

product_option:
product_id

option_translation:
(option_id, language_code)

option_value:
option_id

option_value_translation:
(option_value_id, language_code)

variant:
product_id

variant_option_value:
variant_id + option_id + option_value_id

product_price:
(product_id, currency_code)

price_adjustment:
(option_value_id, currency_code)

variant_inventory:
variant_id
```

تغيير معنى الهوية يستلزم استخدام Record أخرى أو Create/Restore للهوية الصحيحة.

### استثناءات مقصودة

```text
category.parent_id
```

قابلة للتغيير لدعم نقل Category.

```text
product.slug
```

قابلة للتغيير وفق Slug Policy.

الحقول الوصفية والحالة والترتيب والأسعار والكميات قابلة للتعديل وفق قواعد الـDomain.

---

# 7. Stable Codes

الآتي Stable وImmutable ولا يعاد استخدامه بعد Soft Delete:

```text
Category code
Product code
Option code داخل Product
Option Value code داخل Option
SKU
```

---

# 8. Product Slug Lifecycle

`product.code` هي الهوية الثابتة للProduct.

أما:

```text
slug
```

فهي:

> Current Canonical Routing Value

وتكون:

* Globally Unique حاليًا.
* Mutable.
* ليست Product Identity.

إذا تغير:

```text
old-slug → new-slug
```

فلا يحتفظ Catalog V1 بتاريخ `old-slug`.

Slug History وRedirects خارج V1.

إذا كانت Product Soft Deleted ولا تزال تحمل Slug معينة، يظل الـUnique Constraint حاجزًا لها.

---

# 9. Barcode Lifecycle

`SKU` هي Stable Immutable Sellable Identity.

أما:

```text
barcode
```

فهي:

> Nullable Current Unique Commercial Attribute

وتكون:

* Nullable.
* Unique when present.
* Mutable.
* ليست Historical Identity.

طالما Variant الحالية أو المحذوفة ما زالت تحمل Barcode معينة، يظل الـUnique Constraint حاجزًا لها.

إذا تم تغيير Barcode:

```text
123 → 456
```

فلا يحتفظ Catalog V1 بتاريخ `123`.

Barcode History أو Permanent Reservation خارج نطاق V1.

---

# 10. Foreign Key Policy

كل Foreign Keys الداخلية تستخدم:

```text
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

لمنع:

* Cascade Hard Delete.
* تغيير Primary Identity.
* Mutation غير صريحة للعلاقات.

---

# 11. Status Model

لا يستخدم Catalog:

```text
is_active
```

المصدر الوحيد للحالة:

```text
status VARCHAR(20) NOT NULL
```

القيم:

```text
active
inactive
```

مع:

```text
CHECK (status IN ('active','inactive'))
```

وفي PHP يمثل بواسطة Enum اسمه ينتهي بـ:

```text
CatalogStatusEnum
```

Status مختلفة تمامًا عن Soft Delete.

---

# 12. Display Order

كل Collection ذات ترتيب Business-Controlled تستخدم:

```text
display_order INT NOT NULL DEFAULT 0
```

Scopes:

| الكيان             | Scope              |
| ------------------ | ------------------ |
| Categories         | داخل `parent_id`   |
| Products           | Global             |
| Product Categories | داخل `category_id` |
| Product Options    | داخل `product_id`  |
| Option Values      | داخل `option_id`   |
| Variants           | داخل `product_id`  |
| Product Media      | داخل `product_id`  |
| Variant Media      | داخل `variant_id`  |

`display_order` ليست Unique.

الترتيب الحتمي:

```text
ORDER BY display_order, id
```

---

# 13. فصل الحالات التشغيلية

يجب عدم الخلط بين:

```text
Visibility
Selection
Stock State
Pricing Availability
Currency Sellability
Deletion
```

كل واحدة مشتقة بقواعد مستقلة.

---

# 14. Category Visibility

Category ذات:

```text
status = inactive
```

لا تظهر للمستهلك.

ولا يتغير Status الأبناء.

إذا كان Parent inactive، فالـDescendants لا تظهر عبر هذا المسار.

---

## 14.1 Product في أكثر من Category

إذا كانت Product مرتبطة بـ:

```text
Category A = inactive
Category B = active
```

تظل Product قابلة للظهور عبر B.

تعطيل Category يعطل Classification Path فقط.

---

# 15. Product Visibility

```text
product.status = inactive
```

تعني Product مخفية عالميًا عن Customer Consumption.

ولا يتم تعديل:

* Variants.
* Options.
* Inventory.
* Prices.
* Media.

```text
product.deleted_at IS NOT NULL
```

تعني Product غير متاحة بغض النظر عن Status.

---

# 16. Force Out of Stock

على Product:

```text
force_out_of_stock TINYINT(1) NOT NULL DEFAULT 0
```

مع:

```text
CHECK (force_out_of_stock IN (0,1))
```

عندما تكون:

```text
1
```

تظل Product ظاهرة إن سمحت Visibility، لكن Stock State يصبح:

```text
Out of Stock
```

ولا يتم تعديل Inventory.

لا يوجد Variant-level override في V1.

---

# 17. Product Lifecycle

Product الجديدة تبدأ:

```text
status = 'inactive'
```

ويجوز أن تكون أثناء التجهيز بلا:

* Variants.
* Base Prices.
* Options مكتملة.

---

# 18. Variant Identity

Variant هي:

> Stable Sellable Identity

وتشمل:

* `variant_id`.
* SKU.
* Product Ownership.
* Immutable Option Combination.
* Inventory Identity.
* Variant Media association.

---

# 19. Variant Creation

Variant الجديدة تبدأ:

```text
status = 'inactive'
```

ويجوز إنشاؤها Transactionally مع:

```text
Create Variant
Create Inventory Row
Create Composition Rows
```

ولا تصبح Active قبل Structural Validation.

---

# 20. Structural Validity

Variant تكون Structurally Valid عندما:

* Product ownership صحيح.
* Inventory row موجودة.
* لا يوجد Duplicate Combination.
* كل Option Value تابعة للـOption الصحيحة.
* Option وVariant من نفس Product.
* Composition غير متعارضة.
* Composition مكتملة بالنسبة إلى Option model المستهدف.

ولا تعتمد Structural Validity على:

* Stock > 0.
* Base Price.
* Visibility.

---

# 21. Variant Activation

لا يجوز:

```text
variant.status = active
```

إلا إذا كانت Variant:

* Non-deleted.
* Structurally Valid.
* لها Inventory row واحدة.
* Combination غير مكررة.
* تستوفي Full Active Option Coverage.
* تستوفي Pricing Safety لكل Currency لها Base Price موجودة.

Base Price ليست شرطًا لصحة Variant نفسها.

---

# 22. Immutable Variant Composition

بعد Commit إنشاء Variant:

> Composition تصبح Immutable.

مثال:

```text
PANTS-XL-BLACK
XL + Black
```

لا يجوز تحويل نفس Variant إلى:

```text
L + White
```

التغيير يتطلب:

```text
Deactivate / Soft Delete old Variant
Create new Variant
New SKU
```

---

# 23. Variant Composition Rows

Records داخل:

```text
maa_catalog_variant_option_values
```

جزء من هوية الـVariant.

بعد Commit إنشاء Variant لا يسمح Normal Runtime بـ:

* إضافة Composition row.
* حذف Composition row.
* تغيير Option.
* تغيير Option Value.
* Soft Delete مستقل.
* Restore مستقل.

Soft Delete للVariant لا يغير Composition rows.

Restore يعيد نفس Composition الأصلية.

---

# 24. Product Options

كل Product Option في V1 هي:

> Variant-Defining Option.

لا يوجد:

```text
is_required
```

كل Option:

```text
status = active
AND deleted_at IS NULL
```

هي Option مطلوبة لتكوين Active Variant.

---

# 25. Full Active Option Coverage

كل Active Variant في Configurable Product يجب أن تحتوي:

> Exactly one Value لكل Active, non-deleted Option للProduct.

ولا يجوز أكثر من Value لنفس Option.

---

# 26. Option Lifecycle

Option الجديدة تبدأ:

```text
status = 'inactive'
```

حتى يمكن تجهيز Values والReplacement Variants دون التأثير على المنتج الحالي.

---

# 27. إضافة Variant-Defining Option جديدة

إذا كانت Product لديها Variants قائمة وأصبح مطلوبًا إضافة Option جديدة:

> لا يتم تعديل Composition للـVariants القديمة.

يستخدم Replacement Flow:

```text
1. Create new Option as inactive.

2. Create Option Values.

3. Create replacement Variants as inactive.

4. Give every replacement Variant:
   - new SKU
   - full new Combination
   - Inventory row

5. Validate replacements.

6. Atomic Cutover:
   - deactivate old Variants
   - activate new Option
   - activate replacement Variants

7. Preserve old Variant identities unchanged.
```

---

# 28. إزالة Variant-Defining Option

إذا كان المطلوب إزالة Option من Product مع استمرار بيع Product بدونها:

> لا يتم حذفها من Combinations القديمة.

يستخدم Replacement Flow معاكس:

```text
1. Prepare replacement Variants without the Option.

2. Assign new SKUs.

3. Create mandatory Inventory rows.

4. Validate replacements.

5. Atomic Cutover:
   - deactivate old Variants
   - deactivate removed Option
   - activate replacement Variants
```

الـVariants القديمة تحتفظ بهويتها وComposition الأصلية.

### Deactivate فقط بدون Replacement

مسموح Deactivate للـOption دون Replacement.

لكن أي Variant تحتوي هذه الـOption تصبح **غير Effectively Selectable**.

وقد يؤدي ذلك إلى Product Out of Stock.

---

# 29. Staged Composition

Variant جديدة Inactive يمكن أثناء التجهيز أن تحتوي Composition تشير إلى Option ما زالت Inactive.

هذا مسموح فقط للStaging.

لا يمكن تفعيل Variant إلا عندما تصبح جميع الـOptions والValues داخل Composition صالحة وفق النموذج النهائي في نفس Atomic Operation.

---

# 30. Option Value Status

```text
option_value.status = active
```

تعني:

> Administratively Enabled.

ولا تعني أنها Customer-selectable تلقائيًا.

Customer-selectable Values تشتق من الـEffectively Selectable Variants الفعلية.

---

# 31. Effectively Selectable Variant

Variant تعتبر **Effectively Selectable** فقط إذا:

```text
variant.status = active
variant.deleted_at IS NULL
```

وكل Composition Row داخل Variant تحقق:

```text
option.status = active
option.deleted_at IS NULL

option_value.status = active
option_value.deleted_at IS NULL
```

وكذلك:

> يوجد Exactly one Value لكل Active, non-deleted Option الخاصة بالProduct.

وبالتالي إذا تم Deactivate لـOption موجودة في Combination:

> كل Variant تعتمد عليها تصبح غير Effectively Selectable.

ولا يتم تجاهل الـOption وكأنها لم تكن جزءًا من هوية الـVariant.

Stock لا يدخل في تعريف Selectability.

---

# 32. Simple Product

Simple Product:

```text
No Active Product Options
+
Exactly one Effectively Selectable Variant
```

ولا يحتاج العميل Customer Option Selection.

---

# 33. Direct Sellable Resolution

يعني:

> Catalog يستطيع Resolve لـvariant_id واحدة دون Customer Selection.

الشرط:

```text
No Active Product Options
AND
Exactly one Effectively Selectable Variant
```

Stock لا يدخل في Direct Resolution.

Variant ذات مخزون صفر يمكن أن تكون:

```text
Directly Resolvable
```

لكن ليست:

```text
In Stock
```

---

# 34. Configurable Product

Configurable Product لديها Active Options.

فقط Combinations الموجودة كـVariants فعلية تعتبر موجودة.

لا يشترط إنشاء كل Cartesian combinations.

---

# 35. In-Stock Variant

Variant تعتبر:

```text
In Stock
```

إذا:

```text
Effectively Selectable
AND quantity_on_hand > 0
```

Pricing لا تدخل في تعريف In Stock.

---

# 36. Product Stock State

Product تكون Out of Stock إذا:

```text
force_out_of_stock = 1
```

أو:

> لا توجد In-Stock Variants.

Product تكون In Stock إذا:

```text
force_out_of_stock = 0
```

وتوجد Variant واحدة على الأقل:

```text
Effectively Selectable
AND quantity_on_hand > 0
```

**وجود أو غياب Base Price لا يغير Stock State.**

---

# 37. `is_default`

على Variant:

```text
is_default TINYINT(1) NOT NULL DEFAULT 0
```

مع:

```text
CHECK (is_default IN (0,1))
```

معناه:

> Optional Preferred / Initial Selection Hint.

ولا يعني:

* Sellability.
* Fallback.
* Customer Consent.
* Simple Product.
* Direct Resolution.

القيد:

> صفر أو واحدة Default Variant غير محذوفة لكل Product.

---

## 37.1 صلاحية Default Variant

Default Variant يجب أن تكون:

* Non-deleted.
* Active.
* Structurally Valid.
* Effectively Selectable.

لا يشترط Stock > 0.

إذا فقدت صلاحيتها للاختيار:

> يتم إزالة Default أو نقلها إلى Variant أخرى داخل نفس Transaction.

---

# 38. Pricing Model

السعر النهائي لا يخزن.

```text
Final Price
=
Product Base Price
+
SUM(Selected Option Value Price Adjustments)
```

---

# 39. Product Base Price

يوجد Base Price لكل:

```text
Product + Currency
```

---

# 40. Option Value Price Adjustment

يوجد Adjustment اختياري لكل:

```text
Option Value + Currency
```

وهو Signed:

```text
positive
zero
negative
```

---

# 41. Currency Consistency

كل حساب يتم داخل نفس Currency.

```text
EGP Base + EGP Adjustments
```

مسموح.

```text
EGP Base + USD Adjustment
```

ممنوع.

---

# 42. Missing Pricing Data

Missing Adjustment لنفس Currency:

```text
Adjustment = 0
```

Missing Base Price:

```text
Not Sellable In This Currency
```

ولا يعني Out of Stock.

---

# 43. Final Price Invariant

لكل Combination قابلة للاختيار ولكل Currency لديها Base Price:

```text
Final Price >= 0
```

---

# 44. Pricing Safety Triggers

يجب إجراء Pricing Safety Validation عند أي Mutation قد تغير Final Price أو تجعل Combination قابلة للاختيار.

يشمل:

```text
Create Base Price
Update Base Price
Restore Base Price

Create Adjustment
Update Adjustment
Soft Delete Adjustment
Restore Adjustment

Activate Variant
Restore Variant

Activate Option
Restore Option

Activate Option Value
Restore Option Value

Activate Product
Restore Product
```

لكل Base Price موجودة:

```text
Final Price لكل Combination متأثرة >= 0
```

إذا أصبحت أي Combination سالبة:

> ترفض العملية بالكامل.

---

# 45. Soft Delete لـBase Price

Soft Delete لـBase Price يجعل Product:

```text
Not Sellable In This Currency
```

ولا يؤثر على Stock State.

لكن توجد قاعدة إضافية:

> Product ذات `status = active` يجب أن تظل لديها Base Price واحدة غير محذوفة على الأقل.

لذلك:

```text
Soft Delete last remaining Base Price
```

ممنوع طالما Product Active.

إذا أرادت الإدارة إزالة كل الأسعار:

```text
Deactivate Product first
Then remove remaining prices
```

---

# 46. Product Activation Completeness

لا يجوز تحويل Product إلى:

```text
active
```

إلا إذا كانت لديها:

1. Active non-deleted Structurally Valid Variant واحدة على الأقل.

2. Mandatory Inventory row لهذه Variant.

3. Base Price واحدة غير محذوفة على الأقل.

4. Pricing Safety متحقق.

لا يشترط Stock > 0.

Category assignment ليست شرطًا للتفعيل.

---

# 47. Active Product Continuity Invariant

القواعد المطلوبة عند Activation لا تنطبق لحظة التفعيل فقط.

طالما Product:

```text
status = active
```

يجب الحفاظ على:

> Base Price واحدة غير محذوفة على الأقل.

أما فقد المخزون فلا يجبر Product على Deactivate؛ تصبح فقط Out of Stock.

---

# 48. Inventory Model

كل Variant غير محذوفة لها Inventory row واحدة بالضبط.

عند إنشاء Variant:

```text
quantity_on_hand = 0
```

وتنشأ Inventory داخل نفس Transaction.

---

## 48.1 Inventory Rules

```text
quantity_on_hand >= 0
```

لا يوجد في V1:

* Negative Stock.
* Backorders.
* Warehouses.
* Reservations.
* Movement Ledger.
* Unlimited Inventory.

---

## 48.2 Inventory Lifecycle

لا يجوز Soft Delete لـInventory مستقلة بينما Variant غير محذوفة.

Soft Delete للVariant لا يحذف Inventory.

Restore للVariant يعيد استخدام نفس Inventory identity.

---

# 49. Category Hierarchy

Root:

```text
parent_id = NULL
```

DB تمنع:

```text
parent_id = id
```

عن طريق:

```text
CHECK (parent_id IS NULL OR parent_id <> id)
```

Domain تمنع:

```text
A → B → C → A
```

---

## 49.1 Category Delete Dependency

لا يجوز Soft Delete لـCategory لديها Child Categories غير محذوفة.

---

# 50. Option / Option Value Delete Rules

لا يجوز Soft Delete لـOption أو Option Value مستخدمة في Variant غير محذوفة.

يجب معالجة الاعتماد أولًا.

Deactivate مسموح وفق قواعد الـAvailability والReplacement Flow.

---

# 51. Variant Combination Integrity

في:

```text
maa_catalog_variant_option_values
```

يجب:

```text
option_value.option_id = option_id
```

وكذلك:

```text
option.product_id = variant.product_id
```

Cross-Product Composition ممنوعة.

---

# 52. Duplicate Variant Combinations

لا يجوز وجود Variantين غير محذوفتين لنفس Product بنفس Combination.

حتى لو SKUs مختلفة.

لا يستخدم V1:

```text
options_signature
```

أو Hash كمصدر حقيقة.

التحقق Transactionally.

Restore يخضع لنفس القاعدة.

---

# 53. Media Model

Catalog يخزن Reference وMetadata فقط.

لا يخزن Binary content.

الـHost مسؤول عن Processing وStorage.

---

## 53.1 Media Ownership

إذا:

```text
variant_id IS NULL
```

فهي Product Media.

إذا:

```text
variant_id IS NOT NULL
```

فهي Variant Media.

ويجب:

```text
variant.product_id = media.product_id
```

---

# 54. Primary Media

بحد أقصى:

* Primary Product Media واحدة غير محذوفة لكل Product.
* Primary Variant Media واحدة غير محذوفة لكل Variant.

Restore لأي Primary Media يجب أن يحل أي Conflict موجود داخل نفس Transaction.

---

# 55. المواصفات الفيزيائية العامة

كل الجداول:

```text
ENGINE = InnoDB
CHARSET = utf8mb4
```

Collation تتبع Canonical Collation المعتمدة بالمشروع.

كل PK:

```text
BIGINT UNSIGNED AUTO_INCREMENT
```

كل FK:

```text
BIGINT UNSIGNED
```

مع:

```text
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

---

# 56. Complete Database Schema — 15 Tables

## 56.1 `maa_catalog_categories`

| العمود          | النوع                                   |
| --------------- | --------------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT`        |
| `parent_id`     | `BIGINT UNSIGNED NULL`                  |
| `code`          | `VARCHAR(100) NOT NULL`                 |
| `status`        | `VARCHAR(20) NOT NULL DEFAULT 'active'` |
| `display_order` | `INT NOT NULL DEFAULT 0`                |
| `created_at`    | `DATETIME NOT NULL`                     |
| `updated_at`    | `DATETIME NOT NULL`                     |
| `deleted_at`    | `DATETIME NULL`                         |

Constraints:

```text
PRIMARY KEY(id)
UNIQUE(code)
CHECK(status IN ('active','inactive'))
CHECK(parent_id IS NULL OR parent_id <> id)
```

FK:

```text
parent_id → maa_catalog_categories.id
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

---

## 56.2 `maa_catalog_category_translations`

| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `category_id`   | `BIGINT UNSIGNED NOT NULL`       |
| `language_code` | `VARCHAR(16) NOT NULL`           |
| `name`          | `VARCHAR(255) NOT NULL`          |
| `description`   | `TEXT NULL`                      |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(category_id, language_code)
```

---

## 56.3 `maa_catalog_products`

| العمود               | النوع                                     |
| -------------------- | ----------------------------------------- |
| `id`                 | `BIGINT UNSIGNED AUTO_INCREMENT`          |
| `code`               | `VARCHAR(100) NOT NULL`                   |
| `slug`               | `VARCHAR(255) NOT NULL`                   |
| `status`             | `VARCHAR(20) NOT NULL DEFAULT 'inactive'` |
| `force_out_of_stock` | `TINYINT(1) NOT NULL DEFAULT 0`           |
| `display_order`      | `INT NOT NULL DEFAULT 0`                  |
| `created_at`         | `DATETIME NOT NULL`                       |
| `updated_at`         | `DATETIME NOT NULL`                       |
| `deleted_at`         | `DATETIME NULL`                           |

```text
PRIMARY KEY(id)
UNIQUE(code)
UNIQUE(slug)
CHECK(status IN ('active','inactive'))
CHECK(force_out_of_stock IN (0,1))
```

---

## 56.4 `maa_catalog_product_translations`

| العمود              | النوع                            |
| ------------------- | -------------------------------- |
| `id`                | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `product_id`        | `BIGINT UNSIGNED NOT NULL`       |
| `language_code`     | `VARCHAR(16) NOT NULL`           |
| `name`              | `VARCHAR(255) NOT NULL`          |
| `short_description` | `VARCHAR(500) NULL`              |
| `description`       | `TEXT NULL`                      |
| `created_at`        | `DATETIME NOT NULL`              |
| `updated_at`        | `DATETIME NOT NULL`              |
| `deleted_at`        | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(product_id, language_code)
```

---

## 56.5 `maa_catalog_product_categories`

| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `product_id`    | `BIGINT UNSIGNED NOT NULL`       |
| `category_id`   | `BIGINT UNSIGNED NOT NULL`       |
| `display_order` | `INT NOT NULL DEFAULT 0`         |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(product_id, category_id)
```

---

## 56.6 `maa_catalog_product_options`

| العمود          | النوع                                     |
| --------------- | ----------------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT`          |
| `product_id`    | `BIGINT UNSIGNED NOT NULL`                |
| `code`          | `VARCHAR(100) NOT NULL`                   |
| `status`        | `VARCHAR(20) NOT NULL DEFAULT 'inactive'` |
| `display_order` | `INT NOT NULL DEFAULT 0`                  |
| `created_at`    | `DATETIME NOT NULL`                       |
| `updated_at`    | `DATETIME NOT NULL`                       |
| `deleted_at`    | `DATETIME NULL`                           |

```text
PRIMARY KEY(id)
UNIQUE(product_id, code)
CHECK(status IN ('active','inactive'))
```

---

## 56.7 `maa_catalog_product_option_translations`

| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `option_id`     | `BIGINT UNSIGNED NOT NULL`       |
| `language_code` | `VARCHAR(16) NOT NULL`           |
| `name`          | `VARCHAR(255) NOT NULL`          |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(option_id, language_code)
```

---

## 56.8 `maa_catalog_product_option_values`

| العمود          | النوع                                   |
| --------------- | --------------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT`        |
| `option_id`     | `BIGINT UNSIGNED NOT NULL`              |
| `code`          | `VARCHAR(100) NOT NULL`                 |
| `status`        | `VARCHAR(20) NOT NULL DEFAULT 'active'` |
| `display_order` | `INT NOT NULL DEFAULT 0`                |
| `created_at`    | `DATETIME NOT NULL`                     |
| `updated_at`    | `DATETIME NOT NULL`                     |
| `deleted_at`    | `DATETIME NULL`                         |

```text
PRIMARY KEY(id)
UNIQUE(option_id, code)
CHECK(status IN ('active','inactive'))
```

---

## 56.9 `maa_catalog_product_option_value_translations`

| العمود            | النوع                            |
| ----------------- | -------------------------------- |
| `id`              | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `option_value_id` | `BIGINT UNSIGNED NOT NULL`       |
| `language_code`   | `VARCHAR(16) NOT NULL`           |
| `name`            | `VARCHAR(255) NOT NULL`          |
| `created_at`      | `DATETIME NOT NULL`              |
| `updated_at`      | `DATETIME NOT NULL`              |
| `deleted_at`      | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(option_value_id, language_code)
```

---

## 56.10 `maa_catalog_variants`

| العمود          | النوع                                     |
| --------------- | ----------------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT`          |
| `product_id`    | `BIGINT UNSIGNED NOT NULL`                |
| `sku`           | `VARCHAR(100) NOT NULL`                   |
| `barcode`       | `VARCHAR(100) NULL`                       |
| `is_default`    | `TINYINT(1) NOT NULL DEFAULT 0`           |
| `status`        | `VARCHAR(20) NOT NULL DEFAULT 'inactive'` |
| `display_order` | `INT NOT NULL DEFAULT 0`                  |
| `created_at`    | `DATETIME NOT NULL`                       |
| `updated_at`    | `DATETIME NOT NULL`                       |
| `deleted_at`    | `DATETIME NULL`                           |

```text
PRIMARY KEY(id)
UNIQUE(sku)
UNIQUE(barcode)
CHECK(is_default IN (0,1))
CHECK(status IN ('active','inactive'))
```

---

## 56.11 `maa_catalog_variant_option_values`

| العمود            | النوع                            |
| ----------------- | -------------------------------- |
| `id`              | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `variant_id`      | `BIGINT UNSIGNED NOT NULL`       |
| `option_id`       | `BIGINT UNSIGNED NOT NULL`       |
| `option_value_id` | `BIGINT UNSIGNED NOT NULL`       |
| `created_at`      | `DATETIME NOT NULL`              |
| `updated_at`      | `DATETIME NOT NULL`              |
| `deleted_at`      | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(variant_id, option_id)
```

Composition Immutable after Variant creation.

---

## 56.12 `maa_catalog_product_prices`

| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `product_id`    | `BIGINT UNSIGNED NOT NULL`       |
| `currency_code` | `CHAR(3) NOT NULL`               |
| `base_price`    | `DECIMAL(20,6) NOT NULL`         |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(product_id, currency_code)
CHECK(base_price >= 0)
```

---

## 56.13 `maa_catalog_option_value_price_adjustments`

| العمود             | النوع                            |
| ------------------ | -------------------------------- |
| `id`               | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `option_value_id`  | `BIGINT UNSIGNED NOT NULL`       |
| `currency_code`    | `CHAR(3) NOT NULL`               |
| `price_adjustment` | `DECIMAL(20,6) NOT NULL`         |
| `created_at`       | `DATETIME NOT NULL`              |
| `updated_at`       | `DATETIME NOT NULL`              |
| `deleted_at`       | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(option_value_id, currency_code)
```

---

## 56.14 `maa_catalog_variant_inventory`

| العمود             | النوع                            |
| ------------------ | -------------------------------- |
| `id`               | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `variant_id`       | `BIGINT UNSIGNED NOT NULL`       |
| `quantity_on_hand` | `INT NOT NULL DEFAULT 0`         |
| `created_at`       | `DATETIME NOT NULL`              |
| `updated_at`       | `DATETIME NOT NULL`              |
| `deleted_at`       | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
UNIQUE(variant_id)
CHECK(quantity_on_hand >= 0)
```

---

## 56.15 `maa_catalog_media`

| العمود          | النوع                            |
| --------------- | -------------------------------- |
| `id`            | `BIGINT UNSIGNED AUTO_INCREMENT` |
| `product_id`    | `BIGINT UNSIGNED NOT NULL`       |
| `variant_id`    | `BIGINT UNSIGNED NULL`           |
| `relative_path` | `VARCHAR(500) NOT NULL`          |
| `mime_type`     | `VARCHAR(100) NULL`              |
| `size_bytes`    | `BIGINT UNSIGNED NULL`           |
| `width`         | `INT UNSIGNED NULL`              |
| `height`        | `INT UNSIGNED NULL`              |
| `is_primary`    | `TINYINT(1) NOT NULL DEFAULT 0`  |
| `display_order` | `INT NOT NULL DEFAULT 0`         |
| `created_at`    | `DATETIME NOT NULL`              |
| `updated_at`    | `DATETIME NOT NULL`              |
| `deleted_at`    | `DATETIME NULL`                  |

```text
PRIMARY KEY(id)
CHECK(is_primary IN (0,1))
```

---

# 57. Database-Enforced Invariants

قاعدة البيانات تفرض:

* Primary Keys.
* Internal FKs.
* `ON DELETE RESTRICT`.
* `ON UPDATE RESTRICT`.
* Unique Stable Codes.
* Unique Current Slug.
* Unique SKU.
* Unique Current Barcode.
* Unique Translation Identities.
* Unique Product/Category Relation.
* Unique Option Code per Product.
* Unique Value Code per Option.
* Unique `(variant_id, option_id)`.
* Unique Product/Currency Price.
* Unique OptionValue/Currency Adjustment.
* Unique Inventory per Variant.
* `base_price >= 0`.
* `quantity_on_hand >= 0`.
* Status constraints.
* Boolean constraints.
* `parent_id <> id`.

---

# 58. Domain / Transaction-Enforced Invariants

يجب فرض:

1. Category Cycle Prevention.
2. Category Child Delete Dependency.
3. Option/Value Delete Dependency.
4. Option/Value Integrity.
5. Cross-Product Composition Prevention.
6. Duplicate Variant Combination Prevention.
7. Immutable Variant Composition.
8. Immutable Logical Identity Columns.
9. Full Active Option Coverage.
10. Effectively Selectable Composition rules.
11. Variant Activation Validation.
12. Replacement Flow عند إضافة Option.
13. Replacement Flow عند إزالة Option.
14. Product Activation Completeness.
15. Active Product must retain at least one Base Price.
16. Option Activation Safety.
17. Option Value Activation Safety.
18. Restore Safety.
19. Pricing Safety.
20. Max one valid Default Variant.
21. Default Variant Conflict Handling.
22. Inventory 1:1.
23. Inventory Lifecycle.
24. Media Ownership.
25. Primary Media Limits.
26. Primary Restore Conflict Handling.
27. Same-Currency Pricing.
28. Final Price `>= 0`.

---

# 59. ER / Cardinality Summary

```text
Category 1 : N Category
```

```text
Product N : N Category
```

عبر:

```text
maa_catalog_product_categories
```

```text
Product 1 : N Product Translations
Product 1 : N Options
Product 1 : N Variants
Product 1 : N Base Prices
Product 1 : N Media

Option 1 : N Option Translations
Option 1 : N Option Values

Option Value 1 : N Option Value Translations
Option Value 1 : N Price Adjustments

Variant N : N Option Values
```

عبر:

```text
maa_catalog_variant_option_values
```

```text
Variant 1 : 1 Inventory
Variant 1 : N Media
```

---

# 60. Index Strategy

الـIndexes تكون Explicit.

## Categories

```text
maa_catalog_categories
(parent_id, status, deleted_at, display_order, id)
```

## Global Products

```text
maa_catalog_products
(status, deleted_at, display_order, id)
```

## Category Products

```text
maa_catalog_product_categories
(category_id, deleted_at, display_order, product_id)
```

## Product Options

```text
maa_catalog_product_options
(product_id, status, deleted_at, display_order, id)
```

## Option Values

```text
maa_catalog_product_option_values
(option_id, status, deleted_at, display_order, id)
```

## Variants

```text
maa_catalog_variants
(product_id, status, deleted_at, display_order, id)
```

## Variant Composition

الـUnique:

```text
(variant_id, option_id)
```

ويضاف:

```text
(option_value_id, deleted_at, variant_id)
```

ويضاف أيضًا:

```text
(option_id, deleted_at, variant_id)
```

حتى لا نعتمد على Auto-FK index في Option-first dependency lookup.

## Pricing

تكفي:

```text
UNIQUE(product_id, currency_code)
```

و:

```text
UNIQUE(option_value_id, currency_code)
```

## Inventory

```text
UNIQUE(variant_id)
```

## Product Media

```text
(product_id, variant_id, deleted_at, is_primary, display_order, id)
```

## Variant Media

```text
(variant_id, deleted_at, is_primary, display_order, id)
```

---

# 61. Translation Lookup

تكفي الـUnique indexes:

```text
(category_id, language_code)
(product_id, language_code)
(option_id, language_code)
(option_value_id, language_code)
```

ولا تضاف Redundant Indexes بلا Query Requirement.

---

# 62. Stock State Precedence

```text
1. Product deleted
   → خارج Customer Consumption.

2. Product inactive
   → غير ظاهرة.

3. Category Path inactive
   → غير ظاهرة من هذا المسار.

4. force_out_of_stock = 1
   → Visible + Out of Stock.

5. لا توجد In-Stock Variant
   → Visible + Out of Stock.

6. توجد In-Stock Variant
   → Product In Stock.
```

Pricing لا تدخل في Stock State.

---

# 63. Currency Sellability

بعد تحديد Stock State، يتم تقييم العملة:

```text
1. Missing Base Price
   → Not Sellable In This Currency.

2. Base Price موجودة.
   → Calculate Final Price.

3. Missing Adjustment
   → Adjustment = 0.

4. Final Price < 0
   → Invalid State / Operation rejected.

5. Final Price >= 0
   → Sellable In This Currency.
```

---

# 64. Sources of Truth

لا يجوز إضافة مصادر حقيقة مكررة مثل:

```text
variant.final_price
product.stock
is_out_of_stock
options_signature
option_value.is_selectable
```

مصادر الحقيقة:

```text
Product Base Prices
Option Value Price Adjustments
Variant Inventory
Variant Composition
Entity Status
deleted_at
force_out_of_stock
```

الحالات المشتقة:

```text
Final Price
Effectively Selectable Variant
In-Stock Variant
Product Stock State
Direct Sellable Resolution
Customer-selectable Option Values
Sellable In Currency
```

---

# 65. الجداول المقفولة

Catalog V1 يتكون من **15 جدولًا**:

```text
1.  maa_catalog_categories
2.  maa_catalog_category_translations
3.  maa_catalog_products
4.  maa_catalog_product_translations
5.  maa_catalog_product_categories
6.  maa_catalog_product_options
7.  maa_catalog_product_option_translations
8.  maa_catalog_product_option_values
9.  maa_catalog_product_option_value_translations
10. maa_catalog_variants
11. maa_catalog_variant_option_values
12. maa_catalog_product_prices
13. maa_catalog_option_value_price_adjustments
14. maa_catalog_variant_inventory
15. maa_catalog_media
```

---

# 66. الحالة المعمارية

القرارات المقفولة تشمل:

* Host-Agnostic Boundaries.
* UTC Application-managed Timestamps.
* Soft Delete.
* Restore Safety.
* Stable Identity.
* Logical Identity Immutability.
* Mutable Product Slug.
* Mutable Current Barcode.
* Status Model.
* Display Ordering.
* Category Hierarchy.
* Visibility.
* Selectability.
* Stock State.
* Currency Sellability.
* Product Lifecycle.
* Stable Variant Identity.
* Immutable Variant Composition.
* Addition/Removal Replacement Flows.
* Simple Products.
* Configurable Products.
* Direct Sellable Resolution.
* Optional `is_default`.
* Variant-defining Options.
* عدم وجود `is_required`.
* Base Price + Option Value Adjustments.
* Pricing Safety.
* Active Product Base Price Continuity.
* Inventory 1:1.
* Media Ownership.
* Primary Media Rules.
* Database Invariants.
* Domain/Transaction Invariants.
* Index Strategy.

**الحالة النهائية:**

> **Catalog V1 Database Architecture — Locked**

أي تغيير لاحق على هذه القواعد يحتاج قرارًا معماريًا جديدًا قبل التنفيذ.