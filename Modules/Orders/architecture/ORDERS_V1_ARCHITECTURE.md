# Orders V1 Database Architecture — Locked

**Standalone Guest Order + Historical Commercial Snapshot + Stock Reservation Engine**

هذه الوثيقة هي المرجع المعماري لـ **Orders V1**.

هذه الوثيقة مقفولة (Locked) وتمثل المرجع المعماري النهائي للموديول.

---

## 1. الرؤية المعمارية والهوية

الهوية الأساسية للموديول:
`Standalone Guest Order + Historical Commercial Snapshot + Stock Reservation Engine`

- يدعم الموديول إتمام الطلب كزائر (Guest Checkout) دون الحاجة لتسجيل الدخول.
- يجب أن يظل قابلًا للاستخراج (Extractable) ومستقلًا عن الـ Host (Host-Agnostic).
- يُمنع استخدام أي مفتاح أجنبي (FK) يربط جداول Orders بجداول من موديولات أخرى مثل Product Domain أو Geo.

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

### 5.1 عمليات الإلغاء بعد الدفع (Post-Payment Cancellation Semantics)
الانتقالات المسموحة إلى `cancelled` من حالتي `confirmed` أو `processing` لا تزال سارية، ولكن يُعتبر الإلغاء هنا عملية تجارية (Business operation) وليس مجرد تغيير للحالة:
- يصبح الطلب `cancelled` فقط بعد اكتمال جميع التعويضات الضرورية (Compensation) بنجاح، وقد يشمل ذلك:
  - تعويضات في نطاق الدفع/الاسترداد (Payment/refund compensation).
  - تعويضات التجهيز أو المخزون (Fulfillment/inventory compensation).
- حالة `cancelled` تعني أن الإلغاء **قد اكتمل**، وليس مجرد أنه طُلب.
- **يُمنع صراحةً** إضافة حالات وسيطة أو خاصة بالدفع إلى `OrderStatusEnum` مثل: `refund_pending`، `refunded`، أو `cancellation_requested`. حالات الاسترداد تظل ضمن اختصاص نطاق الدفع.
- **استعادة المخزون:** يجب ألا يتم استعادة المخزون تلقائيًا لمجرد تحول الطلب المدفوع إلى `cancelled`. تتم استعادة المخزون فقط عندما يؤكد تعويض التجهيز/المخزون إمكانية إرجاع الكمية المادية لاستعادة البضائع المشحونة أو المجهزة. هذا يمنع اختلاق مخزون وهمي بعد تقدم مرحلة التجهيز.
- الحالات النهائية (Terminal states) المسموحة هي فقط: `completed`, `cancelled`, `expired`.

### 5.2 التعامل مع تأخير الدفع (Late Payment / Races)
- إذا فاز وقت انتهاء الحجز (Reservation expiry) أولاً: الطلب يصبح `expired`، وتعود البضائع للمخزون. نجاح الدفع اللاحق يجب **ألا يُحيي** الطلب (عمليات الاسترداد من شأن نظام الدفع).
- إذا فاز نجاح الدفع أولاً: الطلب يصبح `confirmed`، وتُستهلك الحجوزات. يجب ألا يفعل أمر انتهاء الحجز شيئًا حينها.

---

## 6. حجز المخزون (Stock Reservation)

إدارة حجز المخزون تابعة لموديول Orders وليس Catalog Package.
يتم الحجز لكل (Order Item / Variant).

الحالات المقفولة في `StockReservationStatusEnum`:
- `reserved`
- `consumed`
- `released`
- `expired`

