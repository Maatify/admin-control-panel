<?php

declare(strict_types=1);

namespace Maatify\Settings\Admin\Setting\Infrastructure\Repository;

use Maatify\Settings\Admin\Setting\Contract\AdminSettingQueryRepositoryInterface;
use Maatify\Settings\Shared\DTO\SettingDTO;
use Maatify\Settings\Shared\DTO\SettingListItemDTO;
use PDO;

final class PdoAdminSettingQueryRepository implements AdminSettingQueryRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    public function findByKey(string $settingKey): ?SettingDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `settings` WHERE `setting_key` = :setting_key LIMIT 1');
        $stmt->execute(['setting_key' => $settingKey]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrateDetail($row);
    }

    /**
     * @param  array<string, string|int>  $columnFilters
     * @return array{data: list<SettingListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function list(int $page, int $perPage, ?string $globalSearch, array $columnFilters): array
    {
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM `settings`');
        if ($stmtTotal === false) {
            throw new \RuntimeException('Failed to count settings');
        }
        $total = (int) $stmtTotal->fetchColumn();

        $where  = [];
        $params = [];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $like = '%' . trim($globalSearch) . '%';
            $where[]              = '(`setting_key` LIKE :global_key OR `admin_note` LIKE :global_note)';
            $params['global_key'] = $like;
            $params['global_note'] = $like;
        }

        if (isset($columnFilters['id'])) {
            $where[]      = '`id` = :id';
            $params['id'] = (int) $columnFilters['id'];
        }

        if (isset($columnFilters['key'])) {
            $where[]      = '`setting_key` LIKE :key';
            $params['key'] = '%' . $columnFilters['key'] . '%';
        }

        if (isset($columnFilters['admin_note'])) {
            $where[]           = '`admin_note` LIKE :admin_note';
            $params['admin_note'] = '%' . $columnFilters['admin_note'] . '%';
        }

        if (isset($columnFilters['is_admin_editable'])) {
            $where[]                   = '`is_admin_editable` = :is_admin_editable';
            $params['is_admin_editable'] = (int) $columnFilters['is_admin_editable'];
        }

        if (isset($columnFilters['value_type'])) {
            $where[]               = '`value_type` = :value_type';
            $params['value_type'] = $columnFilters['value_type'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmtFiltered = $this->pdo->prepare("SELECT COUNT(*) FROM `settings` {$whereSql}");
        $stmtFiltered->execute($params);
        $filtered = (int) $stmtFiltered->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt   = $this->pdo->prepare(
            "SELECT * FROM `settings` {$whereSql} ORDER BY `id` ASC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $items = $this->hydrateListItems($rows);

        return [
            'data'       => $items,
            'pagination' => [
                'page'     => $page,
                'per_page' => $perPage,
                'total'    => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    public function listAsKeyValue(): array
    {
        $stmt = $this->pdo->query('SELECT `setting_key`, `setting_value` FROM `settings`');
        if ($stmt === false) {
            throw new \RuntimeException('Failed to fetch settings');
        }

        $result = [];
        /** @var array<string, mixed> $row */
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key   = $row['setting_key'] ?? null;
            $value = $row['setting_value'] ?? null;
            if (is_string($key) && is_string($value)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $row */
    private function hydrateDetail(array $row): SettingDTO
    {
        $id               = $row['id'] ?? null;
        $settingKey       = $row['setting_key'] ?? null;
        $settingValue     = $row['setting_value'] ?? null;
        $valueType        = $row['value_type'] ?? null;
        $isAdminEditable  = $row['is_admin_editable'] ?? null;
        $adminNote        = $row['admin_note'] ?? null;
        $createdAt        = $row['created_at'] ?? null;
        $updatedAt        = $row['updated_at'] ?? null;

        return new SettingDTO(
            id: (is_int($id) || is_string($id)) ? (int) $id : 0,
            settingKey: is_string($settingKey) ? $settingKey : '',
            settingValue: is_string($settingValue) ? $settingValue : '',
            valueType: is_string($valueType) ? $valueType : '',
            isAdminEditable: (is_int($isAdminEditable) || is_string($isAdminEditable)) && (int) $isAdminEditable === 1,
            adminNote: is_string($adminNote) ? $adminNote : null,
            createdAt: is_string($createdAt) ? $createdAt : '',
            updatedAt: is_string($updatedAt) ? $updatedAt : '',
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<SettingListItemDTO>
     */
    private function hydrateListItems(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateListItem($row);
        }
        return $items;
    }

    /** @param array<string, mixed> $row */
    private function hydrateListItem(array $row): SettingListItemDTO
    {
        $id               = $row['id'] ?? null;
        $settingKey       = $row['setting_key'] ?? null;
        $settingValue     = $row['setting_value'] ?? null;
        $valueType        = $row['value_type'] ?? null;
        $isAdminEditable  = $row['is_admin_editable'] ?? null;
        $adminNote        = $row['admin_note'] ?? null;
        $updatedAt        = $row['updated_at'] ?? null;

        return new SettingListItemDTO(
            id: (is_int($id) || is_string($id)) ? (int) $id : 0,
            settingKey: is_string($settingKey) ? $settingKey : '',
            settingValue: is_string($settingValue) ? $settingValue : '',
            valueType: is_string($valueType) ? $valueType : '',
            isAdminEditable: (is_int($isAdminEditable) || is_string($isAdminEditable)) && (int) $isAdminEditable === 1,
            adminNote: is_string($adminNote) ? $adminNote : null,
            updatedAt: is_string($updatedAt) ? $updatedAt : '',
        );
    }
}
