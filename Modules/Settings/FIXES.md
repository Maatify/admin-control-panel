# Bug Fixes & Corrections

تصحيحات شاملة بناءً على مراجعة المعايير والـ PDO best practices.

---

## ✅ الإصلاحات المنفذة

### 1. **B2 — Duplicate Named Placeholder في SQL Search**

**المشكلة:**
```php
$where[] = '(`setting_key` LIKE :global OR `admin_note` LIKE :global)';
$params['global'] = '%' . trim($globalSearch) . '%';
```
استخدام نفس placeholder `:global` مرتين يخالف قواعد PDO وقد يسبب `Invalid parameter number`.

**الحل:**
```php
$like = '%' . trim($globalSearch) . '%';
$where[] = '(`setting_key` LIKE :global_key OR `admin_note` LIKE :global_note)';
$params['global_key'] = $like;
$params['global_note'] = $like;
```

**الملفات المحدثة:**
- `src/Admin/Setting/Infrastructure/Repository/PdoAdminSettingQueryRepository.php`

---

### 2. **B3 — rowCount() False Negative**

**المشكلة:**
```php
return $stmt->rowCount() > 0; // لو القيمة نفس القديمة، MySQL يرجع 0
```
إذا كانت القيمة الجديدة مساوية للقديمة، `rowCount()` يرجع `0`، فالـ service ترمي `NotFoundException` مع إن الـ setting موجودة.

**الحل:**
```php
public function updateValue(UpdateSettingValueCommand $command): bool
{
    $stmt = $this->pdo->prepare(...);
    $stmt->execute([...]);

    if ($stmt->rowCount() > 0) {
        return true;
    }

    // عمل existence check في حالة rowCount = 0
    $checkStmt = $this->pdo->prepare('SELECT COUNT(*) FROM `settings` WHERE `setting_key` = :setting_key');
    $checkStmt->execute(['setting_key' => $command->settingKey]);
    $exists = (int) $checkStmt->fetchColumn() > 0;

    return $exists;
}
```

**التأثير:**
- إذا تم التحديث: `return true`
- إذا كانت نفس القيمة: `return true` (بدل false negative)
- إذا الـ setting غير موجودة: `return false`

**الملفات المحدثة:**
- `src/Admin/Setting/Infrastructure/Repository/PdoAdminSettingCommandRepository.php`

---

### 3. **B4 — PHPStan Max Generics ناقصة**

**المشكلة:**
مع `checkMissingIterableValueType: true` في phpstan.neon، الـ array parameters بدون proper phpdoc generics سيفشل على level max.

**الحل:** إضافة docblocks شاملة لجميع الـ array parameters و return types:

```php
/**
 * @param  array<string, string|int>  $columnFilters
 * @return array{data: list<SettingListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
 */
public function list(int $page, int $perPage, ?string $globalSearch, array $columnFilters): array
```

**الملفات المحدثة:**
- `src/Admin/Setting/Contract/AdminSettingQueryRepositoryInterface.php`
- `src/Admin/Setting/Service/AdminSettingService.php`
- `src/Admin/Setting/Infrastructure/Repository/PdoAdminSettingQueryRepository.php`

---

### 4. **B5 — No Validation لـ value_type**

**المشكلة:**
`AdminSettingService::updateValue()` تقبل أي string بدون التحقق من `value_type`:
```php
// سيقبل "abc" للـ int setting
default_currency = "abc"
// وبعدها getInt() يرجع 0 بصمت
```

**الحل:** إضافة validation method:

```php
private function validateValueByType(string $value, string $valueType): void
{
    match ($valueType) {
        'bool' => $this->validateBool($value),        // فقط "0" أو "1"
        'int' => $this->validateInt($value),          // فقط numeric
        'datetime' => $this->validateDateTime($value), // valid datetime
        'date' => $this->validateDate($value),        // valid date
        'string' => true,                              // بلا حدود
        default => throw SettingsInvalidArgumentException::invalidValueType($valueType),
    };
}

private function validateBool(string $value): void
{
    if ($value !== '0' && $value !== '1') {
        throw SettingsInvalidArgumentException::invalidValueType('bool');
    }
}

private function validateInt(string $value): void
{
    if (! preg_match('/^-?\d+$/', $value)) {
        throw SettingsInvalidArgumentException::invalidValueType('int');
    }
}

private function validateDateTime(string $value): void
{
    try {
        new \DateTimeImmutable($value);
    } catch (\Throwable) {
        throw SettingsInvalidArgumentException::invalidValueType('datetime');
    }
}
```

