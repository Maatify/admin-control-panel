<?php

declare(strict_types=1);

namespace Maatify\EmailDelivery\Renderer;

use Maatify\AdminKernel\Domain\DTO\Email\EmailPayloadInterface;
use Maatify\EmailDelivery\DTO\RenderedEmailDTO;
use Maatify\EmailDelivery\Exception\EmailRenderException;

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
