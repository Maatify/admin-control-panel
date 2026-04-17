<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Unit\ValueObject;

use Maatify\ImageProfileLegacy\ValueObject\AllowedExtensionCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfileLegacy\ValueObject\AllowedExtensionCollection::class)]
final class AllowedExtensionCollectionTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction and normalization
    // -------------------------------------------------------------------------

    public function test_empty_collection_when_no_args(): void
    {
        $collection = new AllowedExtensionCollection();

        self::assertTrue($collection->isEmpty());
        self::assertSame(0, $collection->count());
    }

    public function test_normalizes_to_lowercase(): void
    {
        $collection = new AllowedExtensionCollection('JPG', 'PNG', 'WEBP');

        self::assertTrue($collection->has('jpg'));
        self::assertTrue($collection->has('png'));
        self::assertTrue($collection->has('webp'));
    }

    public function test_strips_leading_dot(): void
    {
        $collection = new AllowedExtensionCollection('.jpg', '.png');

        self::assertTrue($collection->has('jpg'));
        self::assertTrue($collection->has('png'));
    }

    public function test_strips_leading_dot_and_normalizes_case(): void
    {
        $collection = new AllowedExtensionCollection('.JPG', '.PNG');

        self::assertTrue($collection->has('jpg'));
        self::assertTrue($collection->has('png'));
    }

    public function test_removes_duplicates(): void
    {
        $collection = new AllowedExtensionCollection('jpg', 'JPG', '.jpg', 'jpeg', 'jpeg');

        self::assertSame(2, $collection->count());
    }

    public function test_ignores_empty_string_entries(): void
    {
        $collection = new AllowedExtensionCollection('jpg', '', '  ', 'png');

        self::assertSame(2, $collection->count());
    }

    // -------------------------------------------------------------------------
    // has()
    // -------------------------------------------------------------------------

    public function test_has_returns_true_for_known_extension(): void
    {
        $collection = new AllowedExtensionCollection('jpg', 'png');

        self::assertTrue($collection->has('jpg'));
        self::assertTrue($collection->has('png'));
    }

    public function test_has_returns_false_for_unknown_extension(): void
    {
        $collection = new AllowedExtensionCollection('jpg', 'png');

        self::assertFalse($collection->has('webp'));
        self::assertFalse($collection->has('gif'));
    }

    public function test_has_normalizes_input(): void
    {
        $collection = new AllowedExtensionCollection('jpg');

        self::assertTrue($collection->has('JPG'));
        self::assertTrue($collection->has('.jpg'));
        self::assertTrue($collection->has('.JPG'));
    }

    public function test_has_returns_false_for_empty_string(): void
    {
        $collection = new AllowedExtensionCollection('jpg');

        self::assertFalse($collection->has(''));
    }

    // -------------------------------------------------------------------------
    // fromDelimitedString()
    // -------------------------------------------------------------------------

    public function test_from_delimited_string_comma(): void
    {
        $collection = AllowedExtensionCollection::fromDelimitedString('jpg,png,webp');

        self::assertSame(3, $collection->count());
        self::assertTrue($collection->has('jpg'));
        self::assertTrue($collection->has('png'));
        self::assertTrue($collection->has('webp'));
    }

    public function test_from_delimited_string_semicolon(): void
    {
        $collection = AllowedExtensionCollection::fromDelimitedString('jpg;png;webp');

        self::assertSame(3, $collection->count());
    }

    public function test_from_delimited_string_pipe(): void
    {
        $collection = AllowedExtensionCollection::fromDelimitedString('jpg|png|webp');

        self::assertSame(3, $collection->count());
    }

    public function test_from_delimited_string_null_produces_empty(): void
    {
        $collection = AllowedExtensionCollection::fromDelimitedString(null);

        self::assertTrue($collection->isEmpty());
    }

    public function test_from_delimited_string_empty_string_produces_empty(): void
    {
        $collection = AllowedExtensionCollection::fromDelimitedString('');

        self::assertTrue($collection->isEmpty());
    }

    public function test_from_delimited_string_whitespace_only_produces_empty(): void
    {
        $collection = AllowedExtensionCollection::fromDelimitedString('   ');

        self::assertTrue($collection->isEmpty());
    }

    // -------------------------------------------------------------------------
    // Iteration and serialization
    // -------------------------------------------------------------------------

    public function test_is_iterable(): void
    {
        $collection = new AllowedExtensionCollection('jpg', 'png', 'webp');

        $values = [];
        foreach ($collection as $ext) {
            $values[] = $ext;
        }

        self::assertSame(['jpg', 'png', 'webp'], $values);
    }

    public function test_json_serialize_returns_list_of_strings(): void
    {
        $collection = new AllowedExtensionCollection('jpg', 'png');

        self::assertSame(['jpg', 'png'], $collection->jsonSerialize());
    }

    public function test_json_encode_produces_array(): void
    {
        $collection = new AllowedExtensionCollection('jpg', 'png');

        self::assertSame('["jpg","png"]', json_encode($collection));
    }
}
