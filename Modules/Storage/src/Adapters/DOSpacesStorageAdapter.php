<?php

declare(strict_types=1);

namespace Maatify\Storage\Adapters;

use Aws\S3\S3Client;
use Maatify\Storage\Contracts\StorageAdapterInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class DOSpacesStorageAdapter implements StorageAdapterInterface
{
    public function __construct(
        private readonly S3Client $client,
        private readonly string $bucket,
        private readonly string $cdnUrl,
        private readonly string $acl = 'public-read',
    ) {}

    public function store(UploadedFileInterface $file, string $destinationPath): string
    {
        $key    = ltrim($destinationPath, '/');
        $stream = $file->getStream()->detach();

        if (!is_resource($stream)) {
            throw new RuntimeException('Could not read uploaded file stream.');
        }

        $this->client->putObject([
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'Body'        => $stream,
            'ACL'         => $this->acl,
            'ContentType' => $this->resolveMimeFromPath($key),
        ]);

        return rtrim($this->cdnUrl, '/') . '/' . $key;
    }

    public function delete(string $path): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key'    => ltrim($path, '/'),
        ]);
    }

    private function resolveMimeFromPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            default       => 'application/octet-stream',
        };
    }
}
