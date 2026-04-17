<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Contract;

use Maatify\ImageProfile\Command\CreateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileStatusCommand;
use Maatify\ImageProfile\DTO\ImageProfileDTO;

/**
 * Write side — all mutations on maa_image_profiles.
 */
interface ImageProfileCommandRepositoryInterface
{
    public function create(CreateImageProfileCommand $command): ImageProfileDTO;

    public function update(UpdateImageProfileCommand $command): ImageProfileDTO;

    public function updateStatus(UpdateImageProfileStatusCommand $command): ImageProfileDTO;
}
