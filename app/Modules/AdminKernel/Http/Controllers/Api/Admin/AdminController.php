<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Admin;

use JsonException;
use Maatify\AdminKernel\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Admin\DTO\AdminEmailListItemDTO;
use Maatify\AdminKernel\Domain\Admin\Reader\AdminBasicInfoReaderInterface;
use Maatify\AdminKernel\Domain\Admin\Reader\AdminEmailReaderInterface;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminPasswordRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Request\CreateAdminEmailRequestDTO;
use Maatify\AdminKernel\Domain\DTO\Response\ActionResultResponseDTO;
use Maatify\AdminKernel\Domain\DTO\Response\AdminCreateResponseDTO;
use Maatify\AdminKernel\Domain\Enum\IdentifierType;
use Maatify\AdminKernel\Domain\Enum\VerificationStatus;
use Maatify\AdminKernel\Domain\Exception\InvalidIdentifierFormatException;
use Maatify\AdminKernel\Domain\Service\PasswordService;
use Maatify\AdminKernel\Domain\Service\SessionRevocationService;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminTotpSecretStoreInterface;
use Maatify\AdminKernel\Application\Services\AuthoritativeAuditService;
use Maatify\AdminKernel\Domain\Support\CorrelationId;
use Maatify\AdminKernel\Infrastructure\Repository\AdminEmailRepository;
use Maatify\AdminKernel\Infrastructure\Repository\AdminRepository;
use Maatify\AdminKernel\Validation\Schemas\Admin\AdminAddEmailSchema;
use Maatify\AdminKernel\Validation\Schemas\Admin\AdminCreateSchema;
use Maatify\Validation\Guard\ValidationGuard;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Random\RandomException;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

