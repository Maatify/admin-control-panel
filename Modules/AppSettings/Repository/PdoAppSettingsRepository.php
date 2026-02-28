<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 20:45
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AppSettings\Repository;

use PDO;
use Maatify\AppSettings\DTO\AppSettingDTO;
use Maatify\AppSettings\DTO\AppSettingKeyDTO;
use Maatify\AppSettings\DTO\AppSettingUpdateDTO;
use Maatify\AppSettings\DTO\AppSettingsQueryDTO;

/**
 * Class: PdoAppSettingsRepository
 *
 * PDO-based MySQL implementation for AppSettingsRepositoryInterface.
 */
final readonly class PdoAppSettingsRepository implements AppSettingsRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function findOne(string $group, string $key, bool $onlyActive = true): ?array
    {
        $sql = '
        SELECT id, setting_group, setting_key, setting_value, setting_type, is_active
        FROM app_settings
        WHERE setting_group = :group
          AND setting_key = :key
    ';

        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':group', $group, PDO::PARAM_STR);
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->execute();

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $row;
    }

    public function findAllByGroup(string $group): array
    {
        $sql = '
            SELECT id, setting_group, setting_key, setting_value, setting_type, is_active
            FROM app_settings
            WHERE setting_group = :group
              AND is_active = 1
            ORDER BY setting_key ASC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':group', $group, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exists(string $group, string $key, bool $onlyActive = false): bool
    {
        $sql = '
            SELECT 1
            FROM app_settings
            WHERE setting_group = :group
              AND setting_key = :key
        ';

        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':group', $group, PDO::PARAM_STR);
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    public function insert(AppSettingDTO $dto): int
    {
        $sql = '
            INSERT INTO app_settings (
                setting_group,
                setting_key,
                setting_value,
                setting_type,
                is_active
            ) VALUES (
                :group,
                :key,
                :value,
                :type,
                :is_active
            )
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':group', $dto->group, PDO::PARAM_STR);
        $stmt->bindValue(':key', $dto->key, PDO::PARAM_STR);
        $stmt->bindValue(':value', $dto->value, PDO::PARAM_STR);
        $stmt->bindValue(':type', $dto->valueType->value, PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $dto->isActive, PDO::PARAM_BOOL);

        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    public function updateValue(AppSettingUpdateDTO $dto): void
    {
        $sql = 'UPDATE app_settings SET setting_value = :value';

        if ($dto->valueType !== null) {
            $sql .= ', setting_type = :type';
        }

        $sql .= ' WHERE setting_group = :group AND setting_key = :key';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':value', $dto->value, PDO::PARAM_STR);
        $stmt->bindValue(':group', $dto->group, PDO::PARAM_STR);
        $stmt->bindValue(':key', $dto->key, PDO::PARAM_STR);

        if ($dto->valueType !== null) {
            $stmt->bindValue(':type', $dto->valueType->value, PDO::PARAM_STR);
        }

        $stmt->execute();
    }

    public function setActiveStatus(AppSettingKeyDTO $key, bool $isActive): void
    {
        $sql = '
            UPDATE app_settings
            SET is_active = :is_active
            WHERE setting_group = :group
              AND setting_key = :key
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':is_active', $isActive, PDO::PARAM_BOOL);
        $stmt->bindValue(':group', $key->group, PDO::PARAM_STR);
        $stmt->bindValue(':key', $key->key, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function query(AppSettingsQueryDTO $query): array
    {
        $conditions = [];

        // Build query string first
        if ($query->group !== null) {
            $conditions[] = 'setting_group = :group';
        }

        if ($query->isActive !== null) {
            $conditions[] = 'is_active = :is_active';
        }

        if ($query->search !== null && $query->search !== '') {
            $conditions[] = '(setting_key LIKE :search OR setting_value LIKE :search)';
        }

        $sql = 'SELECT id, setting_group, setting_key, setting_value, setting_type, is_active FROM app_settings';

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY setting_group ASC, setting_key ASC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);

        // Bind parameters strictly
        if ($query->group !== null) {
            $stmt->bindValue(':group', $query->group, PDO::PARAM_STR);
        }

        if ($query->isActive !== null) {
            $stmt->bindValue(':is_active', $query->isActive, PDO::PARAM_BOOL);
        }

        if ($query->search !== null && $query->search !== '') {
            $stmt->bindValue(':search', '%' . $query->search . '%', PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $query->perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($query->page - 1) * $query->perPage, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
