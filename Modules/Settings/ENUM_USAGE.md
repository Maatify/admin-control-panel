# SettingValueType Enum — Internal Reference

This document covers the internal `SettingValueType` enum used by `DefaultSettingValueTypeProvider`. The enum is **not exposed** in DTO public contracts; DTOs use `string $valueType` for extensibility.

---

## Built-in Types

```php
use Maatify\Settings\Shared\SettingValueType;

SettingValueType::BOOL      // 'bool'
SettingValueType::INT       // 'int'
SettingValueType::STRING    // 'string'
SettingValueType::DATE      // 'date'
SettingValueType::DATETIME  // 'datetime'
```

---

## الاستخدام الأساسي

### الحصول على Enum من String

```php
$type = SettingValueType::fromValue('bool');
// SettingValueType::BOOL

$type = SettingValueType::fromValue('int');
// SettingValueType::INT

// الخطأ التالي يرفع exception
try {
    $type = SettingValueType::fromValue('invalid');
} catch (\ValueError $e) {
    // "Invalid setting value type: invalid. Allowed: bool, int, string, date, datetime."
}
```

### الحصول على القيمة String

```php
$type = SettingValueType::BOOL;
echo $type->value;  // "bool"
```

### الحصول على الـ Label الودود

```php
echo SettingValueType::BOOL->label();       // "Boolean"
echo SettingValueType::INT->label();        // "Integer"
echo SettingValueType::STRING->label();     // "String"
echo SettingValueType::DATE->label();       // "Date (YYYY-MM-DD)"
echo SettingValueType::DATETIME->label();   // "DateTime (YYYY-MM-DD HH:MM:SS)"
```

---

## الاستخدام في DTOs

### قراءة الإعداد

```php
use Maatify\Settings\Shared\Service\SettingValueService;
use Maatify\Settings\Shared\Contract\SettingValueTypeProviderInterface;

$adminService = $container->get(AdminSettingService::class);
$setting = $adminService->getByKey('default_currency');

// الـ valueType يكون string (قابل للتوسع)
echo $setting->valueType;  // "int"

// الـ provider بيوفر معلومات الـ type
$provider = $container->get(SettingValueTypeProviderInterface::class);
echo $provider->label($setting->valueType);  // "Integer"

// Type-safe check (provider محدد الأنواع المسموحة)
if ($provider->isValid($setting->valueType)) {
    echo "This is a valid type for this provider";
}
```

### JSON Serialization

```php
$json = json_encode($setting);
// {
//   "id": 2,
//   "setting_key": "default_currency",
//   "setting_value": "1",
//   "value_type": "int",  // ✓ string value
//   ...
// }
```

---

## Helper Methods

### الحصول على جميع الأنواع

```php
$allTypes = SettingValueType::all();
// [
//   SettingValueType::BOOL,
//   SettingValueType::INT,
//   SettingValueType::STRING,
//   SettingValueType::DATE,
//   SettingValueType::DATETIME,
// ]
```

### الحصول على جميع القيم String

```php
$values = SettingValueType::values();
// ["bool", "int", "string", "date", "datetime"]
```

---

## Validation في AdminSettingService

### Automatic Type Validation

```php
use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;

$adminService = $container->get(AdminSettingService::class);

// ✓ صحيح - القيمة تطابق النوع
$command = new UpdateSettingValueCommand('default_currency', '42');
$adminService->updateValue($command);  // OK

// ✗ خطأ - القيمة لا تطابق النوع
$command = new UpdateSettingValueCommand('default_currency', 'abc');
$adminService->updateValue($command);
// SettingsInvalidArgumentException::invalidValueForType('abc', 'Integer')
// "Invalid value [abc] for type [Integer]."
```

### Type-Specific Validation

```php
// bool: فقط "0" أو "1"
'maintenance' => '1'  // ✓ OK
'maintenance' => 'true'  // ✗ Error

// int: numeric فقط
'ttl' => '15'  // ✓ OK
'ttl' => '15.5'  // ✗ Error

// date: any format parseable by DateTimeImmutable
'backup_date' => '2026-05-11'  // ✓ OK
'backup_date' => '05/11/2026'  // ✓ OK (DateTimeImmutable parses many formats)
'backup_date' => 'invalid date'  // ✗ Error

// datetime: any format parseable by DateTimeImmutable
'last_sync' => '2026-05-11 14:30:00'  // ✓ OK
'last_sync' => '2026-05-11 14:30'  // ✓ OK (seconds optional)

// string: أي قيمة
'app_name' => 'MyApp'  // ✓ OK
'app_name' => ''  // ✓ OK
```

---

## في Templates أو Admin UI

```php
<select name="value_type">
    <?php foreach (SettingValueType::cases() as $type): ?>
        <option value="<?= $type->value ?>">
            <?= htmlspecialchars($type->label()) ?>
        </option>
    <?php endforeach; ?>
</select>

<!-- Output:
<select name="value_type">
    <option value="bool">Boolean</option>
    <option value="int">Integer</option>
    <option value="string">String</option>
    <option value="date">Date (YYYY-MM-DD)</option>
    <option value="datetime">DateTime (YYYY-MM-DD HH:MM:SS)</option>
</select>
-->
```

---

## Error Handling

### مع Exception Handling

```php
try {
    // محاولة تحديث إعداد
    $command = new UpdateSettingValueCommand('default_currency', 'not_a_number');
    $adminService->updateValue($command);
} catch (SettingsInvalidArgumentException $e) {
    // "Invalid value [not_a_number] for type [Integer]."
    echo $e->getMessage();
} catch (SettingsNotFoundException $e) {
    // "Setting with key [unknown] not found."
    echo $e->getMessage();
}
```

---

## Database Schema

الـ Enum يُخزن كـ string في قاعدة البيانات:

```sql
CREATE TABLE `settings` (
    ...
    `value_type` VARCHAR(16) NOT NULL,  -- 'bool', 'int', 'string', 'date', 'datetime'
    ...
);

-- The database stores `value_type` as string.
-- DTOs expose it as string for extensibility.
-- The enum is only used internally by `DefaultSettingValueTypeProvider` for built-in type helpers.
SELECT * FROM settings WHERE value_type = 'int';
```

---

## الفوائد

✅ **Type Safety** — بدل strings عشوائية  
✅ **Autocompletion** — IDE يقترح الأنواع المتاحة  
✅ **Validation** — تلقائياً عند التحديث  
✅ **Human-Readable Labels** — للـ admin UI  
✅ **No Magic Strings** — الأنواع محدودة ومعروفة  

---

## مثال كامل

```php
use Maatify\Settings\Admin\Setting\Service\AdminSettingService;
use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;
use Maatify\Settings\Shared\SettingValueType;

$adminService = $container->get(AdminSettingService::class);
$provider = $container->get(SettingValueTypeProviderInterface::class);

// الحصول على إعداد
$currency = $adminService->getByKey('default_currency');
echo "Type: " . $provider->label($currency->valueType);  // "Integer"
echo "Value: " . $currency->settingValue;  // "1"

// التحديث مع validation تلقائي
$command = new UpdateSettingValueCommand('default_currency', '3');
$adminService->updateValue($command);  // ✓ يعمل

// القراءة بدون استثناء
$valueService = $container->get(SettingValueService::class);
$currencyId = $valueService->getInt('default_currency');  // 3
```

---

## PHP 8.1+ Requirement

الـ Enums تتطلب PHP 8.1 أو أحدث. تأكد من:

```json
{
  "require": {
    "php": ">=8.1"
  }
}
```

المتطلب الحالي في Module هو `>=8.2` فهو يدعم Enums بالفعل ✓
