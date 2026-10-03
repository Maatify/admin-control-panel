<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language;

use Maatify\LanguageCore\Exception\LanguageNotFoundException;
use Maatify\LanguageCore\Exception\LanguageUpdateFailedException;

/**
 * Host contract for changing a language's code (ADR-019 §6).
 *
 * Inside I18n the language code is the stable identity of a translation, so a code change is
 * an atomic identity migration, never a plain update. An implementation MUST, as ONE
 * all-or-nothing unit of work on the shared connection:
 *
 *  1. reject a code that is empty or has surrounding whitespace (never trim it) and validate
 *     it against the storage contract, before anything is touched;
 *  2. lock the language row and read the current (old) code only after the lock is held;
 *  3. change the LanguageCore code;
 *  4. re-key the authoritative I18n translations and derived summaries from the old to the new code;
 *  5. roll everything back on any failure, so no translation is ever orphaned.
 *
 * The contract belongs to the Domain; the implementation needs a database connection and
 * therefore lives in Infrastructure (see Infrastructure/I18n/Language).
 */
interface LanguageCodeChangeInterface
{
    /**
     * @throws LanguageNotFoundException
     * @throws LanguageUpdateFailedException
     * @throws \Maatify\I18n\Exception\InvalidLanguageCodeException
     * @throws \Maatify\I18n\Exception\LanguageCodeAlreadyInUseException
     * @throws \Maatify\LanguageCore\Exception\LanguageAlreadyExistsException
     */
    public function changeCode(int $languageId, string $newCode): void;
}
