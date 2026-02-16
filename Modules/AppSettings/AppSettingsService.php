<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 20:52
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AppSettings;

use PDOException;
use Maatify\AppSettings\Enum\AppSettingValueTypeEnum;
use Maatify\AppSettings\Exception\DuplicateAppSettingException;
use Maatify\AppSettings\Repository\AppSettingsRepositoryInterface;
use Maatify\AppSettings\DTO\AppSettingDTO;
use Maatify\AppSettings\DTO\AppSettingKeyDTO;
use Maatify\AppSettings\DTO\AppSettingUpdateDTO;
use Maatify\AppSettings\DTO\AppSettingsQueryDTO;
use Maatify\AppSettings\Policy\AppSettingsWhitelistPolicy;
use Maatify\AppSettings\Policy\AppSettingsProtectionPolicy;
use Maatify\AppSettings\Exception\AppSettingNotFoundException;
use Maatify\AppSettings\Exception\InvalidAppSettingException;
use UnexpectedValueException;

/**
 * Class: AppSettingsService
 *
 * Canonical implementation of AppSettingsServiceInterface.
 */
final class AppSettingsService implements AppSettingsServiceInterface
{
    public function __construct(
        private readonly AppSettingsRepositoryInterface $repository,
        private readonly AppSettingsWhitelistPolicy $whitelistPolicy,
        private readonly AppSettingsProtectionPolicy $protectionPolicy,
    ) {
    }

    public function get(string $group, string $key): string
    {
        $this->whitelistPolicy->assertAllowed($group, $key);

        $row = $this->repository->findOne($group, $key, true);

        if ($row === null) {
            throw new AppSettingNotFoundException(
                sprintf('Setting "%s.%s" not found or inactive', $group, $key)
            );
        }

        $value = $row['setting_value'] ?? null;

        if (! is_string($value)) {
            throw new UnexpectedValueException(
                sprintf('Invalid value type for setting "%s.%s"', $group, $key)
            );
        }

        return $value;
    }

    public function getTyped(string $group, string $key): mixed
    {
        $this->whitelistPolicy->assertAllowed($group, $key);

        $row = $this->repository->findOne($group, $key, true);

        if ($row === null) {
            throw new AppSettingNotFoundException(
                sprintf('Setting "%s.%s" not found or inactive', $group, $key)
            );
        }

        $value = $row['setting_value'];
        $typeStr = $row['setting_type'] ?? 'string';
        $type = AppSettingValueTypeEnum::tryFrom($typeStr) ?? AppSettingValueTypeEnum::STRING;

        return match ($type) {
            AppSettingValueTypeEnum::INT => (int)$value,
            AppSettingValueTypeEnum::BOOL => in_array(strtolower($value), ['true', '1'], true),
            AppSettingValueTypeEnum::JSON => json_decode($value, true) ?? [],
            default => (string)$value,
        };
    }

    public function has(string $group, string $key): bool
    {
        $this->whitelistPolicy->assertAllowed($group, $key);

        return $this->repository->exists($group, $key, true);
    }

    public function getGroup(string $group): array
    {
        // 1. Fetch all active settings for the group (no limit)
        $rows = $this->repository->findAllByGroup($group);

        $result = [];

        // 2. Iterate and filter securely
        foreach ($rows as $row) {
            $keyName = $row['setting_key'] ?? null;
            $value   = $row['setting_value'] ?? null;

            if (! is_string($keyName) || ! is_string($value)) {
                continue;
            }

            // 3. Check whitelist for each key individually
            if ($this->whitelistPolicy->isAllowed($group, $keyName)) {
                $result[$keyName] = $value;
            }
        }

        return $result;
    }

    public function create(AppSettingDTO $dto): void
    {
        $this->whitelistPolicy->assertAllowed($dto->group, $dto->key);

        $this->validateValue($dto->value, $dto->valueType);

        try {
            $this->repository->insert($dto);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 || $e->getCode() == 1062) {
                throw new DuplicateAppSettingException(
                    sprintf('Setting "%s.%s" already exists', $dto->group, $dto->key),
                    previous: $e
                );
            }
            throw $e;
        }
    }

    public function update(AppSettingUpdateDTO $dto): void
    {
        $this->whitelistPolicy->assertAllowed($dto->group, $dto->key);

        $key = new AppSettingKeyDTO($dto->group, $dto->key);

        $this->protectionPolicy->assertNotProtected($key);

        // Fetch existing to get current type if not provided, and ensure exists
        $row = $this->repository->findOne($dto->group, $dto->key, false);

        if ($row === null) {
            throw new AppSettingNotFoundException(
                sprintf('Setting "%s.%s" does not exist', $dto->group, $dto->key)
            );
        }

        $currentTypeStr = $row['setting_type'] ?? 'string';
        $currentType = AppSettingValueTypeEnum::tryFrom($currentTypeStr) ?? AppSettingValueTypeEnum::STRING;
        $targetType = $dto->valueType ?? $currentType;

        $this->validateValue($dto->value, $targetType);

        // Repository now handles setting_type if present in DTO
        $this->repository->updateValue($dto);
    }

    public function setActive(AppSettingKeyDTO $key, bool $isActive): void
    {
        $this->whitelistPolicy->assertAllowed($key->group, $key->key);

        $this->protectionPolicy->assertNotProtected($key);

        if (! $this->repository->exists($key->group, $key->key, false)) {
            throw new AppSettingNotFoundException(
                sprintf('Setting "%s.%s" does not exist', $key->group, $key->key)
            );
        }

        $this->repository->setActiveStatus($key, $isActive);
    }

    /**
     * Admin query with metadata enrichment.
     */
    public function query(AppSettingsQueryDTO $query): array
    {
        $rows = $this->repository->query($query);

        foreach ($rows as &$row) {
            $group = $row['setting_group'] ?? null;
            $key   = $row['setting_key'] ?? null;

            if (! is_string($group) || ! is_string($key)) {
                // Skip corrupted row safely
                $row['is_protected'] = false;
                $row['is_editable']  = false;
                continue;
            }

            $isProtected = $this->protectionPolicy->isProtected($group, $key);

            $row['is_protected'] = $isProtected;
            $row['is_editable']  = ! $isProtected;
        }

        return $rows;
    }

    private function validateValue(string $value, AppSettingValueTypeEnum $type): void
    {
        switch ($type) {
            case AppSettingValueTypeEnum::INT:
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    throw new InvalidAppSettingException(
                        sprintf('Value "%s" is not a valid integer', $value)
                    );
                }
                break;

            case AppSettingValueTypeEnum::BOOL:
                if (! in_array(strtolower($value), ['true', 'false', '1', '0'], true)) {
                    throw new InvalidAppSettingException(
                        sprintf('Value "%s" is not a valid boolean', $value)
                    );
                }
                break;

            case AppSettingValueTypeEnum::JSON:
                json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidAppSettingException(
                        'Value is not valid JSON: ' . json_last_error_msg()
                    );
                }
                break;

            case AppSettingValueTypeEnum::STRING:
            default:
                break;
        }
    }
}
