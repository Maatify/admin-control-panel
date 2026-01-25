<?php

declare(strict_types=1);

namespace App\Application\Services;

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

    public function __construct(
        private LoggerInterface $logger,
        // private AuditTrailRecorder $recorder // Dependency to be injected
    ) {
    }

    /**
     * Used when an admin views the details of a specific entity (e.g., User Profile).
     */
    public function recordResourceViewed(int $adminId, string $resourceType, string $resourceId): void
    {
        try {
            // $this->recorder->record(
            //     eventKey: self::EVENT_RESOURCE_VIEWED,
            //     actorId: $adminId,
            //     metadata: ['type' => $resourceType, 'id' => $resourceId]
            // );
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
            // $this->recorder->record(
            //     eventKey: self::EVENT_COLLECTION_VIEWED,
            //     actorId: $adminId,
            //     metadata: ['type' => $resourceType]
            // );
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
            // $this->recorder->record(
            //     eventKey: self::EVENT_SEARCH_PERFORMED,
            //     actorId: $adminId,
            //     metadata: [
            //         'type' => $resourceType,
            //         'query' => $query,
            //         'count' => $resultCount
            //     ]
            // );
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
            // $this->recorder->record(
            //     eventKey: self::EVENT_EXPORT_GENERATED,
            //     actorId: $adminId,
            //     metadata: [
            //         'type' => $resourceType,
            //         'format' => $format,
            //         'count' => $recordCount
            //     ]
            // );
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
            // $this->recorder->record(
            //     eventKey: self::EVENT_SUBJECT_VIEWED,
            //     actorId: $adminId,
            //     metadata: [
            //         'subject_type' => $subjectType,
            //         'subject_id' => $subjectId,
            //         'context' => $context
            //     ]
            // );
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
