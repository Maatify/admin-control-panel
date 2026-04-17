<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\ValueObject;

use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection::class)]
final class AllowedMimeTypeCollectionTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction and normalization
    // -------------------------------------------------------------------------

    public function test_empty_when_no_args(): void
    {
        $collection = new AllowedMimeTypeCollection();

        self::assertTrue($collection->isEmpty());
        self::assertSame(0, $collection->count());
    }

    public function test_normalizes_to_lowercase(): void
    {
        $collection = new AllowedMimeTypeCollection('Image/JPEG', 'IMAGE/PNG');

        self::assertTrue($collection->has('image/jpeg'));
        self::assertTrue($collection->has('image/png'));
    }

    public function test_trims_whitespace(): void
    {
        $collection = new AllowedMimeTypeCollection(' image/jpeg ', '  image/png  ');

        self::assertTrue($collection->has('image/jpeg'));
        self::assertTrue($collection->has('image/png'));
    }

    public function test_removes_duplicates(): void
    {
        $collection = new AllowedMimeTypeCollection('image/jpeg', 'IMAGE/JPEG', 'image/jpeg');

        self::assertSame(1, $collection->count());
    }

    public function test_ignores_empty_entries(): void
    {
        $collection = new AllowedMimeTypeCollection('image/jpeg', '', '  ', 'image/png');

        self::assertSame(2, $collection->count());
    }

    // -------------------------------------------------------------------------
    // has()
    // -------------------------------------------------------------------------

    public function test_has_returns_true_for_known_mime(): void
    {
        $collection = new AllowedMimeTypeCollection('image/jpeg', 'image/png');

        self::assertTrue($collection->has('image/jpeg'));
        self::assertTrue($collection->has('image/png'));
    }

    public function test_has_returns_false_for_unknown_mime(): void
    {
        $collection = new AllowedMimeTypeCollection('image/jpeg', 'image/png');

        self::assertFalse($collection->has('image/webp'));
        self::assertFalse($collection->has('image/gif'));
    }

    public function test_has_normalizes_input(): void
    {
        $collection = new AllowedMimeTypeCollection('image/jpeg');

        self::assertTrue($collection->has('IMAGE/JPEG'));
        self::assertTrue($collection->has(' image/jpeg '));
    }

    public function test_has_returns_false_for_empty_string(): void
    {
        $collection = new AllowedMimeTypeCollection('image/jpeg');

        self::assertFalse($collection->has(''));
    }

    // -------------------------------------------------------------------------
    // fromDelimitedString()
    // -------------------------------------------------------------------------

    public function test_from_delimited_string_comma(): void
    {
        $collection = AllowedMimeTypeCollection::fromDelimitedString('image/jpeg,image/png,image/webp');

        self::assertSame(3, $collection->count());
        self::assertTrue($collection->has('image/jpeg'));
    }

    public function test_from_delimited_string_semicolon(): void
    {
        $collection = AllowedMimeTypeCollection::fromDelimitedString('image/jpeg;image/png');

        self::assertSame(2, $collection->count());
    }

    public function test_from_delimited_string_null_produces_empty(): void
    {
        self::assertTrue(AllowedMimeTypeCollection::fromDelimitedString(null)->isEmpty());
    }

    public function test_from_delimited_string_empty_produces_empty(): void
    {
        self::assertTrue(AllowedMimeTypeCollection::fromDelimitedString('')->isEmpty());
    }

    // -------------------------------------------------------------------------
    // Iteration and serialization
    // -------------------------------------------------------------------------

    public function test_is_iterable(): void
    {
        $collection = new AllowedMimeTypeCollection('image/jpeg', 'image/png');

        $values = [];
        foreach ($collection as $mime) {
            $values[] = $mime;
        }

        self::assertSame(['image/jpeg', 'image/png'], $values);
    }

    public function test_json_serialize_returns_list(): void
    {
        $collection = new AllowedMimeTypeCollection('image/jpeg', 'image/webp');

        self::assertSame(['image/jpeg', 'image/webp'], $collection->jsonSerialize());
    }

    public function test_json_encode_produces_array(): void
    {
        $collection = new AllowedMimeTypeCollection('image/jpeg');

        self::assertSame('["image\/jpeg"]', json_encode($collection));
    }
}
