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
use Maatify\I18n\Contract\TranslationKeyRepositoryInterface;
use Maatify\I18n\Exception\TranslationKeyNotFoundException;

final readonly class MissingCounterService
{
    public function __construct(
        private DomainLanguageSummaryRepositoryInterface $summaryRepository,
        private TranslationKeyRepositoryInterface $keyRepository,
    ) {
    }

    public function onKeyCreated(int $keyId): void
    {
        $key = $this->keyRepository->getById($keyId);

        if ($key === null) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        // total_keys++ + missing++ لكل اللغات
        $this->summaryRepository->incrementTotalKeys(
            $key->scope,
            $key->domain
        );
    }

    public function onKeyDeleted(int $keyId): void
    {
        $key = $this->keyRepository->getById($keyId);

        if ($key === null) {
            return; // fail-soft
        }

        $this->summaryRepository->decrementTotalKeys(
            $key->scope,
            $key->domain
        );
    }

    public function onTranslationCreated(
        int $languageId,
        int $keyId
    ): void {
        $key = $this->keyRepository->getById($keyId);

        if ($key === null) {
            throw new TranslationKeyNotFoundException($keyId);
        }

        $this->summaryRepository->incrementTranslated(
            $key->scope,
            $key->domain,
            $languageId
        );
    }

    public function onTranslationDeleted(
        int $languageId,
        int $keyId
    ): void {
        $key = $this->keyRepository->getById($keyId);

        if ($key === null) {
            return; // fail-soft
        }

        $this->summaryRepository->decrementTranslated(
            $key->scope,
            $key->domain,
            $languageId
        );
    }
}
