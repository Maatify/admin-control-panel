<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\Request\CreateAdminEmailRequestDTO;
use App\Domain\DTO\Request\VerifyAdminEmailRequestDTO;
use App\Domain\DTO\Response\ActionResultResponseDTO;
use App\Domain\DTO\Response\AdminEmailResponseDTO;
use App\Domain\Enum\IdentifierType;
use App\Domain\Exception\InvalidIdentifierFormatException;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminRepository;
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
        private AdminConfigDTO $config
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
        
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            throw new HttpBadRequestException($request, 'Invalid JSON body.');
        }

        $emailInput = $data[IdentifierType::EMAIL->value] ?? null;
        if (!is_string($emailInput) || $emailInput === '') {
            throw new HttpBadRequestException($request, 'Invalid or missing email.');
        }

        try {
            $requestDto = new CreateAdminEmailRequestDTO($emailInput);
        } catch (InvalidIdentifierFormatException $e) {
            throw new HttpBadRequestException($request, 'Invalid email format.');
        }
        $email = $requestDto->email;

        // Blind Index
        $blindIndexKey = $this->config->emailBlindIndexKey;
        $blindIndex = hash_hmac('sha256', $email, $blindIndexKey);

        // Encryption
        $encryptionKey = $this->config->emailEncryptionKey;
        
        $cipher = 'aes-256-gcm';
        $ivLen = openssl_cipher_iv_length($cipher);
        if ($ivLen === false || $ivLen <= 0) {
            throw new RuntimeException('Failed to get IV length.');
        }

        $iv = random_bytes($ivLen);
        $tag = ''; // Passed by reference
        $ciphertext = openssl_encrypt($email, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
             throw new RuntimeException('Encryption failed.');
        }

        $encryptedEmail = base64_encode($iv . $tag . $ciphertext);

        $this->adminEmailRepository->addEmail($adminId, $blindIndex, $encryptedEmail);

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
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            throw new HttpBadRequestException($request, 'Invalid JSON body.');
        }
        
        $emailInput = $data[IdentifierType::EMAIL->value] ?? null;
        if (!is_string($emailInput) || $emailInput === '') {
             throw new HttpBadRequestException($request, 'Invalid or missing email.');
        }

        try {
            $requestDto = new VerifyAdminEmailRequestDTO($emailInput);
        } catch (InvalidIdentifierFormatException $e) {
            throw new HttpBadRequestException($request, 'Invalid email format.');
        }
        $email = $requestDto->email;

        $blindIndexKey = $this->config->emailBlindIndexKey;
        $blindIndex = hash_hmac('sha256', $email, $blindIndexKey);

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
        $adminId = (int)$args['id'];

        $encryptedEmail = $this->adminEmailRepository->getEncryptedEmail($adminId);

        $encryptionKey = $this->config->emailEncryptionKey;
        $cipher = 'aes-256-gcm';

        $data = base64_decode((string)$encryptedEmail, true);
        if ($data === false) {
             throw new RuntimeException('Base64 decode failed.');
        }

        $ivLen = openssl_cipher_iv_length($cipher);
        if ($ivLen === false || $ivLen <= 0) {
            throw new RuntimeException('Failed to get IV length.');
        }

        if (strlen($data) < ($ivLen + 16)) {
             throw new RuntimeException('Invalid encrypted data length.');
        }

        $iv = substr($data, 0, $ivLen);
        $tag = substr($data, $ivLen, 16);
        $ciphertext = substr($data, $ivLen + 16);

        $email = openssl_decrypt($ciphertext, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);

        if ($email === false) {
            $email = null;
        }

        $responseDto = new AdminEmailResponseDTO($adminId, $email);

        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
