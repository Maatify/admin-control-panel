<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Service;

use Maatify\ImageProfile\Command\CreateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileStatusCommand;
use Maatify\ImageProfile\Contract\ImageProfileCommandRepositoryInterface;
use Maatify\ImageProfile\Contract\ImageProfileQueryReaderInterface;
use Maatify\ImageProfile\DTO\ImageProfileDTO;
use Maatify\ImageProfile\Exception\ImageProfileCodeAlreadyExistsException;
use Maatify\ImageProfile\Exception\ImageProfileNotFoundException;

final readonly class ImageProfileCommandService
{
    public function __construct(
        private ImageProfileCommandRepositoryInterface $commandRepo,
        private ImageProfileQueryReaderInterface $queryReader,
    ) {}

    public function create(CreateImageProfileCommand $command): ImageProfileDTO
    {
        $this->assertCodeIsUnique($command->code, null);

        return $this->commandRepo->create($command);
    }

    public function update(UpdateImageProfileCommand $command): ImageProfileDTO
    {
        $this->assertExists($command->id);
        $this->assertCodeIsUnique($command->code, $command->id);

        return $this->commandRepo->update($command);
    }

    public function updateStatus(UpdateImageProfileStatusCommand $command): ImageProfileDTO
    {
        $this->assertExists($command->id);

        return $this->commandRepo->updateStatus($command);
    }

    private function assertExists(int $id): void
    {
        if ($this->queryReader->findById($id) === null) {
            throw ImageProfileNotFoundException::withId($id);
        }
    }

    private function assertCodeIsUnique(string $code, ?int $excludeId): void
    {
        $existing = $this->queryReader->findByCode($code);
        if ($existing === null) {
            return;
        }

        if ($excludeId !== null && $existing->id === $excludeId) {
            return;
        }

        throw ImageProfileCodeAlreadyExistsException::withCode($code);
    }
}
