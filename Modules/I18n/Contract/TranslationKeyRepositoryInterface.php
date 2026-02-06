<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Contract;

use Maatify\I18n\DTO\TranslationKeyDTO;
use Maatify\I18n\DTO\TranslationKeyCollectionDTO;

interface TranslationKeyRepositoryInterface
{
    public function create(
        string $scope,
        string $domain,
        string $key,
        ?string $description
    ): int;

    public function getById(int $id): ?TranslationKeyDTO;

    public function getByStructuredKey(
        string $scope,
        string $domain,
        string $key
    ): ?TranslationKeyDTO;

    public function listAll(): TranslationKeyCollectionDTO;

    public function updateDescription(int $id, ?string $description): void;

    public function rename(
        int $id,
        string $scope,
        string $domain,
        string $key
    ): void;

    /**
     * List all keys for a given (scope + domain).
     *
     * @param   string  $scope
     * @param   string  $domain
     *
     * @return TranslationKeyCollectionDTO
     */
    public function listByScopeAndDomain(
        string $scope,
        string $domain
    ): TranslationKeyCollectionDTO;
}
