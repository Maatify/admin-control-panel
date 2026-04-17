<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Infrastructure\Repository;

use Maatify\ImageProfile\Command\CreateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileStatusCommand;
use Maatify\ImageProfile\Contract\ImageProfileCommandRepositoryInterface;
use Maatify\ImageProfile\DTO\ImageProfileDTO;
use Maatify\ImageProfile\Exception\ImageProfileCodeAlreadyExistsException;
use Maatify\ImageProfile\Exception\ImageProfileNotFoundException;
use Maatify\ImageProfile\Exception\ImageProfilePersistenceException;
use PDO;
use PDOStatement;

final readonly class PdoImageProfileCommandRepository implements ImageProfileCommandRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function create(CreateImageProfileCommand $command): ImageProfileDTO
    {
        $stmt = $this->prepareOrFail(
            'INSERT INTO `maa_image_profiles` (
                `code`,`display_name`,`min_width`,`min_height`,`max_width`,`max_height`,`max_size_bytes`,
                `allowed_extensions`,`allowed_mime_types`,`is_active`,`notes`,`min_aspect_ratio`,`max_aspect_ratio`,
                `requires_transparency`,`preferred_format`,`preferred_quality`,`variants`
            ) VALUES (
                :code,:display_name,:min_width,:min_height,:max_width,:max_height,:max_size_bytes,
                :allowed_extensions,:allowed_mime_types,:is_active,:notes,:min_aspect_ratio,:max_aspect_ratio,
                :requires_transparency,:preferred_format,:preferred_quality,:variants
            )',
        );

        try {
            $stmt->execute($this->paramsFromCommand($command));
        } catch (\PDOException $e) {
            if ($this->isDuplicateCodeConstraintViolation($e)) {
                throw ImageProfileCodeAlreadyExistsException::withCode($command->code);
            }

            throw ImageProfilePersistenceException::fromPdoException($e);
        }

        return $this->fetchDtoOrFail((int) $this->pdo->lastInsertId());
    }

    public function update(UpdateImageProfileCommand $command): ImageProfileDTO
    {
        $stmt = $this->prepareOrFail(
            'UPDATE `maa_image_profiles`
             SET `code`=:code,
                 `display_name`=:display_name,
                 `min_width`=:min_width,
                 `min_height`=:min_height,
                 `max_width`=:max_width,
                 `max_height`=:max_height,
                 `max_size_bytes`=:max_size_bytes,
                 `allowed_extensions`=:allowed_extensions,
                 `allowed_mime_types`=:allowed_mime_types,
                 `is_active`=:is_active,
                 `notes`=:notes,
                 `min_aspect_ratio`=:min_aspect_ratio,
                 `max_aspect_ratio`=:max_aspect_ratio,
                 `requires_transparency`=:requires_transparency,
                 `preferred_format`=:preferred_format,
                 `preferred_quality`=:preferred_quality,
                 `variants`=:variants
             WHERE `id`=:id',
        );

        try {
            $params = $this->paramsFromCommand($command);
            $params[':id'] = $command->id;
            $stmt->execute($params);
        } catch (\PDOException $e) {
            if ($this->isDuplicateCodeConstraintViolation($e)) {
                throw ImageProfileCodeAlreadyExistsException::withCode($command->code);
            }

            throw ImageProfilePersistenceException::fromPdoException($e);
        }

        return $this->fetchDtoOrFail($command->id);
    }

    public function updateStatus(UpdateImageProfileStatusCommand $command): ImageProfileDTO
    {
        $stmt = $this->prepareOrFail('UPDATE `maa_image_profiles` SET `is_active` = :is_active WHERE `id` = :id');
        $stmt->execute([
            ':is_active' => $command->isActive ? 1 : 0,
            ':id' => $command->id,
        ]);

        return $this->fetchDtoOrFail($command->id);
    }

    /**
     * @param   CreateImageProfileCommand|UpdateImageProfileCommand  $command
     * @return array<string, int|string|null>
     */
    private function paramsFromCommand(object $command): array
    {
        return [
            ':code' => $command->code,
            ':display_name' => $command->displayName,
            ':min_width' => $command->minWidth,
            ':min_height' => $command->minHeight,
            ':max_width' => $command->maxWidth,
            ':max_height' => $command->maxHeight,
            ':max_size_bytes' => $command->maxSizeBytes,
            ':allowed_extensions' => $command->allowedExtensions,
            ':allowed_mime_types' => $command->allowedMimeTypes,
            ':is_active' => $command->isActive ? 1 : 0,
            ':notes' => $command->notes,
            ':min_aspect_ratio' => $command->minAspectRatio,
            ':max_aspect_ratio' => $command->maxAspectRatio,
            ':requires_transparency' => $command->requiresTransparency ? 1 : 0,
            ':preferred_format' => $command->preferredFormat,
            ':preferred_quality' => $command->preferredQuality,
            ':variants' => $command->variants,
        ];
    }

    private function fetchDtoOrFail(int $id): ImageProfileDTO
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `maa_image_profiles` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            throw ImageProfileNotFoundException::withId($id);
        }

        return ImageProfileDTO::fromRow($row);
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw ImageProfilePersistenceException::prepareFailed($sql);
        }

        return $stmt;
    }

    private function isDuplicateCodeConstraintViolation(\PDOException $e): bool
    {
        if ($e->getCode() !== '23000') {
            return false;
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, '1062')
            || str_contains($message, 'uq_maa_image_profiles_code')
            || str_contains($message, 'maa_image_profiles.code');
    }
}
