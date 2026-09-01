<?php

declare(strict_types=1);

namespace Maatify\Storage\Services;

use DateTimeImmutable;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\Storage\DTO\FileServeResult;
use Maatify\Storage\Exception\AdapterException;
use Maatify\Storage\Exception\FileNotFoundException;
use Maatify\Storage\Exception\InvalidPathException;
use Maatify\Storage\Exception\ReaderAdapterException;

/**
 * Streams local private files to the browser.
 *
 * Designed for files stored outside the web root (private storage).
 * Returns a FileServeResult DTO containing an open file handle and all
 * HTTP metadata — the controller builds the PSR-7 response from it.
 *
 * Supports HTTP Range requests (RFC 7233) so browsers can:
 * - Seek to any position in a video or audio file
 * - Resume interrupted downloads
 * - Play media without waiting for the full file
 *
 * Local-only: DigitalOcean Spaces files are served via CDN / presigned URLs
 * and do not go through this service.
 *
 * **Controller responsibility:**
 * - Authentication / authorization before calling serve()
 * - Building the PSR-7 response from the returned DTO
 * - Closing $result->stream after the response is sent
 *
 * @example
 * // In your controller:
 * $result = $fileServeService->serve($path, $request->getHeaderLine('Range'));
 *
 * $response = $responseFactory->createResponse($result->isPartial ? 206 : 200)
 *     ->withHeader('Content-Type',   $result->mimeType)
 *     ->withHeader('Content-Length', (string) $result->contentLength)
 *     ->withHeader('Accept-Ranges',  'bytes')
 *     ->withHeader('ETag',           $result->eTag)
 *     ->withHeader('Last-Modified',  $result->lastModified->format('D, d M Y H:i:s \G\M\T'))
 *     ->withHeader('Content-Range',  $result->isPartial
 *         ? "bytes {$result->rangeStart}-{$result->rangeEnd}/{$result->size}"
 *         : '')
 *     ->withBody($streamFactory->createStreamFromResource($result->stream));
 */
