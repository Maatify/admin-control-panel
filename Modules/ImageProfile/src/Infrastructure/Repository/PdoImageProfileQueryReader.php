<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Infrastructure\Repository;

use Maatify\ImageProfile\Contract\ImageProfileQueryReaderInterface;
use Maatify\ImageProfile\DTO\ImageProfileDTO;
use Maatify\ImageProfile\DTO\ImageProfilePaginatedResultDTO;
use Maatify\ImageProfile\DTO\PaginationDTO;
use Maatify\ImageProfile\Exception\ImageProfilePersistenceException;
use PDO;
use PDOStatement;

final class PdoImageProfileQueryReader implements ImageProfileQueryReaderInterface
{
    public function __construct(private readonly PDO $pdo) {}

    /** {@inheritDoc} */
    public function listProfiles(int $page, int $perPage, ?string $globalSearch, array $columnFilters): ImageProfilePaginatedResultDTO
    {
        $page = max(1, $page);
        $limit = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $where[] = '(p.`code` LIKE :global_text OR p.`display_name` LIKE :global_text)';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
        }

        if (isset($columnFilters['is_active'])) {
            $where[] = 'p.`is_active` = :is_active';
            $params['is_active'] = (int) $columnFilters['is_active'];
        }

        if (isset($columnFilters['code'])) {
            $where[] = 'p.`code` = :code';
            $params['code'] = trim((string) $columnFilters['code']);
        }

        if (isset($columnFilters['id'])) {
            $where[] = 'p.`id` = :id';
            $params['id'] = (int) $columnFilters['id'];
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = $this->scalarInt('SELECT COUNT(*) FROM `maa_image_profiles`');

        $filteredStmt = $this->prepareOrFail("SELECT COUNT(*) FROM `maa_image_profiles` p {$whereSql}");
        foreach ($params as $k => $v) {
            $filteredStmt->bindValue(':' . $k, $v);
        }
        $filteredStmt->execute();
        $filtered = (int) $filteredStmt->fetchColumn();

        $stmt = $this->prepareOrFail("SELECT p.* FROM `maa_image_profiles` p {$whereSql} ORDER BY p.`id` ASC LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<ImageProfileDTO> $data */
        $data = array_map(
            static fn (array $row): ImageProfileDTO => ImageProfileDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return new ImageProfilePaginatedResultDTO(
            data: $data,
            pagination: new PaginationDTO(
                page: $page,
                perPage: $limit,
                total: $total,
                filtered: $filtered,
            ),
        );
    }

    /** @return list<ImageProfileDTO> */
    public function listActiveProfiles(): array
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_image_profiles` WHERE `is_active` = 1 ORDER BY `id` ASC');
        $stmt->execute();

        /** @var list<ImageProfileDTO> $data */
        $data = array_map(
            static fn (array $row): ImageProfileDTO => ImageProfileDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return $data;
    }

    public function findById(int $id): ?ImageProfileDTO
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_image_profiles` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $this->fetchAssoc($stmt);

        return $row !== null ? ImageProfileDTO::fromRow($row) : null;
    }

    public function findByCode(string $code): ?ImageProfileDTO
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_image_profiles` WHERE `code` = ? LIMIT 1');
        $stmt->execute([$code]);
        $row = $this->fetchAssoc($stmt);

        return $row !== null ? ImageProfileDTO::fromRow($row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw ImageProfilePersistenceException::prepareFailed($sql);
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
