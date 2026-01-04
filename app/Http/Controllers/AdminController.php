<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\DTO\Request\CreateAdminEmailRequestDTO;
use App\Domain\DTO\Request\VerifyAdminEmailRequestDTO;
use App\Domain\DTO\Response\ActionResultResponseDTO;
use App\Domain\DTO\Response\AdminEmailResponseDTO;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController
{
    private AdminRepository $adminRepository;
    private AdminEmailRepository $adminEmailRepository;

    public function __construct(AdminRepository $adminRepository, AdminEmailRepository $adminEmailRepository)
    {
        $this->adminRepository = $adminRepository;
        $this->adminEmailRepository = $adminEmailRepository;
    }

    public function create(Request $request, Response $response): Response
    {
        $adminId = $this->adminRepository->create();
        $createdAt = $this->adminRepository->getCreatedAt($adminId);

        $dto = new ActionResultResponseDTO(
            adminId: $adminId,
            createdAt: $createdAt
        );

        $response->getBody()->write(json_encode($dto->jsonSerialize()));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function addEmail(Request $request, Response $response, array $args): Response
    {
        $adminId = (int)$args['id'];
        $data = json_decode((string)$request->getBody(), true);
        
        $requestDto = new CreateAdminEmailRequestDTO($data['email'] ?? '');
        $email = $requestDto->email;

        // Blind Index
        $blindIndexKey = $_ENV['EMAIL_BLIND_INDEX_KEY'] ?? '';
        $blindIndex = hash_hmac('sha256', $email, $blindIndexKey);

        // Encryption
        $encryptionKey = $_ENV['EMAIL_ENCRYPTION_KEY'] ?? '';
        
        $cipher = 'aes-256-gcm';
        $ivLen = openssl_cipher_iv_length($cipher);
        $iv = random_bytes($ivLen);
        $tag = '';
        $ciphertext = openssl_encrypt($email, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);
        $encryptedEmail = base64_encode($iv . $tag . $ciphertext);

        $this->adminEmailRepository->addEmail($adminId, $blindIndex, $encryptedEmail);

        $responseDto = new ActionResultResponseDTO(
            adminId: $adminId,
            emailAdded: true
        );

        $response->getBody()->write(json_encode($responseDto->jsonSerialize()));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function lookupEmail(Request $request, Response $response): Response
    {
        $data = json_decode((string)$request->getBody(), true);
        
        $requestDto = new VerifyAdminEmailRequestDTO($data['email'] ?? '');
        $email = $requestDto->email;

        $blindIndexKey = $_ENV['EMAIL_BLIND_INDEX_KEY'];
        $blindIndex = hash_hmac('sha256', $email, $blindIndexKey);

        $adminId = $this->adminEmailRepository->findByBlindIndex($blindIndex);

        if ($adminId !== null) {
            $responseDto = new ActionResultResponseDTO(
                adminId: $adminId,
                exists: true
            );
        } else {
            $responseDto = new ActionResultResponseDTO(
                exists: false
            );
        }

        $response->getBody()->write(json_encode($responseDto->jsonSerialize()));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function getEmail(Request $request, Response $response, array $args): Response
    {
        $adminId = (int)$args['id'];

        $encryptedEmail = $this->adminEmailRepository->getEncryptedEmail($adminId);

        $encryptionKey = $_ENV['EMAIL_ENCRYPTION_KEY'];
        $cipher = 'aes-256-gcm';
        $data = base64_decode((string)$encryptedEmail);
        $ivLen = openssl_cipher_iv_length($cipher);
        $iv = substr($data, 0, $ivLen);
        $tag = substr($data, $ivLen, 16);
        $ciphertext = substr($data, $ivLen + 16);

        $email = openssl_decrypt($ciphertext, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);

        $responseDto = new AdminEmailResponseDTO($adminId, $email);

        $response->getBody()->write(json_encode($responseDto->jsonSerialize()));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
