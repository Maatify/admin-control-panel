<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap\Container;
use App\Domain\Contracts\AdminTotpSecretStoreInterface;
use App\Infrastructure\Repository\AdminRepository;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\Service\PasswordService;
use App\Domain\Ownership\SystemOwnershipRepositoryInterface;
use Ramsey\Uuid\Uuid;

// Container (Loads ENV)
$container = Container::create();

// Check if admins exist
$pdo = $container->get(PDO::class);
assert($pdo instanceof PDO);
$stmt = $pdo->query("SELECT COUNT(*) FROM admins");
assert($stmt !== false);
if ($stmt->fetchColumn() > 0) {
    echo "Bootstrap disabled: Admins already exist.\n";
    exit(1);
}

// Get Config
$config = $container->get(AdminConfigDTO::class);
assert($config instanceof AdminConfigDTO);

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
assert($totpService instanceof TotpServiceInterface);
$secret = $totpService->generateSecret();
echo "TOTP Secret: $secret\n";
echo "Please set up your authenticator app.\n";

$code = readline("Enter OTP Code: ");
if ($code === false || !$totpService->verify($secret, $code)) {
    echo "Invalid OTP. Aborting.\n";
    exit(1);
}

try {
    $pdo->beginTransaction();

    // 1. Admin
    $adminRepo = $container->get(AdminRepository::class);
    assert($adminRepo instanceof AdminRepository);
    $adminId = $adminRepo->createFirstAdmin();

    // 2. Email
    $emailRepo = $container->get(AdminEmailRepository::class);
    assert($emailRepo instanceof AdminEmailRepository);

    $cryptoService = $container->get(App\Application\Crypto\AdminIdentifierCryptoServiceInterface::class);
    assert($cryptoService instanceof App\Application\Crypto\AdminIdentifierCryptoServiceInterface);

    $blindIndex = $cryptoService->deriveEmailBlindIndex($email);
    $encryptedPayload = $cryptoService->encryptEmail($email);

    $emailRepo->addEmail($adminId, $blindIndex, $encryptedPayload);
    $emailRepo->markVerified($adminId, (new DateTimeImmutable())->format('Y-m-d H:i:s'));

    // 3. Password
    $passRepo = $container->get(AdminPasswordRepositoryInterface::class);
    assert($passRepo instanceof AdminPasswordRepositoryInterface);
    $passwordService = $container->get(PasswordService::class);
    assert($passwordService instanceof PasswordService);
    
    $hashResult = $passwordService->hash($password);
    $passRepo->savePassword($adminId, $hashResult['hash'], $hashResult['pepper_id'], false);

    // 4. TOTP (SECURE)
    $totpStore = $container->get(AdminTotpSecretStoreInterface::class);
    assert($totpStore instanceof AdminTotpSecretStoreInterface);
    $totpStore->store($adminId, $secret);

    // 5. System Ownership
    $ownershipRepo = $container->get(SystemOwnershipRepositoryInterface::class);
    assert($ownershipRepo instanceof SystemOwnershipRepositoryInterface);
    $ownershipRepo->assignOwner($adminId);

    // 6. Audit
    $writer = $container->get(AuthoritativeSecurityAuditWriterInterface::class);
    assert($writer instanceof AuthoritativeSecurityAuditWriterInterface);

    $requestId = Uuid::uuid4()->toString();
    $correlationId = Uuid::uuid4()->toString();

    $writer->write(new AuditEventDTO(
        actor_id: $adminId,
        action: 'system_bootstrap',
        target_type: 'system',
        target_id: null,
        risk_level: 'CRITICAL',
        payload: ['email_hash' => $blindIndex],
        correlation_id: $correlationId,
        request_id: $requestId,
        created_at: new DateTimeImmutable()
    ));

    $pdo->commit();
    echo "Admin created successfully. ID: $adminId\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
