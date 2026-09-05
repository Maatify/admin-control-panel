# Cart V1 Architecture — Locked

**Guest Browser-Owned Shopping Intent**

هذه الوثيقة هي المرجع المعماري المقفول لـ **Cart V1**.

يحدد هذا المرجع ملكية الـ Cart، دورة الحياة، الهوية، وآلية التفاعل مع الـ Backend والـ Catalog أثناء الدفع (Checkout). أي تغيير لاحق على قرار معماري مثبت هنا يعتبر **تغييرًا معماريًا جديدًا** وليس مجرد تفصيل تنفيذ.

---

## 1. الرؤية المعمارية والملكية

### 1.1 الهوية المعمارية
الـ Cart هو تعبير عن نية تسوق مملوكة بالكامل لمتصفح الزائر:
`Guest Browser-Owned Shopping Intent`

ليس للـ Cart أي وجود دائم (Persisted Domain) في الـ Backend ضمن إصدار V1.

### 1.2 استخدام الزوار (Guest Use)
- لا يتطلب إنشاء الـ Cart أي تسجيل دخول (No login required).
- لا يتطلب حساب عميل (No customer account required).
- يعمل الـ Cart بشكل كامل للزوار المجهولين (Anonymous visitors).

### 1.3 ملكية التخزين (Storage Ownership)
- الـ Frontend هو المالك الوحيد لتخزين الـ Cart.
- يتم استخدام التخزين المستمر في المتصفح (Persistent browser storage) مثل `localStorage` أو `IndexedDB`، ولا يُلزم الـ Frontend بآلية محددة.
- **لا توجد أي جداول في قاعدة البيانات للـ Cart.** (Backend database tables for Cart = ZERO).
- **يُمنع صراحةً** إنشاء جداول في الـ Backend مثل:
  - `carts`
  - `cart_items`
  - `guest_carts`
  - `cart_sessions`

---

## 2. الهوية والمحتوى

### 2.1 هوية العنصر (Cart Item Identity)
الوحدة الموثوقة للتعبير عن نية التسوق لكل سطر (Line) تتكون حصريًا من:
- `variant_id`
- `quantity`

يجب أن يستخدم الـ Cart هوية الـ Variant وليس الـ Product، حيث أن Product Domain يعرف كل وحدة بيع حقيقية من خلال Variant (بما في ذلك Simple Products).

### 2.2 التعامل مع التكرار (Duplicate Variant Behavior)
يمثل الـ Variant الواحد سطرًا منطقيًا واحدًا في الـ Cart.
إذا تم إضافة نفس الـ Variant مرة أخرى، يجب زيادة وتعديل الكمية (`quantity`) للسطر الحالي، ولا يجوز إنشاء سطور مكررة مستقلة لنفس الـ Variant.

### 2.3 ذاكرة العرض المؤقتة (Presentation Cache)
يُسمح للـ Frontend بالاحتفاظ ببيانات مؤقتة للعرض (Presentation Cache) مثل:
- اسم المنتج (Product name)
- أسماء الخيارات (Option labels)
- الصورة (Image)
- رمز الـ SKU
- السعر المعروض (Displayed price)

**تحذير أمني:** هذه البيانات غير موثوقة (Untrusted) وتستخدم للعرض فقط. الـ Cart **لا يمكن أبدًا أن يكون المرجع الموثوق** لـ:
- السعر (Price)
- المخزون (Stock)
- الإتاحة (Availability)
- قابلية الاختيار (Selectability)
- الترجمات (Translations)
- المجاميع النهائية عند الدفع (Final checkout totals)

### 2.4 البيانات الشخصية (PII)
يجب ألا يكون التخزين المستمر للـ Cart في المتصفح هو المصدر الدائم لـ:
- اسم العميل (Customer name)
- البريد الإلكتروني (Email)
- الهاتف (Phone)
- عنوان الشحن (Shipping address)
- بيانات الدفع (Payment information)

