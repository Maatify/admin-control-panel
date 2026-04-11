<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Ui\Config;

use RuntimeException;

final class MediaUrlConfigDTO
{
    public function __construct(
        public readonly string $assetsCdnUrl,
        public readonly string $cdnImageUrl,
    ) {
    }

    /**
     * @param array<string, mixed> $env
     */
    public static function fromArray(array $env): self
    {
        $assetsCdnUrl = self::requireNonEmptyString($env, 'ASSETS_CDN_URL');
        $cdnImageUrl = self::requireNonEmptyString($env, 'CDN_IMAGE_URL');

        return new self(
            assetsCdnUrl: self::normalizeBaseUrl($assetsCdnUrl),
            cdnImageUrl: self::normalizeBaseUrl($cdnImageUrl),
        );
    }

    /**
     * @param array<string, mixed> $env
     */
    private static function requireNonEmptyString(array $env, string $key): string
    {
        $value = $env[$key] ?? null;

        if (!is_string($value)) {
            throw new RuntimeException(sprintf('%s is required and must be a string.', $key));
        }

        $value = trim($value);

        if ($value === '') {
            throw new RuntimeException(sprintf('%s is required and must not be empty.', $key));
        }

        return $value;
    }

    private static function normalizeBaseUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }

    public function buildAssetUrl(string $path): string
    {
        return $this->assetsCdnUrl . '/' . ltrim($path, '/');
    }

    public function buildImageUrl(string $path): string
    {
        return $this->cdnImageUrl . '/' . ltrim($path, '/');
    }
}
