<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\AppSettings\Metadata;

use Maatify\AdminKernel\Domain\DTO\AppSettingsMetadata\AppSettingsGroupMetadataDTO;
use Maatify\AdminKernel\Domain\DTO\AppSettingsMetadata\AppSettingsKeyMetadataDTO;
use Maatify\AdminKernel\Domain\DTO\AppSettingsMetadata\AppSettingsMetadataResponseDTO;
use Maatify\AppSettings\Policy\AppSettingsWhitelistPolicy;
use Maatify\AppSettings\Policy\AppSettingsProtectionPolicy;
use ReflectionClass;

final class AppSettingsMetadataProvider
{
    public function getMetadata(): AppSettingsMetadataResponseDTO
    {
        $groups = [];

        $allowed = $this->readWhitelist();

        foreach ($allowed as $group => $keys) {
            $groupKeys = [];

            foreach ($keys as $key) {
                $isWildcard = ($key === '*');

                $protected = false;
                if (! $isWildcard) {
                    $protected = AppSettingsProtectionPolicy::isProtected($group, $key);
                }

                $groupKeys[] = new AppSettingsKeyMetadataDTO(
                    key: $key,
                    protected: $protected,
                    wildcard: $isWildcard
                );
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
     * @return array<string, array<int,string>>
     */
    private function readWhitelist(): array
    {
        $ref = new ReflectionClass(AppSettingsWhitelistPolicy::class);
        /** @var array<string, array<int,string>> */
        return $ref->getConstant('ALLOWED');
    }
}
