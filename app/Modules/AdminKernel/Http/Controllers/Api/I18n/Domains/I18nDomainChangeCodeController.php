<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 12:09
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains;

use Maatify\AdminKernel\Domain\Exception\EntityAlreadyExistsException;
use Maatify\AdminKernel\Domain\Exception\EntityInUseException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\I18n\Domain\I18nDomainUpdaterInterface;
use Maatify\AdminKernel\Domain\I18n\Domain\Validation\I18nDomainChangeCodeSchema;
use Maatify\AdminKernel\Domain\I18n\Service\I18nDomainUsageService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nDomainChangeCodeController
{
    public function __construct(
        private I18nDomainUpdaterInterface $writer,
        private I18nDomainUsageService $usageService,
        private ValidationGuard $validationGuard
    )
    {
    }
    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        $this->validationGuard->check(new I18nDomainChangeCodeSchema(), $body);

        $id = 0;
        if (isset($body['id']) && is_numeric($body['id'])) {
            $id = (int)$body['id'];
        }

        $newCode = is_string($body['new_code']) ? $body['new_code'] : '';

        if (! $this->writer->existsById($id)) {
            throw new EntityNotFoundException('I18nDomain', (string)$id);
        }

        $currentCode = $this->writer->getCurrentCode($id);
        if ($this->usageService->isDomainCodeInUse($currentCode)) {
            throw new EntityInUseException(
                'I18nDomain',
                $currentCode,
                'domains or translations'
            );
        }

        if ($this->writer->existsByCode($newCode)) {
            throw new EntityAlreadyExistsException(
                'I18nDomain',
                'code',
                $newCode
            );
        }

        $this->writer->changeCode($id, $newCode);

        $response->getBody()->write(json_encode([
            'status' => 'ok'
        ], JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
