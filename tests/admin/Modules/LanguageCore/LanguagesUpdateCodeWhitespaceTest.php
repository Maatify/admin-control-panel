<?php

declare(strict_types=1);

namespace Tests\Modules\LanguageCore;

use Maatify\AdminKernel\Infrastructure\I18n\Language\PdoLanguageCodeChange;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesUpdateCodeController;
use Maatify\I18n\Management\Service\TranslationWriteService;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\Contract\LanguageSettingsRepositoryInterface;
use Maatify\LanguageCore\Exception\LanguageUpdateFailedException;
use Maatify\LanguageCore\Service\LanguageManagementService;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Validator\RespectValidator;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * The admin "update language code" endpoint passes `code` through unchanged:
 * the request schema only checks 1..16 characters (the storage contract) and no middleware trims it.
 * The code is identity data (it keys the I18n translations), so a code with
 * surrounding whitespace is rejected before anything is touched: no
 * transaction is opened, no row is locked, no LanguageCore update and no I18n
 * re-key happens. It is never silently trimmed and stored.
 *
 * Runs the real controller, the real request schema/validator and the real
 * PdoLanguageCodeChange (the LanguageCodeChangeInterface implementation); only the collaborators it must NOT reach are
 * doubles. The positive path (an exact code is renamed atomically together
 * with its I18n translations) needs a database and is covered by
 * I18nAdminHostLanguageBoundaryIntegrationTest.
 */
final class LanguagesUpdateCodeWhitespaceTest extends TestCase
{
    private PDO&MockObject $pdo;
    private LanguageRepositoryInterface&MockObject $languages;
    private LanguagesUpdateCodeController $controller;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->languages = $this->createMock(LanguageRepositoryInterface::class);

        // TranslationWriteService is final: an uninitialised instance is enough because
        // the rejection happens before it is ever used (a call would fatally error).
        $translationWriter = (new ReflectionClass(TranslationWriteService::class))->newInstanceWithoutConstructor();

        $service = new PdoLanguageCodeChange(
            $this->pdo,
            $this->languages,
            new LanguageManagementService(
                $this->languages,
                $this->createMock(LanguageSettingsRepositoryInterface::class)
            ),
            $translationWriter
        );

        $this->controller = new LanguagesUpdateCodeController(
            $service,
            new ValidationGuard(new RespectValidator())
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function codesWithSurroundingWhitespace(): array
    {
        return [
            'leading and trailing spaces' => [' en '],
            'trailing space'              => ['en '],
            'leading space'               => [' en'],
            'trailing tab'                => ["en\t"],
            'trailing newline'            => ["en\n"],
            'whitespace only'             => ['   '],
        ];
    }

    #[DataProvider('codesWithSurroundingWhitespace')]
    public function testCodeWithSurroundingWhitespaceIsRejectedBeforeAnythingIsTouched(string $code): void
    {
        $this->pdo->expects($this->never())->method('beginTransaction');
        $this->pdo->expects($this->never())->method('inTransaction');
        $this->languages->expects($this->never())->method('getByIdForUpdate');
        $this->languages->expects($this->never())->method('updateCode');

        $this->expectException(LanguageUpdateFailedException::class);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/languages/update-code')
            ->withParsedBody(['language_id' => 7, 'code' => $code]);

        ($this->controller)($request, (new ResponseFactory())->createResponse());
    }
}
