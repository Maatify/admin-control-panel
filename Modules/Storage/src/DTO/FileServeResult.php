<?php

declare(strict_types=1);

namespace Maatify\Storage\DTO;

use DateTimeImmutable;

/**
 * Result of a local file serve operation.
 *
 * Contains everything the controller needs to build a proper HTTP response
 * for streaming a file to the browser — including Range (partial content) support.
 *
 * The controller is responsible for:
 * - Building the PSR-7 response using these values
 * - Setting the correct HTTP status code (200 or 206)
 * - Writing $stream to the response body
 * - Closing the stream after the response is sent
 *
 * @example
 * // Full file (200 OK)
 * $result = $fileServeService->serve('products/image-abc123.jpg');
 *
 * $response = $responseFactory->createResponse(200)
 *     ->withHeader('Content-Type',   $result->mimeType)
 *     ->withHeader('Content-Length', (string) $result->contentLength)
 *     ->withHeader('Accept-Ranges',  'bytes')
 *     ->withHeader('ETag',           $result->eTag)
 *     ->withHeader('Last-Modified',  $result->lastModified->format('D, d M Y H:i:s \G\M\T'))
 *     ->withBody($streamFactory->createStreamFromResource($result->stream));
 *
 * @example
 * // Partial content (206) — browser seeking in a video
 * $result = $fileServeService->serve('courses/lesson-abc123.mp4', $request->getHeaderLine('Range'));
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
final class FileServeResult
{
    /**
     * @param resource          $stream        Open file handle already seeked to $rangeStart.
     *                                         Controller MUST close it after sending the response.
     * @param string            $mimeType      Value for the Content-Type header (e.g. "video/mp4").
     * @param int               $size          Total file size in bytes. Used in Content-Range header.
     * @param string            $eTag          Cache validation token (e.g. for ETag / If-None-Match).
     * @param DateTimeImmutable $lastModified  Last modification time (for Last-Modified header).
     * @param bool              $isPartial     true → respond with 206 Partial Content.
     *                                         false → respond with 200 OK.
     * @param int               $rangeStart    First byte offset being served (0 for full file).
     * @param int               $rangeEnd      Last byte offset being served ($size - 1 for full file).
     * @param int               $contentLength Bytes to stream = $rangeEnd - $rangeStart + 1.
     *                                         Use this as the Content-Length header value.
     */
    public function __construct(
        /** @var resource $stream */
        public readonly mixed           $stream,
        public readonly string          $mimeType,
        public readonly int             $size,
        public readonly string          $eTag,
        public readonly DateTimeImmutable $lastModified,
        public readonly bool            $isPartial,
        public readonly int             $rangeStart,
        public readonly int             $rangeEnd,
        public readonly int             $contentLength,
    ) {}
}
