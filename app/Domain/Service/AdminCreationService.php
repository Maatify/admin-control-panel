<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\Exception\InvalidIdentifierStateException;
use App\Infrastructure\Repository\AdminRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class AdminCreationService
{
    public function __construct(
        private readonly AdminRepository $adminRepository,
        private readonly AdminEmailVerificationRepositoryInterface $adminEmailRepository,
        private readonly AdminPasswordRepositoryInterface $adminPasswordRepository,
        private readonly PasswordService $passwordService,
        private readonly AdminConfigDTO $config,
        private readonly PDO $pdo
    ) {
    }

    public function createAdmin(string $email, string $password): int
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters');
        }

        // 1. Blind Index Check (Collision Detection)
        $blindIndexKey = $this->config->emailBlindIndexKey;
        if (empty($blindIndexKey) || strlen($blindIndexKey) < 32) {
            throw new RuntimeException("EMAIL_BLIND_INDEX_KEY missing or weak");
        }
        $blindIndex = hash_hmac('sha256', $email, $blindIndexKey);

        try {
            $this->pdo->beginTransaction();

            // 2. Create Admin
            $adminId = $this->adminRepository->create();

            // 3. Encrypt Email
            $encryptionKey = $this->config->emailEncryptionKey;
            if (empty($encryptionKey) || strlen($encryptionKey) < 32) {
                throw new RuntimeException("EMAIL_ENCRYPTION_KEY missing or weak");
            }
            $iv = random_bytes(12);
            $tag = "";
            $encryptedEmail = openssl_encrypt($email, 'aes-256-gcm', $encryptionKey, 0, $iv, $tag);
            if ($encryptedEmail === false) {
                 throw new RuntimeException("Encryption failed");
            }
            $encryptedPayload = base64_encode($iv . $tag . $encryptedEmail);

            // 4. Add Email
            // Note: addEmail might throw if duplicate.
            $this->adminEmailRepository->addEmail($adminId, $blindIndex, $encryptedPayload);
            $this->adminEmailRepository->markVerified($adminId, (new DateTimeImmutable())->format('Y-m-d H:i:s'));

            // 5. Password
            $hash = $this->passwordService->hash($password);
            $this->adminPasswordRepository->savePassword($adminId, $hash);

            $this->pdo->commit();

            return $adminId;

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Check for unique constraint violation (Code 23000)
            if ($e instanceof \PDOException && $e->getCode() == '23000') {
                 throw new InvalidIdentifierStateException('Admin already exists');
            }

            throw $e;
        }
    }
}
