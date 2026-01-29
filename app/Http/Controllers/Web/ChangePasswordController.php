<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Auth\ChangePasswordService;
use App\Application\Auth\DTO\ChangePasswordRequestDTO;
use App\Context\RequestContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class ChangePasswordController
{
    public function __construct(
        private Twig $view,
        private ChangePasswordService $changePasswordService,
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

        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$requestContext instanceof RequestContext) {
            throw new \RuntimeException('RequestContext missing');
        }

        $result = $this->changePasswordService->change(
            new ChangePasswordRequestDTO(
                email: (string)$data['email'],
                currentPassword: (string)$data['current_password'],
                newPassword: (string)$data['new_password'],
                requestContext: $requestContext,
            )
        );

        if (!$result->success) {
            return $this->view->render($response, 'auth/change_password.twig', [
                'error' => 'Authentication failed.',
            ]);
        }

        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}
