<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use DateTimeImmutable;
use DI\Container as DIContainer;
use DI\ContainerBuilder;
use Maatify\AdminKernel\Ui\Config\MediaUrlConfigDTO;
use Maatify\AdminKernel\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminPasswordRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminTotpSecretStoreInterface;
use Maatify\AdminKernel\Domain\Contracts\Auth\RedirectTokenProviderInterface;
use Maatify\AdminKernel\Domain\Contracts\TotpServiceInterface;
use Maatify\AdminKernel\Domain\Service\PasswordService;
use Maatify\AdminKernel\Infrastructure\Repository\AdminEmailRepository;
use Maatify\AdminKernel\Infrastructure\Repository\AdminRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Support\UnifiedEndpointBase;

/**
 * End-to-end baseline for the "return to the page I wanted after login" flow.
 *
 * Nothing in the redirect path is mocked: the real kernel, middleware stack,
 * controllers, HMAC token provider (RedirectTokenCryptoSignatureProvider) and
 * database are used. This is the behavioural contract that must keep passing
 * when the HMAC provider is replaced by maatify/php-return-target.
 *
 * Flow under test:
 *   protected page -> 302 /login?r=<signed token> -> POST /login -> 302 <original page>
 */
final class LoginReturnTargetE2ETest extends UnifiedEndpointBase
{
    private const EMAIL = 'return-target@example.com';
    private const PASSWORD = 'Str0ng!Passw0rd#2026';

    private int $adminId;
    private string $totpSecret;

