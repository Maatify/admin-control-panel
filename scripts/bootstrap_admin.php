<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap\Container;
use App\Infrastructure\Repository\AdminRepository;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\TotpSecretRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\Service\PasswordService;
use App\Domain\Ownership\SystemOwnershipRepositoryInterface;

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
    $adminId = $adminRepo->create();

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
    $passRepo->savePassword($adminId, $hashResult['hash'], $hashResult['pepper_id']);

    // 4. TOTP
    $totpRepo = $container->get(TotpSecretRepositoryInterface::class);
    assert($totpRepo instanceof TotpSecretRepositoryInterface);
    $totpRepo->save($adminId, $secret);

    // 5. System Ownership
    $ownershipRepo = $container->get(SystemOwnershipRepositoryInterface::class);
    assert($ownershipRepo instanceof SystemOwnershipRepositoryInterface);
    $ownershipRepo->assignOwner($adminId);

    // 6. Audit
    $writer = $container->get(AuthoritativeSecurityAuditWriterInterface::class);
    assert($writer instanceof AuthoritativeSecurityAuditWriterInterface);
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
