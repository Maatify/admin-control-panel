<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-12 20:53
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Service;

use Maatify\I18n\Contract\DomainLanguageSummaryRepositoryInterface;
use Maatify\I18n\Contract\KeyStatsRepositoryInterface;
use Maatify\I18n\Contract\TranslationKeyRepositoryInterface;
use Maatify\I18n\Exception\TranslationKeyNotFoundException;

final readonly class MissingCounterService
{
    public function __construct(
        private DomainLanguageSummaryRepositoryInterface $summaryRepository,
        private TranslationKeyRepositoryInterface $keyRepository,
        private KeyStatsRepositoryInterface $keyStatsRepository,
    ) {
    }

    public function onKeyCreated(int $keyId): void
    {
        $key = $this->keyRepository->getById($keyId);

        if ($key === null) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        // summary table
        $this->summaryRepository->incrementTotalKeys(
            $key->scope,
            $key->domain
        );

        // key_stats table
        $this->keyStatsRepository->createForKey($keyId);
    }

    public function onKeyDeleted(int $keyId): void
    {
        $key = $this->keyRepository->getById($keyId);

        if ($key === null) {
            return; // fail-soft
        }

        // summary table
        $this->summaryRepository->decrementTotalKeys(
            $key->scope,
            $key->domain
        );

        // key_stats table
        $this->keyStatsRepository->deleteForKey($keyId);
    }

    public function onTranslationCreated(
        int $languageId,
        int $keyId
    ): void {
        $key = $this->keyRepository->getById($keyId);

        if ($key === null) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        // summary table
        $this->summaryRepository->incrementTranslated(
            $key->scope,
            $key->domain,
            $languageId
        );

        // key_stats table
        $this->keyStatsRepository->incrementTranslated($keyId);
    }

    public function onTranslationDeleted(
        int $languageId,
        int $keyId
    ): void {
        $key = $this->keyRepository->getById($keyId);

        if ($key === null) {
            return; // fail-soft
        }

        // summary table
        $this->summaryRepository->decrementTranslated(
            $key->scope,
            $key->domain,
            $languageId
        );

        // key_stats table
        $this->keyStatsRepository->decrementTranslated($keyId);
    }

    public function onKeyMoved(
        string $oldScope,
        string $oldDomain,
        string $newScope,
        string $newDomain
    ): void
    {
        $this->summaryRepository->rebuildScopeDomain($oldScope, $oldDomain);
        $this->summaryRepository->rebuildScopeDomain($newScope, $newDomain);
    }
}
