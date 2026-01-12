<?php

declare(strict_types=1);

namespace App\Context;

interface ContextProviderInterface
{
    public function admin(): ?AdminContext;

    public function request(): RequestContext;
}
