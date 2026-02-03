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

use Maatify\I18n\DTO\TranslationKeyCollectionDTO;
use Maatify\I18n\DTO\TranslationKeyDTO;

interface TranslationKeyRepositoryInterface
{
    public function create(string $key, ?string $description): int;

    public function getById(int $id): ?TranslationKeyDTO;

    public function getByKey(string $key): ?TranslationKeyDTO;

    public function listAll(): TranslationKeyCollectionDTO;

    public function updateDescription(int $id, ?string $description): void;

    public function renameKey(int $id, string $newKey): void;
}
