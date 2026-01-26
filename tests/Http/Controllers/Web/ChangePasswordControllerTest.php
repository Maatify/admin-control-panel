<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Web;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\DTO\AdminEmailIdentifierDTO;
use App\Domain\DTO\AdminPasswordRecordDTO;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Service\PasswordService;
use App\Domain\Service\RecoveryStateService;
use App\Http\Controllers\Web\ChangePasswordController;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

class ChangePasswordControllerTest extends TestCase
{
    private Twig&MockObject $view;
    private AdminIdentifierCryptoServiceInterface&MockObject $cryptoService;
    private AdminIdentifierLookupInterface&MockObject $lookup;
    private AdminPasswordRepositoryInterface&MockObject $passwordRepo;
    private PasswordService&MockObject $passwordService;
    private RecoveryStateService&MockObject $recoveryState;
    private PDO&MockObject $pdo;

    private ChangePasswordController $controller;

    protected function setUp(): void
    {
        $this->view = $this->createMock(Twig::class);
        $this->cryptoService = $this->createMock(AdminIdentifierCryptoServiceInterface::class);
        $this->lookup = $this->createMock(AdminIdentifierLookupInterface::class);
        $this->passwordRepo = $this->createMock(AdminPasswordRepositoryInterface::class);
        $this->passwordService = $this->createMock(PasswordService::class);
        $this->recoveryState = $this->createMock(RecoveryStateService::class);
        $this->pdo = $this->createMock(PDO::class);

        $this->controller = new ChangePasswordController(
            $this->view,
            $this->cryptoService,
            $this->lookup,
            $this->passwordRepo,
            $this->passwordService,
            $this->recoveryState,
            $this->pdo
        );
    }

    public function test_index_renders_template(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->method('getQueryParams')->willReturn(['email' => 'admin@example.com']);

        $this->view->expects($this->once())
            ->method('render')
            ->with($response, 'auth/change_password.twig', ['email' => 'admin@example.com'])
            ->willReturn($response);

        $this->controller->index($request, $response);
    }

    public function test_change_success(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $context = new RequestContext('req-id', '127.0.0.1', 'agent');

        $request->method('getParsedBody')->willReturn([
            'email' => 'admin@example.com',
            'current_password' => 'old_pass',
            'new_password' => 'new_pass'
        ]);
        $request->method('getAttribute')->with(RequestContext::class)->willReturn($context);

        $this->recoveryState->expects($this->once())
            ->method('enforce')
            ->with(RecoveryStateService::ACTION_PASSWORD_CHANGE, null, $context);

        $this->cryptoService->method('deriveEmailBlindIndex')->willReturn('blind_index');
        $this->lookup->method('findByBlindIndex')->willReturn(
            new AdminEmailIdentifierDTO(1, 123, VerificationStatus::VERIFIED)
        );

        $record = new AdminPasswordRecordDTO('hash_old', 'pepper', true);
        $this->passwordRepo->method('getPasswordRecord')->with(123)->willReturn($record);
        $this->passwordService->method('verify')->with('old_pass', 'hash_old', 'pepper')->willReturn(true);

        $this->passwordService->method('hash')->with('new_pass')->willReturn([
            'hash' => 'hash_new',
            'pepper_id' => 'pepper_new'
        ]);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');

        // Verification of savePassword call: clear flag (false)
        $this->passwordRepo->expects($this->once())
            ->method('savePassword')
            ->with(123, 'hash_new', 'pepper_new', false);

        $response->expects($this->once())->method('withHeader')->with('Location', '/login')->willReturnSelf();
        $response->expects($this->once())->method('withStatus')->with(302)->willReturnSelf();

        $this->controller->change($request, $response);
    }

    public function test_change_authentication_failed_renders_error(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $context = new RequestContext('req-id', '127.0.0.1', 'agent');

        $request->method('getParsedBody')->willReturn([
            'email' => 'admin@example.com',
            'current_password' => 'wrong_pass',
            'new_password' => 'new_pass'
        ]);
        $request->method('getAttribute')->with(RequestContext::class)->willReturn($context);

        $this->cryptoService->method('deriveEmailBlindIndex')->willReturn('blind_index');
        $this->lookup->method('findByBlindIndex')->willReturn(
            new AdminEmailIdentifierDTO(1, 123, VerificationStatus::VERIFIED)
        );

        $record = new AdminPasswordRecordDTO('hash_old', 'pepper', true);
        $this->passwordRepo->method('getPasswordRecord')->with(123)->willReturn($record);
        $this->passwordService->method('verify')->with('wrong_pass', 'hash_old', 'pepper')->willReturn(false);

        $this->view->expects($this->once())
            ->method('render')
            ->with($response, 'auth/change_password.twig', ['error' => 'Authentication failed.'])
            ->willReturn($response);

        $this->controller->change($request, $response);
    }

    public function test_change_invalid_email_user_not_found(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $context = new RequestContext('req-id', '127.0.0.1', 'agent');

        $request->method('getParsedBody')->willReturn([
            'email' => 'unknown@example.com',
            'current_password' => 'pass',
            'new_password' => 'new'
        ]);
        $request->method('getAttribute')->with(RequestContext::class)->willReturn($context);

        $this->cryptoService->method('deriveEmailBlindIndex')->willReturn('blind_index');
        $this->lookup->method('findByBlindIndex')->willReturn(null); // User not found

        $this->view->expects($this->once())
            ->method('render')
            ->with($response, 'auth/change_password.twig', ['error' => 'Authentication failed.'])
            ->willReturn($response);

        $this->controller->change($request, $response);
    }

    public function test_change_missing_fields(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        // Missing new_password
        $request->method('getParsedBody')->willReturn([
            'email' => 'admin@example.com',
            'current_password' => 'pass',
        ]);

        $this->view->expects($this->once())
            ->method('render')
            ->with($response, 'auth/change_password.twig', ['error' => 'Invalid request.'])
            ->willReturn($response);

        $this->controller->change($request, $response);
    }

    public function test_change_blocked_by_recovery_lock(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $context = new RequestContext('req-id', '127.0.0.1', 'agent');

        $request->method('getParsedBody')->willReturn([
            'email' => 'admin@example.com',
            'current_password' => 'pass',
            'new_password' => 'new'
        ]);
        $request->method('getAttribute')->with(RequestContext::class)->willReturn($context);

        // Expect enforce to throw RecoveryLockException
        $this->recoveryState->expects($this->once())
            ->method('enforce')
            ->with(RecoveryStateService::ACTION_PASSWORD_CHANGE, null, $context)
            ->willThrowException(new \App\Domain\Exception\RecoveryLockException('Blocked'));

        $this->expectException(\App\Domain\Exception\RecoveryLockException::class);
        $this->expectExceptionMessage('Blocked');

        $this->controller->change($request, $response);
    }
}
