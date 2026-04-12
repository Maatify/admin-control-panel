<?php

declare(strict_types=1);

namespace Maatify\Storage\Contracts;

use Psr\Http\Message\UploadedFileInterface;

interface StorageAdapterInterface
{
    public function store(UploadedFileInterface $file, string $destinationPath): string;

    public function delete(string $path): void;
}
