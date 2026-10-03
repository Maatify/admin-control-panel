<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-10-01 00:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys;

use Maatify\I18n\Exception\TranslationKeyNotFoundException;
use Maatify\I18n\Management\Command\CreateKeyCommand;
use Maatify\I18n\Management\Command\RenameKeyCommand;
use Maatify\I18n\Management\Service\I18nManagementReadService;
use Maatify\I18n\Management\Service\TranslationWriteService;

/**
 * Admin key mutations scoped by the Admin route's scope. The route scope is a
 * Host navigation constraint (the key must belong to it); every write is the
 * I18n package's.
 */
final readonly class I18nScopeKeyCommandService
{
    public function __construct(
        private I18nManagementReadService $read,
        private TranslationWriteService $translationWriter
    )
    {
    }

    public function renameKey(int $keyId, string $scopeCode, string $newKey): void
    {
        $dto = $this->read->getKey($keyId);

        if ($dto->scope !== $scopeCode) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        $this->translationWriter->renameKey(new RenameKeyCommand($keyId, $dto->scope, $dto->domain, $newKey));
    }

    public function createKey(
        string $scope,
        string $domain,
        string $key,
        ?string $description
    ): int {
        return $this->translationWriter->createKey(new CreateKeyCommand($scope, $domain, $key, $description));
    }

    public function updateDescription(int $keyId, string $scopeCode, string $description): void
    {
        $dto = $this->read->getKey($keyId);

        if ($dto->scope !== $scopeCode) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        $this->translationWriter->updateKeyDescription($keyId, $description);
    }
}
