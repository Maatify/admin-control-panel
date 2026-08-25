# Orders V1 Database Architecture — Final Review Candidate

**Standalone Guest Order + Historical Commercial Snapshot + Stock Reservation Engine**

هذه الوثيقة هي المرجع المعماري لـ **Orders V1**.

نظرًا لوجود بعض القرارات المعمارية التي تحتاج إلى حسم (مثل متطلبات جهات الاتصال وعقود الشحن)، تعتبر هذه الوثيقة **مرشح مراجعة نهائي (Final Review Candidate)** وليست مقفولة بشكل تام بعد. يرجى مراجعة القسم الأخير الذي يسرد القرارات المعلقة.

---

## 1. الرؤية المعمارية والهوية

الهوية الأساسية للموديول:
`Standalone Guest Order + Historical Commercial Snapshot + Stock Reservation Engine`

- يدعم الموديول إتمام الطلب كزائر (Guest Checkout) دون الحاجة لتسجيل الدخول.
- يجب أن يظل قابلًا للاستخراج (Extractable) ومستقلًا عن الـ Host (Host-Agnostic).
- يُمنع استخدام أي مفتاح أجنبي (FK) يربط جداول Orders بجداول من موديولات أخرى مثل Catalog أو Geo.

---

## 2. تدفق إنشاء الطلب (Order Creation Flow)

التدفق المعماري المقفول:
Browser Cart
→ Server Revalidation
→ Create Order
→ Reserve Stock
→ Order status = `pending_payment`
→ Payment Attempt(s)
→ Payment success → `confirmed`

- يتم إنشاء الطلب **قبل** الدفع.
- محاولات الدفع لا تنشئ طلبًا جديدًا؛ يتم الإشارة إلى نفس الطلب.
- إنشاء الطلب، أخذ اللقطات (Snapshots)، وحجز المخزون يجب أن تتم كعملية واحدة متكاملة (All-or-nothing).
- يُمنع الإنشاء الجزئي؛ إذا لم يتوفر أي سطر (Line) من الطلب:
  - يُرفض إنشاء الطلب بالكامل.
  - لا يتم تقليل الكميات بصمت (Silently reduce quantities).
  - لا يتم حذف العناصر غير المتوفرة بصمت (Silently remove unavailable items).

---

## 3. هوية الطلب (Order Identity)

كل طلب يحتوي على:
1. `id` داخلي: مفتاح أساسي داخلي.
2. `order_reference`:
   - فريد وغير قابل للتغيير (Immutable).
   - يتم إنشاؤه من قبل الـ Backend.
   - آمن للعرض العام (Opaque/Public-safe).
   - لا يمكن استنتاجه بالتسلسل من الـ `id` الداخلي.
   - يتواجد منذ حالة `pending_payment`.
   - يستخدم للعرض في الفواتير والتتبع.

**ملاحظة:** الـ `order_reference` وحده **لا يعتبر تفويضًا (Authorization)**. بوابة الزوار (Guest Order Portal) لمعاينة الطلب لاحقًا خارج نطاق (Out of scope) هذا الإصدار V1. لا تقم بإضافة آلية Token للزوار إلا إذا تطلب الـ Repository ذلك لاحقًا.

---

## 4. الحماية من التكرار (Idempotency)

يستخدم إنشاء الطلب:
- `checkout_idempotency_key`
- `checkout_request_fingerprint`

السلوك:
- نفس الـ Key + نفس الـ Request (متطابق) → إرجاع/استخدام الطلب الحالي.
- نفس الـ Key + طلب مختلف → تعارض عدم التكرار (Idempotency conflict).
- مفتاح جديد → محاولة دفع جديدة.

**ملاحظة:** محاولات الدفع لها آلية عدم تكرار خاصة بنطاق الدفع، ولا يجوز إعادة استخدام مفتاح عدم تكرار إنشاء الطلب.

---

## 5. حالة الطلب (Order Status)

الحالات المقفولة في `OrderStatusEnum`:
- `pending_payment`
- `confirmed`
- `processing`
- `completed`
- `cancelled`
- `expired`

الانتقالات المسموح بها فقط:
- `pending_payment` → `confirmed`
- `pending_payment` → `cancelled`
- `pending_payment` → `expired`
- `confirmed` → `processing`
- `confirmed` → `cancelled`
- `processing` → `completed`
- `processing` → `cancelled`

الحالات النهائية (Terminal states):
- `completed`, `cancelled`, `expired`.