- يتم الحجز لكل (Order Item / Variant).
- **سياسة المهلة الزمنية للحجز (Reservation Deadline Policy):**
  - يتم إعداد مدة الحجز وتكوينها من قبل الـ Host.
  - عند إنشاء الطلب، يتم حساب مدة الحجز ليصبح وقت انتهاء واحد مطلق `reservation_expires_at`.
  - هذا الوقت المطلق يتم حفظه كلقطة تاريخية على مستوى الطلب ككل (Order).
  - تشترك جميع الحجوزات التابعة للطلب في هذا الموعد النهائي.
  - أي تغييرات لاحقة في إعدادات مهلة الحجز في الـ Host **يجب ألا تؤثر** على الطلبات المنشأة بالفعل.
  - عمليات معالجة انتهاء المهلة (Expiry processing) تعتمد على الموعد المطلق المحفوظ في الطلب، وليس التكوين الحالي.
- **دورة حياة الحجز (Lifecycle) ومتطلبات العمل:**
  - عند إنشاء الحجز: يتم التحقق ذرياً من توفر المخزون الكافي، ويُطرح الكمية المحجوزة من `quantity_on_hand` في الكتالوج لمنع البيع المزدوج، وتصبح حالة الحجز `reserved`. ويجب أن تتم هاتان العمليتان في نفس الـ Transaction.
  - عند نجاح الدفع: تستهلك عملية الدفع الحجز مرة واحدة فقط (`reserved` → `consumed`). **لا يتم** إنقاص المخزون في الكتالوج مرة أخرى.
  - عند الإلغاء قبل الدفع: يتم تحرير الحجز مرة واحدة فقط (`reserved` → `released`) وتتم استعادة الكمية إلى `quantity_on_hand` في الكتالوج.
  - عند انتهاء الحجز: يتم تحرير الحجز مرة واحدة فقط (`reserved` → `expired`) وتتم استعادة الكمية في الكتالوج.
- **المتطلبات الأساسية (Invariants):**
  - يُمنع البيع الزائد (No overselling): لا يُسمح بعمليات الدفع المتزامنة بتخطي المخزون المتاح، ويتطلب ذلك سلوك قفل الصفوف (Row-lock) أو معالجة ذرية.
  - جميع عمليات `consume/release/expire` يجب أن تكون غير قابلة للتكرار (idempotent) وتؤثر على إتاحة المخزون لمرة واحدة فقط.
  - تعني `quantity_on_hand` في الكتالوج "رصيد المخزون المتاح للبيع للعملاء" (Current Sellable Inventory Balance).
  - لا يجوز لـ Orders تحديث جداول Product Domain و Inventory Domain بشكل مباشر، بل يتم عبر المنسق (Host Coordinator).

---

## 7. التنسيق بين الموديولات (Cross-Module Atomicity)

يُمنع وصول Orders إلى جداول Product Domain و Inventory Domain بشكل مباشر. كما يجب ألا يعلم Product Domain بأي شيء عن الـ `order_id` أو الدفع.
- تُدار جميع عمليات إنشاء الطلب (التدقيق، الحجز، التحديثات المشتركة، وإنشاء اللقطات) كعملية ذرية واحدة (All-or-nothing).
- **التنسيق المشترك:** يمتلك منسق الدفع في التطبيق (Host/Application Checkout Coordinator) مسؤولية التنسيق المباشر.
- **التجريد (Transaction abstraction):** سيتم الاعتماد مستقبلاً على مكتبة `maatify/persistence` لتوفير تجريد يضمن مشاركة التحديثات في نفس الـ PDO connection ونفس الـ Database transaction بشكل متداخل وآمن (Nested-safe participation). (هذا التجريد هو متطلب تنفيذ مستقبلي، ولا تقدم المكتبة هذه الميزة حاليًا).
- يجب على أي موديول ينضم لمعاملة قائمة ألا يقوم بعمل `commit` أو `rollback` مستقل؛ المالك الوحيد (Coordinator) هو المتحكم النهائي في إتمام أو تراجع المعاملة.
- لا توجد حاجة لمعاملات موزعة (Distributed transactions) أو Two-phase commit، فالعملية تتم بالكامل عبر اتصال قاعدة بيانات واحد.

---

## 8. اللقطات التجارية (Snapshots)

