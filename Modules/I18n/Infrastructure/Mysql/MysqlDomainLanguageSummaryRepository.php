<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-12 20:51
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Infrastructure\Mysql;

use PDO;
use PDOStatement;
use Maatify\I18n\Contract\DomainLanguageSummaryRepositoryInterface;

final readonly class MysqlDomainLanguageSummaryRepository
    implements DomainLanguageSummaryRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function incrementTotalKeys(string $scope, string $domain): void
    {
        $sql = 'INSERT INTO i18n_domain_language_summary
                (scope, domain, language_id, total_keys, translated_count, missing_count)
            SELECT
                :scope, :domain, l.id, 1, 0, 1
            FROM languages l
            ON DUPLICATE KEY UPDATE
                total_keys = total_keys + 1,
                missing_count = missing_count + 1';

        $stmt = $this->pdo->prepare($sql);
        if (!$stmt instanceof \PDOStatement) {
            return;
        }

        $stmt->execute([
            'scope' => $scope,
            'domain' => $domain,
        ]);
    }

    public function decrementTotalKeys(
        string $scope,
        string $domain
    ): void {
        $this->pdo->prepare(
            'UPDATE i18n_domain_language_summary
             SET total_keys = IF(total_keys > 0, total_keys - 1, 0)
             WHERE scope = :scope
               AND domain = :domain'
        )->execute([
            'scope' => $scope,
            'domain' => $domain,
        ]);
    }

    public function incrementTranslated(
        string $scope,
        string $domain,
        int $languageId
    ): void {
        $this->pdo->prepare(
            'UPDATE i18n_domain_language_summary
             SET translated_count = translated_count + 1,
                 missing_count = IF(missing_count > 0, missing_count - 1, 0)
             WHERE scope = :scope
               AND domain = :domain
               AND language_id = :language_id'
        )->execute([
            'scope' => $scope,
            'domain' => $domain,
            'language_id' => $languageId,
        ]);
    }

    public function decrementTranslated(
        string $scope,
        string $domain,
        int $languageId
    ): void {
        $this->pdo->prepare(
            'UPDATE i18n_domain_language_summary
             SET translated_count = IF(translated_count > 0, translated_count - 1, 0),
                 missing_count = missing_count + 1
             WHERE scope = :scope
               AND domain = :domain
               AND language_id = :language_id'
        )->execute([
            'scope' => $scope,
            'domain' => $domain,
            'language_id' => $languageId,
        ]);
    }

    public function truncate(): void
    {
        $this->pdo->exec('TRUNCATE TABLE i18n_domain_language_summary');
    }

    public function insertRow(
        string $scope,
        string $domain,
        int $languageId,
        int $totalKeys,
        int $translatedCount
    ): void {
        $sql = 'INSERT INTO i18n_domain_language_summary
            (scope, domain, language_id, total_keys, translated_count, missing_count)
            VALUES
            (:scope, :domain, :language_id, :total_keys, :translated_count, :missing_count)';

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt instanceof \PDOStatement) {
            return;
        }

        $stmt->execute([
            'scope' => $scope,
            'domain' => $domain,
            'language_id' => $languageId,
            'total_keys' => $totalKeys,
            'translated_count' => $translatedCount,
            'missing_count' => max(0, $totalKeys - $translatedCount),
        ]);
    }

}
