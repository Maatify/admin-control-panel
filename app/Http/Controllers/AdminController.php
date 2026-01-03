<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Request $request, Response $response): Response
    {
        $stmt = $this->pdo->prepare("INSERT INTO admins (created_at) VALUES (NOW())");
        $stmt->execute();

        $id = (int)$this->pdo->lastInsertId();
        
        $stmt = $this->pdo->prepare("SELECT created_at FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        $createdAt = $stmt->fetchColumn();

        $payload = json_encode([
            'admin_id' => $id,
            'created_at' => $createdAt
        ]);

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

        $stmt = $this->pdo->prepare("INSERT INTO admin_emails (admin_id, email_blind_index, email_encrypted) VALUES (?, ?, ?)");
        $stmt->execute([$adminId, $blindIndex, $encryptedEmail]);

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

        $stmt = $this->pdo->prepare("SELECT admin_id FROM admin_emails WHERE email_blind_index = ?");
        $stmt->execute([$blindIndex]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $payload = json_encode([
                'exists' => true,
                'admin_id' => $result['admin_id']
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

        $stmt = $this->pdo->prepare("SELECT email_encrypted FROM admin_emails WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $encryptedEmail = $stmt->fetchColumn();

        $encryptionKey = $_ENV['EMAIL_ENCRYPTION_KEY'];
        $cipher = 'aes-256-gcm';
        $data = base64_decode($encryptedEmail);
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
