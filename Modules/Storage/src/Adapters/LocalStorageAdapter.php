<?php

declare(strict_types=1);

namespace Maatify\Storage\Adapters;

use Maatify\Storage\Contracts\StorageAdapterInterface;
use Maatify\Storage\Exceptions\AdapterException;
use Psr\Http\Message\UploadedFileInterface;

final class LocalStorageAdapter implements StorageAdapterInterface
{
    private string $basePath;
    private string $baseUrl;

    public function __construct(string $basePath, string $baseUrl = '/images')
    {
        $this->basePath = rtrim($basePath, '/');
        $this->baseUrl  = rtrim($baseUrl, '/');

        $this->ensureDirectoryExists($this->basePath);
    }

    public function store(UploadedFileInterface $file, string $destinationPath): string
    {
        $fullPath = $this->basePath . '/' . ltrim($destinationPath, '/');
        $this->ensureDirectoryExists(dirname($fullPath));
        $file->moveTo($fullPath);

        return $this->baseUrl . '/' . ltrim($destinationPath, '/');
    }

    public function delete(string $path): void
    {
        $fullPath = $this->basePath . '/' . ltrim($path, '/');

        if (file_exists($fullPath) && !unlink($fullPath)) {
            throw AdapterException::failedToDeleteFile($fullPath);
        }
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw AdapterException::failedToCreateDirectory($directory);
        }
    }
}
