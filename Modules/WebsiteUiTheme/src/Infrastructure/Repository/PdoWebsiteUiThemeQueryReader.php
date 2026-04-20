<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Infrastructure\Repository;

use Maatify\WebsiteUiTheme\Contract\WebsiteUiThemeQueryReaderInterface;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDropdownCollectionDTO;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDropdownItemDTO;
use Maatify\WebsiteUiTheme\Exception\WebsiteUiThemePersistenceException;
use PDO;
use PDOStatement;

final readonly class PdoWebsiteUiThemeQueryReader implements WebsiteUiThemeQueryReaderInterface
{
    public function __construct(private PDO $pdo) {}

    public function listAllForDropdown(): WebsiteUiThemeDropdownCollectionDTO
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_website_ui_themes` ORDER BY `entity_type` ASC, `display_name` ASC, `id` ASC');
        $stmt->execute();

        /** @var list<WebsiteUiThemeDropdownItemDTO> $items */
        $items = array_map(
            static fn (array $row): WebsiteUiThemeDropdownItemDTO => WebsiteUiThemeDropdownItemDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return new WebsiteUiThemeDropdownCollectionDTO($items);
    }

    public function listByEntityTypeForDropdown(string $entityType): WebsiteUiThemeDropdownCollectionDTO
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_website_ui_themes` WHERE `entity_type` = :entity_type ORDER BY `display_name` ASC, `id` ASC');
        $stmt->bindValue(':entity_type', trim($entityType));
        $stmt->execute();

        /** @var list<WebsiteUiThemeDropdownItemDTO> $items */
        $items = array_map(
            static fn (array $row): WebsiteUiThemeDropdownItemDTO => WebsiteUiThemeDropdownItemDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return new WebsiteUiThemeDropdownCollectionDTO($items);
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw WebsiteUiThemePersistenceException::prepareFailed($sql);
        }

        return $stmt;
    }

    /** @return list<array<string,mixed>> */
    private function fetchAllAssoc(PDOStatement $stmt): array
    {
        /** @var list<array<string,mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }
}