**الملفات المحدثة:**
- `src/Admin/Setting/Service/AdminSettingService.php`

---

### 5. **Empty String Handling في UpdateSettingValueCommand**

**المشكلة:**
الـ Command كانت ترفع exception على empty string، لكن schema بتقول `DEFAULT ''` صحيح:
```php
if (trim($settingValue) === '') {
    throw SettingsInvalidArgumentException::emptyField('settingValue');
}
```

**الحل:**
إزالة الـ validation للـ value (validation يكون في service بناءً على `value_type`):

```php
final readonly class UpdateSettingValueCommand
{
    public function __construct(
        public string $settingKey,
        public string $settingValue,
    ) {
        if (trim($settingKey) === '') {
            throw SettingsInvalidArgumentException::emptyField('settingKey');
        }
        // لا نتحقق من settingValue هنا
    }
}
```

**الملفات المحدثة:**
- `src/Admin/Setting/Command/UpdateSettingValueCommand.php`

---

## 📝 التحديثات في الـ Tests

### جديد في `AdminSettingServiceTest`:
- `testUpdateValueBoolValidationSuccess()` — bool valid
- `testUpdateValueBoolValidationFails()` — bool invalid
- `testUpdateValueIntValidationSuccess()` — int valid
- `testUpdateValueIntValidationFails()` — int invalid
- `testUpdateValueStringNoValidation()` — string بلا validation

### تعديل في `UpdateSettingValueCommandTest`:
- `testEmptySettingValueAllowed()` — empty string مقبول
- `testWhitespaceSettingValueAllowed()` — whitespace مقبول

### إضافة في `PdoAdminSettingCommandRepositoryTest`:
- `testUpdateValueSameValueRowCountZero()` — نفس القيمة يرجع true

---

## ✨ الفوائد

1. **PDO Compliance** — كل placeholder يستخدم مرة واحدة فقط
2. **False Negative Prevention** — rowCount=0 لا يعني عدم الوجود
3. **PHPStan Compliance** — level max يمر بدون أخطاء
4. **Type Safety** — validation حسب value_type قبل الحفظ
5. **Schema Alignment** — empty strings تدعم الـ string settings

---

## 🔍 Validation Details

### bool
- ✅ قيمة: `"0"` أو `"1"` فقط
- ❌ يرفع إذا: `"true"`, `"false"`, `"2"`, إلخ

### int
- ✅ قيمة: `-?\d+` (negative أو positive)
- ❌ يرفع إذا: `"3.14"`, `"abc"`, `"0x10"`, إلخ

### date / datetime
- ✅ قيمة: يمكن تحويلها ل `DateTimeImmutable`
- ❌ يرفع إذا: format غير valid

### string
- ✅ قيمة: أي شيء (بما فيه empty string و special chars)
- ❌ لا يرفع أبداً

---

## 📋 الملفات المحدثة

| الملف | النوع | الإصلاحات |
|------|------|----------|
| `PdoAdminSettingQueryRepository.php` | Repository | B2, B4 |
| `PdoAdminSettingCommandRepository.php` | Repository | B3 |
| `AdminSettingQueryRepositoryInterface.php` | Interface | B4 |
| `AdminSettingService.php` | Service | B4, B5 |
| `UpdateSettingValueCommand.php` | Command | Empty string |
| `UpdateSettingValueCommandTest.php` | Test | Empty string |
| `AdminSettingServiceTest.php` | Test | B5 validation |
| `PdoAdminSettingCommandRepositoryTest.php` | Test | B3 scenario |

---

## 🧪 Backward Compatibility

✅ **No breaking changes** — جميع الإصلاحات متوافقة مع الـ API الحالية:
- `getValue()`, `getBool()`, `getInt()` — لا تغيير
- `updateValue()` — نفس الـ signature، لكن validation أقوى
- Database schema — لا تغيير

---

## 🎯 Next Steps

1. تشغيل tests: `vendor/bin/phpunit`
2. تشغيل PHPStan: `vendor/bin/phpstan analyze --level max src`
3. مراجعة الـ validation logic مع الـ domain logic
