<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:10
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\Reversible\Algorithms;

use Maatify\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;
use Maatify\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class Aes256GcmAlgorithmTest extends TestCase
{
    private Aes256GcmAlgorithm $algorithm;
    private string $key;

    protected function setUp(): void
    {
        $this->algorithm = new Aes256GcmAlgorithm();
        $this->key = random_bytes(32);
    }

    public function testEncryptAndDecryptSuccessfully(): void
    {
        $plain = 'secret-message';

        $result = $this->algorithm->encrypt($plain, $this->key);

        $this->assertNotEmpty($result->cipher);
        $this->assertNotEmpty($result->iv);
        $this->assertNotEmpty($result->tag);

        $metadata = new ReversibleCryptoMetadataDTO(
            $result->iv,
            $result->tag
        );

        $decrypted = $this->algorithm->decrypt(
            $result->cipher,
            $this->key,
            $metadata
        );

        $this->assertSame($plain, $decrypted);
    }

    public function testEncryptFailsWithInvalidKeyLength(): void
    {
        $this->expectException(RuntimeException::class);

        $this->algorithm->encrypt('test', random_bytes(16));
    }

    public function testDecryptFailsWithWrongKey(): void
    {
        $result = $this->algorithm->encrypt('secret', $this->key);

        $metadata = new ReversibleCryptoMetadataDTO(
            $result->iv,
            $result->tag
        );

        $this->expectException(\Throwable::class);

        $this->algorithm->decrypt(
            $result->cipher,
            random_bytes(32),
            $metadata
        );
    }

    public function testDecryptFailsWhenCipherIsTampered(): void
    {
        $result = $this->algorithm->encrypt('secret', $this->key);

        $len = strlen($result->cipher);
        $len = $len > 0 ? $len : 1;
        $tamperedCipher = $result->cipher ^ random_bytes($len);

        $metadata = new ReversibleCryptoMetadataDTO(
            $result->iv,
            $result->tag
        );

        $this->expectException(\Throwable::class);

        $this->algorithm->decrypt(
            $tamperedCipher,
            $this->key,
            $metadata
        );
    }
}
