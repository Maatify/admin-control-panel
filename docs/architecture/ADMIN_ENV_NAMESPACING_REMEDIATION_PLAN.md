# خطة إصلاح Namespacing لمفاتيح بيئة Admin

## الحالة

هذه PR توثيقية فقط.

- الفرع: `codex/admin-env-namespacing-study`
- لا يوجد فيها تعديل Runtime أو تعديل اختبار أو تعديل موديول.
- التنفيذ يبدأ في مرحلة مستقلة بعد اعتماد هذه الخطة.

## الهدف

منع تعارض مفاتيح بيئة Admin عند تشغيله مع تطبيق آخر داخل نفس المستضيف أو نفس عملية التشغيل، مع إبقاء عقود الموديولات القابلة لإعادة الاستخدام كما هي.

## قاعدة الملكية والتسمية

كل Environment input يملكه Admin يستخدم مقدمة `ADMIN_`، ولا تتم إضافة fallback لاسم Admin العام القديم.

الاستثناءات العامة الوحيدة المقفلة هي:

1. `LOG_PATH`
2. `LOG_RETENTION_DAYS`
3. `LOG_TIMEZONE`
4. `STORAGE_DRIVER`

`ADMIN_URL` يظل كما هو لأنه بالفعل Admin-namespaced.

## مصفوفة الأسماء النهائية

### Application وواجهة Admin

| الاسم الحالي | الاسم النهائي |
|---|---|
| `APP_ENV` | `ADMIN_APP_ENV` |
| `APP_DEBUG` | `ADMIN_APP_DEBUG` |
| `APP_TIMEZONE` | `ADMIN_APP_TIMEZONE` |
| `APP_NAME` | `ADMIN_APP_NAME` |
| `ADMIN_URL` | `ADMIN_URL` — استثناء مقفول |
| `ASSET_BASE_URL` | `ADMIN_ASSET_BASE_URL` |
| `LOGO_URL` | `ADMIN_LOGO_URL` |
| `HOST_TEMPLATE_PATH` | `ADMIN_HOST_TEMPLATE_PATH` |

### Database

| الاسم الحالي | الاسم النهائي |
|---|---|
| `DB_HOST` | `ADMIN_DB_HOST` |
| `DB_NAME` | `ADMIN_DB_NAME` |
| `DB_USER` | `ADMIN_DB_USER` |
| `DB_PASS` | `ADMIN_DB_PASS` |

### Security وCrypto وPassword

| الاسم الحالي | الاسم النهائي |
|---|---|
| `EMAIL_BLIND_INDEX_KEY` | `ADMIN_EMAIL_BLIND_INDEX_KEY` |
| `CRYPTO_KEYS` | `ADMIN_CRYPTO_KEYS` |
| `CRYPTO_ACTIVE_KEY_ID` | `ADMIN_CRYPTO_ACTIVE_KEY_ID` |
| `PASSWORD_PEPPERS` | `ADMIN_PASSWORD_PEPPERS` |
| `PASSWORD_ACTIVE_PEPPER_ID` | `ADMIN_PASSWORD_ACTIVE_PEPPER_ID` |
| `PASSWORD_ARGON2_OPTIONS` | `ADMIN_PASSWORD_ARGON2_OPTIONS` |
| `TOTP_ISSUER` | `ADMIN_TOTP_ISSUER` |
| `TOTP_ENROLLMENT_TTL_SECONDS` | `ADMIN_TOTP_ENROLLMENT_TTL_SECONDS` |

### Mail

| الاسم الحالي | الاسم النهائي |
|---|---|
| `MAIL_HOST` | `ADMIN_MAIL_HOST` |
| `MAIL_PORT` | `ADMIN_MAIL_PORT` |
| `MAIL_USERNAME` | `ADMIN_MAIL_USERNAME` |
| `MAIL_PASSWORD` | `ADMIN_MAIL_PASSWORD` |
| `MAIL_FROM_ADDRESS` | `ADMIN_MAIL_FROM_ADDRESS` |
| `MAIL_FROM_NAME` | `ADMIN_MAIL_FROM_NAME` |
| `MAIL_ENCRYPTION` | `ADMIN_MAIL_ENCRYPTION` |
| `MAIL_TIMEOUT_SECONDS` | `ADMIN_MAIL_TIMEOUT_SECONDS` |
| `MAIL_CHARSET` | `ADMIN_MAIL_CHARSET` |
| `MAIL_DEBUG_LEVEL` | `ADMIN_MAIL_DEBUG_LEVEL` |

### Abuse Protection وRecovery

