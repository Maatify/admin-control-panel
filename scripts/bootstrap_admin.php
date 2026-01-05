<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap\Container;
use Dotenv\Dotenv;
use App\Infrastructure\Repository\AdminRepository;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\TotpSecretRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\Service\PasswordService;

// Load Env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Container
$container = Container::create();

// Check if admins exist
$pdo = $container->get(PDO::class);
$stmt = $pdo->query("SELECT COUNT(*) FROM admins");
if ($stmt->fetchColumn() > 0) {
    echo "Bootstrap disabled: Admins already exist.\n";
    exit(1);
}

// Inputs
echo "Bootstrap First Admin\n";
$email = readline("Email: ");
$password = readline("Password: ");

if (empty($email) || empty($password)) {
    echo "Email and Password required.\n";
    exit(1);
}

// Generate TOTP
$totpService = $container->get(TotpServiceInterface::class);
$secret = $totpService->generateSecret();
echo "TOTP Secret: $secret\n";
echo "Please set up your authenticator app.\n";

$code = readline("Enter OTP Code: ");
if (!$totpService->verify($secret, $code)) {
    echo "Invalid OTP. Aborting.\n";
    exit(1);
}

try {
    $pdo->beginTransaction();

    // 1. Admin
    $adminRepo = $container->get(AdminRepository::class);
    $adminId = $adminRepo->create();

    // 2. Email
    $emailRepo = $container->get(AdminEmailRepository::class);
    $blindIndexKey = $_ENV['EMAIL_BLIND_INDEX_KEY'] ?? '';
    if (strlen($blindIndexKey) < 32) {
        throw new RuntimeException("EMAIL_BLIND_INDEX_KEY missing or weak");
    }
    $blindIndex = hash_hmac('sha256', $email, $blindIndexKey);

    // Encryption
    $encryptionKey = $_ENV['ENCRYPTION_KEY'] ?? '';
    if (strlen($encryptionKey) < 32) {
         throw new RuntimeException("ENCRYPTION_KEY missing or weak. Set it in .env");
    }
    $iv = random_bytes(12);
    $tag = "";
    $encryptedEmail = openssl_encrypt($email, 'aes-256-gcm', $encryptionKey, 0, $iv, $tag);
    $encryptedPayload = base64_encode($iv . $tag . $encryptedEmail);

    $emailRepo->addEmail($adminId, $blindIndex, $encryptedPayload);
    $emailRepo->markVerified($adminId, (new DateTimeImmutable())->format('Y-m-d H:i:s'));

    // 3. Password
    $passRepo = $container->get(AdminPasswordRepositoryInterface::class);
    $passwordService = $container->get(PasswordService::class);
    $passRepo->savePassword($adminId, $passwordService->hash($password));

    // 4. TOTP
    $totpRepo = $container->get(TotpSecretRepositoryInterface::class);
    $totpRepo->save($adminId, $secret);

    // 5. Audit
    $writer = $container->get(AuthoritativeSecurityAuditWriterInterface::class);
    $writer->write(new AuditEventDTO(
        $adminId,
        'system_bootstrap',
        'system',
        null,
        'CRITICAL',
        ['email_hash' => $blindIndex],
        bin2hex(random_bytes(16)),
        new DateTimeImmutable()
    ));

    $pdo->commit();
    echo "Admin created successfully. ID: $adminId\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