**قواعد:**
- لا يوجد انتقال للخلف.
- لا توجد إحياء للطلب (No resurrection). الانتهاء (Expired) أو الإلغاء (Cancelled) إلى مؤكد (Confirmed) محظور.
- فشل محاولة الدفع لا يغير الطلب من `pending_payment`.

### 5.1 التعامل مع تأخير الدفع (Late Payment / Races)
- إذا فاز وقت انتهاء الحجز (Reservation expiry) أولاً: الطلب يصبح `expired`، وتعود البضائع للمخزون. نجاح الدفع اللاحق يجب **ألا يُحيي** الطلب (عمليات الاسترداد من شأن نظام الدفع).
- إذا فاز نجاح الدفع أولاً: الطلب يصبح `confirmed`، وتُستهلك الحجوزات. يجب ألا يفعل أمر انتهاء الحجز شيئًا حينها.

---

## 6. حجز المخزون (Stock Reservation)

إدارة حجز المخزون تابعة لموديول Orders وليس Catalog.
يتم الحجز لكل (Order Item / Variant).

الحالات المقفولة في `StockReservationStatusEnum`:
- `reserved`
- `consumed`
- `released`
- `expired`

- يتم الحجز لكل (Order Item / Variant).
- **دورة حياة الحجز (Lifecycle):**
  - عند نجاح الحجز: يتم إنقاص مخزون الكتالوج (`Catalog quantity_on_hand -= reserved quantity`) وحالة الحجز تصبح `reserved`.
  - عند نجاح الدفع: `reserved` → `consumed`. (لا يتم إنقاص الكتالوج مرة أخرى).
  - عند الإلغاء قبل الدفع: `reserved` → `released` ويزداد الكتالوج (`Catalog quantity_on_hand += reserved quantity`).
  - عند انتهاء الحجز: `reserved` → `expired` ويزداد الكتالوج (`Catalog quantity_on_hand += reserved quantity`).
- يجب أن تكون جميع عمليات `consume/release/expire` غير قابلة للتكرار (idempotent) وتؤثر على المخزون مرة واحدة فقط.
- يُمنع البيع الزائد (No overselling).
- يتم تنسيق العمليات والتحديثات من خلال `Host/Application Coordinator` ولا يجوز لـ Orders تحديث جداول Catalog بشكل مباشر.

---

## 7. التنسيق بين الموديولات (Cross-Module Atomicity)

يُمنع وصول Orders إلى جداول Catalog بشكل مباشر. كما يجب ألا يعلم Catalog بأي شيء عن الـ `order_id` أو الدفع.
- يجب الاعتماد على منسق تطبيق/هوست (Host/Application Coordinator) ينسق عقود Orders ومخزون Catalog كعملية ذرية (Atomic transaction) باستخدام آلية الـ Transaction المدعومة فعليًا في بنية المشروع (مثل منسق معاملات مشترك).

---

## 8. اللقطات التجارية (Snapshots)

### 8.1 اللقطة التجارية للمنتج (Commercial Snapshot)
الطلب هو سجل تاريخي تجاري. لا يجوز الاعتماد على Catalog لإعادة بناء البيانات.
لقطة العنصر `Order Item` تتضمن على الأقل:
- مرجع `product_id` (تاريخي بدون FK).
- `product_code`.
- مرجع `variant_id` (تاريخي بدون FK).
- `sku`.
- `base_unit_price`.
- `unit_price`.
- `quantity`.
- `line_total`.

### 8.2 لقطة الترجمات الشاملة (All-Language Snapshot)
- يجب حفظ **كل** الترجمات المتوفرة في الكتالوج وقت إنشاء الطلب لبيانات المنتج والخيارات والقيم.
- لقطة الترجمة يجب أن تكون جداول علائقية وليست JSON.
- حقل `checkout_language_code` في الطلب يستخدم فقط كسياق تاريخي، ولا يحد من الترجمات الملتقطة.

### 8.3 لقطة جهات الاتصال (Contact Snapshot)
- يتم تخزين بيانات الاتصال (مثل الاسم الكامل، البريد الإلكتروني، والهاتف) للزائر بمعزل عن أي نظام Profiles. (بدون FK لجدول العملاء).

### 8.4 لقطة الشحن (Shipping Snapshot)
يجب التقاط عنوان الشحن وطريقة الشحن التي تم اختيارها وقت الدفع.
معلومات العناوين تتضمن إشارات تاريخية برموز الـ Country والـ City دون استخدام مفاتيح أجنبية (No FK to Geo).

### 8.5 اللقطة المالية (Financial Snapshot)
الحد الأدنى المطلوب:
- `subtotal_amount`
- `shipping_amount`
- `total_amount`

