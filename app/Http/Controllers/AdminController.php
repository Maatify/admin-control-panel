<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\AdminEmailRepository;
use App\Infrastructure\Persistence\AdminRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController
{
    private AdminRepository $adminRepository;
    private AdminEmailRepository $adminEmailRepository;

    public function __construct(
        AdminRepository $adminRepository,
        AdminEmailRepository $adminEmailRepository
    ) {
        $this->adminRepository = $adminRepository;
        $this->adminEmailRepository = $adminEmailRepository;
    }

    public function create(Request $request, Response $response): Response
    {
        $result = $this->adminRepository->create();

        $payload = json_encode($result);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function addEmail(Request $request, Response $response, array $args): Response
    {
        $adminId = (int)$args['id'];
        $data = json_decode((string)$request->getBody(), true);
        $email = trim(strtolower($data['email'] ?? ''));

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

        $this->adminEmailRepository->add($adminId, $blindIndex, $encryptedEmail);

        $payload = json_encode([
            'admin_id' => $adminId,
            'email_added' => true
        ]);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function lookupEmail(Request $request, Response $response): Response
    {
        $data = json_decode((string)$request->getBody(), true);
        $email = trim(strtolower($data['email']));

        $blindIndexKey = $_ENV['EMAIL_BLIND_INDEX_KEY'];
        $blindIndex = hash_hmac('sha256', $email, $blindIndexKey);

        $adminId = $this->adminEmailRepository->findByBlindIndex($blindIndex);

        if ($adminId !== null) {
            $payload = json_encode([
                'exists' => true,
                'admin_id' => $adminId
            ]);
        } else {
            $payload = json_encode([
                'exists' => false
            ]);
        }

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function getEmail(Request $request, Response $response, array $args): Response
    {
        $adminId = (int)$args['id'];

        $encryptedEmail = $this->adminEmailRepository->getEncryptedEmail($adminId);

        // Treat null as empty string to match previous PDO fetchColumn() behavior which returned false (and base64_decode(false) is "")
        $encryptedEmailValue = $encryptedEmail ?? '';

        $encryptionKey = $_ENV['EMAIL_ENCRYPTION_KEY'];
        $cipher = 'aes-256-gcm';
        $data = base64_decode($encryptedEmailValue);
        $ivLen = openssl_cipher_iv_length($cipher);
        $iv = substr($data, 0, $ivLen);
        $tag = substr($data, $ivLen, 16);
        $ciphertext = substr($data, $ivLen + 16);

        $email = openssl_decrypt($ciphertext, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);

        $payload = json_encode([
            'admin_id' => $adminId,
            'email' => $email
        ]);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
