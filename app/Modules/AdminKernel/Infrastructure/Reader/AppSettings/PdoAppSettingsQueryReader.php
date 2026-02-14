<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Reader\AppSettings;

use Maatify\AdminKernel\Domain\AppSettings\DTO\AppSettingsListItemDTO;
use Maatify\AdminKernel\Domain\AppSettings\DTO\AppSettingsListResponseDTO;
use Maatify\AdminKernel\Domain\AppSettings\Reader\AppSettingsQueryReaderInterface;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use Maatify\AppSettings\Policy\AppSettingsProtectionPolicy;
use PDO;
use RuntimeException;

final readonly class PdoAppSettingsQueryReader implements AppSettingsQueryReaderInterface
{
    public function __construct(
        private PDO $pdo,
        private AppSettingsProtectionPolicy $protectionPolicy,
    ) {
    }

    public function queryAppSettings(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): AppSettingsListResponseDTO {
        $where  = [];
        $params = [];

        // ─────────────────────────────
        // Global search
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);
            if ($g !== '') {
                $where[] = '(s.setting_group LIKE :global_text OR s.setting_key LIKE :global_text OR s.setting_value LIKE :global_text)';
                $params['global_text'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {
            if ($alias === 'id') {
                $where[] = 's.id = :id';
                $params['id'] = (int) $value;
            }

            if ($alias === 'setting_group') {
                $where[] = 's.setting_group = :setting_group';
                $params['setting_group'] = trim((string) $value);
            }

            if ($alias === 'setting_key') {
                $where[] = 's.setting_key LIKE :setting_key';
                $params['setting_key'] = '%' . trim((string) $value) . '%';
            }

            if ($alias === 'is_active') {
                $where[] = 's.is_active = :is_active';
                $params['is_active'] = (int) $value;
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM app_settings');
        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to execute total app_settings count query');
        }
        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*) FROM app_settings s {$whereSql}"
        );
        $stmtFiltered->execute($params);
        $filtered = (int) $stmtFiltered->fetchColumn();

        // ─────────────────────────────
        // Data
        // ─────────────────────────────
        $limit  = $query->perPage;
        $offset = ($query->page - 1) * $limit;

        $sql = "
            SELECT
                s.id,
                s.setting_group,
                s.setting_key,
                s.setting_value,
                s.is_active
            FROM app_settings s
            {$whereSql}
            ORDER BY
                s.setting_group ASC,
                s.setting_key ASC,
                s.id ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];

        foreach ($rows as $row) {
            $id       = $row['id'] ?? null;
            $group    = $row['setting_group'] ?? null;
            $key      = $row['setting_key'] ?? null;
            $value    = $row['setting_value'] ?? null;
            $isActive = $row['is_active'] ?? null;

            if (! is_int($id) && ! is_string($id)) {
                continue;
            }

            if (! is_string($group) || ! is_string($key) || ! is_string($value)) {
                continue;
            }

            if (! is_int($isActive) && ! is_string($isActive)) {
                continue;
            }

            $isProtected = $this->protectionPolicy->isProtected($group, $key);

            $items[] = new AppSettingsListItemDTO(
                id: (int) $id,
                setting_group: $group,
                setting_key: $key,
                setting_value: $value,
                is_active: (int) $isActive,
                is_protected: $isProtected,
                is_editable: ! $isProtected,
            );
        }

        $pagination = new PaginationDTO(
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
            filtered: $filtered
        );

        return new AppSettingsListResponseDTO(
            data: $items,
            pagination: $pagination
        );
    }
}
