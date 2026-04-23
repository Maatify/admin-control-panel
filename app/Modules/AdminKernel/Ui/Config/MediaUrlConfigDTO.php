<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Ui\Config;

use RuntimeException;

final class MediaUrlConfigDTO
{
    private const DEFAULT_IMAGE = 'images/no-image-available.svg';

    public function __construct(
        public readonly string $assetsCdnUrl,
        public readonly string $cdnImageUrl,
        public readonly ?string $assetVersion,
    ) {
    }

    /**
     * @param array<string, mixed> $env
     */
    public static function fromArray(array $env): self
    {
        $assetsCdnUrl = self::requireNonEmptyString($env, 'ASSETS_CDN_URL');
        $cdnImageUrl = self::requireNonEmptyString($env, 'CDN_IMAGE_URL');
        $assetVersion = self::optionalTrimmedString($env, 'ASSET_VERSION');

        return new self(
            assetsCdnUrl: self::normalizeBaseUrl($assetsCdnUrl),
            cdnImageUrl: self::normalizeBaseUrl($cdnImageUrl),
            assetVersion: $assetVersion,
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

    /**
     * @param array<string, mixed> $env
     */
    private static function optionalTrimmedString(array $env, string $key): ?string
    {
        if (!array_key_exists($key, $env) || $env[$key] === null) {
            return null;
        }

        $value = $env[$key];

        if (!is_string($value)) {
            throw new RuntimeException(sprintf('%s must be a string when provided.', $key));
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function normalizeBaseUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }

    public function buildAssetUrl(string $path): string
    {
        $url = $this->assetsCdnUrl . '/' . ltrim($path, '/');

        if ($this->assetVersion === null) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'v=' . rawurlencode($this->assetVersion);
    }

    public function buildImageUrl(?string $path): string
    {
        $path = $path !== null ? trim($path) : '';

        if ($path === '') {
            $path = self::DEFAULT_IMAGE;
        }

        return $this->cdnImageUrl . '/' . ltrim($path, '/');
    }
}