### 8.1 اللقطة التجارية للمنتج (Commercial Snapshot)
الطلب هو سجل تاريخي تجاري. لا يجوز الاعتماد على Product Domain لإعادة بناء البيانات.
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

- نظرًا لوجود أسماء أساسية إلزامية لـ Country و City في المرجع الحالي (Geo)، يجب الاحتفاظ بهذه الأسماء كجزء من اللقطة الأساسية لعنوان الشحن.
- الترجمات الفعلية للعنوان التي كانت متوفرة وقت الدفع تحفظ في جداول الترجمة الخاصة باللقطة.
- **حدود التكامل (Integration Boundary):** تستخدم هوية لغة الجغرافيا (Geo translation identity) الـ `language_id` الخاص بالـ Host. ومع ذلك، يجب على المنسق (Host/Application Coordinator) تحويل هذا المعرف إلى رمز دائم (BCP-47 `language_code`) عند تكوين لقطة الترجمة الخاصة بـ Orders. يُمنع تمامًا اختلاق ترجمات غير موجودة أو تخزين نصوص بديلة (Fallback text) على أنها صفوف ترجمة فعلية. إذا كانت الترجمة غير موجودة، يتم ترك الحقل `NULL`، بشرط توفر ترجمة واحدة على الأقل في السطر.

### 8.5 اللقطة المالية (Financial Snapshot)
الحد الأدنى المطلوب:
- `subtotal_amount`
- `shipping_amount`
- `total_amount`

جميع القيم المالية تستخدم بدقة التخزين الموحدة: `DECIMAL(20,6)`، ولا تستخدم نوع Float في الـ PHP.

---

## 9. قواعد الاحتفاظ والحذف (Order Deletion / Retention)
يُعتبر موديول Orders **سجلاً تجارياً تاريخياً غير قابل للحذف (Append-Only Historical Commercial Records)**. بناءً على ذلك:
- لا يوجد حذف مرن (No soft delete) ولا حذف نهائي (No hard delete) للسجل ككل في وقت التشغيل.
- لا يوجد عمود `deleted_at` في جداول الـ Orders.
- السجلات واللقطات التاريخية (Child snapshots) لا تملك دورة حياة حذف مستقلة.
- المعرفات مثل `order_reference` و `checkout_idempotency_key` لا يُعاد استخدامها أبداً.
- دورة حياة الطلب تُمثل عبر `OrderStatusEnum` وليس بالحذف.
- *ملاحظة:* إذا اقتضت الحاجة القانونية مسح بيانات حساسة (PII anonymization) أو إتلاف فعلي، فهذا يُدار كإجراء إداري منفصل (Administrative retention process) خارج نطاق معمارية التشغيل القياسية لـ Orders V1.

---

## 10. النموذج العلائقي المتوقع (Expected Relational Model)

1. `maa_orders_orders`
2. `maa_orders_order_contacts`
3. `maa_orders_order_shipping_addresses`
4. `maa_orders_order_shipping_address_translations`
5. `maa_orders_order_shipping_methods`
6. `maa_orders_order_shipping_method_translations`
7. `maa_orders_order_items`
8. `maa_orders_order_item_translations`
9. `maa_orders_order_item_option_values`
10. `maa_orders_order_item_option_value_translations`
11. `maa_orders_stock_reservations`

يجب أن تعتمد هذه الجداول محرك `InnoDB` بترميز `utf8mb4_unicode_ci`، واستخدام توقيتات UTC مدارة من التطبيق.


---

## 11. القرارات المقفولة / متطلبات التنفيذ (Locked Decisions / Implementation Prerequisites)

