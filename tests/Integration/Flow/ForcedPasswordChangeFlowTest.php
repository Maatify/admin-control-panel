<?php

declare(strict_types=1);

namespace Tests\Integration\Flow;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\RequestContext;
use App\Domain\Admin\Enum\AdminStatusEnum;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\AdminEmailIdentifierDTO;
use App\Domain\DTO\AdminPasswordRecordDTO;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\MustChangePasswordException;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\PasswordService;
use App\Domain\Service\RecoveryStateService;
use App\Http\Controllers\Web\ChangePasswordController;
use App\Infrastructure\Repository\AdminRepository;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

class ForcedPasswordChangeFlowTest extends TestCase
{
    // Dependencies
    private AdminAuthenticationService $authService;
    private ChangePasswordController $changePasswordController;

    // Mocks
    private AdminPasswordRepositoryInterface&MockObject $passwordRepo;
    private AdminSessionRepositoryInterface&MockObject $sessionRepo;
    private AdminIdentifierLookupInterface&MockObject $lookup;
    private AdminRepository&MockObject $adminRepo;

    // State
    /** @var array<int, array{hash: string, pepper: string, must_change: bool}> */
    private array $users = [];
    /** @var array<string, int> */
    private array $sessions = [];

    protected function setUp(): void
    {
        // 1. Setup Stateful Mocks

        // --- Password Repository ---
        $this->passwordRepo = $this->createMock(AdminPasswordRepositoryInterface::class);
        $this->passwordRepo->method('getPasswordRecord')
            ->willReturnCallback(function (int $id) {
                if (!isset($this->users[$id])) return null;
                $u = $this->users[$id];
                return new AdminPasswordRecordDTO($u['hash'], $u['pepper'], $u['must_change']);
            });

        $this->passwordRepo->method('savePassword')
            ->willReturnCallback(function (int $id, string $hash, string $pepper, bool $mustChange) {
                $this->users[$id] = [
                    'hash' => $hash,
                    'pepper' => $pepper,
                    'must_change' => $mustChange,
                ];
            });

        // --- Session Repository ---
        $this->sessionRepo = $this->createMock(AdminSessionRepositoryInterface::class);
        $this->sessionRepo->method('createSession')
            ->willReturnCallback(function (int $id) {
                $token = bin2hex(random_bytes(16));
                $this->sessions[$token] = $id;
                return $token;
            });

        // --- Identifier Lookup ---
        $this->lookup = $this->createMock(AdminIdentifierLookupInterface::class);
        $this->lookup->method('findByBlindIndex')
            ->willReturnCallback(function ($blindIndex) {
                // Simplified: blindIndex = "idx_" . email
                if ($blindIndex === 'idx_admin@example.com') {
                    // Return DTO
                    return new AdminEmailIdentifierDTO(1, 1, VerificationStatus::VERIFIED);
                }
                return null;
            });

        // --- Admin Repository ---
        $this->adminRepo = $this->createMock(AdminRepository::class);
        $this->adminRepo->method('getStatus')->willReturn(AdminStatusEnum::ACTIVE);

        // --- Crypto Service ---
        $cryptoService = $this->createMock(AdminIdentifierCryptoServiceInterface::class);
        $cryptoService->method('deriveEmailBlindIndex')->willReturnCallback(function ($email) {
            return 'idx_' . $email;
        });

        // --- Password Service ---
        $passwordService = $this->createMock(PasswordService::class);
        $passwordService->method('verify')->willReturnCallback(function ($pass, $hash, $pepper) {
            // Simplified: valid if pass == hash (reversed) or just check consistency
            // Let's assume hash = "hash_" . pass
            return $hash === 'hash_' . $pass;
        });
        $passwordService->method('hash')->willReturnCallback(function ($pass) {
            return ['hash' => 'hash_' . $pass, 'pepper_id' => 'pepper_1'];
        });
        $passwordService->method('needsRehash')->willReturn(false);

        // --- Others (Loose Mocks) ---
        $logger = $this->createMock(SecurityEventLoggerInterface::class);
        $audit = $this->createMock(AuthoritativeSecurityAuditWriterInterface::class);
        $recovery = $this->createMock(RecoveryStateService::class);
        $pdo = $this->createMock(PDO::class);
        $view = $this->createMock(Twig::class);

        // 2. Instantiate Service
        $this->authService = new AdminAuthenticationService(
            $this->lookup,
            $this->passwordRepo,
            $this->sessionRepo,
            $logger,
            $audit,
            $recovery,
            $pdo,
            $passwordService,
            $this->adminRepo
        );

        // 3. Instantiate Controller
        $this->changePasswordController = new ChangePasswordController(
            $view,
            $cryptoService,
            $this->lookup,
            $this->passwordRepo,
            $passwordService,
            $recovery,
            $logger,
            $audit,
            $pdo
        );
    }

    public function test_full_flow_forced_password_change(): void
    {
        // 1. Create Admin with must_change_password = true
        $adminId = 1;
        $email = 'admin@example.com';
        $currentPass = 'password123';

        $this->users[$adminId] = [
            'hash' => 'hash_' . $currentPass,
            'pepper' => 'pepper_1',
            'must_change' => true
        ];

        $context = new RequestContext('req-1', '127.0.0.1', 'phpunit');

        // 2. Login -> Should fail with MustChangePasswordException
        try {
            $this->authService->login('idx_' . $email, $currentPass, $context);
            $this->fail('Login should have thrown MustChangePasswordException');
        } catch (MustChangePasswordException $e) {
            $this->assertSame('Password change required.', $e->getMessage());
        }

        // Verify no session created
        $this->assertEmpty($this->sessions);

        // 3. Change Password
        $newPass = 'newPassword456';

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'email' => $email,
            'current_password' => $currentPass,
            'new_password' => $newPass
        ]);
        $request->method('getAttribute')->with(RequestContext::class)->willReturn($context);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withHeader')->with('Location', '/login')->willReturnSelf();
        $response->expects($this->once())->method('withStatus')->with(302)->willReturnSelf();

        $this->changePasswordController->change($request, $response);

        // Verify DB updated
        $this->assertFalse($this->users[$adminId]['must_change']);
        $this->assertSame('hash_' . $newPass, $this->users[$adminId]['hash']);

        // 4. Login Again -> Should Success
        $result = $this->authService->login('idx_' . $email, $newPass, $context);

        $this->assertNotNull($result->token);
        $this->assertArrayHasKey($result->token, $this->sessions);
        $this->assertSame($adminId, $this->sessions[$result->token]);
    }
}
