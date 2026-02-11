<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 12:06
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys;

use Maatify\AdminKernel\Domain\Exception\EntityAlreadyExistsException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use Maatify\AdminKernel\Domain\I18n\Keys\I18nScopeKeyCommandService;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Validation\Schemas\I18n\TranslationKey\TranslationKeyUpdateNameSchema;
use Maatify\I18n\Exception\TranslationKeyAlreadyExistsException;
use Maatify\I18n\Exception\TranslationKeyNotFoundException;
use Maatify\I18n\Exception\TranslationUpdateFailedException;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

final readonly class I18nScopeKeysUpdateNameController
{
    public function __construct(
        private I18nScopeDetailsRepositoryInterface $scopeDetailsReader,
        private I18nScopeKeyCommandService $writer,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {}

    /**
     * @param array{scope_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $scopeId = (int) $args['scope_id'];

        /** @var array{key_id:int, key_name:string} $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(
            new TranslationKeyUpdateNameSchema(),
            $body
        );

        // Validate scope id and if not found will throw entity not found exception
        $scopeDto = $this->scopeDetailsReader->getScopeDetailsById($scopeId);

        $keyId = (int) $body['key_id'];
        $keyName = $body['key_name'];

        try{
            $this->writer->renameKey(
                keyId: $keyId,
                scopeCode: $scopeDto->code,
                newKey: $keyName
            );
        }catch (TranslationKeyNotFoundException $e){
            throw new EntityNotFoundException('key not found', 'keyId');
        }catch (TranslationKeyAlreadyExistsException){
            throw new EntityAlreadyExistsException('key already exists', 'keyName', $keyName);
        }catch (TranslationUpdateFailedException $e){
            throw new InvalidOperationException('keyId', 'rename', $e->getMessage());
        }

        return $this->json->success($response);
    }
}
