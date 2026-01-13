<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Domain\DTO\Request\CreateAdminEmailRequestDTO;
use App\Domain\DTO\Request\VerifyAdminEmailRequestDTO;
use App\Domain\DTO\Response\ActionResultResponseDTO;
use App\Domain\DTO\Response\AdminEmailResponseDTO;
use App\Domain\Enum\IdentifierType;
use App\Domain\Exception\InvalidIdentifierFormatException;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminRepository;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\AdminAddEmailSchema;
use App\Modules\Validation\Schemas\AdminGetEmailSchema;
use App\Modules\Validation\Schemas\AdminLookupEmailSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Random\RandomException;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;

class AdminController
{
    public function __construct(
        private AdminRepository $adminRepository,
        private AdminEmailRepository $adminEmailRepository,
        private ValidationGuard $validationGuard,
        private AdminIdentifierCryptoServiceInterface $cryptoService
    ) {
    }

    public function create(Request $request, Response $response): Response
    {
        $adminId = $this->adminRepository->create();
        $createdAt = $this->adminRepository->getCreatedAt($adminId);

        $dto = new ActionResultResponseDTO(
            adminId: $adminId,
            createdAt: $createdAt
        );

        $json = json_encode($dto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @param array<string, string> $args
     * @throws RandomException
     * @throws HttpBadRequestException
     */
    public function addEmail(Request $request, Response $response, array $args): Response
    {
        $adminId = (int)$args['id'];
        
        $data = (array)$request->getParsedBody();

        $input = array_merge($data, $args);

        $this->validationGuard->check(new AdminAddEmailSchema(), $input);

        $emailInput = $data[IdentifierType::EMAIL->value] ?? null;

        try {
            $requestDto = new CreateAdminEmailRequestDTO($emailInput);
        } catch (InvalidIdentifierFormatException $e) {
            // Should be caught by validation guard technically, but if schema checks v::email(), it is good.
            // But we keep this just in case.
            throw new HttpBadRequestException($request, 'Invalid email format.');
        }
        $email = $requestDto->email;

        // Blind Index
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);

        // Encryption
        $encryptedDto = $this->cryptoService->encryptEmail($email);

        $this->adminEmailRepository->addEmail($adminId, $blindIndex, $encryptedDto);

        $responseDto = new ActionResultResponseDTO(
            adminId: $adminId,
            emailAdded: true,
        );

        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @throws HttpBadRequestException
     */
    public function lookupEmail(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();

        $this->validationGuard->check(new AdminLookupEmailSchema(), $data);
        
        $emailInput = $data[IdentifierType::EMAIL->value] ?? null;

        try {
            $requestDto = new VerifyAdminEmailRequestDTO($emailInput);
        } catch (InvalidIdentifierFormatException $e) {
             // Redundant with validation but safe
            throw new HttpBadRequestException($request, 'Invalid email format.');
        }
        $email = $requestDto->email;

        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);

        $adminId = $this->adminEmailRepository->findByBlindIndex($blindIndex);

        if ($adminId !== null) {
            $responseDto = new ActionResultResponseDTO(
                adminId: $adminId,
                exists: true,
            );
        } else {
            $responseDto = new ActionResultResponseDTO(
                exists: false,
            );
        }

        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @param array<string, string> $args
     */
    public function getEmail(Request $request, Response $response, array $args): Response
    {
        $this->validationGuard->check(new AdminGetEmailSchema(), $args);

        $adminId = (int)$args['id'];

        $encryptedEmailDto = $this->adminEmailRepository->getEncryptedEmail($adminId);

        $email = null;
        if ($encryptedEmailDto !== null) {
            $email = $this->cryptoService->decryptEmail($encryptedEmailDto);
        }

        $responseDto = new AdminEmailResponseDTO($adminId, $email);

        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
