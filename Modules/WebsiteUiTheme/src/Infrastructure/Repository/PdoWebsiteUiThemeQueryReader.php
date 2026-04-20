<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Infrastructure\Repository;

use Maatify\WebsiteUiTheme\Contract\WebsiteUiThemeQueryReaderInterface;
use Maatify\WebsiteUiTheme\DTO\PaginationDTO;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDTO;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemePaginatedResultDTO;
use Maatify\WebsiteUiTheme\Exception\WebsiteUiThemePersistenceException;
use PDO;
use PDOStatement;

final readonly class PdoWebsiteUiThemeQueryReader implements WebsiteUiThemeQueryReaderInterface
{
    public function __construct(private PDO $pdo) {}

    public function listThemes(int $page, int $perPage, ?string $globalSearch, array $columnFilters): WebsiteUiThemePaginatedResultDTO
    {
        $page = max(1, $page);
        $limit = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $where[] = '(t.`entity_type` LIKE :global_text OR t.`theme_file` LIKE :global_text OR t.`display_name` LIKE :global_text)';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
        }

        if (isset($columnFilters['id'])) {
            $where[] = 't.`id` = :id';
            $params['id'] = (int) $columnFilters['id'];
        }

        if (isset($columnFilters['entity_type'])) {
            $where[] = 't.`entity_type` = :entity_type';
            $params['entity_type'] = trim((string) $columnFilters['entity_type']);
        }

        if (isset($columnFilters['theme_file'])) {
            $where[] = 't.`theme_file` = :theme_file';
            $params['theme_file'] = trim((string) $columnFilters['theme_file']);
        }

        if (isset($columnFilters['display_name'])) {
            $where[] = 't.`display_name` = :display_name';
            $params['display_name'] = trim((string) $columnFilters['display_name']);
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = $this->scalarInt('SELECT COUNT(*) FROM `maa_website_ui_themes`');

        $filteredStmt = $this->prepareOrFail("SELECT COUNT(*) FROM `maa_website_ui_themes` t {$whereSql}");
        foreach ($params as $k => $v) {
            $filteredStmt->bindValue(':' . $k, $v);
        }
        $filteredStmt->execute();
        $filtered = (int) $filteredStmt->fetchColumn();

        $stmt = $this->prepareOrFail("SELECT t.* FROM `maa_website_ui_themes` t {$whereSql} ORDER BY t.`id` ASC LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<WebsiteUiThemeDTO> $data */
        $data = array_map(
            static fn (array $row): WebsiteUiThemeDTO => WebsiteUiThemeDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return new WebsiteUiThemePaginatedResultDTO(
            data: $data,
            pagination: new PaginationDTO(
                page: $page,
                perPage: $limit,
                total: $total,
                filtered: $filtered,
            ),
        );
    }

    /** @return list<WebsiteUiThemeDTO> */
    public function listAllThemes(): array
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_website_ui_themes` ORDER BY `entity_type` ASC, `display_name` ASC, `id` ASC');
        $stmt->execute();

        /** @var list<WebsiteUiThemeDTO> $data */
        $data = array_map(
            static fn (array $row): WebsiteUiThemeDTO => WebsiteUiThemeDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return $data;
    }

    /** @return list<WebsiteUiThemeDTO> */
    public function listThemesByEntityType(string $entityType): array
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_website_ui_themes` WHERE `entity_type` = :entity_type ORDER BY `display_name` ASC, `id` ASC');
        $stmt->bindValue(':entity_type', trim($entityType));
        $stmt->execute();

        /** @var list<WebsiteUiThemeDTO> $data */
        $data = array_map(
            static fn (array $row): WebsiteUiThemeDTO => WebsiteUiThemeDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return $data;
    }

    public function findById(int $id): ?WebsiteUiThemeDTO
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_website_ui_themes` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $this->fetchAssoc($stmt);

        return $row !== null ? WebsiteUiThemeDTO::fromRow($row) : null;
    }

    public function findByEntityTypeAndThemeFile(string $entityType, string $themeFile): ?WebsiteUiThemeDTO
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_website_ui_themes` WHERE `entity_type` = :entity_type AND `theme_file` = :theme_file LIMIT 1');
        $stmt->bindValue(':entity_type', trim($entityType));
        $stmt->bindValue(':theme_file', trim($themeFile));
        $stmt->execute();
        $row = $this->fetchAssoc($stmt);

        return $row !== null ? WebsiteUiThemeDTO::fromRow($row) : null;
    }

    public function existsByThemeFile(string $themeFile): bool
    {
        $stmt = $this->prepareOrFail('SELECT 1 FROM `maa_website_ui_themes` WHERE `theme_file` = :theme_file LIMIT 1');
        $stmt->bindValue(':theme_file', trim($themeFile));
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function existsByThemeFileAndEntityType(string $themeFile, string $entityType): bool
    {
        $stmt = $this->prepareOrFail('SELECT 1 FROM `maa_website_ui_themes` WHERE `theme_file` = :theme_file AND `entity_type` = :entity_type LIMIT 1');
        $stmt->bindValue(':theme_file', trim($themeFile));
        $stmt->bindValue(':entity_type', trim($entityType));
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw WebsiteUiThemePersistenceException::prepareFailed($sql);
        }

        return $stmt;
    }

    /** @return array<string,mixed>|null */
    private function fetchAssoc(PDOStatement $stmt): ?array
    {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            return null;
        }

        return $row;
    }

    /** @return list<array<string,mixed>> */
    private function fetchAllAssoc(PDOStatement $stmt): array
    {
        /** @var list<array<string,mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @param list<int|string> $params */
    private function scalarInt(string $sql, array $params = []): int
    {
        $stmt = $this->prepareOrFail($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();

        return is_numeric($value) ? (int) $value : 0;
    }
}
