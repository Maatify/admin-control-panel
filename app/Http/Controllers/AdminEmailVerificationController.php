<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\DTO\Response\VerificationResponseDTO;
use App\Domain\Service\AdminEmailVerificationService;
use App\Infrastructure\Repository\AdminEmailRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AdminEmailVerificationController
{
    public function __construct(
        private readonly AdminEmailVerificationService $service,
        private readonly AdminEmailRepository $repository
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function verify(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminId = (int)$args['id'];

        $this->service->verify($adminId);

        $status = $this->repository->getVerificationStatus($adminId);

        $dto = new VerificationResponseDTO($adminId, $status);

        $response->getBody()->write((string)json_encode($dto));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
