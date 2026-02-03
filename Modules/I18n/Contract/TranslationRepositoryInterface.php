<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 01:16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Contract;

use Maatify\I18n\DTO\TranslationCollectionDTO;
use Maatify\I18n\DTO\TranslationDTO;

interface TranslationRepositoryInterface
{
    /**
     * Create or update translation for (language_id + key_id).
     */
    public function upsert(int $languageId, int $keyId, string $value): int;

    public function getById(int $id): ?TranslationDTO;

    public function getByLanguageAndKey(int $languageId, int $keyId): ?TranslationDTO;

    public function listByLanguage(int $languageId): TranslationCollectionDTO;

    public function listByKey(int $keyId): TranslationCollectionDTO;

    public function deleteByLanguageAndKey(int $languageId, int $keyId): void;
}