بيانات الاتصال والشحن تصبح لقطات محفوظة في الخادم (Server-side snapshots) فقط عند إنشاء الطلب (Order).

---

## 3. العمليات والتكامل

### 3.1 الاستعادة والتحديث (Rehydration)
عند عرض أو استعادة الـ Cart، يجب استدعاء الـ Backend (عبر الـ Catalog Package Coordinator) لتحديث وإعادة التحقق (Hydrate/Revalidate) من بيانات العرض والإتاحة الحالية للمنتجات.

### 3.2 إتمام الطلب (Checkout)
عند الدفع، يرسل المتصفح نية التسوق وبيانات الدفع إلى الـ Backend.
**يجب على الـ Backend Coordinator إعادة التحقق (Revalidate) مما يلي بشكل صارم عن طريق التنسيق بين مجالات الكتالوج:**
- وجود الـ Variant (عبر Product Domain).
- إتاحة الـ Product والـ Variant (عبر Product Domain).
- قابلية الاختيار الفعلية (Effective selectability) (عبر Product Domain).
- المخزون الحالي (عبر Inventory Domain).
- الكمية المطلوبة (Requested quantity).
- العملة المطلوبة (Requested currency).
- التسعير الفعّال (عبر Pricing Domain).
- السعر النهائي المحسوب (Final calculated price) (بتنسيق الـ Package Coordinator).

**قاعدة:** الأسعار، المخزون، الأسماء، والمجاميع المرسلة من المتصفح لا يُوثق بها أبدًا.

### 3.3 الحماية من التكرار (Checkout Idempotency)
- يقوم الـ Frontend بإنشاء مفتاح عدم تكرار واحد (`checkout_idempotency_key`) لكل محاولة دفع (Checkout attempt) حقيقية.
- أي إعادة إرسال بسبب مشاكل الشبكة (Network retry / Double-submit) لنفس المحاولة يجب أن تستخدم **نفس المفتاح**.
- إذا تغير الـ Cart بشكل ملموس وبدأت محاولة دفع جديدة، يجوز إنشاء مفتاح جديد.

### 3.4 انتقال الملكية (Ownership Transition)
بمجرد نجاح إنشاء الطلب (Order creation):
- يصبح الـ Order هو السجل التجاري الموثوق (Authoritative commercial record).
- أي محاولات إعادة دفع (Payment retries) تعمل على نفس الـ Order الموجود.
- يجب ألا يقوم الـ Cart بإنشاء Order آخر لنفس محاولة الدفع (Idempotent attempt).

---

## 4. النموذج المفاهيمي للبيانات (Conceptual DTO Model)

نظرًا لعدم وجود تخزين دائم في الـ Backend للـ Cart، يتم تصميم العقود (Contracts) باستخدام مفهوم كائنات نقل البيانات (DTO) فقط، ويُمنع استخدام مصفوفات البيانات الخام (Raw-array contracts).

هذا النموذج يمثل هيكلة التخزين في المتصفح والتواصل مع الخادم، ولا يتم تنفيذ أي جداول قاعدة بيانات له:

### 4.1 التخزين الرئيسي للـ Cart (Root Cart DTO)
يمثل حالة الـ Cart الإجمالية:
- `CartStorageDTO`
  - `items`: `CartItemStorageCollectionDTO`
  - *(أي بيانات أخرى مملوكة للمتصفح مثل تفضيلات العملة المبدئية)*

### 4.2 مجموعة عناصر الـ Cart (Cart Item Storage Collection DTO)
يمثل مجموعة العناصر لتجنب استخدام المصفوفات الخام (Raw arrays/lists) في العقود:
- `CartItemStorageCollectionDTO`
  - يحتوي على مجموعة من الـ `CartItemStorageDTO`

### 4.3 عنصر الـ Cart (Cart Item Storage DTO)
يمثل العنصر الفردي داخل الـ Cart:
- `CartItemStorageDTO`
  - `variantId` (المعرف المرجعي للـ Variant)
  - `quantity` (الكمية المطلوبة)