| الاسم الحالي | الاسم النهائي |
|---|---|
| `TURNSTILE_SITE_KEY` | `ADMIN_TURNSTILE_SITE_KEY` |
| `TURNSTILE_SECRET_KEY` | `ADMIN_TURNSTILE_SECRET_KEY` |
| `HCAPTCHA_SITE_KEY` | `ADMIN_HCAPTCHA_SITE_KEY` |
| `HCAPTCHA_SECRET_KEY` | `ADMIN_HCAPTCHA_SECRET_KEY` |
| `RECAPTCHA_V2_SITE_KEY` | `ADMIN_RECAPTCHA_V2_SITE_KEY` |
| `RECAPTCHA_V2_SECRET_KEY` | `ADMIN_RECAPTCHA_V2_SECRET_KEY` |
| `ABUSE_CHALLENGE_PROVIDER` | `ADMIN_ABUSE_CHALLENGE_PROVIDER` |
| `RECOVERY_MODE` | `ADMIN_RECOVERY_MODE` |

### Storage وMedia

| الاسم الحالي | الاسم النهائي |
|---|---|
| `STORAGE_DRIVER` | `STORAGE_DRIVER` — مفتاح مشترك مقفول |
| `LOCAL_BASE_PATH` | `ADMIN_LOCAL_BASE_PATH` |
| `LOCAL_BASE_URL` | `ADMIN_LOCAL_BASE_URL` |
| `DO_SPACES_KEY` | `ADMIN_DO_SPACES_KEY` |
| `DO_SPACES_SECRET` | `ADMIN_DO_SPACES_SECRET` |
| `DO_SPACES_REGION` | `ADMIN_DO_SPACES_REGION` |
| `DO_SPACES_ENDPOINT` | `ADMIN_DO_SPACES_ENDPOINT` |
| `DO_SPACES_BUCKET` | `ADMIN_DO_SPACES_BUCKET` |
| `DO_SPACES_CDN_URL` | `ADMIN_DO_SPACES_CDN_URL` |
| `DO_SPACES_ACL` | `ADMIN_DO_SPACES_ACL` |
| `ASSETS_CDN_URL` | `ADMIN_ASSETS_CDN_URL` |
| `CDN_IMAGE_URL` | `ADMIN_CDN_IMAGE_URL` |
| `ASSET_VERSION` | `ADMIN_ASSET_VERSION` |

### Logging — لا تغيير

| الاسم | القرار |
|---|---|
| `LOG_PATH` | يظل كما هو |
| `LOG_RETENTION_DAYS` | يظل كما هو |
| `LOG_TIMEZONE` | يظل كما هو |

لا يتم إعادة تسمية هذه المفاتيح أو تغيير قيمها أو نقل ملكيتها في هذه الخطة.

## حدود التنفيذ المستقبلي

### ملفات البيئة

`.env.example` هو ملف البيئة الوحيد المتعقب والمسلّم ضمن PR التنفيذ.

أما `.env` و`.env.test` فهما حاليًا ضمن `.gitignore`؛ تحديثهما سيكون خطوة ترحيل محلية/تشغيلية وضمن إعداد الـDeployment، وليس ملفًا متعقبًا أو Deliverable من PR.

`Modules/Storage/.env.example` يظل عقدًا عامًا للموديول ولا يتم تعديله.

### AdminKernel

تحديث [AdminRuntimeConfigDTO.php](../../app/Modules/AdminKernel/Kernel/DTO/AdminRuntimeConfigDTO.php) ليقرأ كل أسماء Admin النهائية أعلاه، بما فيها `APP_DEBUG` و`APP_TIMEZONE` و`APP_NAME` و`EMAIL_BLIND_INDEX_KEY` وCaptcha وRecovery.

تحديث [MediaUrlConfigDTO.php](../../app/Modules/AdminKernel/Ui/Config/MediaUrlConfigDTO.php) صراحةً؛ فهو القارئ الفعلي لمفاتيح:

- `ADMIN_ASSETS_CDN_URL`
- `ADMIN_CDN_IMAGE_URL`
- `ADMIN_ASSET_VERSION`

ولا تُنسب مفاتيح Media إلى `AdminRuntimeConfigDTO`.

### public/index.php

يظل `public/index.php` حد تركيب المستضيف:

1. يمرر إعدادات Admin إلى `AdminKernel`.
2. يبني Adapter محليًا للتخزين.
3. يترجم:

```text
ADMIN_LOCAL_BASE_PATH   -> LOCAL_BASE_PATH
ADMIN_LOCAL_BASE_URL    -> LOCAL_BASE_URL
ADMIN_DO_SPACES_*       -> DO_SPACES_*
STORAGE_DRIVER           -> STORAGE_DRIVER
```

ثم يمرر المصفوفة الناتجة إلى `StorageConfig`. لا يتم تمرير مصفوفة Admin العامة إلى الموديول، ولا يتم تعديل `Modules/Storage`.

### api-tester وحدود الأمان

تحديث `public/api-tester.php` ضمن التنفيذ المستقبلي ليستخدم `ADMIN_APP_ENV` في قرار السماح المحلي/المنع.

