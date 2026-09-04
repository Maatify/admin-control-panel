<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Services;

use Maatify\AdminKernel\Application\Contracts\AuditTrailRecorderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Tracks "who viewed what" — specifically data exposure, navigation, exports, and search history.
 *
 * BEHAVIOR GUARANTEE: FAIL-OPEN (Best Effort)
 * Logging visibility events MUST NOT block read operations.
 */
class AuditTrailService
{
    private const EVENT_RESOURCE_VIEWED = 'resource.view';
    private const EVENT_COLLECTION_VIEWED = 'collection.view';
    private const EVENT_SEARCH_PERFORMED = 'search.perform';
    private const EVENT_EXPORT_GENERATED = 'export.generate';
    private const EVENT_SUBJECT_VIEWED = 'subject.view';

    private const ACTOR_TYPE_ADMIN = 'ADMIN';

    public function __construct(
        private LoggerInterface $logger,
        private AuditTrailRecorderInterface $recorder
    ) {
    }

    /**
     * Used when an admin views the details of a specific entity (e.g., User Profile).
     */
    public function recordResourceViewed(int $adminId, string $resourceType, string $resourceId): void
    {
        try {
            $this->recorder->record(
                eventKey: self::EVENT_RESOURCE_VIEWED,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                entityType: $resourceType,
                entityId: (int)$resourceId,
                metadata: ['type' => $resourceType, 'id' => $resourceId]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordResourceViewed', $e);
        }
    }

    /**
     * Used when an admin views a list/index of entities, optionally with filters.
     */
    public function recordCollectionViewed(int $adminId, string $resourceType): void
    {
        try {
            $this->recorder->record(
                eventKey: self::EVENT_COLLECTION_VIEWED,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                entityType: $resourceType,
                metadata: ['type' => $resourceType]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordCollectionViewed', $e);
        }
    }

    /**
     * Used when an admin executes a search query.
     */
    public function recordSearchPerformed(int $adminId, string $resourceType, string $query, int $resultCount): void
    {
        try {
            $this->recorder->record(
                eventKey: self::EVENT_SEARCH_PERFORMED,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                entityType: $resourceType,
                metadata: [
                    'query' => $query,
                    'count' => $resultCount
                ]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordSearchPerformed', $e);
        }
    }

    /**
     * Used when an admin generates and downloads a data export (CSV, PDF).
     */
    public function recordExportGenerated(int $adminId, string $resourceType, string $format, int $recordCount): void
    {
        try {
            $this->recorder->record(
                eventKey: self::EVENT_EXPORT_GENERATED,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                entityType: $resourceType,
                metadata: [
                    'format' => $format,
                    'count' => $recordCount
                ]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordExportGenerated', $e);
        }
    }

    /**
     * Used when an admin views sensitive data belonging to a specific subject (e.g., User, Customer).
     */
    public function recordSubjectViewed(int $adminId, string $subjectType, int $subjectId, string $context): void
    {
        try {
            $this->recorder->record(
                eventKey: self::EVENT_SUBJECT_VIEWED,
                actorType: self::ACTOR_TYPE_ADMIN,
                actorId: $adminId,
                entityType: $subjectType, // Subject IS the entity being viewed
                entityId: $subjectId,
                subjectType: $subjectType,
                subjectId: $subjectId,
                metadata: [
                    'context' => $context
                ]
            );
        } catch (Throwable $e) {
            $this->logFailure('recordSubjectViewed', $e);
        }
    }

    private function logFailure(string $method, Throwable $e): void
    {
        $this->logger->error(
            sprintf('[AuditTrailService] %s failed: %s', $method, $e->getMessage()),
            ['exception' => $e]
        );
    }
}
