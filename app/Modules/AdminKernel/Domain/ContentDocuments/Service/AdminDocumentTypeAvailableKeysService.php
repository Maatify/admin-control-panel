<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-17 10:17
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\Service;

use Maatify\AdminKernel\Domain\ContentDocuments\DTO\DocumentTypeDropdownItemDTO;
use Maatify\AdminKernel\Domain\ContentDocuments\DTO\DocumentTypeDropdownResponseDTO;
use Maatify\AdminKernel\Domain\ContentDocuments\Enum\DocumentTypeKeyEnum;
use Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface;

final readonly class AdminDocumentTypeAvailableKeysService
{
    public function __construct(
        private ContentDocumentsFacadeInterface $facade,
    ) {
    }

    /**
     * @return DocumentTypeDropdownResponseDTO
     */
    public function list(): DocumentTypeDropdownResponseDTO
    {
        $registeredKeys = $this->facade->listRegisteredDocumentTypeKeys();

        $registeredValues = array_map(
            static fn ($key): string => (string) $key,
            $registeredKeys
        );

        /** @var DocumentTypeDropdownItemDTO[] $items */
        $items = [];

        foreach (DocumentTypeKeyEnum::cases() as $case) {
            if (in_array($case->value, $registeredValues, true)) {
                continue;
            }

            $items[] = new DocumentTypeDropdownItemDTO(
                key: $case->value,
                label: $case->label(),
            );
        }

        return new DocumentTypeDropdownResponseDTO($items);
    }
}

