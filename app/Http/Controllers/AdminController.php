<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\DTO\Request\CreateAdminEmailRequestDTO;
use App\Domain\DTO\Request\VerifyAdminEmailRequestDTO;
use App\Domain\DTO\Response\ActionResultResponseDTO;
use App\Domain\DTO\Response\AdminEmailResponseDTO;
use App\Domain\Enum\IdentifierType;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Random\RandomException;

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

        $json = json_encode($dto->jsonSerialize());
        assert($json !== false);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @param array<string, string> $args
     * @throws RandomException
     */
    public function addEmail(Request $request, Response $response, array $args): Response
    {
        $adminId = (int)$args['id'];
        $data = json_decode((string)$request->getBody(), true);
        assert(is_array($data));
        
        $emailInput = $data[IdentifierType::EMAIL->value] ?? '';
        assert(is_string($emailInput));
        $requestDto = new CreateAdminEmailRequestDTO($emailInput);
        $email = $requestDto->email;

        // Blind Index
        $blindIndexKey = $_ENV['EMAIL_BLIND_INDEX_KEY'] ?? '';
        $blindIndex = hash_hmac('sha256', $email, $blindIndexKey);
        assert(is_string($blindIndex));

        // Encryption
        $encryptionKey = $_ENV['EMAIL_ENCRYPTION_KEY'] ?? '';
        
        $cipher = 'aes-256-gcm';
        $ivLen = openssl_cipher_iv_length($cipher);
        assert(is_int($ivLen) && $ivLen > 0);
        $iv = random_bytes($ivLen);
        assert(is_string($iv));
        $tag = '';
        $ciphertext = openssl_encrypt($email, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);
        assert(is_string($ciphertext));
        $encryptedEmail = base64_encode($iv . $tag . $ciphertext);

        $this->adminEmailRepository->addEmail($adminId, $blindIndex, $encryptedEmail);

        $responseDto = new ActionResultResponseDTO(
            adminId: $adminId,
            emailAdded: true,
        );

        $json = json_encode($responseDto->jsonSerialize());
        assert($json !== false);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function lookupEmail(Request $request, Response $response): Response
    {
        $data = json_decode((string)$request->getBody(), true);
        assert(is_array($data));
        
        $emailInput = $data[IdentifierType::EMAIL->value] ?? '';
        assert(is_string($emailInput));
        $requestDto = new VerifyAdminEmailRequestDTO($emailInput);
        $email = $requestDto->email;

        $blindIndexKey = $_ENV['EMAIL_BLIND_INDEX_KEY'];
        assert(is_string($blindIndexKey));
        $blindIndex = hash_hmac('sha256', $email, $blindIndexKey);
        assert(is_string($blindIndex));

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

        $json = json_encode($responseDto->jsonSerialize());
        assert($json !== false);
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

        $encryptionKey = $_ENV['EMAIL_ENCRYPTION_KEY'];
        assert(is_string($encryptionKey));
        $cipher = 'aes-256-gcm';
        $data = base64_decode((string)$encryptedEmail);
        assert(is_string($data));
        $ivLen = openssl_cipher_iv_length($cipher);
        assert(is_int($ivLen));
        $iv = substr($data, 0, $ivLen);
        assert(is_string($iv));
        $tag = substr($data, $ivLen, 16);
        assert(is_string($tag));
        $ciphertext = substr($data, $ivLen + 16);
        assert(is_string($ciphertext));

        $email = openssl_decrypt($ciphertext, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);

        if ($email === false) {
            $email = null;
        }

        $responseDto = new AdminEmailResponseDTO($adminId, $email);

        $json = json_encode($responseDto->jsonSerialize());
        assert($json !== false);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
