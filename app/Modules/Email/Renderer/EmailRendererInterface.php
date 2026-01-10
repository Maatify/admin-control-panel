<?php

declare(strict_types=1);

namespace App\Modules\Email\Renderer;

use App\Domain\DTO\Email\EmailPayloadInterface;
use App\Modules\Email\DTO\RenderedEmailDTO;
use App\Modules\Email\Exception\EmailRenderingException;

interface EmailRendererInterface
{
    /**
     * @throws EmailRenderingException
     */
    public function render(
        string $templateKey,
        string $language,
        EmailPayloadInterface $payload
    ): RenderedEmailDTO;
}
