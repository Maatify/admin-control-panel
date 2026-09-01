# خطة إصلاح Namespacing لمفاتيح بيئة Admin

## الحالة

هذه الوثيقة هي خطة مراجعة فقط.

- الفرع: `codex/admin-env-namespacing-study`
- لا تحتوي المرحلة الحالية على تعديل تشغيلي للكود.
- التنفيذ يبدأ فقط بعد اعتماد هذه الخطة.

## الهدف

منع تعارض مفاتيح بيئة Admin عند تشغيل Admin مع تطبيق آخر داخل نفس المستضيف أو نفس عملية التشغيل، مع الحفاظ على عقود الموديولات العامة وعدم تعديل أي موديول يقرأ مفتاح البيئة مباشرة.

## حدود التغيير

المواضع المسموح تعديلها في مرحلة التنفيذ:

1. `.env`
2. `.env.example`
3. `.env.test`
4. `app/Modules/AdminKernel/**`
5. `public/index.php`

أي ملف خارج هذه الحدود لا يتم تعديله تلقائيًا. وبالأخص لا يتم تعديل أي ملف داخل `Modules/**`.

## عقد التسمية المعتمد للمراجعة

تُستخدم مقدمة `ADMIN_` للمفاتيح التي يملكها Admin، مع إبقاء العقد الداخلي للموديولات كما هو.

| الاسم الحالي | الاسم المقترح | الحالة |
|---|---|---|
| `APP_ENV` | `ADMIN_APP_ENV` | ضمن التنفيذ المقترح |
| `DB_*` | `ADMIN_DB_*` | ضمن التنفيذ المقترح |
| `MAIL_*` | `ADMIN_MAIL_*` | ضمن التنفيذ المقترح |
| `CRYPTO_*` | `ADMIN_CRYPTO_*` | ضمن التنفيذ المقترح |
| `PASSWORD_*` | `ADMIN_PASSWORD_*` | ضمن التنفيذ المقترح |
| `TOTP_*` | `ADMIN_TOTP_*` | ضمن التنفيذ المقترح |
| `DO_SPACES_*` | `ADMIN_DO_SPACES_*` | ضمن التنفيذ المقترح، مع Adapter في `public/index.php` |
| `ASSETS_CDN_URL` | `ADMIN_ASSETS_CDN_URL` | ضمن التنفيذ المقترح |
| `CDN_IMAGE_URL` | `ADMIN_CDN_IMAGE_URL` | ضمن التنفيذ المقترح |
| `ASSET_VERSION` | `ADMIN_ASSET_VERSION` | ضمن التنفيذ المقترح |

### استثناءات إلزامية

هذه المفاتيح لا تُغير أسماءها أو قيمها أو طريقة التعامل معها:

1. `LOG_PATH`
2. `LOG_RETENTION_DAYS`
3. `LOG_TIMEZONE`
4. `STORAGE_DRIVER`

`STORAGE_DRIVER` مفتاح مشترك؛ لا يوجد `ADMIN_STORAGE_DRIVER`، ولا يتم تعديل `StorageConfig`.

`ADMIN_URL` يظل كما هو لأنه بالفعل يحمل Namespacing خاصًا بـAdmin.

## قاعدة الموديولات

الموديولات التي تقرأ أسماء عامة مثل `DO_SPACES_*` أو `STORAGE_DRIVER` لا يتم تعديلها.

عند الحاجة، يقوم `public/index.php` ببناء مصفوفة Adapter محلية:

```text
ADMIN_DO_SPACES_*  ->  DO_SPACES_*
STORAGE_DRIVER     ->  STORAGE_DRIVER
```

ثم يمررها إلى العقد العامة للموديول. بذلك يكون العزل في حدود Admin فقط، ولا يتغير الموديول القابل لإعادة الاستخدام.

أما مفاتيح `CRYPTO_*` و`PASSWORD_*` التي تظهر داخل Adapters الداخلية فهي أسماء مصفوفات داخلية وليست قراءة جديدة من بيئة التشغيل، ولذلك لا تُعامل كـEnvironment namespace مستقل.

## مسارات القراءة التي يجب تغطيتها

### AdminKernel

تحديث [AdminRuntimeConfigDTO.php](../../app/Modules/AdminKernel/Kernel/DTO/AdminRuntimeConfigDTO.php) ليقرأ الأسماء الجديدة، مع بقاء التحقق Fail-Closed.

يشمل ذلك تمرير القيم إلى إعدادات:

- قاعدة البيانات
- البريد
- التشفير
- Password peppers
- TOTP
- إعدادات الوسائط

### public/index.php

تحديث [public/index.php](../../public/index.php) ليقوم بتحميل مفاتيح Admin الجديدة، ثم تحويل مفاتيح التخزين التي يحتاجها `StorageConfig` إلى عقد الموديول العامة.

لا يتم تمرير متغيرات Admin العامة إلى الموديول كما هي، ولا يتم تعديل `Modules/Storage`.

