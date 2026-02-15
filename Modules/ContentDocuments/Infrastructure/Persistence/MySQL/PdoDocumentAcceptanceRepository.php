<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Infrastructure\Persistence\MySQL;

use DateTimeImmutable;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentAcceptanceRepositoryInterface;
use Maatify\ContentDocuments\Domain\Entity\DocumentAcceptance;
use Maatify\ContentDocuments\Domain\Exception\DocumentAlreadyAcceptedException;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use PDO;
use PDOException;

final readonly class PdoDocumentAcceptanceRepository implements DocumentAcceptanceRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function hasAccepted(
        ActorIdentity $actor,
        int $documentId,
        DocumentVersion $version
    ): bool {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM document_acceptance
             WHERE actor_type = :actor_type
               AND actor_id = :actor_id
               AND document_id = :document_id
               AND version = :version
             LIMIT 1'
        );

        $stmt->execute([
            'actor_type'  => $actor->actorType,
            'actor_id'    => $actor->actorId,
            'document_id' => $documentId,
            'version'     => (string) $version,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function save(DocumentAcceptance $acceptance): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO document_acceptance
                    (actor_type, actor_id, document_id, version, accepted_at, ip_address, user_agent)
                 VALUES
                    (:actor_type, :actor_id, :document_id, :version, :accepted_at, :ip_address, :user_agent)'
            );

            $stmt->execute([
                'actor_type'  => $acceptance->actor->actorType,
                'actor_id'    => $acceptance->actor->actorId,
                'document_id' => $acceptance->documentId,
                'version'     => (string) $acceptance->version,
                'accepted_at' => $acceptance->acceptedAt->format('Y-m-d H:i:s'),
                'ip_address'  => $acceptance->ipAddress,
                'user_agent'  => $acceptance->userAgent,
            ]);

        } catch (PDOException $e) {
            if (
                (string)$e->getCode() === '23000'
                && isset($e->errorInfo[2])
                && str_contains($e->errorInfo[2], 'uq_actor_document_version')
            ) {
                throw new DocumentAlreadyAcceptedException();
            }

            throw $e;
        }
    }

    /**
     * @return list<DocumentAcceptance>
     */
    public function findByActor(ActorIdentity $actor): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM document_acceptance
             WHERE actor_type = :actor_type
               AND actor_id = :actor_id
             ORDER BY accepted_at DESC'
        );

        $stmt->execute([
            'actor_type' => $actor->actorType,
            'actor_id'   => $actor->actorId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id          = $row['id'] ?? null;
            $actorType   = $row['actor_type'] ?? null;
            $actorId     = $row['actor_id'] ?? null;
            $documentId  = $row['document_id'] ?? null;
            $version     = $row['version'] ?? null;
            $acceptedAt  = $row['accepted_at'] ?? null;
            $ipAddress   = $row['ip_address'] ?? null;
            $userAgent   = $row['user_agent'] ?? null;

            if (
                !is_numeric($id) ||
                !is_string($actorType) ||
                !is_numeric($actorId) ||
                !is_numeric($documentId) ||
                !is_string($version) ||
                !is_string($acceptedAt)
            ) {
                continue;
            }

            $result[] = new DocumentAcceptance(
                id: (int) $id,
                actor: new ActorIdentity(
                    $actorType,
                    (int) $actorId
                ),
                documentId: (int) $documentId,
                version: new DocumentVersion($version),
                acceptedAt: new DateTimeImmutable($acceptedAt),
                ipAddress: is_string($ipAddress) ? $ipAddress : null,
                userAgent: is_string($userAgent) ? $userAgent : null,
            );
        }

        return $result;
    }

    public function findOne(
        ActorIdentity $actor,
        int $documentId,
        DocumentVersion $version
    ): ?DocumentAcceptance {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM document_acceptance
             WHERE actor_type = :actor_type
               AND actor_id = :actor_id
               AND document_id = :document_id
               AND version = :version
             LIMIT 1'
        );

        $stmt->execute([
            'actor_type'  => $actor->actorType,
            'actor_id'    => $actor->actorId,
            'document_id' => $documentId,
            'version'     => (string) $version,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        $id         = $row['id'] ?? null;
        $actorType  = $row['actor_type'] ?? null;
        $actorId    = $row['actor_id'] ?? null;
        $docId      = $row['document_id'] ?? null;
        $ver        = $row['version'] ?? null;
        $acceptedAt = $row['accepted_at'] ?? null;

        if (
            !is_numeric($id) ||
            !is_string($actorType) ||
            !is_numeric($actorId) ||
            !is_numeric($docId) ||
            !is_string($ver) ||
            !is_string($acceptedAt)
        ) {
            return null;
        }

        return new DocumentAcceptance(
            id: (int) $id,
            actor: new ActorIdentity(
                $actorType,
                (int) $actorId
            ),
            documentId: (int) $docId,
            version: new DocumentVersion($ver),
            acceptedAt: new DateTimeImmutable($acceptedAt),
            ipAddress: is_string($row['ip_address'] ?? null) ? $row['ip_address'] : null,
            userAgent: is_string($row['user_agent'] ?? null) ? $row['user_agent'] : null,
        );
    }

    /**
     * @return list<array{document_id:int, version:string}>
     */
    public function findAcceptedDocumentVersions(ActorIdentity $actor): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT document_id, version
         FROM document_acceptance
         WHERE actor_type = :actor_type
           AND actor_id = :actor_id'
        );

        $stmt->execute([
            'actor_type' => $actor->actorType,
            'actor_id'   => $actor->actorId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $out = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $documentId = $row['document_id'] ?? null;
            $version    = $row['version'] ?? null;

            if (!is_numeric($documentId) || !is_string($version)) {
                continue;
            }

            $out[] = [
                'document_id' => (int) $documentId,
                'version'     => $version,
            ];
        }

        return $out;
    }


}