تم حسم جميع القرارات المعمارية وتم قفل المستند. تبقى الملاحظات التالية كمتطلبات للتنفيذ المستقبلي:
- بناء تجريد المعاملات (لضمان نفس اتصال PDO ونفس المعاملة) في مكتبة `maatify/persistence` يظل خطوة تنفيذية قادمة ولن يتم تفصيل الـ API الخاصة به في هذه الوثيقة. (لا تتطلب المعمارية معاملات موزعة Distributed Transactions أو Two-phase Commit).
- تنفيذ سير عمل التجهيز والشحن الفعلي (Shipping/Fulfillment runtime implementation) وتحديث الـ `shipment_reference` يُعتبر عملاً مستقبلياً خارج نطاق إنشاء السجل الأساسي V1 للطلب. يبدأ الحقل كـ `NULL` عند إنشاء الطلب، وقد يتم تعبئته لاحقاً كبيانات تشغيلية (Operational data)، مع التأكيد على عدم إضافة حالات شحن جديدة أو تصميم شحنات متعددة في هذا الإصدار.


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
- **عدم الاعتماد على جداول خارجية (No FK to host tables):** يمنع تمامًا وجود مفاتيح أجنبية لـ Product، Geo، Payment، أو Shipping.

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
- **الاحتفاظ بجميع الترجمات (All available translations captured at creation):** النظام ملزم بالتقاط جميع الترجمات الحالية من Product Domain وعدم اختلاق ترجمات افتراضية غير موجودة.
- **ثبات اللقطة التاريخية (Historical snapshot immutability):** بعد الإنشاء، يمنع تعديل بيانات العناصر والتسعير المرجعي.

### 12.3 محددات التنسيق بين الموديولات (Cross-module coordination invariants)
- **سباق تأخير الدفع (Late payment race handling):** يتم إدارته بشكل آمن بالاعتماد على منسق تطبيقات يضمن عدم إحياء طلب منتهي.

---

## 13. تفاصيل الجداول والمخطط (Physical Schema Details)

*(ملاحظة: هذه الجداول تمثل البنية المرشحة الحالية وتفتقر لمعلومات طرق الشحن التي لم تُحسم بعد. يجب أن تستخدم توقيتات UTC `DATETIME` مدارة من التطبيق)*

### 13.1 `maa_orders_orders`
**الغرض:** تخزين السجل التجاري الرئيسي للطلب.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `order_reference` (VARCHAR(64), NOT NULL): معرّف عشوائي آمن للعرض.
- `checkout_idempotency_key` (VARCHAR(128), NOT NULL)
- `checkout_request_fingerprint` (VARCHAR(128), NOT NULL)
- `status` (VARCHAR(32), NOT NULL)
- `subtotal_amount` (DECIMAL(20,6), NOT NULL)
- `shipping_amount` (DECIMAL(20,6), NOT NULL)
- `total_amount` (DECIMAL(20,6), NOT NULL)
- `currency_code` (CHAR(3), NOT NULL): رمز العملة (ISO 4217) المرجعي للسجل (No FK).
- `checkout_language_code` (VARCHAR(16), NULL): (BCP-47)
- `reservation_expires_at` (DATETIME, NOT NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`order_reference`)
  - UNIQUE (`checkout_idempotency_key`)
  - CHECK (`subtotal_amount` >= 0)
  - CHECK (`shipping_amount` >= 0)
  - CHECK (`total_amount` >= 0)
  - CHECK (`status` IN ('pending_payment', 'confirmed', 'processing', 'completed', 'cancelled', 'expired'))
**الفهارس:** `idx_orders_status_created_id` (`status`, `created_at`, `id`), `idx_orders_reservation_expiry` (`status`, `reservation_expires_at`, `id`)

### 13.2 `maa_orders_order_contacts`
**الغرض:** لقطة بيانات الزائر المستقلة.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `order_id` (BIGINT UNSIGNED, NOT NULL)
  - FOREIGN KEY (`order_id`) REFERENCES `maa_orders_orders.id`
  - ON DELETE RESTRICT
  - ON UPDATE RESTRICT
- `full_name` (VARCHAR(255), NOT NULL)
- `email` (VARCHAR(255), NULL)
- `phone` (VARCHAR(50), NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)

**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`order_id`)
  - CHECK (`email` IS NOT NULL OR `phone` IS NOT NULL)