جميع القيم المالية تستخدم بدقة التخزين الموحدة: `DECIMAL(20,6)`، ولا تستخدم نوع Float في الـ PHP.

---

## 9. قواعد الاحتفاظ والحذف (Order Deletion / Retention)
يجب التوفيق بين سياسات الحذف المرن (Soft-delete) الخاصة بالموديولات وبين كون الطلب سجلًا تجاريًا، بحيث قد يقتضي الأمر منع الحذف في وقت التشغيل والاحتفاظ به كسجل دائم.

---

## 10. النموذج العلائقي المتوقع (Expected Relational Model)

1. `maa_orders_orders`
2. `maa_orders_order_contacts`
3. `maa_orders_order_shipping_addresses`
4. `maa_orders_order_shipping_address_translations`
5. `maa_orders_order_items`
6. `maa_orders_order_item_translations`
7. `maa_orders_order_item_option_values`
8. `maa_orders_order_item_option_value_translations`
9. `maa_orders_stock_reservations`

*(قد تتم إضافة جدول إضافي لوسيلة الشحن في حال تم حسم متطلباتها).*

يجب أن تعتمد هذه الجداول محرك `InnoDB` بترميز `utf8mb4_unicode_ci`، واستخدام توقيتات UTC مدارة من التطبيق.

*(ملاحظة: سياسة وجود عمود `deleted_at` للحذف المرن تبقى معلقة حتى حسم سياسة السجل التجاري المذكورة في القرارات المعلقة).*

---

## 11. القرارات المعمارية المعلقة (Unresolved Decisions)

قبل أن يتم قفل هذا المرجع المعماري (Locked)، يجب حسم القرارات التالية عبر أدلة فعلية من مستودع الكود (Repository Evidence):

1. **إلزامية جهات الاتصال (Contact Requiredness):**
   هل البريد الإلكتروني (Email) و/أو الهاتف (Phone) إلزامي أم اختياري في الطلب بناءً على أعراف المستودع الحالية؟
2. **عقد لقطة وسيلة الشحن (Shipping Method Snapshot Contract):**
   بنية الجدول والبيانات التاريخية التي سيتم التقاطها لوسيلة الشحن (مثل `method code`, `provider`, `name translations`).
3. **آلية التنسيق للمعاملات المشتركة (Cross-Module Atomic Transaction Mechanism):**
   تحديد المنسق المشترك (Transaction Coordinator) الفعلي المعتمد في بنية التطبيق لربط عمليات Orders مع Catalog بشكل ذري.
4. **سياسة الحذف كسجل تجاري (Commercial-Record Deletion/Soft-Delete Policy):**
   التأكيد على استثناءات أو قواعد سياسة الحذف (Soft Delete vs Append Only) لهذه السجلات التجارية.


## 12. محددات قاعدة البيانات (Database Invariants)

يجب التمييز بوضوح بين المستويات التالية للمحددات:

### 12.1 محددات تفرضها قاعدة البيانات (Database-enforced invariants)
- **وحدانية مرجع الطلب (Order reference uniqueness):** يجب أن يكون `order_reference` فريدًا كليًا (UNIQUE constraint).
- **عدم التكرار (Checkout idempotency uniqueness):** يجب أن يكون `checkout_idempotency_key` فريدًا لكل طلب (UNIQUE constraint).
- **تأمين حالة الطلب (Valid Order statuses):** مقيد بـ CHECK constraint (أو تطبيق صارم في الـ Repository في حال عدم دعم CHECK) لضمان القيم `pending_payment`, `confirmed`, `processing`, `completed`, `cancelled`, `expired` فقط.
- **وحدانية لقطة الترجمة (Translation snapshot uniqueness):** لكل عنصر (Item/Option Value)، لا يجوز تكرار الترجمة لنفس `language_code` (UNIQUE constraint).
- **وحدانية الحجز (One logical reservation per Order Item):** يجب أن يكون هناك حجز نشط واحد فقط مرتبط بسطر الطلب.
- **الكميات الإيجابية (Positive quantities):** كميات العناصر يجب أن تكون > 0 (CHECK constraint).
- **القيم المالية (Nonnegative commercial money values):** جميع الحقول المالية (subtotal, shipping, total) يجب أن تكون >= 0 (CHECK constraint).
- **عدم الاعتماد على جداول خارجية (No FK to host tables):** يمنع تمامًا وجود مفاتيح أجنبية لـ Catalog، Geo، Payment، أو Shipping.

