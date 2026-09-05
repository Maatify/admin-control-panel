# Catalog Package Architecture

هذه الوثيقة تمثل الـ Source of Truth لمستوى الـ Package (Umbrella) الذي ينسق بين الـ Base Modules المستقلة (Catalog, Product, Pricing, Inventory).

## 1. الرؤية المعمارية

الـ Catalog Package يعرف ويجمع الـ Modules التالية:
* Catalog Base (Taxonomy / Categories)
* Product
* Pricing
* Inventory

لا يعرف أي من هذه الـ Base Modules بعضها البعض ولا يعرفون الـ Catalog Package. لا يوجد بينهم Cross-module FK أو JOIN أو Shared transaction ownership.

## 2. Variant ↔ Inventory Lifecycle Orchestration

الـ Inventory Base Module مستقل ولا يعرف الـ Variant. الـ Catalog Package يفرض التنسيق الخاص بالـ Variant كالتالي:

* **Variant Inventory Entity:** `stock_subject_type = 'variant'`, `stock_subject_id = variant.id`
* **Lifecycle Rule:** Every non-deleted Variant participating in Catalog Package has exactly one Inventory identity.
* **Creation:** عند إنشاء Variant، يجب أن يقوم المنسق (Package Coordinator) بإنشاء سجل مخزون مطابق في الـ Inventory Module، ويبدأ بكمية `0`.
* **Restore:** عند عمل Restore لـ Variant، يعود نفس سجل الـ Inventory identity. لا تنشأ identity جديدة.
* **Soft Delete:** عمل Soft Delete للـ Variant لا يمحو المخزون بشكل مستقل، ولكن المنسق يمنع حذف الـ Inventory بشكل مستقل طالما أن الـ Variant موجودة وغير محذوفة نهائياً.
* **Replacement:** عند إنشاء Replacement Variant نتيجة تغيير الـ Options، تنشأ Inventory identity جديدة للـ Variant الجديدة.

## 3. Final Price Equation

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

## 4. Product ↔ Category Contract

آلية الـ Persistence للربط بين المنتج والفئة متروكة Unresolved، لكن الـ Business Contract مقفول على النحو التالي:

* **Logical Identity:** `(product_id, category_id)`
* **Uniqueness:** لا يمكن تكرار نفس الربط لنفس `(product_id, category_id)`.
* **Display Order Scope:** خاص بكل `category_id`.
* **Ordering Rule:** `ORDER BY display_order, id`
* **Visibility Semantics:**
    * إذا كان Category A = inactive و Category B = active، والـ Product مرتبط بهما: تعطيل A يعطل مسار العرض (Classification Path) الخاص بـ A فقط. الـ Product تظل قابلة للظهور عبر B.
    * حالة الـ Category لا تقوم بتعديل `Product.status`.
    * حذف الـ Category أو تغيير حالته لا يعدل سجلات Product/Variant.

## 5. Cross-Domain Operations & Safety

يتولى الـ Catalog Package ضمان:
* **Product Activation Completeness:** لكي يصبح المنتج فعّالاً بشكل كامل، يتطلب الأمر وجود Variant بهيكلية صحيحة، وسجل مخزون (لا يُشترط كمية > 0 للـ Activation)، وسعر أساسي واحد على الأقل، مع تطبيق معايير السلامة السعرية.
* **Active Product Base Price Continuity:** لا يجوز حذف آخر Base Price لمنتج نشط.
* **Restore Safety:** ضمان التوافق عند الاستعادة عبر المجالات المختلفة.
* **Pricing Safety Orchestration:** ضمان أن التعديلات في خيارات المنتجات لا تكسر معادلات السعر.
