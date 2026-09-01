<?php

declare(strict_types=1);

namespace Maatify\I18n\Infrastructure\Mysql;

use Maatify\I18n\Contract\I18nDashboardStatsReaderInterface;
use Maatify\I18n\DTO\I18nStatCountDTO;
use PDO;

final readonly class MysqlI18nDashboardStatsReader implements I18nDashboardStatsReaderInterface
{
    public function __construct(private PDO $pdo) {}

    /** @return list<I18nStatCountDTO> */
    public function coveragePercentByLanguage(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                l.`name` AS `label`,
                CASE
                    WHEN SUM(dls.`total_keys`) = 0 THEN 0
                    ELSE ROUND(SUM(dls.`translated_count`) * 100.0 / SUM(dls.`total_keys`), 0)
                END AS `cnt`
            FROM `i18n_domain_language_summary` dls
            JOIN `languages` l ON l.`id` = dls.`language_id`
            GROUP BY dls.`language_id`, l.`name`
            ORDER BY `cnt` DESC
        ");

        if ($stmt === false) {
            throw new \RuntimeException('Failed to execute i18n coveragePercentByLanguage query');
        }

        return $this->hydrate($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return list<I18nStatCountDTO> */
    public function missingTranslationsByLanguage(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                l.`name` AS `label`,
                SUM(dls.`missing_count`) AS `cnt`
            FROM `i18n_domain_language_summary` dls
            JOIN `languages` l ON l.`id` = dls.`language_id`
            GROUP BY dls.`language_id`, l.`name`
            ORDER BY `cnt` DESC
        ");

        if ($stmt === false) {
            throw new \RuntimeException('Failed to execute i18n missingTranslationsByLanguage query');
        }

        return $this->hydrate($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return list<I18nStatCountDTO> */
    public function keyCountByScope(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                s.`name` AS `label`,
                COUNT(*) AS `cnt`
            FROM `i18n_keys` k
            JOIN `i18n_scopes` s ON s.`code` = k.`scope`
            GROUP BY k.`scope`, s.`name`
            ORDER BY `cnt` DESC
        ");

        if ($stmt === false) {
            throw new \RuntimeException('Failed to execute i18n keyCountByScope query');
        }

        return $this->hydrate($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<I18nStatCountDTO>
     */
    private function hydrate(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $label = $row['label'] ?? null;
            $cnt   = $row['cnt']   ?? null;

            if (is_string($label) && (is_int($cnt) || is_string($cnt) || is_float($cnt))) {
                $items[] = new I18nStatCountDTO(
                    label: $label,
                    count: (int) $cnt,
                );
            }
        }

        return $items;
    }
}
