<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\I18n\Language;

use Maatify\AdminKernel\Domain\I18n\Language\LanguageCodeChangeInterface;
use Maatify\I18n\Management\Service\TranslationWriteService;
use Maatify\I18n\ValueObject\LanguageCode;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\Exception\LanguageNotFoundException;
use Maatify\LanguageCore\Exception\LanguageUpdateFailedException;
use Maatify\LanguageCore\Service\LanguageManagementService;
use PDO;
use Throwable;

/**
 * PDO implementation of LanguageCodeChangeInterface: the Host-owned atomic language-code
 * identity migration (ADR-019 §6). It lives in Infrastructure because it owns the transaction on
 * the shared PDO connection.
 *
 * Inside I18n the language code is the stable identity of a translation, so a
 * code change must move LanguageCore and the I18n rows together:
 *
 *  1. validate the new code against the storage contract
 *  2. lock the language row and read the current (old) code
 *  3. change the LanguageCore code
 *  4. re-key authoritative I18n translations + derived summaries
 *
 * Steps 2-4 run in ONE transaction on the shared connection; the row lock
 * serializes concurrent renames so the old code is never stale, and any
 * failure rolls both sides back, so no I18n row is ever orphaned.
 */
final readonly class PdoLanguageCodeChange implements LanguageCodeChangeInterface
{
    public function __construct(
        private PDO $pdo,
        private LanguageRepositoryInterface $languageRepository,
        private LanguageManagementService $languageManagement,
        private TranslationWriteService $translationWriter,
    ) {
    }

    /**
     * @throws LanguageNotFoundException
     * @throws LanguageUpdateFailedException
     * @throws \Maatify\I18n\Exception\InvalidLanguageCodeException
     * @throws \Maatify\I18n\Exception\LanguageCodeAlreadyInUseException
     * @throws \Maatify\LanguageCore\Exception\LanguageAlreadyExistsException
     */
    public function changeCode(int $languageId, string $newCode): void
    {
        // The supplied value is validated as-is and persisted unchanged (ADR-019:
        // no silent normalization). Surrounding whitespace is rejected, never trimmed.
        if (trim($newCode) === '' || $newCode !== trim($newCode)) {
            throw new LanguageUpdateFailedException('code');
        }

        $newCode = LanguageCode::fromNullable($newCode)->identity();

        $ownsTransaction = !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            // Lock the Host identity row BEFORE capturing the old code, so two
            // concurrent renames are serialized and each acts on the code that
            // is really current (never a stale one read before the lock).
            $language = $this->languageRepository->getByIdForUpdate($languageId);

            if ($language === null) {
                throw new LanguageNotFoundException($languageId);
            }

            $oldCode = $language->code;

            if ($oldCode !== $newCode) {
                $this->languageManagement->updateLanguageCode($languageId, $newCode);
                $this->translationWriter->rekeyLanguageCode($oldCode, $newCode);
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
