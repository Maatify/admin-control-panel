<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Coverage;

use Maatify\AdminKernel\Domain\I18n\Coverage\DTO\ScopeCoverageByDomainItemDTO;
use Maatify\AdminKernel\Domain\I18n\Coverage\DTO\ScopeCoverageByLanguageItemDTO;
use Maatify\AdminKernel\Domain\I18n\Coverage\I18nScopeCoverageReaderInterface;
use PDO;

final readonly class PdoI18nScopeCoverageReader implements I18nScopeCoverageReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function getScopeCoverageByLanguage(int $scopeId): array
    {
        // 1. Resolve Scope Code
        $stmtScope = $this->pdo->prepare('SELECT code FROM i18n_scopes WHERE id = :id');
        $stmtScope->execute(['id' => $scopeId]);
        $scopeCode = $stmtScope->fetchColumn();

        if (!$scopeCode) {
            return [];
        }

        // 2. Query Summary, Grouped by Language
        // We MUST join i18n_domain_scopes to ensure we only count assigned domains
        // But i18n_domain_language_summary stores per (scope, domain), so if i18n_domain_scopes
        // is strictly enforced on creation, summary rows should be valid.
        // HOWEVER, the prompt says: "Enforce the mapping (domain_scopes) so results match assigned domains only."
        // So we join explicitly.

        $sql = "
            SELECT
                l.id AS language_id,
                l.code AS language_code,
                l.name AS language_name,
                ls.icon AS language_icon,
                SUM(dls.total_keys) AS total_keys,
                SUM(dls.translated_count) AS translated_count,
                SUM(dls.missing_count) AS missing_count
            FROM i18n_domain_language_summary dls
            JOIN i18n_domain_scopes ds
                ON ds.scope_code = dls.scope
                AND ds.domain_code = dls.domain
            JOIN languages l
                ON l.id = dls.language_id
            LEFT JOIN language_settings ls
                ON ls.language_id = l.id
            WHERE dls.scope = :scope_code
            GROUP BY l.id
            ORDER BY l.id ASC, l.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['scope_code' => $scopeCode]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $total = (int)$row['total_keys'];
            $translated = (int)$row['translated_count'];

            $percent = $total > 0
                ? round(($translated / $total) * 100, 1)
                : 0.0;

            $result[] = new ScopeCoverageByLanguageItemDTO(
                languageId: (int)$row['language_id'],
                languageCode: (string)$row['language_code'],
                languageName: (string)$row['language_name'],
                languageIcon: $row['language_icon'] ?? null,
                totalKeys: $total,
                translatedCount: $translated,
                missingCount: (int)$row['missing_count'],
                completionPercent: (float)$percent
            );
        }

        return $result;
    }

    public function getScopeCoverageByDomain(int $scopeId, int $languageId): array
    {
        // 1. Resolve Scope Code
        $stmtScope = $this->pdo->prepare('SELECT code FROM i18n_scopes WHERE id = :id');
        $stmtScope->execute(['id' => $scopeId]);
        $scopeCode = $stmtScope->fetchColumn();

        if (!$scopeCode) {
            return [];
        }

        // 2. Query Summary, List Domains for specific Language
        $sql = "
            SELECT
                d.id AS domain_id,
                d.code AS domain_code,
                d.name AS domain_name,
                dls.total_keys,
                dls.translated_count,
                dls.missing_count
            FROM i18n_domain_language_summary dls
            JOIN i18n_domain_scopes ds
                ON ds.scope_code = dls.scope
                AND ds.domain_code = dls.domain
            JOIN i18n_domains d
                ON d.code = dls.domain
            WHERE dls.scope = :scope_code
              AND dls.language_id = :language_id
            ORDER BY dls.missing_count DESC, d.sort_order ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'scope_code' => $scopeCode,
            'language_id' => $languageId
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $total = (int)$row['total_keys'];
            $translated = (int)$row['translated_count'];

            $percent = $total > 0
                ? round(($translated / $total) * 100, 1)
                : 0.0;

            $result[] = new ScopeCoverageByDomainItemDTO(
                domainId: (int)$row['domain_id'],
                domainCode: (string)$row['domain_code'],
                domainName: (string)$row['domain_name'],
                totalKeys: $total,
                translatedCount: $translated,
                missingCount: (int)$row['missing_count'],
                completionPercent: (float)$percent
            );
        }

        return $result;
    }
}
