# Custom Setting Value Types Guide

كيفية إضافة أنواع قيم مخصصة بدون تعديل الـ module.

---

## الطريقة

### 1. قم بإنشاء Custom Provider

```php
<?php

namespace App\Settings;

use Maatify\Settings\Shared\Contract\SettingValueTypeProviderInterface;
use Maatify\Settings\Exception\SettingsInvalidArgumentException;

final class CustomSettingValueTypeProvider implements SettingValueTypeProviderInterface
{
    private const TYPES = [
        'bool',
        'int',
        'string',
        'date',
        'datetime',
        'email',      // ← نوع مخصص
        'url',        // ← نوع مخصص
        'json',       // ← نوع مخصص
    ];

    /** @return list<string> */
    public function all(): array
    {
        return self::TYPES;
    }

    public function isValid(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public function label(string $type): string
    {
        return match ($type) {
            'bool' => 'Boolean',
            'int' => 'Integer',
            'string' => 'String',
            'date' => 'Date (YYYY-MM-DD)',
            'datetime' => 'DateTime (YYYY-MM-DD HH:MM:SS)',
            'email' => 'Email Address',
            'url' => 'URL',
            'json' => 'JSON',
            default => throw SettingsInvalidArgumentException::invalidValueType($type),
        };
    }

    public function validate(string $value, string $type): void
    {
        match ($type) {
            'bool' => $this->validateBool($value, $type),
            'int' => $this->validateInt($value, $type),
            'string' => $this->validateString($value, $type),
            'date' => $this->validateDate($value, $type),
            'datetime' => $this->validateDateTime($value, $type),
            'email' => $this->validateEmail($value, $type),
            'url' => $this->validateUrl($value, $type),
            'json' => $this->validateJson($value, $type),
            default => throw SettingsInvalidArgumentException::invalidValueType($type),
        };
    }

    // ── Built-in validators ────────────────────────────────

    private function validateBool(string $value, string $type): void
    {
        if ($value !== '0' && $value !== '1') {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $this->label($type));
        }
    }

    private function validateInt(string $value, string $type): void
    {
        if (! preg_match('/^-?\d+$/', $value)) {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $this->label($type));
        }
    }

    private function validateString(string $value, string $type): void
    {
        // strings accept any value
    }

    private function validateDate(string $value, string $type): void
    {
        try {
            new \DateTimeImmutable($value);
        } catch (\Throwable) {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $this->label($type));
        }
    }

    private function validateDateTime(string $value, string $type): void
    {
        try {
            new \DateTimeImmutable($value);
        } catch (\Throwable) {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $this->label($type));
        }
    }

    // ── Custom validators ──────────────────────────────────

    private function validateEmail(string $value, string $type): void
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $this->label($type));
        }
    }

    private function validateUrl(string $value, string $type): void
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $this->label($type));
        }
    }

    private function validateJson(string $value, string $type): void
    {
        json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw SettingsInvalidArgumentException::invalidValueForType($value, $this->label($type));
        }
    }
}
```

### 2. سجل الـ Provider في DI Container

```php
use DI\ContainerBuilder;
use App\Settings\CustomSettingValueTypeProvider;
use Maatify\Settings\Shared\Contract\SettingValueTypeProviderInterface;

$builder = new ContainerBuilder();

// Override default provider
$builder->addDefinitions([
    SettingValueTypeProviderInterface::class => CustomSettingValueTypeProvider::class,
]);

$container = $builder->build();
```

---

## الاستخدام

### في Admin

```php
$adminService = $container->get(AdminSettingService::class);

// يستخدم validation مخصص تلقائياً
$command = new UpdateSettingValueCommand('admin_email', 'invalid-email');
$adminService->updateValue($command);
// ✗ SettingsInvalidArgumentException: Invalid value [invalid-email] for type [Email Address].

$command = new UpdateSettingValueCommand('admin_email', 'admin@example.com');
$adminService->updateValue($command);  // ✓ OK
```

### في UI

```php
$typeProvider = $container->get(SettingValueTypeProviderInterface::class);

<select name="value_type">
    <?php foreach ($typeProvider->all() as $type): ?>
        <option value="<?= htmlspecialchars($type) ?>">
            <?= htmlspecialchars($typeProvider->label($type)) ?>
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
    <option value="email">Email Address</option>
    <option value="url">URL</option>
    <option value="json">JSON</option>
</select>
-->
```

---

## مثال متقدم: مع Configuration

```php
final class ConfigurableSettingValueTypeProvider implements SettingValueTypeProviderInterface
{
    /** @var array<string, callable(string): void> */
    private array $validators = [];

    /** @var array<string, string> */
    private array $labels = [];

    public function __construct(
        /** @var list<string> */
        private array $types,
        array $validators = [],
        array $labels = [],
    ) {
        $this->validators = $validators + $this->defaultValidators();
        $this->labels = $labels + $this->defaultLabels();
    }

    public function all(): array
    {
        return $this->types;
    }

    public function isValid(string $type): bool
    {
        return in_array($type, $this->types, true);
    }

    public function label(string $type): string
    {
        if (! $this->isValid($type)) {
            throw SettingsInvalidArgumentException::invalidValueType($type);
        }
        return $this->labels[$type] ?? $type;
    }

    public function validate(string $value, string $type): void
    {
        if (! $this->isValid($type)) {
            throw SettingsInvalidArgumentException::invalidValueType($type);
        }

        $validator = $this->validators[$type] ?? null;
        if ($validator) {
            $validator($value);
        }
    }

    /** @return array<string, callable(string): void> */
    private function defaultValidators(): array
    {
        return [
            'bool' => fn($v) => $this->validateBool($v),
            'int' => fn($v) => $this->validateInt($v),
            'string' => fn($v) => true,
        ];
    }

    /** @return array<string, string> */
    private function defaultLabels(): array
    {
        return [
            'bool' => 'Boolean',
            'int' => 'Integer',
            'string' => 'String',
        ];
    }

    // ... validation methods
}

// الاستخدام
$provider = new ConfigurableSettingValueTypeProvider(
    types: ['bool', 'int', 'string', 'email', 'custom'],
    validators: [
        'email' => fn($v) => filter_var($v, FILTER_VALIDATE_EMAIL) or 
            throw SettingsInvalidArgumentException::invalidValueForType($v, 'Email'),
        'custom' => fn($v) => preg_match('/^[A-Z]+$/', $v) or
            throw SettingsInvalidArgumentException::invalidValueForType($v, 'Custom Type'),
    ],
    labels: [
        'email' => 'Email Address',
        'custom' => 'Custom Type',
    ],
);

$container->set(SettingValueTypeProviderInterface::class, $provider);
```

---

## الفوائد

✅ **مرونة كاملة** — أضيف أي نوع تريده  
✅ **بدون تعديل الـ module** — يبقى نظيف وبسيط  
✅ **Dependency Injection** — سهل الـ testing  
✅ **Custom Validation** — كل مشروع حسب احتياجاته  
✅ **Configuration-Based** — بدون hardcoding  

---

## ملاحظات

- الـ provider يتم inject في `AdminSettingService` تلقائياً
- الـ default implementation يدعم الأنواع الأساسية
- يمكن ovveride جميع الـ labels والـ validators
- validation يحدث تلقائياً عند `updateValue()`
