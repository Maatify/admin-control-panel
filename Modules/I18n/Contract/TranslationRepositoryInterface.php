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

use Maatify\I18n\DTO\TranslationCollectionDTO;
use Maatify\I18n\DTO\TranslationDTO;
use Maatify\I18n\DTO\TranslationUpsertResultDTO;

interface TranslationRepositoryInterface
{
    public function upsert(int $languageId, int $keyId, string $value): TranslationUpsertResultDTO;

    public function getById(int $id): ?TranslationDTO;

    public function getByLanguageAndKey(int $languageId, int $keyId): ?TranslationDTO;

    public function listByLanguage(int $languageId): TranslationCollectionDTO;

    public function listByKey(int $keyId): TranslationCollectionDTO;

    /**
     * @return int affected rows (0 or 1)
     */
    public function deleteByLanguageAndKey(int $languageId, int $keyId): int;

    /**
     * @param array<int> $keyIds
     */
    public function countByLanguageAndKeyIds(int $languageId, array $keyIds): int;
}
