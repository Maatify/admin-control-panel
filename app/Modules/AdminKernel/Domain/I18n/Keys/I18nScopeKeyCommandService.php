<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-11 09:09
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys;

use Maatify\I18n\Contract\TranslationKeyRepositoryInterface;
use Maatify\I18n\Exception\TranslationKeyNotFoundException;
use Maatify\I18n\Service\TranslationWriteService;

final readonly class I18nScopeKeyCommandService
{
    public function __construct(
        private TranslationKeyRepositoryInterface $translationKeyRepository,
        private TranslationWriteService $translationWriter
    )
    {
    }

    public function renameKey(int $keyId, string $scopeCode, string $newKey): void
    {
        $dto = $this->translationKeyRepository->getById($keyId);

        if ($dto === null || $dto->scope !== $scopeCode) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        $this->translationWriter->renameKey($keyId, $dto->scope, $dto->domain, $newKey);
    }

    public function createKey(
        string $scope,
        string $domain,
        string $key,
        ?string $description
    ): int{
        return $this->translationWriter->createKey($scope, $domain, $key, $description);
    }

    public function updateDescription(int $keyId, string $scopeCode, string $description): void
    {
        $dto = $this->translationKeyRepository->getById($keyId);

        if ($dto === null || $dto->scope !== $scopeCode) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        $this->translationWriter->updateKeyDescription($keyId, $description);
    }
}
