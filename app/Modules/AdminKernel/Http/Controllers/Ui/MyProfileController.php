<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui;

use Maatify\AdminKernel\Application\Auth\ChangePasswordService;
use Maatify\AdminKernel\Application\Auth\DTO\ChangePasswordRequestDTO;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Admin\Reader\AdminProfileReaderInterface;
use Maatify\AdminKernel\Domain\Admin\Reader\AdminEmailReaderInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Views\Twig;

readonly class MyProfileController
{
    public function __construct(
        private Twig $view,
        private AdminProfileReaderInterface $profileReader,
        private AdminEmailReaderInterface $emailReader,
        private ChangePasswordService $changePasswordService,
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $adminContext = $request->getAttribute(AdminContext::class);
        if (!$adminContext instanceof AdminContext) {
            throw new RuntimeException('AdminContext missing');
        }

        $profile = $this->profileReader->getProfile($adminContext->adminId);

        return $this->view->render($response, 'pages/me/profile.twig', [
            'profile' => $profile,
        ]);
    }

    public function changePasswordForm(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/me/password.twig', []);
    }

    public function changePasswordSubmit(Request $request, Response $response): Response
    {
        $adminContext = $request->getAttribute(AdminContext::class);
        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$adminContext instanceof AdminContext || !$requestContext instanceof RequestContext) {
            throw new RuntimeException('Context missing');
        }

        $data = $request->getParsedBody();
        if (!is_array($data) || !isset($data['current_password'], $data['new_password'])) {
            return $this->view->render($response, 'pages/me/password.twig', [
                'error' => 'Invalid request data.',
            ]);
        }

        // We need the admin's email to use ChangePasswordService.
        // Get the first email. In this architecture, it should be the primary/verified one.
        $emails = $this->emailReader->listByAdminId($adminContext->adminId);
        if (empty($emails)) {
            throw new RuntimeException('Admin email not found');
        }
        $email = $emails[0]->email;

        try {
            $result = $this->changePasswordService->change(
                new ChangePasswordRequestDTO(
                    email: $email,
                    currentPassword: (string)$data['current_password'],
                    newPassword: (string)$data['new_password'],
                    requestContext: $requestContext,
                )
            );

            if (!$result->success) {
                return $this->view->render($response, 'pages/me/password.twig', [
                    'error' => 'Current password is incorrect or change failed.',
                ]);
            }

            return $response
                ->withHeader('Location', '/me/profile')
                ->withStatus(302);
        } catch (\Throwable $e) {
            return $this->view->render($response, 'pages/me/password.twig', [
                'error' => 'An error occurred while changing password.',
            ]);
        }
    }
}
