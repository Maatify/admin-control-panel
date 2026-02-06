<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-06 20:30
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Service;

use Maatify\I18n\Contract\ScopeRepositoryInterface;
use Maatify\I18n\Contract\DomainRepositoryInterface;
use Maatify\I18n\Contract\DomainScopeRepositoryInterface;
use Maatify\I18n\DTO\DomainDTO;
use Maatify\I18n\DTO\ScopeDTO;
use Maatify\I18n\Enum\I18nPolicyModeEnum;
use RuntimeException;

final readonly class I18nGovernancePolicyService
{
    public function __construct(
        private ScopeRepositoryInterface $scopeRepository,
        private DomainRepositoryInterface $domainRepository,
        private DomainScopeRepositoryInterface $domainScopeRepository,
        private I18nPolicyModeEnum $mode = I18nPolicyModeEnum::STRICT
    ) {
    }

    /**
     * @throws RuntimeException
     */
    public function assertScopeAndDomainAllowed(
        string $scope,
        string $domain
    ): void {
        $scopeDto = $this->scopeRepository->getByCode($scope);
        $domainDto = $this->domainRepository->getByCode($domain);

        if ($this->mode === I18nPolicyModeEnum::STRICT) {
            $this->assertStrict($scope, $domain, $scopeDto, $domainDto);
            return;
        }

        $this->assertPermissive($scope, $domain, $scopeDto, $domainDto);
    }

    public function isScopeAndDomainReadable(
        string $scope,
        string $domain
    ): bool {
        try {
            $this->assertScopeAndDomainAllowed($scope, $domain);
            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    private function assertStrict(
        string $scope,
        string $domain,
        ?ScopeDTO $scopeDto,
        ?DomainDTO $domainDto
    ): void {
        if ($scopeDto === null || !$scopeDto->isActive) {
            throw new RuntimeException('Invalid or inactive scope.');
        }

        if ($domainDto === null || !$domainDto->isActive) {
            throw new RuntimeException('Invalid or inactive domain.');
        }

        if (
            !$this->domainScopeRepository
                ->isDomainAllowedForScope($scope, $domain)
        ) {
            throw new RuntimeException(
                'Domain is not allowed for the given scope.'
            );
        }
    }

    private function assertPermissive(
        string $scope,
        string $domain,
        ?ScopeDTO $scopeDto,
        ?DomainDTO $domainDto
    ): void {
        if ($scopeDto !== null && !$scopeDto->isActive) {
            throw new RuntimeException('Inactive scope.');
        }

        if ($domainDto !== null && !$domainDto->isActive) {
            throw new RuntimeException('Inactive domain.');
        }

        if ($scopeDto !== null && $domainDto !== null) {
            if (
                !$this->domainScopeRepository
                    ->isDomainAllowedForScope($scope, $domain)
            ) {
                throw new RuntimeException(
                    'Domain is not allowed for the given scope.'
                );
            }
        }
    }
}
