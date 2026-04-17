<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 */

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Integration\Infrastructure\PDO;

use Maatify\ImageProfileLegacy\Infrastructure\Persistence\PDO\PdoImageProfileProvider;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for PdoImageProfileProvider using an in-memory SQLite
 * database — no external database required.
 *
 * SQLite type affinity differences vs MySQL:
 *   - INT UNSIGNED is stored as INTEGER; cast is applied in assertions.
 *   - TINYINT(1) stored as INTEGER; truthy cast matches MySQL behaviour.
 *   - NULL handling is identical.
 *
 */
#[CoversClass(\Maatify\ImageProfileLegacy\Infrastructure\Persistence\PDO\PdoImageProfileProvider::class)]
final class PdoImageProfileProviderTest extends TestCase
{
    private PDO $pdo;
    private PdoImageProfileProvider $provider;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE image_profiles (
                id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                code                TEXT    NOT NULL UNIQUE,
                display_name        TEXT    DEFAULT NULL,
                min_width           INTEGER DEFAULT NULL,
                min_height          INTEGER DEFAULT NULL,
                max_width           INTEGER DEFAULT NULL,
                max_height          INTEGER DEFAULT NULL,
                max_size_bytes      INTEGER DEFAULT NULL,
                allowed_extensions  TEXT    DEFAULT NULL,
                allowed_mime_types  TEXT    DEFAULT NULL,
                is_active           INTEGER NOT NULL DEFAULT 1,
                notes               TEXT    DEFAULT NULL,
                created_at          TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at          TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->provider = new PdoImageProfileProvider($this->pdo);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function insertProfile(
        string  $code,
        bool    $isActive = true,
        ?int    $minWidth = null,
        ?int    $minHeight = null,
        ?int    $maxWidth = null,
        ?int    $maxHeight = null,
        ?int    $maxSizeBytes = null,
        ?string $extensions = null,
        ?string $mimeTypes = null,
        ?string $displayName = null,
        ?string $notes = null,
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO image_profiles
                (code, display_name, min_width, min_height, max_width, max_height,
                 max_size_bytes, allowed_extensions, allowed_mime_types, is_active, notes)
            VALUES
                (:code, :display_name, :min_width, :min_height, :max_width, :max_height,
                 :max_size_bytes, :allowed_extensions, :allowed_mime_types, :is_active, :notes)
        ');

        $stmt->execute([
            ':code'               => $code,
            ':display_name'       => $displayName,
            ':min_width'          => $minWidth,
            ':min_height'         => $minHeight,
            ':max_width'          => $maxWidth,
            ':max_height'         => $maxHeight,
            ':max_size_bytes'     => $maxSizeBytes,
            ':allowed_extensions' => $extensions,
            ':allowed_mime_types' => $mimeTypes,
            ':is_active'          => $isActive ? 1 : 0,
            ':notes'              => $notes,
        ]);
    }

    // -------------------------------------------------------------------------
    // findByCode()
    // -------------------------------------------------------------------------

    public function test_find_by_code_returns_null_when_table_is_empty(): void
    {
        self::assertNull($this->provider->findByCode('anything'));
    }

    public function test_find_by_code_returns_null_for_unknown_code(): void
    {
        $this->insertProfile('known_profile');

        self::assertNull($this->provider->findByCode('unknown_profile'));
    }

    public function test_find_by_code_returns_profile(): void
    {
        $this->insertProfile('product_thumbnail', true, 100, 100, 2000, 2000, 1_048_576, 'jpg,png', 'image/jpeg,image/png', 'Product Thumbnail');

        $profile = $this->provider->findByCode('product_thumbnail');

        self::assertNotNull($profile);
        self::assertSame('product_thumbnail', $profile->code);
        self::assertSame('Product Thumbnail', $profile->displayName);
        self::assertSame(100, $profile->minWidth);
        self::assertSame(100, $profile->minHeight);
        self::assertSame(2000, $profile->maxWidth);
        self::assertSame(2000, $profile->maxHeight);
        self::assertSame(1_048_576, $profile->maxSizeBytes);
        self::assertTrue($profile->isActive);
    }

    public function test_find_by_code_returns_inactive_profile(): void
    {
        // Provider must NOT filter by is_active
        $this->insertProfile('disabled_profile', false);

        $profile = $this->provider->findByCode('disabled_profile');

        self::assertNotNull($profile);
        self::assertFalse($profile->isActive);
    }

    public function test_find_by_code_maps_null_bounds_correctly(): void
    {
        $this->insertProfile('no_bounds');

        $profile = $this->provider->findByCode('no_bounds');

        self::assertNotNull($profile);
        self::assertNull($profile->minWidth);
        self::assertNull($profile->minHeight);
        self::assertNull($profile->maxWidth);
        self::assertNull($profile->maxHeight);
        self::assertNull($profile->maxSizeBytes);
    }

    public function test_find_by_code_maps_allowed_extensions(): void
    {
        $this->insertProfile('typed_profile', true, null, null, null, null, null, 'jpg,png,webp');

        $profile = $this->provider->findByCode('typed_profile');

        self::assertNotNull($profile);
        self::assertFalse($profile->allowedExtensions->isEmpty());
        self::assertTrue($profile->allowedExtensions->has('jpg'));
        self::assertTrue($profile->allowedExtensions->has('png'));
        self::assertTrue($profile->allowedExtensions->has('webp'));
    }

    public function test_find_by_code_maps_null_extensions_to_empty_collection(): void
    {
        $this->insertProfile('no_ext_restriction');

        $profile = $this->provider->findByCode('no_ext_restriction');

        self::assertNotNull($profile);
        self::assertTrue($profile->allowedExtensions->isEmpty());
    }

    public function test_find_by_code_maps_allowed_mime_types(): void
    {
        $this->insertProfile('typed_profile', true, null, null, null, null, null, null, 'image/jpeg,image/png');

        $profile = $this->provider->findByCode('typed_profile');

        self::assertNotNull($profile);
        self::assertTrue($profile->allowedMimeTypes->has('image/jpeg'));
        self::assertTrue($profile->allowedMimeTypes->has('image/png'));
    }

    public function test_find_by_code_maps_null_mime_types_to_empty_collection(): void
    {
        $this->insertProfile('no_mime_restriction');

        $profile = $this->provider->findByCode('no_mime_restriction');

        self::assertNotNull($profile);
        self::assertTrue($profile->allowedMimeTypes->isEmpty());
    }

    public function test_find_by_code_maps_notes_field(): void
    {
        $this->insertProfile('noted_profile', true, null, null, null, null, null, null, null, null, 'Internal note here');

        $profile = $this->provider->findByCode('noted_profile');

        self::assertNotNull($profile);
        self::assertSame('Internal note here', $profile->notes);
    }

    // -------------------------------------------------------------------------
    // listAll()
    // -------------------------------------------------------------------------

    public function test_list_all_returns_empty_on_empty_table(): void
    {
        $collection = $this->provider->listAll();

        self::assertTrue($collection->isEmpty());
    }

    public function test_list_all_returns_all_profiles(): void
    {
        $this->insertProfile('profile_a', true);
        $this->insertProfile('profile_b', false);
        $this->insertProfile('profile_c', true);

        $collection = $this->provider->listAll();

        self::assertSame(3, $collection->count());
    }

    public function test_list_all_includes_inactive(): void
    {
        $this->insertProfile('active_profile', true);
        $this->insertProfile('inactive_profile', false);

        $collection = $this->provider->listAll();

        self::assertSame(2, $collection->count());
    }

    // -------------------------------------------------------------------------
    // listActive()
    // -------------------------------------------------------------------------

    public function test_list_active_returns_empty_on_empty_table(): void
    {
        $collection = $this->provider->listActive();

        self::assertTrue($collection->isEmpty());
    }

    public function test_list_active_returns_only_active(): void
    {
        $this->insertProfile('active_a', true);
        $this->insertProfile('inactive_x', false);
        $this->insertProfile('active_b', true);

        $collection = $this->provider->listActive();

        self::assertSame(2, $collection->count());
        foreach ($collection as $profile) {
            self::assertTrue($profile->isActive);
        }
    }

    public function test_list_active_returns_empty_when_all_inactive(): void
    {
        $this->insertProfile('disabled_a', false);
        $this->insertProfile('disabled_b', false);

        $collection = $this->provider->listActive();

        self::assertTrue($collection->isEmpty());
    }
}