### 12.2 محددات يفرضها التطبيق (Domain/application-enforced invariants)
- **الانتقالات المسموحة (Allowed status transitions):** يمنع الانتقال للخلف، يمنع إحياء الطلب (No Order resurrection).
- **سلوك تعارض البصمة (Request fingerprint conflict behavior):** إذا توافق الـ idempotency key مع fingerprint مختلف، يجب رفض الطلب كتعارض.
- **حسابات التسعير (Pricing arithmetic):**
  - `base_unit_price + SUM(adjustments) = unit_price`
  - `unit_price * quantity = line_total`
  - `SUM(line_total) = subtotal`
  - `subtotal + shipping = total_amount`
- **كل شيء أو لا شيء (All-or-nothing checkout):** إما اكتمال إنشاء الطلب وحجز كافة العناصر أو فشله بالكامل.
- **استهلاك الحجز بدقة (Reservation consume/release/expire exactly once):** أي حجز يتأثر بمرة واحدة فقط، لتجنب تغييرات المخزون الخاطئة.
- **عدم البيع الزائد (No overselling):** يمنع تأكيد حجز جديد إذا كان المخزون المتاح لا يكفي.
- **الاحتفاظ بجميع الترجمات (All available translations captured at creation):** النظام ملزم بالتقاط جميع الترجمات الحالية من الكتالوج وعدم اختلاق ترجمات افتراضية غير موجودة.
- **ثبات اللقطة التاريخية (Historical snapshot immutability):** بعد الإنشاء، يمنع تعديل بيانات العناصر والتسعير المرجعي.

### 12.3 محددات التنسيق بين الموديولات (Cross-module coordination invariants)
- **سباق تأخير الدفع (Late payment race handling):** يتم إدارته بشكل آمن بالاعتماد على منسق تطبيقات يضمن عدم إحياء طلب منتهي.

---

## 13. تفاصيل الجداول والمخطط (Physical Schema Details)

*(ملاحظة: هذه الجداول تمثل البنية المرشحة الحالية وتفتقر لمعلومات طرق الشحن التي لم تُحسم بعد. يجب أن تستخدم توقيتات UTC `DATETIME` مدارة من التطبيق)*

### 13.1 `maa_orders_orders`
**الغرض:** تخزين السجل التجاري الرئيسي للطلب.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT, PK)
- `order_reference` (VARCHAR(64), UNIQUE, NOT NULL): معرّف عشوائي آمن للعرض.
- `checkout_idempotency_key` (VARCHAR(128), UNIQUE, NOT NULL)
- `checkout_request_fingerprint` (VARCHAR(128), NOT NULL)
- `status` (VARCHAR(32), NOT NULL, CHECK: 'pending_payment', 'confirmed', ...): حالة الطلب.
- `subtotal_amount` (DECIMAL(20,6), NOT NULL, CHECK >= 0)
- `shipping_amount` (DECIMAL(20,6), NOT NULL, CHECK >= 0)
- `total_amount` (DECIMAL(20,6), NOT NULL, CHECK >= 0)
- `currency_code` (CHAR(3), NOT NULL): رمز العملة (ISO 4217) المرجعي للسجل (No FK).
- `checkout_language_code` (VARCHAR(16), NULL): (BCP-47)
- `reservation_expires_at` (DATETIME, NOT NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**الفهارس:** `idx_orders_status_created` (`status`, `created_at`), `idx_orders_reservation_expiry` (`status`, `reservation_expires_at`, `id`)

### 13.2 `maa_orders_order_contacts`
**الغرض:** لقطة بيانات الزائر المستقلة.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT, PK)
- `order_id` (BIGINT UNSIGNED, NOT NULL, UNIQUE, FK -> `maa_orders_orders` ON DELETE RESTRICT ON UPDATE RESTRICT)
- `full_name` (VARCHAR(255), NOT NULL)
- `email` (VARCHAR(255), NULL): *(ينتظر حسم الإلزامية)*
- `phone` (VARCHAR(50), NULL): *(ينتظر حسم الإلزامية)*
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)

### 13.3 `maa_orders_order_shipping_addresses`
**الغرض:** لقطة عنوان الشحن المختار.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT, PK)
- `order_id` (BIGINT UNSIGNED, NOT NULL, UNIQUE, FK -> `maa_orders_orders` ON DELETE RESTRICT ON UPDATE RESTRICT)
- `recipient_name` (VARCHAR(255), NOT NULL)
- `recipient_phone` (VARCHAR(50), NOT NULL)
- `country_code` (CHAR(2), NOT NULL): مرجع تاريخي لـ Geo.
- `city_code` (VARCHAR(100), NULL): مرجع تاريخي.
- `address_line_1` (VARCHAR(500), NOT NULL)
- `address_line_2` (VARCHAR(500), NULL)
- `state_region` (VARCHAR(100), NULL)
- `district` (VARCHAR(100), NULL)
- `postal_code` (VARCHAR(50), NULL)
- `delivery_instructions` (TEXT, NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)

