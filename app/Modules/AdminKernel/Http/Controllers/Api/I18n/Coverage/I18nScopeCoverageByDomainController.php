<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Coverage;

use Maatify\AdminKernel\Domain\I18n\Coverage\I18nScopeCoverageReaderInterface;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeCoverageByDomainController
{
    public function __construct(
        private I18nScopeCoverageReaderInterface $reader,
        private JsonResponseFactory $json
    ) {
    }

    /**
     * @param array{scope_id: string, language_id: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $scopeId = (int)$args['scope_id'];
        $languageId = (int)$args['language_id'];

        $result = $this->reader->getScopeCoverageByDomain($scopeId, $languageId);

        return $this->json->data($response, $result);
    }
}
