<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse;

use Maatify\AdminKernel\Domain\Contracts\Abuse\ChallengeWidgetRendererInterface;
use Maatify\AdminKernel\Infrastructure\Security\Abuse\Enums\AbuseChallengeProviderEnum;

final class NullChallengeWidgetRenderer implements ChallengeWidgetRendererInterface
{
    public function render(): string
    {
        return '';
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function providerKey(): string
    {
        // No provider is active
        return AbuseChallengeProviderEnum::NONE->value;
    }

    public function tokenFieldName(): string
    {
        // No token is expected or processed
        return '';
    }

    public function renderWidgetHtml(): string
    {
        // Explicitly render nothing
        return '';
    }
}