### 13.3 `maa_orders_order_shipping_addresses`
**الغرض:** لقطة عنوان الشحن المختار.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `order_id` (BIGINT UNSIGNED, NOT NULL)
  - FOREIGN KEY (`order_id`) REFERENCES `maa_orders_orders.id`
  - ON DELETE RESTRICT
  - ON UPDATE RESTRICT
- `recipient_name` (VARCHAR(255), NOT NULL)
- `recipient_phone` (VARCHAR(50), NOT NULL)
- `country_code` (CHAR(2), NOT NULL): مرجع تاريخي لـ Geo.
- `country_name` (VARCHAR(100), NOT NULL): الاسم الأساسي الفعلي وقت الدفع.
- `city_code` (VARCHAR(100), NULL): مرجع تاريخي.
- `city_name` (VARCHAR(100), NOT NULL): الاسم الأساسي الفعلي وقت الدفع.
- `address_line_1` (VARCHAR(500), NOT NULL)
- `address_line_2` (VARCHAR(500), NULL)
- `state_region` (VARCHAR(100), NULL)
- `district` (VARCHAR(100), NULL)
- `postal_code` (VARCHAR(50), NULL)
- `delivery_instructions` (TEXT, NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)

**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`order_id`)

### 13.4 `maa_orders_order_shipping_address_translations`
**الغرض:** ترجمات بيانات المدينة/الدولة التاريخية وقت إنشاء الطلب.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `address_id` (BIGINT UNSIGNED, NOT NULL)
  - FOREIGN KEY (`address_id`) REFERENCES `maa_orders_order_shipping_addresses.id`
  - ON DELETE RESTRICT
  - ON UPDATE RESTRICT
- `language_code` (VARCHAR(16), NOT NULL): (BCP-47)
- `country_name` (VARCHAR(100), NULL)
- `city_name` (VARCHAR(100), NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`address_id`, `language_code`)
  - CHECK (`country_name` IS NOT NULL OR `city_name` IS NOT NULL)


### 13.5 `maa_orders_order_shipping_methods`
**الغرض:** لقطة طريقة الشحن المختارة وقت الطلب (يُمنع الـ FK لجداول الشحن الخارجية).
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `order_id` (BIGINT UNSIGNED, NOT NULL)
  - FOREIGN KEY (`order_id`) REFERENCES `maa_orders_orders.id`
  - ON DELETE RESTRICT
  - ON UPDATE RESTRICT
- `method_code` (VARCHAR(100), NOT NULL)
- `provider_code` (VARCHAR(100), NULL)
- `service_code` (VARCHAR(100), NULL)
- `method_name` (VARCHAR(255), NOT NULL)
- `shipment_reference` (VARCHAR(100), NULL): قد يكون `NULL` عند إنشاء الطلب ويتم تعبئته لاحقاً كبيانات شحن تشغيلية. (يُمنع إضافة حالات شحن إضافية أو تصميم شحنات متعددة Multi-shipment ضمن هذا الإصدار).
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)

**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`order_id`)

### 13.6 `maa_orders_order_shipping_method_translations`
**الغرض:** ترجمات اسم طريقة الشحن الفعلية وقت إنشاء الطلب (يُمنع التوليد التلقائي لترجمات غير موجودة).
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `shipping_method_id` (BIGINT UNSIGNED, NOT NULL)
  - FOREIGN KEY (`shipping_method_id`) REFERENCES `maa_orders_order_shipping_methods.id`
  - ON DELETE RESTRICT
  - ON UPDATE RESTRICT
- `language_code` (VARCHAR(16), NOT NULL): (BCP-47)
- `name` (VARCHAR(255), NOT NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)

**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`shipping_method_id`, `language_code`)

### 13.7 `maa_orders_order_items`
**الغرض:** سجلات العناصر المطلوبة.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `order_id` (BIGINT UNSIGNED, NOT NULL)
  - FOREIGN KEY (`order_id`) REFERENCES `maa_orders_orders.id`
  - ON DELETE RESTRICT
  - ON UPDATE RESTRICT
