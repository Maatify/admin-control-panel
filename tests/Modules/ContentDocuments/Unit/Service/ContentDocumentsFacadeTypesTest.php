<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Unit\Service;

use Maatify\ContentDocuments\Application\Service\ContentDocumentsFacade;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentAcceptanceServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentEnforcementServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentLifecycleServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentQueryServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentTypeServiceInterface;
use Maatify\ContentDocuments\Domain\DTO\DocumentTypeDTO;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PHPUnit\Framework\TestCase;

final class ContentDocumentsFacadeTypesTest extends TestCase
{
    public function testListTypesDelegatesToTypeService(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $trRepo   = $this->createMock(DocumentTranslationRepositoryInterface::class);
        $query    = $this->createMock(DocumentQueryServiceInterface::class);
        $accept   = $this->createMock(DocumentAcceptanceServiceInterface::class);
        $life     = $this->createMock(DocumentLifecycleServiceInterface::class);
        $enf      = $this->createMock(DocumentEnforcementServiceInterface::class);
        $typeSvc  = $this->createMock(DocumentTypeServiceInterface::class);
        $clock    = $this->createMock(ClockInterface::class);

        $typeSvc->expects($this->once())
            ->method('list')
            ->willReturn([
                new DocumentTypeDTO(
                    id: 1,
                    key: 'terms',
                    requiresAcceptanceDefault: true,
                    isSystem: true,
                    createdAt: new \DateTimeImmutable('2024-01-01 00:00:00'),
                    updatedAt: null
                ),
            ]);

        $facade = new ContentDocumentsFacade(
            documentRepository: $docRepo,
            translationRepository: $trRepo,
            queryService: $query,
            acceptanceService: $accept,
            lifecycleService: $life,
            enforcementService: $enf,
            documentTypeService: $typeSvc,
            clock: $clock
        );

        $out = $facade->listTypes();
        self::assertCount(1, $out);
        self::assertSame('terms', $out[0]->key);
    }

    public function testCreateAndUpdateTypeDelegate(): void
    {
        $docRepo  = $this->createMock(DocumentRepositoryInterface::class);
        $trRepo   = $this->createMock(DocumentTranslationRepositoryInterface::class);
        $query    = $this->createMock(DocumentQueryServiceInterface::class);
        $accept   = $this->createMock(DocumentAcceptanceServiceInterface::class);
        $life     = $this->createMock(DocumentLifecycleServiceInterface::class);
        $enf      = $this->createMock(DocumentEnforcementServiceInterface::class);
        $typeSvc  = $this->createMock(DocumentTypeServiceInterface::class);
        $clock    = $this->createMock(ClockInterface::class);

        $typeSvc->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(DocumentTypeKey::class), true, true)
            ->willReturn(99);

        $typeSvc->expects($this->once())
            ->method('update')
            ->with(99, false, true);

        $facade = new ContentDocumentsFacade(
            documentRepository: $docRepo,
            translationRepository: $trRepo,
            queryService: $query,
            acceptanceService: $accept,
            lifecycleService: $life,
            enforcementService: $enf,
            documentTypeService: $typeSvc,
            clock: $clock
        );

        $id = $facade->createType(new DocumentTypeKey('terms'), true, true);
        self::assertSame(99, $id);

        $facade->updateType(99, false, true);
        self::assertTrue(true);
    }
}