### 13.4 `maa_orders_order_shipping_address_translations`
**الغرض:** ترجمات بيانات المدينة/الدولة التاريخية وقت إنشاء الطلب.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT, PK)
- `address_id` (BIGINT UNSIGNED, NOT NULL, FK -> `maa_orders_order_shipping_addresses` ON DELETE RESTRICT ON UPDATE RESTRICT)
- `language_code` (VARCHAR(16), NOT NULL): (BCP-47)
- `country_name` (VARCHAR(100), NOT NULL)
- `city_name` (VARCHAR(100), NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:** UNIQUE KEY (`address_id`, `language_code`)

### 13.5 `maa_orders_order_items`
**الغرض:** سجلات العناصر المطلوبة.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT, PK)
- `order_id` (BIGINT UNSIGNED, NOT NULL, FK -> `maa_orders_orders` ON DELETE RESTRICT ON UPDATE RESTRICT)
- `product_id` (BIGINT UNSIGNED, NOT NULL): مرجع تاريخي لـ Catalog.
- `product_code` (VARCHAR(100), NOT NULL)
- `variant_id` (BIGINT UNSIGNED, NOT NULL): مرجع تاريخي لـ Catalog.
- `sku` (VARCHAR(100), NOT NULL)
- `base_unit_price` (DECIMAL(20,6), NOT NULL, CHECK >= 0)
- `unit_price` (DECIMAL(20,6), NOT NULL, CHECK >= 0)
- `quantity` (INT UNSIGNED, NOT NULL, CHECK > 0)
- `line_total` (DECIMAL(20,6), NOT NULL, CHECK >= 0)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:** UNIQUE KEY (`order_id`, `variant_id`)
**الفهارس:** `idx_order_items_order_id` (`order_id`)

### 13.6 `maa_orders_order_item_translations`
**الغرض:** لقطة لجميع ترجمات المنتج المطلوبة.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT, PK)
- `order_item_id` (BIGINT UNSIGNED, NOT NULL, FK -> `maa_orders_order_items` ON DELETE RESTRICT ON UPDATE RESTRICT)
- `language_code` (VARCHAR(16), NOT NULL): (BCP-47)
- `product_name` (VARCHAR(255), NOT NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:** UNIQUE KEY (`order_item_id`, `language_code`)

### 13.7 `maa_orders_order_item_option_values`
**الغرض:** لقطة الخيارات المحددة للعنصر.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT, PK)
- `order_item_id` (BIGINT UNSIGNED, NOT NULL, FK -> `maa_orders_order_items` ON DELETE RESTRICT ON UPDATE RESTRICT)
- `option_id` (BIGINT UNSIGNED, NOT NULL): مرجع تاريخي.
- `option_code` (VARCHAR(100), NOT NULL)
- `option_value_id` (BIGINT UNSIGNED, NOT NULL): مرجع تاريخي.
- `option_value_code` (VARCHAR(100), NOT NULL)
- `price_adjustment` (DECIMAL(20,6), NOT NULL DEFAULT 0.00)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:** UNIQUE KEY (`order_item_id`, `option_id`)

### 13.8 `maa_orders_order_item_option_value_translations`
**الغرض:** لقطة ترجمات الخيارات والقيم للعنصر.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT, PK)
- `item_option_value_id` (BIGINT UNSIGNED, NOT NULL, FK -> `maa_orders_order_item_option_values` ON DELETE RESTRICT ON UPDATE RESTRICT)
- `language_code` (VARCHAR(16), NOT NULL): (BCP-47)
- `option_name` (VARCHAR(255), NOT NULL)
- `option_value_name` (VARCHAR(255), NOT NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:** UNIQUE KEY (`item_option_value_id`, `language_code`)

### 13.9 `maa_orders_stock_reservations`
**الغرض:** محرك حجز المخزون للعنصر.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT, PK)
- `order_item_id` (BIGINT UNSIGNED, NOT NULL, UNIQUE, FK -> `maa_orders_order_items` ON DELETE RESTRICT ON UPDATE RESTRICT)
- `quantity` (INT UNSIGNED, NOT NULL, CHECK > 0)
- `status` (VARCHAR(32), NOT NULL, CHECK: 'reserved', 'consumed', 'released', 'expired')
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**الفهارس:** `idx_reservations_status` (`status`)
