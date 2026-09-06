<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Category\DTO;

use ReflectionClass;
use ReflectionProperty;
use Maatify\Catalog\Category\DTO\CategoryIdDTO;
use Maatify\Catalog\Category\DTO\CreateCategoryDTO;
use Maatify\Catalog\Category\DTO\MoveCategoryDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryTranslationDTO;
use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CategoryOperationDTOTest extends TestCase
{
    public function testCanonicalStringIdentityIsNormalizedToAnInteger(): void
    {
        $identity = new CategoryIdDTO('42', 'categoryId');

        self::assertSame(42, $identity->value);
    }

    #[DataProvider('invalidIdentityProvider')]
    public function testNonCanonicalStringIdentitiesAreRejected(string $value): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CategoryIdDTO($value, 'categoryId');
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIdentityProvider(): iterable
    {
        yield 'zero' => ['0'];
        yield 'leading zero' => ['07'];
        yield 'positive sign' => ['+7'];
        yield 'negative sign' => ['-7'];
        yield 'whitespace' => ['7 '];
        yield 'decimal' => ['7.0'];
        yield 'scientific notation' => ['7e1'];
        yield 'overflow' => [str_repeat('9', strlen((string) PHP_INT_MAX) + 1)];
    }

    public function testCreateRejectsAnOverlongCode(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CreateCategoryDTO(str_repeat('x', 101));
    }

    public function testDisplayOrderMustBePositive(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new UpdateCategoryDisplayOrderDTO(1, 0);
    }

    public function testTranslationUpdateHasNoLogicalIdentityInputs(): void
    {
        $update = new UpdateCategoryTranslationDTO(4, 'Shirts', null);
        $propertyNames = array_map(
            static fn (ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass($update))->getProperties(),
        );

        self::assertSame(4, $update->translationId);
        self::assertNotContains('categoryId', $propertyNames);
        self::assertNotContains('languageCode', $propertyNames);
        self::assertNotContains('id', $propertyNames);
        self::assertFalse(property_exists(MoveCategoryDTO::class, 'code'));
    }
}