readonly class AdminController
{
    public function __construct(
        private AdminRepository $adminRepository,
        private AdminEmailRepository $adminEmailRepository,
        private ValidationGuard $validationGuard,
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private AdminPasswordRepositoryInterface $passwordRepository,
        private PasswordService $passwordService,
        private PDO $pdo,
        private SessionRevocationService $sessionRevocationService,
        private AdminTotpSecretStoreInterface $totpSecretStore,
        private AuthoritativeAuditService $auditService,

        private AdminEmailReaderInterface $emailReader,
        private AdminBasicInfoReaderInterface $basicInfoReader,
    ) {
    }

    /**
     * Admin creation requires email-first flow (security invariant).
     * An admin MUST NOT be created without a unique, validated email.
     */
    public function create(Request $request, Response $response): Response
    {
        /** @var \Maatify\AdminKernel\Context\AdminContext $adminContext */
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
            throw new RuntimeException('AdminContext missing');
        }

        /** @var RequestContext $requestContext */
        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$requestContext instanceof RequestContext) {
            throw new RuntimeException('RequestContext missing');
        }

        // 1️⃣ Read + Validate input
        $data = (array) $request->getParsedBody();
        $this->validationGuard->check(new AdminCreateSchema(), $data);

        $emailInput = $data[IdentifierType::EMAIL->value] ?? null;

        try {
            $emailDto = new CreateAdminEmailRequestDTO($emailInput);
        } catch (InvalidIdentifierFormatException) {
            throw new HttpBadRequestException($request, 'Invalid email format.');
        }

        $email = $emailDto->email;

        // 2️⃣ Derive Blind Index
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);

        // 3️⃣ Uniqueness check (FAIL-FAST)
        $existingAdminEmailIdentifierDTO = $this->adminEmailRepository->findByBlindIndex($blindIndex);
        if ($existingAdminEmailIdentifierDTO !== null) {
            throw new HttpBadRequestException($request, 'Email already registered.');
        }

        $correlationId = CorrelationId::generate();

        // 4️⃣ Begin transaction
        $this->pdo->beginTransaction();

        try {
            // 5️⃣ Create admin
            $displayName = trim((string) ($data['display_name'] ?? ''));

            if ($displayName === '') {
                throw new HttpBadRequestException($request, 'Display name is required.');
            }

            $adminId = $this->adminRepository->create($displayName);
            $createdAt = $this->adminRepository->getCreatedAt($adminId);

            // 6️⃣ Encrypt email
            $encryptedEmail = $this->cryptoService->encryptEmail($email);

            // 7️⃣ Insert email (PENDING)
            $this->adminEmailRepository->addEmail(
                $adminId,
                $blindIndex,
                $encryptedEmail
            );

            // 8️⃣ Generate temp password
            $tempPassword = bin2hex(random_bytes(8));

            // 9️⃣ Hash + save password
            $hashResult = $this->passwordService->hash($tempPassword);

            $this->passwordRepository->savePassword(
                $adminId,
                $hashResult['hash'],
                $hashResult['pepper_id'],
                true,
                (new \DateTimeImmutable('+15 minutes'))->format('Y-m-d H:i:s')
            );

            // 1️⃣2️⃣ Commit
            $this->pdo->commit();

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // 1️⃣3️⃣ Response (temp password shown once)
        $responseDto = new AdminCreateResponseDTO(
            adminId: $adminId,
            createdAt: $createdAt,
            tempPassword: $tempPassword
        );

        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }


    /**
     * @param array<string, string> $args
     * @throws RandomException
     * @throws HttpBadRequestException
     */
    public function addEmail(Request $request, Response $response, array $args): Response
    {
        $adminId = (int)$args['id'];
        
        $data = (array)$request->getParsedBody();

        $input = array_merge($data, $args);

        $this->validationGuard->check(new AdminAddEmailSchema(), $input);

        $emailInput = $data[IdentifierType::EMAIL->value] ?? null;

        try {
            $requestDto = new CreateAdminEmailRequestDTO($emailInput);
        } catch (InvalidIdentifierFormatException $e) {
            // Should be caught by validation guard technically, but if schema checks v::email(), it is good.
            // But we keep this just in case.
            throw new HttpBadRequestException($request, 'Invalid email format.');
        }
        $email = $requestDto->email;

        // Blind Index
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);

        $existing = $this->adminEmailRepository->findByBlindIndex($blindIndex);

        if ($existing !== null) {

            $emailId = $existing->emailId;

            if($existing->adminId !== $adminId){
                throw new HttpBadRequestException($request, 'Email is already used by another admin.');
            }
            switch ($existing->verificationStatus){
                case VerificationStatus::VERIFIED:
                    throw new HttpBadRequestException($request, 'Email already verified.');
                case VerificationStatus::FAILED:
                    throw new HttpBadRequestException($request, 'Email already failed.');
                case VerificationStatus::PENDING:
                    throw new HttpBadRequestException($request, 'Email already pending.');
                case VerificationStatus::REPLACED:
                    $this->adminEmailRepository->markPending($existing->emailId);
                break;
            }
        }
        else{
            // Encryption
            $encryptedDto = $this->cryptoService->encryptEmail($email);
            $emailId = $this->adminEmailRepository->addEmail($adminId, $blindIndex, $encryptedDto);
        }

        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }

        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$requestContext instanceof RequestContext) {
            throw new \RuntimeException('RequestContext missing');
        }

        $responseDto = new ActionResultResponseDTO(
            adminId: $adminId,
            emailAdded: true,
        );

        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * ===============================
     * Admin Emails — LIST
     * GET api/admins/{id}/emails
     * ===============================
     *
     * - Read-only
     * - UI only
     * - No mutations
     * - No audit
     *
     * @param   Request                $request
     * @param   Response               $response
     * @param   array<string, string>  $args
     *
     * @return Response
     * @throws JsonException
     */
    public function getEmails(Request $request, Response $response, array $args): Response
    {
        $adminId = (int)$args['id'];

        $displayName = $this->basicInfoReader->getDisplayName($adminId);

        if ($displayName === null) {
            throw new HttpNotFoundException($request, 'Admin not found');
        }

        $emails = $this->emailReader->listByAdminId($adminId);

        $payload = [
            'admin_id' => $adminId,
            'display_name' => $displayName,
            'items' => array_map(
                static fn(AdminEmailListItemDTO $dto) => $dto->jsonSerialize(),
                $emails
            ),
        ];

        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

//    /**
//     * @throws HttpBadRequestException
//     */
//    public function lookupEmail(Request $request, Response $response): Response
//    {
//        $data = (array)$request->getParsedBody();
//
//        $this->validationGuard->check(new AdminLookupEmailSchema(), $data);
//
//        $emailInput = $data[IdentifierType::EMAIL->value] ?? null;
//
//        try {
//            $requestDto = new VerifyAdminEmailRequestDTO($emailInput);
//        } catch (InvalidIdentifierFormatException $e) {
//             // Redundant with validation but safe
//            throw new HttpBadRequestException($request, 'Invalid email format.');
//        }
//        $email = $requestDto->email;
//
//        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);
//
//        $adminEmailIdentifierDTO = $this->adminEmailRepository->findByBlindIndex($blindIndex);
//
//        if ($adminEmailIdentifierDTO !== null) {
//            $responseDto = new ActionResultResponseDTO(
//                adminId: $adminEmailIdentifierDTO->adminId,
//                exists: true,
//            );
//        } else {
//            $responseDto = new ActionResultResponseDTO(
//                exists: false,
//            );
//        }
//
//        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
//        $response->getBody()->write($json);
//        return $response
//            ->withHeader('Content-Type', 'application/json')
//            ->withStatus(200);
//    }

//    /**
//     * @param array<string, string> $args
//     */
//    public function getEmail(Request $request, Response $response, array $args): Response
//    {
//        $this->validationGuard->check(new AdminGetEmailSchema(), $args);
//
//        $adminId = (int)$args['id'];
//
//        $encryptedEmailDto = $this->adminEmailRepository->getEncryptedEmail($adminId);
//
//        $email = null;
//        if ($encryptedEmailDto !== null) {
//            $email = $this->cryptoService->decryptEmail($encryptedEmailDto);
//        }
//
//        $responseDto = new AdminEmailResponseDTO($adminId, $email);
//
//        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
//        $response->getBody()->write($json);
//        return $response
//            ->withHeader('Content-Type', 'application/json')
//            ->withStatus(200);
//    }


    public function generateTemporaryPassword(Request $request, Response $response, array $args): Response
    {
        $targetAdminId = (int)($args['admin_id'] ?? 0);
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext || !$requestContext instanceof RequestContext) {
            throw new RuntimeException('Context missing');
        }
        if ($targetAdminId <= 0 || $targetAdminId === $adminContext->adminId) {
            throw new HttpBadRequestException($request, 'Invalid target admin.');
        }
        $tempPassword = bin2hex(random_bytes(8));
        $expiresAt = (new \DateTimeImmutable('+15 minutes'))->format('Y-m-d H:i:s');
        $this->pdo->beginTransaction();
        try {
            $hashResult = $this->passwordService->hash($tempPassword);
            $this->passwordRepository->savePassword($targetAdminId, $hashResult['hash'], $hashResult['pepper_id'], true, $expiresAt);
            $this->sessionRevocationService->revokeAllActiveForAdmin($targetAdminId, $adminContext->adminId, $requestContext, 'admin_temp_password_reset');
            $this->auditService->recordSystemConfigChanged($adminContext->adminId, 'admin.temp_password.generate', (string)$targetAdminId, 'issued');
            $this->pdo->commit();
        } catch (\Throwable $e) { $this->pdo->rollBack(); throw $e; }
        $response->getBody()->write((string)json_encode(['admin_id'=>$targetAdminId,'temporary_password'=>$tempPassword,'expires_at'=>$expiresAt], JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type','application/json')->withStatus(200);
    }

    public function resetTwoFactor(Request $request, Response $response, array $args): Response
    {
        $targetAdminId = (int)($args['admin_id'] ?? 0);
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext || !$requestContext instanceof RequestContext) {
            throw new RuntimeException('Context missing');
        }
        if ($targetAdminId <= 0 || $targetAdminId === $adminContext->adminId) {
            throw new HttpBadRequestException($request, 'Invalid target admin.');
        }
        $this->pdo->beginTransaction();
        try {
            $this->totpSecretStore->delete($targetAdminId);
            $this->sessionRevocationService->revokeAllActiveForAdmin($targetAdminId, $adminContext->adminId, $requestContext, 'admin_2fa_reset');
            $this->auditService->recordSystemConfigChanged($adminContext->adminId, 'admin.2fa.reset', (string)$targetAdminId, 'reset');
            $this->pdo->commit();
        } catch (\Throwable $e) { $this->pdo->rollBack(); throw $e; }
        $response->getBody()->write((string)json_encode(['admin_id'=>$targetAdminId,'two_factor_reset'=>true], JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type','application/json')->withStatus(200);
    }

}
