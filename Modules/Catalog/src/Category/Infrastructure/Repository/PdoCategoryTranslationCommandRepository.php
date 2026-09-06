<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Infrastructure\Repository;

use DateTimeImmutable;
use Maatify\Catalog\Category\Contract\CategoryTranslationCommandRepositoryInterface;
use Maatify\Catalog\Category\DTO\UpdateCategoryTranslationDTO;
use PDO;

/** PDO write adapter for Category translation content. */
final readonly class PdoCategoryTranslationCommandRepository implements CategoryTranslationCommandRepositoryInterface
{
    private const TRANSLATION_TABLE = 'maa_catalog_category_translations';

    public function __construct(private PDO $pdo) {}

    public function update(UpdateCategoryTranslationDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::TRANSLATION_TABLE . '` '
            . 'SET `name` = :name, `description` = :description, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL',
        );
        $statement->execute([
            'name' => $command->name,
            'description' => $command->description,
            'updated_at' => $occurredAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'id' => $command->translationId,
        ]);

        return $statement->rowCount() > 0;
    }
}
