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
use PDOStatement;
use Maatify\AppSettings\DTO\AppSettingDTO;
use Maatify\AppSettings\DTO\AppSettingKeyDTO;
use Maatify\AppSettings\DTO\AppSettingUpdateDTO;
use Maatify\AppSettings\DTO\AppSettingsQueryDTO;

/**
 * Class: PdoAppSettingsRepository
 *
 * PDO-based MySQL implementation for AppSettingsRepositoryInterface.
 *
 * Responsibilities:
 * - Execute SQL queries only
 * - Respect is_active semantics
 * - Return raw data (no casting, no validation)
 *
 * Forbidden:
 * - Business logic
 * - Validation
 * - Whitelist rules
 * - Caching
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
        SELECT id, setting_group, setting_key, setting_value, is_active
        FROM app_settings
        WHERE setting_group = :group
          AND setting_key = :key
    ';

        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }

        $stmt = $this->prepareAndExecute($sql, [
            'group' => $group,
            'key'   => $key,
        ]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $row;
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

        $stmt = $this->prepareAndExecute($sql, [
            'group' => $group,
            'key'   => $key,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    public function insert(AppSettingDTO $dto): int
    {
        $sql = '
            INSERT INTO app_settings (
                setting_group,
                setting_key,
                setting_value,
                is_active
            ) VALUES (
                :group,
                :key,
                :value,
                :is_active
            )
        ';

        $this->prepareAndExecute($sql, [
            'group'     => $dto->group,
            'key'       => $dto->key,
            'value'     => $dto->value,
            'is_active' => $dto->isActive ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateValue(AppSettingUpdateDTO $dto): void
    {
        $sql = '
            UPDATE app_settings
            SET setting_value = :value
            WHERE setting_group = :group
              AND setting_key = :key
        ';

        $this->prepareAndExecute($sql, [
            'group' => $dto->group,
            'key'   => $dto->key,
            'value' => $dto->value,
        ]);
    }

    public function setActiveStatus(AppSettingKeyDTO $key, bool $isActive): void
    {
        $sql = '
            UPDATE app_settings
            SET is_active = :is_active
            WHERE setting_group = :group
              AND setting_key = :key
        ';

        $this->prepareAndExecute($sql, [
            'group'     => $key->group,
            'key'       => $key->key,
            'is_active' => $isActive ? 1 : 0,
        ]);
    }

    public function query(AppSettingsQueryDTO $query): array
    {
        $conditions = [];
        $params = [];

        if ($query->group !== null) {
            $conditions[] = 'setting_group = :group';
            $params['group'] = $query->group;
        }

        if ($query->isActive !== null) {
            $conditions[] = 'is_active = :is_active';
            $params['is_active'] = $query->isActive ? 1 : 0;
        }

        if ($query->search !== null && $query->search !== '') {
            $conditions[] = '(setting_key LIKE :search OR setting_value LIKE :search)';
            $params['search'] = '%' . $query->search . '%';
        }

        $sql = '
            SELECT id, setting_group, setting_key, setting_value, is_active
            FROM app_settings
        ';

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= '
            ORDER BY setting_group ASC, setting_key ASC
            LIMIT :limit OFFSET :offset
        ';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $name => $value) {
            $stmt->bindValue(':' . $name, $value);
        }

        $stmt->bindValue(':limit', $query->perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($query->page - 1) * $query->perPage, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Prepare and execute a PDO statement with parameters.
     *
     * This helper is intentionally private to avoid leaking
     * abstraction details outside the repository.
     *
     * @param   string                $sql
     * @param   array<string, mixed>  $params
     *
     * @return PDOStatement
     */
    private function prepareAndExecute(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }
}
