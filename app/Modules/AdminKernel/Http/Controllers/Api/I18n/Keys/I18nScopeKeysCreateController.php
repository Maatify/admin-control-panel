<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 12:01
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys;

use Maatify\AdminKernel\Domain\Exception\EntityAlreadyExistsException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use Maatify\AdminKernel\Domain\I18n\Keys\I18nScopeKeyCommandService;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Validation\Schemas\I18n\TranslationKey\TranslationKeyCreateSchema;
use Maatify\I18n\Exception\TranslationKeyAlreadyExistsException;
use Maatify\I18n\Exception\TranslationKeyCreateFailedException;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeKeysCreateController
{
    public function __construct(
        private I18nScopeDetailsRepositoryInterface $scopeDetailsReader,
        private I18nScopeKeyCommandService $writer,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {
    }

    /**
     * @param array{scope_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $scopeId = (int) $args['scope_id'];

        /** @var array{domain_code:string, key_name:string, description:string|null} $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new TranslationKeyCreateSchema(), $body);

        // 2) Explicit type narrowing (phpstan-safe)
        $keyName = $body['key_name'];

        $description = null;
        if (array_key_exists('description', $body)) {
            if (!is_string($body['description'])) {
                throw new InvalidOperationException('description', 'create', 'Invalid description value.');
            }
            $description = $body['description'];
        }

        // Validate scope id and if not found will throw entity not found exception
        $scopeDto = $this->scopeDetailsReader->getScopeDetailsById($scopeId);

        // 3) Execute service
        try{
            $this->writer->createKey(
                scope: $scopeDto->code,
                domain: $body['domain_code'],
                key: $body['key_name'],
                description: $description
            );
        }catch (TranslationKeyAlreadyExistsException $e){
            throw new EntityAlreadyExistsException('translation_key already exists', 'keyName', $keyName);
        }catch (TranslationKeyCreateFailedException $e){
            throw new InvalidOperationException('translation_key', 'create', $e->getMessage());
        }

        // 4) Response
        return $this->json->success($response);

    }
}
