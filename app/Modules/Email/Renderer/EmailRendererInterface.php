<?php

declare(strict_types=1);

namespace App\Modules\Email\Renderer;

use Maatify\AdminKernel\Domain\DTO\Email\EmailPayloadInterface;
use App\Modules\Email\DTO\RenderedEmailDTO;
use App\Modules\Email\Exception\EmailRenderException;

interface EmailRendererInterface
{
    /**
     * @throws EmailRenderException
     */
    public function render(
        string $templateKey,
        string $language,
        EmailPayloadInterface $payload
    ): RenderedEmailDTO;
}