لا يجوز أن يعتمد هذا الحد الأمني على `APP_ENV` عام يخص تطبيقًا آخر. يجب الحفاظ على التعليقات والـTODOs الموجودة أثناء التنفيذ.

### Test infrastructure

تحديث إعدادات الاختبار اللازمة لتستخدم العقد الجديدة باستمرار:

- `tests/bootstrap.php`
- `tests/Support/MySQLTestHelper.php`
- `phpunit.xml`

لا تُقبل الاختبارات وهي مكسورة بسبب بقاء أسماء Admin القديمة، ولا تتم إعادة إدخال متغيرات Admin العامة كحل توافق.

يتم فحص Scripts الحالية التي تستدعي `AdminRuntimeConfigDTO`، مثل Workers وBootstrap، للتأكد من أنها تعمل مع العقد الجديدة دون تعديل غير ضروري خارج النطاق.

## قاعدة الموديولات القابلة لإعادة الاستخدام

لا يتم تعديل أي ملف داخل `Modules/**`.

الأسماء العامة التي يحتاجها الموديول تظل داخل عقده الداخلي فقط. التحويل من أسماء Admin إلى هذه الأسماء يحدث عند حد التركيب في `public/index.php`، وبذلك لا يتأثر استخراج الموديول أو استخدامه في تطبيق آخر.

## حماية البيانات

إعادة تسمية Environment variable لا تعني تدوير السر. يجب الحفاظ على القيم نفسها بالنسبة إلى:

- `CRYPTO_KEYS`
- `CRYPTO_ACTIVE_KEY_ID`
- `PASSWORD_PEPPERS`
- `PASSWORD_ACTIVE_PEPPER_ID`
- `EMAIL_BLIND_INDEX_KEY`

لا توجد Migration لقاعدة البيانات بسبب إعادة التسمية وحدها.

## تسلسل التنفيذ

1. تحديث `.env.example` كمرجع Canonical متعقب.
2. تنفيذ ترحيل محلي لـ`.env` و`.env.test` دون اعتبارهما ملفات PR.
3. تحديث `AdminRuntimeConfigDTO` وكل قراءاته داخل `AdminKernel`.
4. تحديث `MediaUrlConfigDTO` بأسماء Media الجديدة.
5. تحديث `public/index.php` وAdapter التخزين المحلي.
6. تحديث `public/api-tester.php` وملفات Test infrastructure اللازمة.
7. التأكد من عدم تعديل `Modules/**` أو مفاتيح Logging أو `STORAGE_DRIVER`.
8. منع fallback للمفاتيح القديمة.
9. تحديث مصادر الـDeployment في مرحلة لاحقة وبشكل متزامن.

## معايير القبول

لا تعتبر مرحلة التنفيذ مكتملة إلا بعد إثبات الآتي:

1. نجاح Boot للـAdmin باستخدام أسماء Admin الجديدة فقط.
2. عدم وجود fallback إلى أسماء Admin العامة القديمة.
3. قراءة `AdminRuntimeConfigDTO` للأسماء المحسومة في هذه الوثيقة.
4. قراءة `MediaUrlConfigDTO` لأسماء Media الجديدة.
5. استخدام `public/api-tester.php` لعقد Admin في بوابة الأمان.
6. عمل Storage عبر Adapter حدود Admin دون تعديل `Modules/Storage`.
7. صحة تهيئة Local Storage وDigitalOcean Spaces.
8. بقاء `LOG_PATH` و`LOG_RETENTION_DAYS` و`LOG_TIMEZONE` و`STORAGE_DRIVER` دون تغيير.
9. بقاء `ADMIN_URL` دون تغيير.
10. اتباع Test infrastructure للأسماء الجديدة.
11. عدم وجود قراءات Admin قديمة غير مقصودة في الفحص الساكن.
12. نجاح `composer analyse`.
13. نجاح `composer test`.
14. نجاح Permission Linter.
15. نجاح `git diff --check`.
16. الحفاظ على كل التعليقات والـTODOs الموجودة.

## المرحلة اللاحقة: النسخة المرجعية والـDeployment

بعد اعتماد وتنفيذ هذا العقد في Admin Control Panel، تُفتح مرحلة مستقلة لتطبيق الأسماء نفسها على النسخة المرجعية ومصادر التشغيل:

- Secret Manager
- CI/CD
- Workers
- Cron/Systemd/Supervisor
- متغيرات الحاويات أو المستضيف
- Health checks

تتم عملية الانتقال بتحديث أسماء المتغيرات والقيم التشغيلية معًا، ثم إزالة الاعتماد على الأسماء القديمة بعد نجاح التحقق. لا يتم تدوير الأسرار لمجرد تغيير أسمائها، وتظل مفاتيح Logging الأربعة المستثناة (`LOG_*` الثلاثة و`STORAGE_DRIVER`) كما هي.
