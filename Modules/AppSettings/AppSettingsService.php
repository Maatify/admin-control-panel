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

use Maatify\AppSettings\Repository\AppSettingsRepositoryInterface;
use Maatify\AppSettings\DTO\AppSettingDTO;
use Maatify\AppSettings\DTO\AppSettingKeyDTO;
use Maatify\AppSettings\DTO\AppSettingUpdateDTO;
use Maatify\AppSettings\DTO\AppSettingsQueryDTO;
use Maatify\AppSettings\Policy\AppSettingsWhitelistPolicy;
use Maatify\AppSettings\Policy\AppSettingsProtectionPolicy;
use Maatify\AppSettings\Exception\AppSettingNotFoundException;
use Maatify\AppSettings\Exception\InvalidAppSettingException;

/**
 * Class: AppSettingsService
 *
 * Canonical implementation of AppSettingsServiceInterface.
 *
 * Responsibilities:
 * - Enforce whitelist rules
 * - Enforce protection rules
 * - Respect is_active semantics
 * - Delegate persistence to repository
 *
 * Forbidden:
 * - SQL
 * - HTTP concerns
 * - Silent failures
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
            throw new \UnexpectedValueException(
                sprintf('Invalid value type for setting "%s.%s"', $group, $key)
            );
        }

        return $value;
    }

    public function has(string $group, string $key): bool
    {
        $this->whitelistPolicy->assertAllowed($group, $key);

        return $this->repository->exists($group, $key, true);
    }

    public function getGroup(string $group): array
    {
        $this->whitelistPolicy->assertAllowed($group, '*');

        $query = new AppSettingsQueryDTO(
            page    : 1,
            perPage : 10_000,
            search  : null,
            group   : $group,
            isActive: true
        );

        $rows = $this->repository->query($query);

        $result = [];

        foreach ($rows as $row) {
            $keyName = $row['setting_key'] ?? null;
            $value   = $row['setting_value'] ?? null;

            if (! is_string($keyName) || ! is_string($value)) {
                continue;
            }

            $result[$keyName] = $value;
        }

        return $result;
    }

    public function create(AppSettingDTO $dto): void
    {
        $this->whitelistPolicy->assertAllowed($dto->group, $dto->key);

        if ($this->repository->exists($dto->group, $dto->key, false)) {
            throw new InvalidAppSettingException(
                sprintf('Setting "%s.%s" already exists', $dto->group, $dto->key)
            );
        }

        $this->repository->insert($dto);
    }

    public function update(AppSettingUpdateDTO $dto): void
    {
        $this->whitelistPolicy->assertAllowed($dto->group, $dto->key);

        $key = new AppSettingKeyDTO($dto->group, $dto->key);

        $this->protectionPolicy->assertNotProtected($key);

        if (! $this->repository->exists($dto->group, $dto->key, false)) {
            throw new AppSettingNotFoundException(
                sprintf('Setting "%s.%s" does not exist', $dto->group, $dto->key)
            );
        }

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
     *
     * Adds:
     * - is_protected
     * - is_editable
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
}
