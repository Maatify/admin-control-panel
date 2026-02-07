<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Scope\Writer;

use Maatify\AdminKernel\Domain\DTO\I18nScopes\I18nScopeCreateDTO;

interface I18nScopeCreateWriterInterface
{
    public function create(I18nScopeCreateDTO $dto): int;

    /**
     * Admin-only existence check.
     *
     * This method is intentionally placed here as a privileged
     * control-plane validation for admin create/update flows.
     */
    public function existsByCode(string $code): bool;
}