- `product_id` (BIGINT UNSIGNED, NOT NULL): مرجع تاريخي لـ Product Domain.
- `product_code` (VARCHAR(100), NOT NULL)
- `variant_id` (BIGINT UNSIGNED, NOT NULL): مرجع تاريخي لـ Product Domain.
- `sku` (VARCHAR(100), NOT NULL)
- `base_unit_price` (DECIMAL(20,6), NOT NULL)
- `unit_price` (DECIMAL(20,6), NOT NULL)
- `quantity` (INT UNSIGNED, NOT NULL)
- `line_total` (DECIMAL(20,6), NOT NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`order_id`, `variant_id`)
  - CHECK (`base_unit_price` >= 0)
  - CHECK (`unit_price` >= 0)
  - CHECK (`quantity` > 0)
  - CHECK (`line_total` >= 0)

**الفهارس:** `idx_order_items_order_id` (`order_id`)

### 13.8 `maa_orders_order_item_translations`
**الغرض:** لقطة لجميع ترجمات المنتج المطلوبة.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `order_item_id` (BIGINT UNSIGNED, NOT NULL)
  - FOREIGN KEY (`order_item_id`) REFERENCES `maa_orders_order_items.id`
  - ON DELETE RESTRICT
  - ON UPDATE RESTRICT
- `language_code` (VARCHAR(16), NOT NULL): (BCP-47)
- `product_name` (VARCHAR(255), NOT NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`order_item_id`, `language_code`)

### 13.9 `maa_orders_order_item_option_values`
**الغرض:** لقطة الخيارات المحددة للعنصر.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `order_item_id` (BIGINT UNSIGNED, NOT NULL)
  - FOREIGN KEY (`order_item_id`) REFERENCES `maa_orders_order_items.id`
  - ON DELETE RESTRICT
  - ON UPDATE RESTRICT
- `option_id` (BIGINT UNSIGNED, NOT NULL): مرجع تاريخي.
- `option_code` (VARCHAR(100), NOT NULL)
- `option_value_id` (BIGINT UNSIGNED, NOT NULL): مرجع تاريخي.
- `option_value_code` (VARCHAR(100), NOT NULL)
- `price_adjustment` (DECIMAL(20,6), NOT NULL DEFAULT 0.00)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`order_item_id`, `option_id`)

### 13.10 `maa_orders_order_item_option_value_translations`
**الغرض:** لقطة ترجمات الخيارات والقيم للعنصر.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `item_option_value_id` (BIGINT UNSIGNED, NOT NULL)
  - FOREIGN KEY (`item_option_value_id`) REFERENCES `maa_orders_order_item_option_values.id`
  - ON DELETE RESTRICT
  - ON UPDATE RESTRICT
- `language_code` (VARCHAR(16), NOT NULL): (BCP-47)
- `option_name` (VARCHAR(255), NULL)
- `option_value_name` (VARCHAR(255), NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)
**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`item_option_value_id`, `language_code`)
  - CHECK (`option_name` IS NOT NULL OR `option_value_name` IS NOT NULL)

### 13.11 `maa_orders_stock_reservations`
**الغرض:** محرك حجز المخزون للعنصر.
- `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- `order_item_id` (BIGINT UNSIGNED, NOT NULL)
  - FOREIGN KEY (`order_item_id`) REFERENCES `maa_orders_order_items.id`
  - ON DELETE RESTRICT
  - ON UPDATE RESTRICT
- `quantity` (INT UNSIGNED, NOT NULL)
- `status` (VARCHAR(32), NOT NULL)
- `created_at` (DATETIME, NOT NULL)
- `updated_at` (DATETIME, NOT NULL)

**القيود:**
  - PRIMARY KEY (`id`)
  - UNIQUE (`order_item_id`)
  - CHECK (`quantity` > 0)
  - CHECK (`status` IN ('reserved', 'consumed', 'released', 'expired'))
**الفهارس:** `idx_reservations_status` (`status`)
