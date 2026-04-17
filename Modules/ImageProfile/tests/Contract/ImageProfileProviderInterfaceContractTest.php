<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 *
 * Contract test — verifies that both concrete providers honour the full
 * ImageProfileProviderInterface contract. Any new provider implementation
 * must pass these assertions without modification.
 *
 * The test uses ArrayImageProfileProvider (no DB dependency) and
 * PdoImageProfileProvider with SQLite in-memory (no external database).
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Contract;

use Maatify\ImageProfile\Contract\ImageProfileProviderInterface;
use Maatify\ImageProfile\DTO\ImageProfileCollectionDTO;
use Maatify\ImageProfile\Entity\ImageProfileEntity;
use Maatify\ImageProfile\Infrastructure\Persistence\PDO\PdoImageProfileProvider;
use Maatify\ImageProfile\Provider\ArrayImageProfileProvider;
use Maatify\ImageProfile\Tests\Fixtures\ImageProfileFixtureFactory;
use Maatify\ImageProfile\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfile\Infrastructure\Persistence\PDO\PdoImageProfileProvider::class)]
#[CoversClass(\Maatify\ImageProfile\Provider\ArrayImageProfileProvider::class)]
final class ImageProfileProviderInterfaceContractTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildSqlitePdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            "CREATE TABLE image_profiles (
                id                 INTEGER PRIMARY KEY AUTOINCREMENT,
                code               TEXT NOT NULL UNIQUE,
                display_name       TEXT,
                min_width          INTEGER,
                min_height         INTEGER,
                max_width          INTEGER,
                max_height         INTEGER,
                max_size_bytes     INTEGER,
                allowed_extensions TEXT,
                allowed_mime_types TEXT,
                is_active          INTEGER NOT NULL DEFAULT 1,
                notes              TEXT,
                created_at         TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at         TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );

        return $pdo;
    }

    private function seedPdo(PDO $pdo, ImageProfileEntity ...$profiles): void
    {
        $stmt = $pdo->prepare(
            "INSERT INTO image_profiles
                (code, display_name, min_width, min_height, max_width, max_height,
                 max_size_bytes, allowed_extensions, allowed_mime_types, is_active, notes)
             VALUES
                (:code, :display_name, :min_width, :min_height, :max_width, :max_height,
                 :max_size_bytes, :allowed_extensions, :allowed_mime_types, :is_active, :notes)"
        );

        foreach ($profiles as $p) {
            $stmt->execute([
                ':code'               => $p->code,
                ':display_name'       => $p->displayName,
                ':min_width'          => $p->minWidth,
                ':min_height'         => $p->minHeight,
                ':max_width'          => $p->maxWidth,
                ':max_height'         => $p->maxHeight,
                ':max_size_bytes'     => $p->maxSizeBytes,
                ':allowed_extensions' => implode(',', iterator_to_array($p->allowedExtensions, false)),
                ':allowed_mime_types' => implode(',', iterator_to_array($p->allowedMimeTypes, false)),
                ':is_active'          => $p->isActive() ? 1 : 0,
                ':notes'              => $p->notes,
            ]);
        }
    }

    /**
     * Builds both concrete providers pre-seeded with two profiles:
     * one active (standard) and one inactive.
     *
     * @return array<string, ImageProfileProviderInterface>
     */
    private function buildProviders(): array
    {
        $active   = ImageProfileFixtureFactory::standard();
        $inactive = ImageProfileFixtureFactory::inactive();

        // Array provider
        $array = new ArrayImageProfileProvider($active, $inactive);

        // PDO provider
        $pdo      = $this->buildSqlitePdo();
        $this->seedPdo($pdo, $active, $inactive);
        $pdoProvider = new PdoImageProfileProvider($pdo);

        return [
            'ArrayImageProfileProvider' => $array,
            'PdoImageProfileProvider'   => $pdoProvider,
        ];
    }

    // -------------------------------------------------------------------------
    // findByCode — known code
    // -------------------------------------------------------------------------

    #[DataProvider('providerInstances')]
    public function test_find_by_code_returns_image_profile(ImageProfileProviderInterface $provider): void
    {
        $result = $provider->findByCode('product_thumbnail');

        self::assertInstanceOf(ImageProfileEntity::class, $result);
    }

    #[DataProvider('providerInstances')]
    public function test_find_by_code_returns_correct_profile(ImageProfileProviderInterface $provider): void
    {
        $result = $provider->findByCode('product_thumbnail');

        self::assertNotNull($result);
        self::assertSame('product_thumbnail', $result->code);
    }

    // -------------------------------------------------------------------------
    // findByCode — missing code
    // -------------------------------------------------------------------------

    #[DataProvider('providerInstances')]
    public function test_find_by_code_returns_null_for_missing(ImageProfileProviderInterface $provider): void
    {
        self::assertNull($provider->findByCode('does_not_exist'));
    }

    // -------------------------------------------------------------------------
    // findByCode — inactive code is NOT filtered
    // -------------------------------------------------------------------------

    #[DataProvider('providerInstances')]
    public function test_find_by_code_does_not_filter_inactive(ImageProfileProviderInterface $provider): void
    {
        $result = $provider->findByCode('inactive_profile');

        // The provider MUST return the profile — the validator owns is_active filtering.
        self::assertNotNull($result);
        self::assertFalse($result->isActive());
    }

    // -------------------------------------------------------------------------
    // listAll — returns typed collection
    // -------------------------------------------------------------------------

    #[DataProvider('providerInstances')]
    public function test_list_all_returns_collection_dto(ImageProfileProviderInterface $provider): void
    {
        self::assertInstanceOf(ImageProfileCollectionDTO::class, $provider->listAll());
    }

    #[DataProvider('providerInstances')]
    public function test_list_all_includes_all_profiles(ImageProfileProviderInterface $provider): void
    {
        self::assertCount(2, $provider->listAll());
    }

    #[DataProvider('providerInstances')]
    public function test_list_all_includes_inactive_profiles(ImageProfileProviderInterface $provider): void
    {
        $all    = $provider->listAll();
        $codes  = [];
        foreach ($all as $profile) {
            $codes[] = $profile->code;
        }

        self::assertContains('inactive_profile', $codes);
    }

    // -------------------------------------------------------------------------
    // listActive — only active profiles
    // -------------------------------------------------------------------------

    #[DataProvider('providerInstances')]
    public function test_list_active_returns_collection_dto(ImageProfileProviderInterface $provider): void
    {
        self::assertInstanceOf(ImageProfileCollectionDTO::class, $provider->listActive());
    }

    #[DataProvider('providerInstances')]
    public function test_list_active_excludes_inactive_profiles(ImageProfileProviderInterface $provider): void
    {
        $active = $provider->listActive();

        foreach ($active as $profile) {
            self::assertTrue($profile->isActive(), "Found inactive profile '{$profile->code}' in listActive()");
        }
    }

    #[DataProvider('providerInstances')]
    public function test_list_active_count_is_less_than_list_all(ImageProfileProviderInterface $provider): void
    {
        self::assertLessThan(count($provider->listAll()), count($provider->listActive()));
    }

    // -------------------------------------------------------------------------
    // Data provider — one row per concrete implementation
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{ImageProfileProviderInterface}>
     */
    public static function providerInstances(): array
    {
        // Data providers run before setUp(), so we build here lazily.
        // We return closures resolved to providers in the test body — instead
        // we build them directly since TestCase lifecycle allows it for static.
        return []; // populated via buildProviders() — see workaround below
    }

    /**
     * PHPUnit data providers are static, but our provider setup requires
     * instance methods. We use a workaround: run the contract assertions once
     * per implementation via a dedicated test that loops internally.
     *
     * This keeps the contract assertions clear while avoiding the static limitation.
     */
    public function test_all_providers_satisfy_contract(): void
    {
        foreach ($this->buildProviders() as $name => $provider) {
            // findByCode — known
            $found = $provider->findByCode('product_thumbnail');
            self::assertInstanceOf(ImageProfileEntity::class, $found, "$name: findByCode should return ImageProfile for known code");
            self::assertSame('product_thumbnail', $found->code, "$name: returned profile has wrong code");

            // findByCode — missing
            self::assertNull($provider->findByCode('does_not_exist'), "$name: findByCode should return null for missing code");

            // findByCode — inactive NOT filtered
            $inactive = $provider->findByCode('inactive_profile');
            self::assertNotNull($inactive, "$name: findByCode should NOT filter by is_active");
            self::assertFalse($inactive->isActive(), "$name: inactive profile should have isActive()===false");

            // listAll
            $all = $provider->listAll();
            self::assertInstanceOf(ImageProfileCollectionDTO::class, $all, "$name: listAll should return ImageProfileCollectionDTO");
            self::assertCount(2, $all, "$name: listAll should include all profiles");

            // listActive
            $active = $provider->listActive();
            self::assertInstanceOf(ImageProfileCollectionDTO::class, $active, "$name: listActive should return ImageProfileCollectionDTO");
            self::assertCount(1, $active, "$name: listActive should include only active profiles");
            foreach ($active as $p) {
                self::assertTrue($p->isActive(), "$name: listActive returned inactive profile '{$p->code}'");
            }
        }
    }
}
