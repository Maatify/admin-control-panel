<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language;

use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\Exception\LanguageNotFoundException;

/**
 * Host-side resolution of the Admin/LanguageCore language ID to the exact
 * language code (ADR-019).
 *
 * The ID is Host navigation identity only; it must never cross the I18n
 * boundary, so every Admin call into I18n translation runtime resolves the
 * code here first.
 */
final readonly class LanguageCodeResolver
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository
    ) {
    }

    /**
     * @throws LanguageNotFoundException
     */
    public function resolveCode(int $languageId): string
    {
        $language = $this->languageRepository->getById($languageId);

        if ($language === null) {
            throw new LanguageNotFoundException($languageId);
        }

        return $language->code;
    }
}
