<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim
 */

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Service\PasswordService;
use App\Domain\Service\RecoveryStateService;
use DateTimeImmutable;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class ChangePasswordController
{
    public function __construct(
        private Twig $view,

        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private AdminIdentifierLookupInterface $identifierLookup,
        private AdminPasswordRepositoryInterface $passwordRepository,
        private PasswordService $passwordService,

        private RecoveryStateService $recoveryState,

        private PDO $pdo
    ) {
    }

    /**
     * GET /auth/change-password
     */
    public function index(Request $request, Response $response): Response
    {
        $email = $request->getQueryParams()['email'] ?? '';

        return $this->view->render($response, 'auth/change_password.twig', [
            'email' => is_string($email) ? $email : '',
        ]);
    }

    /**
     * POST /auth/change-password
     */
    public function change(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (
            !is_array($data) ||
            !isset($data['email'], $data['current_password'], $data['new_password'])
        ) {
            return $this->view->render($response, 'auth/change_password.twig', [
                'error' => 'Invalid request.',
            ]);
        }

        $email = (string) $data['email'];
        $currentPassword = (string) $data['current_password'];
        $newPassword = (string) $data['new_password'];

        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$requestContext instanceof RequestContext) {
            throw new \RuntimeException('RequestContext missing');
        }

        // 🔒 Recovery enforcement
        $this->recoveryState->enforce(
            RecoveryStateService::ACTION_PASSWORD_CHANGE,
            null,
            $requestContext
        );

        // 1️⃣ Resolve Admin ID
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);
        $adminEmailIdentifierDTO = $this->identifierLookup->findByBlindIndex($blindIndex);

        if ($adminEmailIdentifierDTO === null) {
            return $this->view->render($response, 'auth/change_password.twig', [
                'error' => 'Authentication failed.',
            ]);
        }

        $adminId = $adminEmailIdentifierDTO->adminId;

        // 2️⃣ Verify current password
        $record = $this->passwordRepository->getPasswordRecord($adminId);
        if (
            $record === null ||
            !$this->passwordService->verify(
                $currentPassword,
                $record->hash,
                $record->pepperId
            )
        ) {
            return $this->view->render($response, 'auth/change_password.twig', [
                'error' => 'Authentication failed.',
            ]);
        }

        // 3️⃣ Persist password change
        $this->pdo->beginTransaction();
        try {
            $hashResult = $this->passwordService->hash($newPassword);

            $this->passwordRepository->savePassword(
                $adminId,
                $hashResult['hash'],
                $hashResult['pepper_id'],
                false // clear must_change_password
            );

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // 4️⃣ Redirect to login
        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}
