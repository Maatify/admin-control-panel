<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\Infrastructure\Repository;

use Maatify\WebsiteUiTheme\Command\CreateWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\Command\DeleteWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\Command\UpdateWebsiteUiThemeCommand;
use Maatify\WebsiteUiTheme\Contract\WebsiteUiThemeCommandRepositoryInterface;
use Maatify\WebsiteUiTheme\DTO\WebsiteUiThemeDTO;
use Maatify\WebsiteUiTheme\Exception\WebsiteUiThemeAlreadyExistsException;
use Maatify\WebsiteUiTheme\Exception\WebsiteUiThemeNotFoundException;
use Maatify\WebsiteUiTheme\Exception\WebsiteUiThemePersistenceException;
use PDO;
use PDOStatement;

final readonly class PdoWebsiteUiThemeCommandRepository implements WebsiteUiThemeCommandRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function create(CreateWebsiteUiThemeCommand $command): WebsiteUiThemeDTO
    {
        $stmt = $this->prepareOrFail(
            'INSERT INTO `maa_website_ui_themes` (`entity_type`,`theme_file`,`display_name`) VALUES (:entity_type,:theme_file,:display_name)',
        );

        try {
            $stmt->execute($this->paramsFromCommand($command));
        } catch (\PDOException $e) {
            if ($this->isDuplicateConstraintViolation($e)) {
                throw WebsiteUiThemeAlreadyExistsException::withEntityTypeAndThemeFile($command->entityType, $command->themeFile);
            }

            throw WebsiteUiThemePersistenceException::fromPdoException($e);
        }

        return $this->fetchDtoOrFail((int) $this->pdo->lastInsertId());
    }

    public function update(UpdateWebsiteUiThemeCommand $command): WebsiteUiThemeDTO
    {
        $stmt = $this->prepareOrFail(
            'UPDATE `maa_website_ui_themes`
             SET `entity_type`=:entity_type,
                 `theme_file`=:theme_file,
                 `display_name`=:display_name
             WHERE `id`=:id',
        );

        try {
            $params = $this->paramsFromCommand($command);
            $params[':id'] = $command->id;
            $stmt->execute($params);
        } catch (\PDOException $e) {
            if ($this->isDuplicateConstraintViolation($e)) {
                throw WebsiteUiThemeAlreadyExistsException::withEntityTypeAndThemeFile($command->entityType, $command->themeFile);
            }

            throw WebsiteUiThemePersistenceException::fromPdoException($e);
        }

        return $this->fetchDtoOrFail($command->id);
    }

    public function delete(DeleteWebsiteUiThemeCommand $command): void
    {
        $stmt = $this->prepareOrFail('DELETE FROM `maa_website_ui_themes` WHERE `id` = :id');
        $stmt->execute([':id' => $command->id]);
    }

    /** @param CreateWebsiteUiThemeCommand|UpdateWebsiteUiThemeCommand $command
     *  @return array<string,string>
     */
    private function paramsFromCommand(object $command): array
    {
        return [
            ':entity_type' => trim($command->entityType),
            ':theme_file' => trim($command->themeFile),
            ':display_name' => trim($command->displayName),
        ];
    }

    private function fetchDtoOrFail(int $id): WebsiteUiThemeDTO
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_website_ui_themes` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            throw WebsiteUiThemeNotFoundException::withId($id);
        }

        return WebsiteUiThemeDTO::fromRow($row);
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw WebsiteUiThemePersistenceException::prepareFailed($sql);
        }

        return $stmt;
    }

    private function isDuplicateConstraintViolation(\PDOException $e): bool
    {
        if ($e->getCode() !== '23000') {
            return false;
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, '1062')
            || str_contains($message, 'uq_maa_website_ui_themes_entity_type_theme_file')
            || str_contains($message, 'maa_website_ui_themes.entity_type')
            || str_contains($message, 'maa_website_ui_themes.theme_file');
    }
}