## آثار خارج الحدود الحالية

ظهر أثناء الجرد أن بعض الملفات خارج `AdminKernel` و`public/index.php` تقرأ أسماء عامة:

- `public/api-tester.php` يقرأ `APP_ENV`.
- `tests/bootstrap.php` و`tests/Support/MySQLTestHelper.php` يستخدمان `APP_ENV` و`DB_*`.
- `phpunit.xml` يحقن `APP_ENV`.

هذه الملفات ليست موديولات، لكنها خارج حدود التعديل المعتمدة حاليًا. لذلك يجب قبل التنفيذ اختيار أحد المسارين:

1. اعتماد أنها خارج نطاق هذه المرحلة، مع تسجيل أن أدوات الاختبار و`api-tester` لن تتبع العقد الجديدة.
2. توسيع نطاق التنفيذ صراحةً ليشمل ملفات التشغيل والاختبارات اللازمة فقط.

لا يتم اختيار المسار الثاني تلقائيًا.

## مفاتيح تحتاج قرارًا منفصلًا

ملف البيئة يحتوي مفاتيح أخرى قابلة للتداخل، لكنها ليست ضمن قائمة التسمية المعتمدة الحالية، مثل:

- `APP_DEBUG`
- `APP_TIMEZONE`
- `APP_NAME`
- `ASSET_BASE_URL`
- `LOGO_URL`
- `HOST_TEMPLATE_PATH`
- `EMAIL_BLIND_INDEX_KEY`
- `LOCAL_BASE_PATH`
- `LOCAL_BASE_URL`
- مفاتيح Captcha و`ABUSE_CHALLENGE_PROVIDER`
- `RECOVERY_MODE`

لن يتم تغيير هذه المفاتيح ضمن التنفيذ قبل اعتماد وضعها صراحةً؛ إبقاؤها عامة يعني أنها تظل مرشحة للتداخل.

## تسلسل التنفيذ بعد الاعتماد

1. تثبيت مصفوفة الأسماء النهائية وعدم إضافة مفاتيح غير معتمدة.
2. تحديث `.env.example` أولًا كمرجع canonical.
3. تحديث `.env` و`.env.test` بنفس الأسماء، مع الحفاظ على القيم السرية نفسها.
4. تحديث `AdminRuntimeConfigDTO` داخل `AdminKernel`.
5. تحديث `public/index.php` وAdapter التخزين فقط.
6. التأكد من أن `STORAGE_DRIVER` ومفاتيح Logging الثلاثة لم تتغير.
7. منع fallback للمفاتيح القديمة داخل Runtime الخاص بـAdmin.
8. فحص جميع القراءات القديمة وتوثيق أي قراءة مسموحة داخل عقد موديول عام.

## حماية البيانات

إعادة تسمية متغير البيئة لا تعني تدوير السر. يجب إبقاء القيم كما هي بالنسبة إلى:

- `CRYPTO_KEYS`
- `CRYPTO_ACTIVE_KEY_ID`
- `PASSWORD_PEPPERS`
- `PASSWORD_ACTIVE_PEPPER_ID`
- مفتاح الـblind index

لا توجد Migration لقاعدة البيانات في هذه المرحلة.

## التحقق وقبول المرحلة

لا تعتبر المرحلة مكتملة إلا بعد:

1. نجاح Boot من `public/index.php` بالمفاتيح الجديدة.
2. نجاح إنشاء `AdminRuntimeConfigDTO` دون المفاتيح القديمة.
3. نجاح تهيئة Storage عبر Adapter دون تعديل الموديول.
4. إثبات عدم تغير مفاتيح Logging الثلاثة و`STORAGE_DRIVER`.
5. فحص ساكن لعدم وجود قراءات Admin قديمة، مع استثناء عقود الموديولات الداخلية والملفات المستبعدة صراحةً.
6. تشغيل بوابات المشروع المتاحة: `composer analyse` و`composer test` وPermission Linter و`git diff --check`.

إذا تعذر تشغيل الاختبارات بسبب بقاء `APP_ENV` أو `DB_*` في ملفات الاختبار خارج النطاق، تُسجل النتيجة كأثر معروف ولا يتم التحايل عليه بإعادة مفاتيح عامة إلى Runtime الخاص بالإنتاج.

## المرحلة اللاحقة: النسخة المرجعية والـDeployment

بعد اعتماد وتنفيذ هذا التغيير في Admin Control Panel، تُفتح مرحلة مستقلة لتطبيق نفس العقد على النسخة المرجعية ومصادر التشغيل:

- Secret Manager
- CI/CD
- Workers
- Cron/Systemd/Supervisor
- متغيرات الحاويات أو المستضيف
- Health checks

تتم عملية الانتقال بتحديث الأسماء والقيم معًا، ثم إزالة الاعتماد على الأسماء القديمة بعد نجاح التحقق. مفاتيح `LOG_*` الثلاثة و`STORAGE_DRIVER` تظل مستثناة في هذه المرحلة أيضًا.
