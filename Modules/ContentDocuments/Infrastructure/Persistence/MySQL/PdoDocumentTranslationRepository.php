<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Infrastructure\Persistence\MySQL;

use DateTimeImmutable;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface;
use Maatify\ContentDocuments\Domain\Entity\DocumentTranslation;
use PDO;
use PDOException;

final readonly class PdoDocumentTranslationRepository implements DocumentTranslationRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function findByDocumentAndLanguage(
        int $documentId,
        int $languageId
    ): ?DocumentTranslation {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM document_translations
             WHERE document_id = :document_id
               AND language_id = :language_id
             LIMIT 1'
        );

        $stmt->execute([
            'document_id' => $documentId,
            'language_id' => $languageId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @return list<DocumentTranslation>
     */
    public function findByDocument(int $documentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM document_translations
             WHERE document_id = :document_id
             ORDER BY language_id ASC'
        );

        $stmt->execute([
            'document_id' => $documentId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $result[] = $this->hydrate($row);
        }

        return $result;
    }

    public function save(DocumentTranslation $translation): void
    {
        // IMPORTANT:
        // - Do not rely on ON DUPLICATE KEY UPDATE (hidden coupling).
        // - If entity id is provided, we must respect it (update-by-id).

        if ($translation->id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE document_translations
                 SET title = :title,
                     meta_title = :meta_title,
                     meta_description = :meta_description,
                     content = :content,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id
                   AND document_id = :document_id
                   AND language_id = :language_id'
            );

            $stmt->execute([
                'id'              => $translation->id,
                'document_id'     => $translation->documentId,
                'language_id'     => $translation->languageId,
                'title'           => $translation->title,
                'meta_title'      => $translation->metaTitle,
                'meta_description'=> $translation->metaDescription,
                'content'         => $translation->content,
            ]);

            return;
        }

        // id == 0 => attempt insert, fallback to update-by-natural-key on duplicate
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO document_translations
                    (document_id, language_id, title, meta_title, meta_description, content)
                 VALUES
                    (:document_id, :language_id, :title, :meta_title, :meta_description, :content)'
            );

            $stmt->execute([
                'document_id'      => $translation->documentId,
                'language_id'      => $translation->languageId,
                'title'            => $translation->title,
                'meta_title'       => $translation->metaTitle,
                'meta_description' => $translation->metaDescription,
                'content'          => $translation->content,
            ]);
        } catch (PDOException $e) {
            // Duplicate key => update existing row by (document_id, language_id)
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            $stmt = $this->pdo->prepare(
                'UPDATE document_translations
                 SET title = :title,
                     meta_title = :meta_title,
                     meta_description = :meta_description,
                     content = :content,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE document_id = :document_id
                   AND language_id = :language_id'
            );

            $stmt->execute([
                'document_id'      => $translation->documentId,
                'language_id'      => $translation->languageId,
                'title'            => $translation->title,
                'meta_title'       => $translation->metaTitle,
                'meta_description' => $translation->metaDescription,
                'content'          => $translation->content,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): DocumentTranslation
    {
        $id              = $row['id'] ?? null;
        $documentId      = $row['document_id'] ?? null;
        $languageId      = $row['language_id'] ?? null;
        $title           = $row['title'] ?? null;
        $metaTitle       = $row['meta_title'] ?? null;
        $metaDescription = $row['meta_description'] ?? null;
        $content         = $row['content'] ?? null;
        $createdAt       = $row['created_at'] ?? null;
        $updatedAt       = $row['updated_at'] ?? null;

        if (
            !is_numeric($id) ||
            !is_numeric($documentId) ||
            !is_numeric($languageId) ||
            !is_string($title) ||
            !is_string($content) ||
            !is_string($createdAt)
        ) {
            throw new \RuntimeException('Invalid document_translation row shape.');
        }

        return new DocumentTranslation(
            id: (int) $id,
            documentId: (int) $documentId,
            languageId: (int) $languageId,
            title: $title,
            metaTitle: is_string($metaTitle) ? $metaTitle : null,
            metaDescription: is_string($metaDescription) ? $metaDescription : null,
            content: $content,
            createdAt: new DateTimeImmutable($createdAt),
            updatedAt: is_string($updatedAt)
                ? new DateTimeImmutable($updatedAt)
                : null,
        );
    }
}
