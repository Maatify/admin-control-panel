<?php

declare(strict_types=1);

namespace Tests\Modules\ContentDocuments\Unit\Service;

use DateTimeImmutable;
use Maatify\ContentDocuments\Application\Service\DocumentTranslationService;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface;
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;
use Maatify\ContentDocuments\Domain\Entity\Document;
use Maatify\ContentDocuments\Domain\Entity\DocumentTranslation;
use Maatify\ContentDocuments\Domain\Exception\DocumentNotFoundException;
use Maatify\ContentDocuments\Domain\Exception\DocumentVersionImmutableException;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DocumentTranslationServiceTest extends TestCase
{
    private MockObject&DocumentRepositoryInterface $documentRepo;
    private MockObject&DocumentTranslationRepositoryInterface $translationRepo;
    private MockObject&ClockInterface $clock;
    private DocumentTranslationService $service;

    protected function setUp(): void
    {
        $this->documentRepo = $this->createMock(DocumentRepositoryInterface::class);
        $this->translationRepo = $this->createMock(DocumentTranslationRepositoryInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);

        $this->service = new DocumentTranslationService(
            $this->documentRepo,
            $this->translationRepo,
            $this->clock
        );
    }

    private function createDocument(bool $active, ?DateTimeImmutable $publishedAt, ?DateTimeImmutable $archivedAt): Document
    {
        return new Document(
            id: 1,
            documentTypeId: 1,
            typeKey: new DocumentTypeKey('terms'),
            version: new DocumentVersion('v1'),
            isActive: $active,
            requiresAcceptance: true,
            publishedAt: $publishedAt,
            archivedAt: $archivedAt,
            createdAt: new DateTimeImmutable(),
            updatedAt: null
        );
    }

    private function createDTO(): DocumentTranslationDTO
    {
        return new DocumentTranslationDTO(
            documentId: 1,
            languageId: 1,
            title: 'Terms V1',
            metaTitle: 'Meta',
            metaDescription: 'Desc',
            content: 'Content',
            createdAt: null,
            updatedAt: null
        );
    }

    public function testSaveFailsIfDocumentNotFound(): void
    {
        $this->documentRepo->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->expectException(DocumentNotFoundException::class);
        $this->service->save($this->createDTO());
    }

    public function testSaveFailsIfPublished(): void
    {
        $doc = $this->createDocument(false, new DateTimeImmutable(), null);

        $this->documentRepo->expects(self::once())
            ->method('findById')
            ->willReturn($doc);

        $this->expectException(DocumentVersionImmutableException::class);
        $this->service->save($this->createDTO());
    }

    public function testSaveFailsIfActive(): void
    {
        $doc = $this->createDocument(true, new DateTimeImmutable(), null);

        $this->documentRepo->expects(self::once())
            ->method('findById')
            ->willReturn($doc);

        $this->expectException(DocumentVersionImmutableException::class);
        $this->service->save($this->createDTO());
    }

    public function testSaveFailsIfArchived(): void
    {
        $doc = $this->createDocument(false, null, new DateTimeImmutable());

        $this->documentRepo->expects(self::once())
            ->method('findById')
            ->willReturn($doc);

        $this->expectException(DocumentVersionImmutableException::class);
        $this->service->save($this->createDTO());
    }

    public function testSaveCreatesTranslationIfNotFound(): void
    {
        $doc = $this->createDocument(false, null, null);
        $dto = $this->createDTO();

        $this->documentRepo->expects(self::once())
            ->method('findById')
            ->willReturn($doc);

        $this->translationRepo->expects(self::once())
            ->method('findByDocumentAndLanguage')
            ->with(1, 1)
            ->willReturn(null);

        $now = new DateTimeImmutable();
        $this->clock->expects(self::once())
            ->method('now')
            ->willReturn($now);

        $this->translationRepo->expects(self::once())
            ->method('create')
            ->with(self::callback(function (DocumentTranslation $t) use ($dto, $now) {
                return $t->documentId === $dto->documentId
                    && $t->languageId === $dto->languageId
                    && $t->title === $dto->title
                    && $t->createdAt == $now;
            }));

        $this->service->save($dto);
    }

    public function testSaveUpdatesTranslationIfFound(): void
    {
        $doc = $this->createDocument(false, null, null);
        $dto = $this->createDTO();
        $existing = new DocumentTranslation(
            id: 10,
            documentId: 1,
            languageId: 1,
            title: 'Old Title',
            metaTitle: 'Old Meta',
            metaDescription: 'Old Desc',
            content: 'Old Content',
            createdAt: new DateTimeImmutable(),
            updatedAt: null
        );

        $this->documentRepo->expects(self::once())
            ->method('findById')
            ->willReturn($doc);

        $this->translationRepo->expects(self::once())
            ->method('findByDocumentAndLanguage')
            ->with(1, 1)
            ->willReturn($existing);

        $now = new DateTimeImmutable();
        $this->clock->expects(self::once())
            ->method('now')
            ->willReturn($now);

        $this->translationRepo->expects(self::once())
            ->method('update')
            ->with(self::callback(function (DocumentTranslation $t) use ($existing, $dto, $now) {
                return $t->id === $existing->id
                    && $t->title === $dto->title
                    && $t->updatedAt == $now;
            }));

        $this->service->save($dto);
    }
}
