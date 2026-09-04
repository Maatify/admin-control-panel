<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 01:07
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Domains;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\I18n\Domain\DTO\I18nDomainDetailsDTO;
use Maatify\AdminKernel\Domain\I18n\Domain\I18nDomainDetailsReaderInterface;
use PDO;
use RuntimeException;

final readonly class PdoI18nDomainDetailsReader implements I18nDomainDetailsReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function getDomainDetailsById(int $id): I18nDomainDetailsDTO
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT
                id,
                code,
                name,
                description,
                is_active,
                sort_order
            FROM i18n_domains
            WHERE id = :id
            LIMIT 1
            "
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare domain details query');
        }

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new EntityNotFoundException('Domain not found', 'domain_id');
        }


        /**
         * @var  array{
         *   id:int,
         *   code:string,
         *   name:string,
         *   description:string|null,
         *   is_active:int,
         *   sort_order:int,
         * }$row
         */

        return new I18nDomainDetailsDTO(
            (int) $row['id'],
            (string) $row['code'],
            (string) $row['name'],
            (string) $row['description'],
            (int) $row['is_active'],
            (int) $row['sort_order'],
        );
    }
}
