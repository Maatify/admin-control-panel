<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Service;

use Maatify\WebsiteUiTheme\Command\CreateWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\Command\DeleteWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\Command\UpdateWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\Contract\WebsiteUiThemeCommandRepositoryInterface;
use Maatify\WebsiteUiTheme\Contract\WebsiteUiThemeQueryReaderInterface;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDTO;
use Maatify\WebsiteUiTheme\Exception\WebsiteUiThemeAlreadyExistsException;
use Maatify\WebsiteUiTheme\Exception\WebsiteUiThemeNotFoundException;

final readonly class WebsiteUiThemeCommandService
{
    public function __construct(
        private WebsiteUiThemeCommandRepositoryInterface $commandRepo,
        private WebsiteUiThemeQueryReaderInterface $queryReader,
    ) {}

    public function create(CreateWebsiteUiThemeCommand $command): WebsiteUiThemeDTO
    {
        $this->assertUnique($command->entityType, $command->themeFile, null);

        return $this->commandRepo->create($command);
    }

    public function update(UpdateWebsiteUiThemeCommand $command): WebsiteUiThemeDTO
    {
        $this->assertExists($command->id);
        $this->assertUnique($command->entityType, $command->themeFile, $command->id);

        return $this->commandRepo->update($command);
    }

    public function delete(DeleteWebsiteUiThemeCommand $command): void
    {
        $this->assertExists($command->id);

        $this->commandRepo->delete($command);
    }

    private function assertExists(int $id): void
    {
        if ($this->queryReader->findById($id) === null) {
            throw WebsiteUiThemeNotFoundException::withId($id);
        }
    }

    private function assertUnique(string $entityType, string $themeFile, ?int $excludeId): void
    {
        $existing = $this->queryReader->findByEntityTypeAndThemeFile($entityType, $themeFile);
        if ($existing === null) {
            return;
        }

        if ($excludeId !== null && $existing->id === $excludeId) {
            return;
        }

        throw WebsiteUiThemeAlreadyExistsException::withEntityTypeAndThemeFile($entityType, $themeFile);
    }
}
