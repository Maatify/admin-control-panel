<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\TotpSecretRepositoryInterface;
use RuntimeException;

class FileTotpSecretRepository implements TotpSecretRepositoryInterface
{
    private string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/');
        if (!is_dir($this->storagePath)) {
            if (!mkdir($this->storagePath, 0700, true) && !is_dir($this->storagePath)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $this->storagePath));
            }
        }
    }

    public function save(int $adminId, string $secret): void
    {
        $filepath = $this->getFilePath($adminId);
        $result = file_put_contents($filepath, $secret);
        if ($result === false) {
            throw new RuntimeException('Failed to write TOTP secret to file.');
        }
        chmod($filepath, 0600); // Secure read/write for owner only
    }

    public function get(int $adminId): ?string
    {
        $filepath = $this->getFilePath($adminId);
        if (!file_exists($filepath)) {
            return null;
        }
        $content = file_get_contents($filepath);
        return $content !== false ? trim($content) : null;
    }

    private function getFilePath(int $adminId): string
    {
        return $this->storagePath . '/' . $adminId . '.secret';
    }
}
