<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\AppSettings\Metadata;

use Maatify\AdminKernel\Domain\AppSettings\DTO\AppSettingsKeyMetadataDTO;
use Maatify\AdminKernel\Domain\AppSettings\DTO\AppSettingsMetadata\AppSettingsGroupMetadataDTO;
use Maatify\AdminKernel\Domain\AppSettings\DTO\AppSettingsMetadata\AppSettingsMetadataResponseDTO;
use Maatify\AppSettings\Policy\AppSettingsProtectionPolicy;
use Maatify\AppSettings\Policy\AppSettingsWhitelistPolicy;
use Maatify\AppSettings\Repository\AppSettingsRepositoryInterface;
use Maatify\AppSettings\DTO\AppSettingsQueryDTO;

final readonly class AppSettingsMetadataProvider
{
    public function __construct(
        private AppSettingsWhitelistPolicy $whitelistPolicy,
        private AppSettingsProtectionPolicy $protectionPolicy,
        private AppSettingsRepositoryInterface $repository,
    ) {
    }

    public function getMetadata(): AppSettingsMetadataResponseDTO
    {
        $groups = [];

        // 🔹 1) Whitelisted structure
        $allowed = $this->whitelistPolicy->getAllowed();

        // 🔹 2) Existing settings in DB
        $existingMap = $this->buildExistingMap();

        foreach ($allowed as $group => $keys) {

            $groupKeys = [];

            foreach ($keys as $key) {

                $isWildcard = ($key === '*');

                // 🔹 Skip already existing keys (except wildcard)
                if (! $isWildcard && isset($existingMap[$group][$key])) {
                    continue;
                }

                $isProtected = false;
                $isEditable  = true;

                if (! $isWildcard) {
                    $isProtected = $this->protectionPolicy->isProtected($group, $key);
                    $isEditable  = ! $isProtected;
                }

                $groupKeys[] = new AppSettingsKeyMetadataDTO(
                    key: $key,
                    protected: $isProtected,
                    wildcard: $isWildcard,
                    editable: $isEditable
                );
            }

            // 🔹 Do not include empty groups
            if ($groupKeys === []) {
                continue;
            }

            $groups[] = new AppSettingsGroupMetadataDTO(
                name: $group,
                label: ucfirst(str_replace('_', ' ', $group)),
                keys: $groupKeys
            );
        }

        return new AppSettingsMetadataResponseDTO($groups);
    }

    /**
     * Build a fast lookup map of existing settings.
     *
     * @return array<string, array<string, bool>>
     */
    private function buildExistingMap(): array
    {
        $query = new AppSettingsQueryDTO(
            page: 1,
            perPage: 10_000,
            search: null,
            group: null,
            isActive: null
        );

        $rows = $this->repository->query($query);

        $map = [];

        foreach ($rows as $row) {

            $group = $row['setting_group'] ?? null;
            $key   = $row['setting_key'] ?? null;

            if (! is_string($group) || ! is_string($key)) {
                continue;
            }

            $map[$group][$key] = true;
        }

        return $map;
    }
}
