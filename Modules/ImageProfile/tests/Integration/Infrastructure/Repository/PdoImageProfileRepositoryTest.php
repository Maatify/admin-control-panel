<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Integration\Infrastructure\Repository;

use Maatify\ImageProfile\Application\DTO\CreateImageProfileRequest;
use Maatify\ImageProfile\Application\DTO\UpdateImageProfileRequest;
use Maatify\ImageProfile\Exception\ImageProfileNotFoundException;
use Maatify\ImageProfile\Infrastructure\Repository\PDO\PdoImageProfileRepository;
use Maatify\ImageProfile\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for PdoImageProfileRepository using an in-memory SQLite
 * database — no external database required.
 *
 */
#[CoversClass(\Maatify\ImageProfile\Infrastructure\Repository\PDO\PdoImageProfileRepository::class)]
final class PdoImageProfileRepositoryTest extends TestCase
{
    private PDO                        $pdo;
    private PdoImageProfileRepository  $repository;

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
                min_aspect_ratio    REAL    DEFAULT NULL,
                max_aspect_ratio    REAL    DEFAULT NULL,
                requires_transparency INTEGER NOT NULL DEFAULT 0,
                preferred_format    TEXT    DEFAULT NULL,
                preferred_quality   INTEGER DEFAULT NULL,
                variants            TEXT    DEFAULT NULL,
                created_at          TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at          TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->repository = new PdoImageProfileRepository($this->pdo);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCreateRequest(
        string $code = 'test_profile',
        bool   $isActive = true,
    ): CreateImageProfileRequest {
        return new CreateImageProfileRequest(
            code:              $code,
            displayName:       'Test Profile',
            minWidth:          100,
            minHeight:         100,
            maxWidth:          2000,
            maxHeight:         2000,
            maxSizeBytes:      2_097_152,
            allowedExtensions: new AllowedExtensionCollection('jpg', 'png', 'webp'),
            allowedMimeTypes:  new AllowedMimeTypeCollection('image/jpeg', 'image/png', 'image/webp'),
            isActive:          $isActive,
            notes:             null,
        );
    }

    private function makeUpdateRequest(): UpdateImageProfileRequest
    {
        return new UpdateImageProfileRequest(
            displayName:       'Updated Name',
            minWidth:          200,
            minHeight:         200,
            maxWidth:          4000,
            maxHeight:         4000,
            maxSizeBytes:      5_242_880,
            allowedExtensions: new AllowedExtensionCollection('jpg', 'webp'),
            allowedMimeTypes:  new AllowedMimeTypeCollection('image/jpeg', 'image/webp'),
            notes:             'Updated note',
        );
    }

    // -------------------------------------------------------------------------
    // existsByCode()
    // -------------------------------------------------------------------------

    public function test_exists_by_code_returns_false_on_empty_table(): void
    {
        self::assertFalse($this->repository->existsByCode('anything'));
    }

    public function test_exists_by_code_returns_true_after_insert(): void
    {
        $this->repository->save($this->makeCreateRequest('my_profile'));

        self::assertTrue($this->repository->existsByCode('my_profile'));
    }

    public function test_exists_by_code_returns_false_for_different_code(): void
    {
        $this->repository->save($this->makeCreateRequest('profile_a'));

        self::assertFalse($this->repository->existsByCode('profile_b'));
    }

    // -------------------------------------------------------------------------
    // save()
    // -------------------------------------------------------------------------

    public function test_save_returns_profile_with_id(): void
    {
        $profile = $this->repository->save($this->makeCreateRequest());

        self::assertNotNull($profile->id);
        self::assertGreaterThan(0, $profile->id);
    }

    public function test_save_returns_profile_with_correct_code(): void
    {
        $profile = $this->repository->save($this->makeCreateRequest('banner_profile'));

        self::assertSame('banner_profile', $profile->code);
    }

