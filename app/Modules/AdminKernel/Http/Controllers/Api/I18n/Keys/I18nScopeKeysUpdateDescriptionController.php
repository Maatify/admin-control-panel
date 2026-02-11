<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use Maatify\AdminKernel\Domain\I18n\Keys\I18nScopeKeyCommandService;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Validation\Schemas\I18n\TranslationKey\TranslationKeyUpdateDescriptionSchema;
use Maatify\I18n\Exception\TranslationKeyNotFoundException;
use Maatify\I18n\Exception\TranslationUpdateFailedException;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeKeysUpdateDescriptionController
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

        /** @var array{key_id:int, description:string} $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(
            new TranslationKeyUpdateDescriptionSchema(),
            $body
        );

        // Validate scope id and if not found will throw entity not found exception
        $dto = $this->scopeDetailsReader->getScopeDetailsById($scopeId);

        try{
            $this->writer->updateDescription(
                keyId: (int) $body['key_id'],
                scopeCode: $dto->code,
                description: $body['description'],
            );
        }catch (TranslationKeyNotFoundException $e){
            throw new EntityNotFoundException('key not found', 'keyId');
        }catch (TranslationUpdateFailedException $e){
            throw new InvalidOperationException('keyId', 'update_description', $e->getMessage());
        }

        return $this->json->success($response);
    }
}