final class FileServeService
{
    /**
     * @param string $basePath Absolute path to the private storage root (outside web root).
     *                         Example: /var/www/storage/private
     */
    public function __construct(
        private readonly string $basePath,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * Prepare a file for streaming to the browser.
     *
     * Opens the file, seeks to the correct byte offset (Range support),
     * and returns a DTO with everything needed to build the HTTP response.
     *
     * The returned stream is already positioned at $result->rangeStart.
     * The controller must close the stream after the response is sent.
     *
     * @param string      $path        Relative storage path (e.g. "products/image-abc123.jpg").
     * @param string|null $rangeHeader Value of the HTTP Range header from the request, or null.
     *                                 Example: "bytes=0-1023" or "bytes=500-" or "bytes=-500"
     *
     * @return FileServeResult DTO with open stream + all HTTP metadata.
     *
     * @throws InvalidPathException   If path fails security validation.
     * @throws FileNotFoundException  If file does not exist on disk.
     * @throws ReaderAdapterException If file is not readable.
     * @throws AdapterException       If file stream cannot be opened.
     */
    public function serve(string $path, ?string $rangeHeader = null): FileServeResult
    {
        $this->validatePath($path);

        $absolutePath = $this->resolveAbsolutePath($path);

        if (!is_file($absolutePath)) {
            throw FileNotFoundException::notFound($path);
        }

        if (!is_readable($absolutePath)) {
            throw ReaderAdapterException::adapterError("File is not readable: {$path}");
        }

        $size = filesize($absolutePath);
        if ($size === false) {
            throw ReaderAdapterException::adapterError("Cannot determine file size: {$path}");
        }

        $mtime = filemtime($absolutePath);
        $lastModified = new DateTimeImmutable('@' . ($mtime !== false ? $mtime : $this->clock->now()->getTimestamp()));

        $mimeType = $this->resolveMimeType($absolutePath);
        $eTag     = '"' . md5($lastModified->getTimestamp() . $size) . '"';

        // 0-byte file: serve as empty 200 OK — skip Range parsing entirely.
        // Range arithmetic on an empty file produces invalid offsets (rangeEnd = -1).
        if ($size === 0) {
            $stream = fopen($absolutePath, 'rb');
            if ($stream === false) {
                throw AdapterException::failedToOpenFile($path);
            }

            return new FileServeResult(
                stream:        $stream,
                mimeType:      $mimeType,
                size:          0,
                eTag:          $eTag,
                lastModified:  $lastModified,
                isPartial:     false,
                rangeStart:    0,
                rangeEnd:      0,
                contentLength: 0,
            );
        }

        [$rangeStart, $rangeEnd, $isPartial] = $this->parseRange($rangeHeader, $size);

        $contentLength = $rangeEnd - $rangeStart + 1;

        $stream = fopen($absolutePath, 'rb');
        if ($stream === false) {
            throw AdapterException::failedToOpenFile($path);
        }

        if ($rangeStart > 0 && fseek($stream, $rangeStart) !== 0) {
            fclose($stream);
            throw AdapterException::failedToOpenFile($path);
        }

        return new FileServeResult(
            stream:        $stream,
            mimeType:      $mimeType,
            size:          $size,
            eTag:          $eTag,
            lastModified:  $lastModified,
            isPartial:     $isPartial,
            rangeStart:    $rangeStart,
            rangeEnd:      $rangeEnd,
            contentLength: $contentLength,
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parse the HTTP Range header into start/end byte offsets.
     *
     * Handles all standard Range formats (RFC 7233):
     * - "bytes=0-999"   → first 1000 bytes
     * - "bytes=500-"    → from byte 500 to end of file
     * - "bytes=-500"    → last 500 bytes
     *
     * Invalid or missing Range headers fall back to the full file (200 OK).
     *
     * @param string|null $rangeHeader Raw Range header value (e.g. "bytes=0-1023").
     * @param int         $fileSize    Total file size in bytes.
     *
     * @return array{int, int, bool} [$rangeStart, $rangeEnd, $isPartial]
     */
    private function parseRange(?string $rangeHeader, int $fileSize): array
    {
        $fullFile = [0, $fileSize - 1, false];

        if ($rangeHeader === null || $rangeHeader === '') {
            return $fullFile;
        }

        if (!str_starts_with($rangeHeader, 'bytes=')) {
            return $fullFile;
        }

        $range = substr($rangeHeader, 6);

        // Multiple ranges (e.g. "bytes=0-50, 100-150") — not supported, serve full file
        if (str_contains($range, ',')) {
            return $fullFile;
        }

        [$startRaw, $endRaw] = array_pad(explode('-', $range, 2), 2, '');

        // "bytes=-500" → last 500 bytes
        if ($startRaw === '' && $endRaw !== '') {
            if (!ctype_digit($endRaw)) {
                return $fullFile;
            }

            $suffix = (int) $endRaw;
            if ($suffix <= 0) {
                return $fullFile;
            }

            $start = max(0, $fileSize - $suffix);
            $end   = $fileSize - 1;
            return [$start, $end, true];
        }

        // Reject non-numeric range parts (e.g. "bytes=abc-def", "bytes=10-abc")
        if (
            ($startRaw !== '' && !ctype_digit($startRaw)) ||
            ($endRaw   !== '' && !ctype_digit($endRaw))
        ) {
            return $fullFile;
        }

        // "bytes=500-" or "bytes=500-999"
        $start = $startRaw !== '' ? (int) $startRaw : 0;
        $end   = $endRaw   !== '' ? (int) $endRaw   : $fileSize - 1;

        // Clamp and validate
        $start = max(0, $start);
        $end   = min($end, $fileSize - 1);

        if ($start > $end) {
            return $fullFile;
        }

        $isPartial = ($start !== 0 || $end !== $fileSize - 1);

        return [$start, $end, $isPartial];
    }

    /**
     * Resolve relative path to absolute filesystem path.
     */
    private function resolveAbsolutePath(string $path): string
    {
        return rtrim($this->basePath, '/') . '/' . $path;
    }

    /**
     * Resolve MIME type from magic bytes, falling back to extension map.
     *
     * Returns 'application/octet-stream' if type cannot be determined.
     */
    private function resolveMimeType(string $absolutePath): string
    {
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                // finfo objects are freed automatically (PHP 8.1+) — an explicit
                // finfo_close() call is deprecated as of PHP 8.5.
                $mime = finfo_file($finfo, $absolutePath);
                if ($mime !== false && $mime !== '') {
                    return $mime;
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($absolutePath);
            if ($mime !== false && $mime !== '') {
                return $mime;
            }
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'svg'         => 'image/svg+xml',
            'mp4'         => 'video/mp4',
            'webm'        => 'video/webm',
            'mov'         => 'video/quicktime',
            'avi'         => 'video/x-msvideo',
            'mp3'         => 'audio/mpeg',
            'wav'         => 'audio/wav',
            'ogg'         => 'audio/ogg',
            'aac'         => 'audio/aac',
            'pdf'         => 'application/pdf',
            default       => 'application/octet-stream',
        };
    }

    /**
     * Validate storage path against traversal and injection attacks.
     *
     * Enforces the same rules as FileReaderService:
     * - No empty paths
     * - No leading slashes
     * - No backslashes
     * - No null bytes
     * - No directory traversal (.., /../)
     * - No encoded traversal (%2e%2e, double-encoded variants)
     * - No double slashes
     *
     * @throws InvalidPathException
     */
    private function validatePath(string $path): void
    {
        if ($path === '') {
            throw InvalidPathException::emptyPath();
        }

        if (str_starts_with($path, '/')) {
            throw InvalidPathException::invalidPath($path);
        }

        if (str_contains($path, '\\')) {
            throw InvalidPathException::invalidPath($path);
        }

        if (str_contains($path, "\0")) {
            throw InvalidPathException::containsNullBytes($path);
        }

        if (str_contains($path, '..') || str_contains($path, '/../')) {
            throw InvalidPathException::containsTraversal($path);
        }

        $decoded = $path;
        for ($i = 0; $i < 2; $i++) {
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        if (str_contains($decoded, '..') || str_contains($decoded, '/../')) {
            throw InvalidPathException::containsTraversal($path);
        }

        if (preg_match('#(?<!:)//+#', $path)) {
            throw InvalidPathException::invalidPath($path);
        }
    }
}