    public function test_save_persists_all_fields(): void
    {
        $request = new CreateImageProfileRequest(
            code:              'full_profile',
            displayName:       'Full Profile',
            minWidth:          100,
            minHeight:         200,
            maxWidth:          1920,
            maxHeight:         1080,
            maxSizeBytes:      3_145_728,
            allowedExtensions: new AllowedExtensionCollection('jpg', 'png'),
            allowedMimeTypes:  new AllowedMimeTypeCollection('image/jpeg', 'image/png'),
            isActive:          true,
            notes:             'Created in test',
        );

        $profile = $this->repository->save($request);

        self::assertSame('full_profile', $profile->code);
        self::assertSame('Full Profile', $profile->displayName);
        self::assertSame(100, $profile->minWidth);
        self::assertSame(200, $profile->minHeight);
        self::assertSame(1920, $profile->maxWidth);
        self::assertSame(1080, $profile->maxHeight);
        self::assertSame(3_145_728, $profile->maxSizeBytes);
        self::assertTrue($profile->isActive);
        self::assertSame('Created in test', $profile->notes);
        self::assertTrue($profile->allowedExtensions->has('jpg'));
        self::assertTrue($profile->allowedExtensions->has('png'));
        self::assertTrue($profile->allowedMimeTypes->has('image/jpeg'));
    }

    public function test_save_persists_inactive_profile(): void
    {
        $profile = $this->repository->save($this->makeCreateRequest('inactive_code', false));

        self::assertFalse($profile->isActive);
    }

    public function test_save_maps_null_bounds(): void
    {
        $request = new CreateImageProfileRequest(
            code:              'no_bounds',
            displayName:       null,
            minWidth:          null,
            minHeight:         null,
            maxWidth:          null,
            maxHeight:         null,
            maxSizeBytes:      null,
            allowedExtensions: new AllowedExtensionCollection(),
            allowedMimeTypes:  new AllowedMimeTypeCollection(),
        );

        $profile = $this->repository->save($request);

        self::assertNull($profile->displayName);
        self::assertNull($profile->minWidth);
        self::assertNull($profile->maxSizeBytes);
        self::assertTrue($profile->allowedExtensions->isEmpty());
        self::assertTrue($profile->allowedMimeTypes->isEmpty());
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_returns_updated_entity(): void
    {
        $this->repository->save($this->makeCreateRequest('update_me'));

        $updated = $this->repository->update('update_me', $this->makeUpdateRequest());

        self::assertSame('Updated Name', $updated->displayName);
        self::assertSame(200, $updated->minWidth);
        self::assertSame(4000, $updated->maxWidth);
        self::assertSame(5_242_880, $updated->maxSizeBytes);
        self::assertSame('Updated note', $updated->notes);
    }

    public function test_update_does_not_change_code(): void
    {
        $this->repository->save($this->makeCreateRequest('stable_code'));

        $updated = $this->repository->update('stable_code', $this->makeUpdateRequest());

        self::assertSame('stable_code', $updated->code);
    }

    public function test_update_does_not_change_is_active(): void
    {
        $this->repository->save($this->makeCreateRequest('active_profile', true));

        $updated = $this->repository->update('active_profile', $this->makeUpdateRequest());

        self::assertTrue($updated->isActive); // is_active is not touched by update
    }

    public function test_update_throws_not_found_for_unknown_code(): void
    {
        $this->expectException(ImageProfileNotFoundException::class);

        $this->repository->update('does_not_exist', $this->makeUpdateRequest());
    }

    // -------------------------------------------------------------------------
    // toggleActive()
    // -------------------------------------------------------------------------

    public function test_toggle_active_enables_inactive_profile(): void
    {
        $this->repository->save($this->makeCreateRequest('toggle_me', false));

        $result = $this->repository->toggleActive('toggle_me', true);

        self::assertTrue($result->isActive);
    }

    public function test_toggle_active_disables_active_profile(): void
    {
        $this->repository->save($this->makeCreateRequest('toggle_me', true));

        $result = $this->repository->toggleActive('toggle_me', false);

        self::assertFalse($result->isActive);
    }

    public function test_toggle_active_does_not_change_other_fields(): void
    {
        $this->repository->save($this->makeCreateRequest('toggle_me'));

        $result = $this->repository->toggleActive('toggle_me', false);

        self::assertSame('toggle_me', $result->code);
        self::assertSame('Test Profile', $result->displayName);
    }

    public function test_toggle_active_throws_not_found_for_unknown_code(): void
    {
        $this->expectException(ImageProfileNotFoundException::class);

        $this->repository->toggleActive('ghost_profile', true);
    }
}
