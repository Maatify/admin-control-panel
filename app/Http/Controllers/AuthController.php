<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\ContextProviderInterface;
use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\DTO\LoginRequestDTO;
use App\Domain\DTO\LoginResponseDTO;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Service\AdminAuthenticationService;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\AuthLoginSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class AuthController
{
    public function __construct(
        private AdminAuthenticationService $authService,
        private string $blindIndexKey,
        private ValidationGuard $validationGuard,
        private ContextProviderInterface $contextProvider,
        private AdminActivityLogService $adminActivityLogService,
    ) {}

    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $this->validationGuard->check(new AuthLoginSchema(), $data);

        $dto = new LoginRequestDTO(
            (string) $data['email'],
            (string) $data['password']
        );

        // Blind Index Calculation
        $blindIndex = hash_hmac('sha256', $dto->email, $this->blindIndexKey);
        assert(is_string($blindIndex));

        try {
            $result = $this->authService->login($blindIndex, $dto->password);

            // 🔹 Resolve Contexts via Provider
            // Note: admin() uses the admin_id from attributes, but login() returns the ID.
            // Since the request attribute is set by SessionGuardMiddleware which validates the session,
            // and login() creates a NEW session, the request might NOT have the admin_id attribute yet?
            // Wait, Activity Log for LOGIN_SUCCESS uses the ID from the result in the previous implementation.
            // But ContextProvider relies on Request Attributes.
            // If we just logged in, the request does NOT have the admin_id attribute.
            //
            // "Rules: `admin()` MUST return `null` if admin is not authenticated"
            //
            // If we use $this->contextProvider->admin(), it will be null because the request is not authenticated yet (cookie not set/sent).
            //
            // HOWEVER, the `adminContext` object can be created manually if needed, OR we rely on the provider.
            // If the provider relies ONLY on request attributes, it cannot serve the just-logged-in user context unless we update the request?
            // But HttpContextProvider is immutable regarding the request it holds? No, it holds the request object.
            // If we update the request in the container, does HttpContextProvider see it?
            // No, HttpContextProvider has the request injected in constructor.
            //
            // CRITICAL ISSUE:
            // The prompt says: "Replace context creation with: $adminContext = $this->contextProvider->admin();"
            // This works for authenticated requests.
            // For Login Controller, we are transitioning from Guest -> Authenticated.
            // The `ContextProvider` will return NULL for admin().
            // But `AdminActivityLogService` needs `AdminContext`.
            //
            // If `admin()` returns null, we can't log "LOGIN_SUCCESS" with the actor ID using the provider?
            // Actually, `AdminContext` is a value object.
            // If `ContextProvider` returns null, we can construct `AdminContext` manually from the result?
            // "Controller Refactor... Replace context creation with... $this->contextProvider->admin()".
            // This instruction might be for "standard" controllers.
            // But for AuthController, we have the ID.
            //
            // Let's look at the instruction again: "Choose ONE controller... Replace context creation with..."
            // If I choose AuthController, and I follow instructions blindly, I will get null.
            // If I get null, I can't log the actor properly (or I log actor 0/null?).
            // But `LOGIN_SUCCESS` implies we know who it is.
            //
            // Maybe I should choose `LoginController` (Web) or verify if I can construct it manually if provider returns null?
            // "Rules: Do NOT refactor all controllers". "This is a reference implementation only".
            //
            // If I stick to `AuthController`, I should see if `admin()` works. It won't.
            // So `AuthController` might be a bad candidate for "Replace with provider" IF the provider is strictly read-only from request.
            //
            // HOWEVER, the prompt explicitly says: "Choose ONE controller (AuthController OR Web LoginController)".
            // This implies I SHOULD be able to use it there.
            // Maybe the prompt implies that for Login Success, we *should* rely on the context?
            // No, that's impossible.
            //
            // Wait, if I look at the previous code: `AdminContext::fromAdminId($result->adminId)`.
            // The new code: `$this->contextProvider->admin()`.
            // This is a logic mismatch for *Login*.
            //
            // BUT, `AdminActivityLogService::log` requires `AdminContext`.
            // If `admin()` returns null, we can't pass it.
            //
            // HYPOTHESIS: The prompt assumes I'm refactoring a controller where the user is ALREADY logged in?
            // But the options are AuthController (Login) or Web LoginController (Login).
            // Both are for logging in.
            //
            // Is it possible that `admin()` is supposed to be used for *other* actions?
            // But the task is to refactor this controller.
            //
            // Maybe I should construct `AdminContext` manually if provider returns null, but prefer provider?
            // Or maybe the provider allows `withAdminId`? No, interface is read-only.
            //
            // OPTION: For `LOGIN_SUCCESS`, we HAVE the ID from `$result`. We should construct `AdminContext` manually.
            // AND use `$this->contextProvider->request()` for the request context.
            // The prompt "Replace context creation with..." might be a general instruction.
            //
            // Let's strictly follow "Replace context creation with...".
            // If I do that, `admin()` is null.
            // Then `AdminActivityLogService` will receive null? No, type hint is `AdminContext`.
            // So code will crash if I pass null.
            //
            // "Rules: `admin()` MUST return `null` if admin is not authenticated".
            // So I MUST NOT use `$this->contextProvider->admin()` for the actor in Login Success, because the actor is not yet in the request context.
            // I MUST use `new AdminContext($result->adminId)`.
            //
            // BUT I CAN use `$this->contextProvider->request()`.
            //
            // Refactor instruction: "Remove Direct usage of AdminContextResolver... RequestContextResolver".
            // "Inject ContextProviderInterface".
            //
            // So:
            // $requestContext = $this->contextProvider->request();
            // $adminContext = new AdminContext($result->adminId);
            //
            // This satisfies the removal of Resolvers, and usage of Provider for Request.
            // The prompt says "Replace context creation with... [code block]".
            // If I can't use the code block exactly for Admin, I should use it for Request.

            $adminContext = \App\Context\AdminContext::fromAdminId($result->adminId);
            $requestContext = $this->contextProvider->request();

            $this->adminActivityLogService->log(
                adminContext: $adminContext,
                requestContext: $requestContext,
                action: AdminActivityAction::LOGIN_SUCCESS,
                metadata: [
                    'method' => 'password',
                ]
            );

            $responseDto = new LoginResponseDTO($result->token);
            $response->getBody()->write((string) json_encode($responseDto));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidCredentialsException $e) {
            // ❌ No admin context here (unknown identity)
            $response->getBody()->write((string) json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        } catch (AuthStateException $e) {
            $response->getBody()->write((string) json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
    }
}