    protected function configureContainer(ContainerBuilder $containerBuilder): void
    {
        // Host bindings normally supplied by public/admin/index.php for the UI layer.
        $mediaUrlConfig = new MediaUrlConfigDTO('http://localhost/assets/', 'http://localhost/images/', 'test');

        $containerBuilder->addDefinitions([
            MediaUrlConfigDTO::class => static fn (): MediaUrlConfigDTO => $mediaUrlConfig,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        [$this->adminId, $this->totpSecret] = $this->createLoginReadyAdmin();
    }

    // ------------------------------------------------------------------
    // 1. Issuing: guarded page -> /login?r=
    // ------------------------------------------------------------------

    public function test_unauthenticated_protected_page_redirects_to_login_with_signed_token(): void
    {
        $response = $this->get('/dashboard');

        self::assertSame(302, $response->getStatusCode());

        $location = $response->getHeaderLine('Location');
        self::assertStringStartsWith('/login?r=', $location);

        $parsed = $this->tokenProvider()->verifyAndParse($this->extractToken($location));
        self::assertNotNull($parsed, 'Issued token must verify with the real provider.');
        self::assertSame('/dashboard', $parsed->path);
    }

    public function test_query_string_of_original_target_is_preserved_in_token(): void
    {
        $response = $this->get('/dashboard?tab=audit&page=2');

        $parsed = $this->tokenProvider()->verifyAndParse(
            $this->extractToken($response->getHeaderLine('Location'))
        );

        self::assertNotNull($parsed);
        self::assertSame('/dashboard?tab=audit&page=2', $parsed->path);
    }

    public function test_login_page_itself_never_produces_a_token(): void
    {
        $response = $this->get('/login');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringNotContainsString('name="r"', (string) $response->getBody());
    }

    public function test_login_page_carries_token_into_form_as_hidden_field(): void
    {
        $token = $this->tokenProvider()->issue('/dashboard?tab=audit');

        $response = $this->get('/login?r=' . urlencode($token));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString($token, (string) $response->getBody());
    }

    // ------------------------------------------------------------------
    // 2. Consuming: POST /login honours a valid token
    // ------------------------------------------------------------------

    public function test_full_flow_redirects_back_to_original_page_after_login(): void
    {
        $guarded = $this->get('/dashboard?tab=audit');
        $loginLocation = $guarded->getHeaderLine('Location');

        $login = $this->postLogin(['r' => $this->extractToken($loginLocation)]);

        self::assertSame(302, $login->getStatusCode());
        self::assertSame('/dashboard?tab=audit', $login->getHeaderLine('Location'));
        self::assertNotNull($this->authCookie($login), 'auth_token cookie must be set.');
    }

    public function test_token_submitted_via_query_string_is_honoured(): void
    {
        $token = $this->tokenProvider()->issue('/dashboard?tab=audit');

        $login = $this->postLogin([], '/login?r=' . urlencode($token));

        self::assertSame(302, $login->getStatusCode());
        self::assertSame('/dashboard?tab=audit', $login->getHeaderLine('Location'));
    }

    public function test_body_token_takes_precedence_over_query_token(): void
    {
        $body = $this->tokenProvider()->issue('/dashboard?from=body');
        $query = $this->tokenProvider()->issue('/dashboard?from=query');

        $login = $this->postLogin(['r' => $body], '/login?r=' . urlencode($query));

        self::assertSame('/dashboard?from=body', $login->getHeaderLine('Location'));
    }

    public function test_login_without_token_goes_to_dashboard(): void
    {
        $login = $this->postLogin();

        self::assertSame(302, $login->getStatusCode());
        self::assertSame('/dashboard', $login->getHeaderLine('Location'));
    }

    public function test_blank_token_goes_to_dashboard(): void
    {
        $login = $this->postLogin(['r' => '   ']);

        self::assertSame('/dashboard', $login->getHeaderLine('Location'));
    }

    // ------------------------------------------------------------------
    // 3. Rejection: every invalid token falls back to /dashboard
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{0: callable(string): string}>
     */
    public static function invalidTokenProvider(): array
    {
        return [
            'tampered signature' => [static function (string $token): string {
                [$payload, $sig] = explode('.', $token);
                $flipped = ($sig[0] === 'A' ? 'B' : 'A') . substr($sig, 1);

                return $payload . '.' . $flipped;
            }],
            'tampered payload' => [static function (string $token): string {
                [, $sig] = explode('.', $token);
                $forged = rtrim(strtr(base64_encode(
                    (string) json_encode(['p' => '/admins/delete', 'exp' => time() + 300])
                ), '+/', '-_'), '=');

                return $forged . '.' . $sig;
            }],
            'payload without signature' => [static fn (string $token): string => explode('.', $token)[0] . '.'],
            'no dot separator' => [static fn (string $token): string => str_replace('.', '', $token)],
            'extra segment' => [static fn (string $token): string => $token . '.extra'],
            'garbage' => [static fn (string $token): string => 'not-a-token'],
            'plain external url' => [static fn (string $token): string => 'https://evil.example/phish'],
            'plain internal path (unsigned)' => [static fn (string $token): string => '/admins/delete'],
        ];
    }

    /**
     * @param callable(string): string $mutate
     */
    #[DataProvider('invalidTokenProvider')]
    public function test_invalid_token_falls_back_to_dashboard(callable $mutate): void
    {
        $valid = $this->tokenProvider()->issue('/dashboard?tab=audit');

        $login = $this->postLogin(['r' => $mutate($valid)]);

        self::assertSame(302, $login->getStatusCode());
        self::assertSame('/dashboard', $login->getHeaderLine('Location'));
        self::assertNotNull($this->authCookie($login), 'Login still succeeds; only the target is dropped.');
    }

    public function test_expired_token_falls_back_to_dashboard(): void
    {
        $login = $this->postLogin(['r' => $this->signRaw(['p' => '/dashboard?tab=audit', 'exp' => time() - 10])]);

        self::assertSame('/dashboard', $login->getHeaderLine('Location'));
    }

    public function test_validly_signed_external_target_is_rejected(): void
    {
        $login = $this->postLogin(['r' => $this->signRaw(['p' => 'https://evil.example/', 'exp' => time() + 300])]);

        self::assertSame('/dashboard', $login->getHeaderLine('Location'));
    }

    public function test_validly_signed_protocol_relative_target_is_rejected(): void
    {
        $login = $this->postLogin(['r' => $this->signRaw(['p' => '//evil.example/x', 'exp' => time() + 300])]);

        self::assertSame('/dashboard', $login->getHeaderLine('Location'));
    }

    public function test_validly_signed_login_loop_target_is_rejected(): void
    {
        $login = $this->postLogin(['r' => $this->signRaw(['p' => '/login?x=1', 'exp' => time() + 300])]);

        self::assertSame('/dashboard', $login->getHeaderLine('Location'));
    }

    public function test_validly_signed_header_injection_target_is_rejected(): void
    {
        $login = $this->postLogin(['r' => $this->signRaw(['p' => "/dashboard\r\nSet-Cookie: x=1", 'exp' => time() + 300])]);

        self::assertSame('/dashboard', $login->getHeaderLine('Location'));
        self::assertStringNotContainsString("\n", $login->getHeaderLine('Location'));
    }

    public function test_token_issued_with_unsafe_path_is_normalised_at_issue_time(): void
    {
        $token = $this->tokenProvider()->issue('https://evil.example/');

        $login = $this->postLogin(['r' => $token]);

        self::assertSame('/dashboard', $login->getHeaderLine('Location'));
    }

    // ------------------------------------------------------------------
    // 4. Failure paths keep the token for the retry
    // ------------------------------------------------------------------

    public function test_failed_login_does_not_redirect_and_keeps_token_in_form(): void
    {
        $token = $this->tokenProvider()->issue('/dashboard?tab=audit');

        $login = $this->postLogin(['r' => $token, 'password' => 'wrong-password']);

        self::assertNotSame(302, $login->getStatusCode(), 'Failed login must not redirect to the target.');
        self::assertNull($this->authCookie($login));
        self::assertStringContainsString($token, (string) $login->getBody());
    }

    // ------------------------------------------------------------------
    // 5. Step-up (2FA) leg preserves the target as well
    // ------------------------------------------------------------------

    public function test_step_up_verify_redirects_to_signed_target_after_valid_totp(): void
    {
        $cookie = $this->loginAndGetCookie();

        $token = $this->tokenProvider()->issue('/dashboard?tab=audit');
        $response = $this->app->handle(
            $this->request('POST', '/2fa/verify', [
                'code' => $this->currentTotp(),
                'scope' => 'login',
                'r' => $token,
            ])->withCookieParams(['auth_token' => $cookie])
        );

        self::assertSame(302, $response->getStatusCode(), (string) $response->getBody());
        self::assertSame('/dashboard?tab=audit', $response->getHeaderLine('Location'));
    }

    public function test_step_up_verify_ignores_tampered_target(): void
    {
        $cookie = $this->loginAndGetCookie();

        $response = $this->app->handle(
            $this->request('POST', '/2fa/verify', [
                'code' => $this->currentTotp(),
                'scope' => 'login',
                'r' => $this->signRaw(['p' => 'https://evil.example/', 'exp' => time() + 300]),
            ])->withCookieParams(['auth_token' => $cookie])
        );

        self::assertSame(302, $response->getStatusCode(), (string) $response->getBody());
        self::assertSame('/dashboard', $response->getHeaderLine('Location'));
    }

    // ------------------------------------------------------------------
    // 6. The whole chain, hop by hop, exactly as a browser would walk it
    // ------------------------------------------------------------------

    public function test_complete_chain_guarded_page_login_step_up_then_original_page(): void
    {
        // Hop 1: anonymous visit to a deep link -> login with a signed token.
        $hop1 = $this->get('/dashboard?tab=audit');
        self::assertSame(302, $hop1->getStatusCode());
        $loginUrl = $hop1->getHeaderLine('Location');
        self::assertStringStartsWith('/login?r=', $loginUrl);

        // Hop 2 (rendering /login with the token in the form) is covered by
        // test_login_page_carries_token_into_form_as_hidden_field. It is not repeated here:
        // the Twig environment is a per-process singleton, and rendering a page before a
        // protected page in the same process makes TwigAdminContextMiddleware::addGlobal() throw.
        $token = $this->extractToken($loginUrl);

        // Hop 3: credentials accepted -> back to the deep link.
        $login = $this->postLogin(['r' => $token]);
        self::assertSame('/dashboard?tab=audit', $login->getHeaderLine('Location'));
        $cookie = $this->authCookie($login);
        self::assertNotNull($cookie);

        // Hop 4: session is not 2FA-verified yet -> guard re-issues a token for the SAME target.
        $hop4 = $this->app->handle(
            $this->request('GET', $login->getHeaderLine('Location'))->withCookieParams(['auth_token' => $cookie])
        );
        self::assertSame(302, $hop4->getStatusCode());
        $stepUpUrl = $hop4->getHeaderLine('Location');
        self::assertStringStartsWith('/2fa/verify', $stepUpUrl);
        $parsed = $this->tokenProvider()->verifyAndParse($this->extractToken($stepUpUrl));
        self::assertNotNull($parsed);
        self::assertSame('/dashboard?tab=audit', $parsed->path);

        // Hop 5: valid TOTP -> back to the deep link again.
        $hop5 = $this->app->handle(
            $this->request('POST', '/2fa/verify', [
                'code' => $this->currentTotp(),
                'scope' => 'login',
                'r' => $this->extractToken($stepUpUrl),
            ])->withCookieParams(['auth_token' => $cookie])
        );
        self::assertSame(302, $hop5->getStatusCode(), (string) $hop5->getBody());
        self::assertSame('/dashboard?tab=audit', $hop5->getHeaderLine('Location'));

        // Hop 6: the original page is finally served, no further redirect.
        $final = $this->app->handle(
            $this->request('GET', $hop5->getHeaderLine('Location'))->withCookieParams(['auth_token' => $cookie])
        );
        self::assertSame(200, $final->getStatusCode(), $final->getHeaderLine('Location'));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function tokenProvider(): RedirectTokenProviderInterface
    {
        $provider = $this->container()->get(RedirectTokenProviderInterface::class);
        self::assertInstanceOf(RedirectTokenProviderInterface::class, $provider);

        return $provider;
    }

    private function container(): DIContainer
    {
        $container = $this->app->getContainer();
        self::assertInstanceOf(DIContainer::class, $container);

        return $container;
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function request(string $method, string $uri, ?array $body = null): ServerRequestInterface
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest($method, $uri, ['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeader('Accept', 'text/html')
            ->withHeader('User-Agent', 'phpunit-e2e');

        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $request = $request->withQueryParams($query);

        if ($body !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withParsedBody($body);
        }

        return $request;
    }

    private function get(string $uri): ResponseInterface
    {
        return $this->app->handle($this->request('GET', $uri));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function postLogin(array $overrides = [], string $uri = '/login'): ResponseInterface
    {
        $body = array_merge(['email' => self::EMAIL, 'password' => self::PASSWORD], $overrides);

        return $this->app->handle($this->request('POST', $uri, $body));
    }

    private function loginAndGetCookie(): string
    {
        $login = $this->postLogin();
        self::assertSame(302, $login->getStatusCode(), (string) $login->getBody());

        $cookie = $this->authCookie($login);
        self::assertNotNull($cookie, 'Login must set auth_token.');

        return $cookie;
    }

    private function authCookie(ResponseInterface $response): ?string
    {
        foreach ($response->getHeader('Set-Cookie') as $header) {
            if (preg_match('/^auth_token=([^;]+)/', $header, $m) === 1) {
                return $m[1];
            }
        }

        return null;
    }

    private function extractToken(string $location): string
    {
        $query = [];
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $token = $query['r'] ?? null;
        self::assertIsString($token, 'Location must carry ?r=. Got: ' . $location);

        return $token;
    }

    /**
     * Sign an arbitrary payload with the real key material, bypassing issue()'s
     * path normalisation, to prove verifyAndParse() independently validates it.
     *
     * @param array<string, mixed> $payload
     */
    private function signRaw(array $payload): string
    {
        $provider = $this->tokenProvider();
        $method = new \ReflectionMethod($provider, 'signPayload');

        $encoded = rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=');
        $signature = $method->invoke($provider, $encoded);
        self::assertIsString($signature);

        return $encoded . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    private function currentTotp(): string
    {
        return $this->generateTotp($this->totpSecret);
    }

    /**
     * RFC 6238 (SHA1, 30s, 6 digits) so the test does not depend on the
     * service under test to produce its own valid code.
     */
    private function generateTotp(string $base32Secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(strtoupper(rtrim($base32Secret, '='))) as $char) {
            $bits .= str_pad(decbin((int) strpos($alphabet, $char)), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $key .= chr((int) bindec($byte));
            }
        }

        $counter = pack('N*', 0) . pack('N*', intdiv(time(), 30));
        $hash = hash_hmac('sha1', $counter, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $value = (
            ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff)
        ) % 1_000_000;

        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Mirrors scripts/bootstrap_admin.php: a verified admin with a password and a TOTP secret.
     *
     * @return array{0: int, 1: string}
     */
    private function createLoginReadyAdmin(): array
    {
        $c = $this->container();
        $pdo = $this->pdo;
        self::assertNotNull($pdo);

        $totp = $c->get(TotpServiceInterface::class);
        $adminRepo = $c->get(AdminRepository::class);
        $emailRepo = $c->get(AdminEmailRepository::class);
        $crypto = $c->get(AdminIdentifierCryptoServiceInterface::class);
        $passRepo = $c->get(AdminPasswordRepositoryInterface::class);
        $passwords = $c->get(PasswordService::class);
        $totpStore = $c->get(AdminTotpSecretStoreInterface::class);

        self::assertInstanceOf(TotpServiceInterface::class, $totp);
        self::assertInstanceOf(AdminRepository::class, $adminRepo);
        self::assertInstanceOf(AdminEmailRepository::class, $emailRepo);
        self::assertInstanceOf(AdminIdentifierCryptoServiceInterface::class, $crypto);
        self::assertInstanceOf(AdminPasswordRepositoryInterface::class, $passRepo);
        self::assertInstanceOf(PasswordService::class, $passwords);
        self::assertInstanceOf(AdminTotpSecretStoreInterface::class, $totpStore);

        $secret = $totp->generateSecret();

        $adminId = $adminRepo->createFirstAdmin();

        $emailId = $emailRepo->addEmail(
            $adminId,
            $crypto->deriveEmailBlindIndex(self::EMAIL),
            $crypto->encryptEmail(self::EMAIL)
        );
        $emailRepo->markVerified($emailId, (new DateTimeImmutable())->format('Y-m-d H:i:s'));

        $hash = $passwords->hash(self::PASSWORD);
        $passRepo->savePassword($adminId, $hash['hash'], $hash['pepper_id'], false);

        $totpStore->store($adminId, $secret);

        return [$adminId, $secret];
    }
}
