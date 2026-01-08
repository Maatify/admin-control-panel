<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\DTO\Admin\CreateAdminRequestDTO;
use App\Domain\DTO\Admin\CreateAdminResponseDTO;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\Service\PasswordService;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminRepository;
use DateTimeImmutable;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Throwable;

class AdminCreateController
{
    public function __construct(
        private readonly AdminRepository $adminRepository,
        private readonly AdminEmailRepository $adminEmailRepository,
        private readonly AdminPasswordRepositoryInterface $adminPasswordRepository,
        private readonly PasswordService $passwordService,
        private readonly AdminConfigDTO $config,
        private readonly AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private readonly PDO $pdo
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                throw new RuntimeException('Invalid request body');
            }

            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            if (!is_string($email) || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->error($response, 'Invalid email address', 400);
            }

            if (!is_string($password) || strlen($password) < 8) {
                return $this->error($response, 'Password must be at least 8 characters long', 400);
            }

            $dto = new CreateAdminRequestDTO($email, $password);

            // Blind Index Check
            $blindIndexKey = $this->config->emailBlindIndexKey;
            $blindIndex = hash_hmac('sha256', $dto->email, $blindIndexKey);

            if ($this->adminEmailRepository->findByBlindIndex($blindIndex) !== null) {
                return $this->error($response, 'Email already registered', 409);
            }

            $this->pdo->beginTransaction();

            try {
                // 1. Create Admin
                $adminId = $this->adminRepository->create();
                $createdAt = $this->adminRepository->getCreatedAt($adminId);

                // 2. Add Email
                $encryptionKey = $this->config->emailEncryptionKey;
                $cipher = 'aes-256-gcm';
                $ivLen = openssl_cipher_iv_length($cipher);
                assert(is_int($ivLen) && $ivLen > 0);
                $iv = random_bytes($ivLen);
                $tag = '';
                // @phpstan-ignore-next-line
                $ciphertext = openssl_encrypt($dto->email, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);
                assert(is_string($ciphertext));
                $encryptedEmail = base64_encode($iv . $tag . $ciphertext);

                $this->adminEmailRepository->addEmail($adminId, $blindIndex, $encryptedEmail);

                // 3. Mark Verified (Direct Creation = Trusted)
                $this->adminEmailRepository->markVerified($adminId, (new DateTimeImmutable())->format('Y-m-d H:i:s'));

                // 4. Set Password
                $passwordHash = $this->passwordService->hash($dto->password);
                $this->adminPasswordRepository->savePassword($adminId, $passwordHash);

                // 5. Audit
                $actorId = $request->getAttribute('admin_id');
                assert(is_int($actorId));

                $this->auditWriter->write(new AuditEventDTO(
                    actorId: $actorId,
                    eventType: 'admin_created',
                    targetType: 'admin',
                    targetId: (string)$adminId,
                    severity: 'INFO',
                    payload: [
                        'email_blind_index' => $blindIndex
                    ],
                    eventId: bin2hex(random_bytes(16)),
                    timestamp: new DateTimeImmutable()
                ));

                $this->pdo->commit();

                $responseDto = new CreateAdminResponseDTO($adminId, $createdAt);

                $response->getBody()->write(json_encode($responseDto, JSON_THROW_ON_ERROR));
                return $response->withHeader('Content-Type', 'application/json');

            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $e;
            }

        } catch (Throwable $e) {
            // Log the error (Psr\Log\LoggerInterface should be injected ideally, but adhering to scope)
            // Return generic error to prevent Info Disclosure
            return $this->error($response, 'Internal Server Error', 500);
        }
    }

    private function error(Response $response, string $message, int $status): Response
    {
        $payload = json_encode(['error' => $message], JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
