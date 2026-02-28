<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Domain\Policy;

use LogicException;
use Maatify\AppSettings\Domain\Enum\AppSettingsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;
use Maatify\Exceptions\Policy\DefaultErrorPolicy;

final class AppSettingsErrorPolicy implements ErrorPolicyInterface
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function validate(
        ErrorCodeInterface $code,
        ErrorCategoryInterface $category
    ): void {
        $allowed = self::allowedCodesByCategory();
        $categoryId = $category->getValue();
        $codeId = $code->getValue();

        if (!isset($allowed[$categoryId])) {
            return;
        }

        if ($allowed[$categoryId] === []) {
            return;
        }

        if (!in_array($codeId, $allowed[$categoryId], true)) {
            throw new LogicException(sprintf(
                'Error code "%s" is not allowed for category "%s" in AppSettings policy.',
                $codeId,
                $categoryId
            ));
        }
    }

    public function severity(ErrorCategoryInterface $category): int
    {
        return DefaultErrorPolicy::default()->severity($category);
    }

    /**
     * @return array<string,array<int,string>>
     */
    private static function allowedCodesByCategory(): array
    {
        return [
            'NOT_FOUND' => [
                AppSettingsErrorCodeEnum::APP_SETTING_NOT_FOUND->getValue(),
            ],
            'CONFLICT' => [
                AppSettingsErrorCodeEnum::DUPLICATE_APP_SETTING->getValue(),
            ],
            'BUSINESS_RULE' => [
                AppSettingsErrorCodeEnum::APP_SETTING_PROTECTED->getValue(),
            ],
            'VALIDATION' => [
                AppSettingsErrorCodeEnum::INVALID_APP_SETTING->getValue(),
            ],
        ];
    }
}
