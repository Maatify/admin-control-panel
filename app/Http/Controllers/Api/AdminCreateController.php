<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\Exception\InvalidIdentifierFormatException;
use App\Domain\Service\PasswordService;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class AdminCreateController
{
    public function __construct(
        private readonly AdminRepository $adminRepository,
        private readonly AdminEmailRepository $adminEmailRepository,
        private readonly AdminPasswordRepositoryInterface $adminPasswordRepository,
        private readonly PasswordService $passwordService,
        private readonly AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private readonly AdminConfigDTO $config,
        private readonly PDO $pdo
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            $response->getBody()->write((string)json_encode(['status' => 'error', 'message' => 'Invalid JSON']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validate Input Inline (No DTO)
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $passwordConfirmation = $data['password_confirmation'] ?? null;

        try {
            if (!is_string($email) || trim($email) === '') {
                throw new InvalidIdentifierFormatException('Invalid or missing email');
            }
            $email = trim(strtolower($email));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidIdentifierFormatException('Invalid email format');
            }

            if (!is_string($password) || $password === '') {
                throw new InvalidArgumentException('Password is required');
            }

            if ($password !== $passwordConfirmation) {
                throw new InvalidArgumentException('Passwords do not match');
            }
        } catch (InvalidIdentifierFormatException $e) {
            $response->getBody()->write((string)json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => ['email' => $e->getMessage()]
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $field = 'password';
            if (str_contains(strtolower($e->getMessage()), 'match')) {
                $field = 'password_confirmation';
            }
            $response->getBody()->write((string)json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => [$field => $e->getMessage()]
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $creatorId = $request->getAttribute('admin_id');
        if (!is_int($creatorId)) {
             $creatorId = 0;
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Check if email exists
            $blindIndexKey = $this->config->emailBlindIndexKey;
            $blindIndex = hash_hmac('sha256', $email, $blindIndexKey);

            if ($this->adminEmailRepository->findByBlindIndex($blindIndex) !== null) {
                $this->pdo->rollBack();
                $response->getBody()->write((string)json_encode([
                    'status' => 'error',
                    'message' => 'Email already exists',
                    'errors' => ['email' => 'Email already exists']
                ]));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
            }

            // 2. Create Admin
            $newAdminId = $this->adminRepository->create();

            // 3. Encrypt and Add Email
            $encryptionKey = $this->config->emailEncryptionKey;
            $cipher = 'aes-256-gcm';
            $ivLen = openssl_cipher_iv_length($cipher);
            assert(is_int($ivLen) && $ivLen > 0);
            $iv = random_bytes($ivLen);
            $tag = '';
            // @phpstan-ignore-next-line
            $encryptedEmailRaw = openssl_encrypt($email, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);
            if ($encryptedEmailRaw === false) {
                 throw new \RuntimeException("Encryption failed");
            }
            $encryptedEmail = base64_encode($iv . $tag . $encryptedEmailRaw);

            $this->adminEmailRepository->addEmail($newAdminId, $blindIndex, $encryptedEmail);
            $this->adminEmailRepository->markVerified($newAdminId, (new DateTimeImmutable())->format('Y-m-d H:i:s'));

            // 4. Password
            $hash = $this->passwordService->hash($password);
            $this->adminPasswordRepository->savePassword($newAdminId, $hash);

            // 5. Audit
            $this->auditWriter->write(new AuditEventDTO(
                $creatorId,
                'admin_created',
                'admin',
                $newAdminId,
                'WARNING',
                ['email_hash' => substr($blindIndex, 0, 8) . '...'],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->pdo->commit();

            $response->getBody()->write((string)json_encode(['status' => 'ok', 'admin_id' => $newAdminId]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Log error internally if needed
            $response->getBody()->write((string)json_encode(['status' => 'error', 'message' => 'Internal Server Error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
