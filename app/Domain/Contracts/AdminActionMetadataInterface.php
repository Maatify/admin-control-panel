<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface AdminActionMetadataInterface
{
    public function actionName(): string;

    public function targetType(): string;

    public function targetId(): ?int;

    public function summary(): string;
}
