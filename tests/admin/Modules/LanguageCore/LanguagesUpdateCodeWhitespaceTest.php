<?php

declare(strict_types=1);

namespace Tests\Modules\LanguageCore;

use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesUpdateCodeController;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\Contract\LanguageSettingsRepositoryInterface;
use Maatify\LanguageCore\DTO\LanguageDTO;
use Maatify\LanguageCore\Exception\LanguageUpdateFailedException;
use Maatify\LanguageCore\Service\LanguageManagementService;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Validator\RespectValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * The admin "update language code" endpoint passes `code` through unchanged:
 * the request schema only checks 1..32 characters and no middleware trims it.
 * LanguageCore therefore decides what happens to a code with surrounding
 * whitespace. It is identity data, so it is rejected, never silently trimmed
 * and stored.
 *
 * Runs the real controller, the real request schema/validator and the real
 * LanguageManagementService; only the repositories are doubles.
 */
final class LanguagesUpdateCodeWhitespaceTest extends TestCase
{
    private LanguageRepositoryInterface&MockObject $languages;
    private LanguagesUpdateCodeController $controller;

    protected function setUp(): void
    {
        $this->languages = $this->createMock(LanguageRepositoryInterface::class);
        $this->languages->method('getById')->willReturn(
            new LanguageDTO(7, 'English', 'en', true, null, '2026-01-01 00:00:00', null)
        );

        $service = new LanguageManagementService(
            $this->languages,
            $this->createMock(LanguageSettingsRepositoryInterface::class)
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
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('codesWithSurroundingWhitespace')]
    public function testCodeWithSurroundingWhitespaceIsRejectedAndNeverStored(string $code): void
    {
        $this->languages->expects($this->never())->method('updateCode');
        $this->languages->expects($this->never())->method('getByCode');

        $this->expectException(LanguageUpdateFailedException::class);

        $this->invoke($code);
    }

    public function testWhitespaceOnlyCodeIsRejectedAndNeverStored(): void
    {
        $this->languages->expects($this->never())->method('updateCode');

        $this->expectException(LanguageUpdateFailedException::class);

        $this->invoke('   ');
    }

    public function testExactCodeIsStoredAsSupplied(): void
    {
        $this->languages->method('getByCode')->willReturn(null);
        $this->languages->expects($this->once())
            ->method('updateCode')
            ->with(7, 'en-GB')
            ->willReturn(true);

        $response = $this->invoke('en-GB');

        self::assertSame(200, $response->getStatusCode());
    }

    private function invoke(string $code): \Psr\Http\Message\ResponseInterface
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/languages/update-code')
            ->withParsedBody(['language_id' => 7, 'code' => $code]);

        return ($this->controller)($request, (new ResponseFactory())->createResponse());
    }
}
