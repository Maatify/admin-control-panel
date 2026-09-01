<?php

declare(strict_types=1);

namespace Maatify\Storage\Http;

use Maatify\Storage\DTO\FileServeResult;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Builds a complete PSR-7 HTTP response for streaming a local private file.
 *
 * Takes a FileServeResult DTO (from FileServeService) and returns a ready-to-use
 * PSR-7 ResponseInterface — the controller just returns it.
 *
 * Framework-agnostic: depends only on PSR-17 interfaces.
 * Works with Slim, Mezzio, Laravel, or any PSR-7 framework.
 *
 * **Slim + PHP-DI:**
 * PHP-DI will inject ResponseFactoryInterface from whatever Slim registers in
 * the container (e.g. Slim\Psr7\Factory\ResponseFactory).
 * StreamFactoryInterface is no longer required — body streaming is handled
 * internally by LimitedResourceStream.
 *
 * @example
 * // Slim controller
 * class VideoController
 * {
 *     public function __construct(
 *         private FileServeService   $serveService,
 *         private FileServeResponder $responder,
 *     ) {}
 *
 *     public function stream(Request $request, Response $response, array $args): ResponseInterface
 *     {
 *         // Your auth check here
 *         $result = $this->serveService->serve(
 *             $args['path'],
 *             $request->getHeaderLine('Range')
 *         );
 *
 *         return $this->responder->respond($result);
 *     }
 * }
 */
final class FileServeResponder
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    /**
     * Build a complete PSR-7 response for the given serve result.
     *
     * Sets the following headers automatically:
     * - Content-Type    : MIME type detected from file content
     * - Content-Length  : exact byte count being sent (respects Range)
     * - Accept-Ranges   : bytes (signals browser that seeking is supported)
     * - ETag            : cache validation token
     * - Last-Modified   : file modification time
     * - Content-Range   : byte range (only for 206 Partial Content responses)
     *
     * HTTP status:
     * - 200 OK              when full file is served
     * - 206 Partial Content when a Range request is satisfied
     *
     * **Stream lifecycle:** The response body is a LimitedResourceStream that
     * wraps $result->stream and enforces the byte budget ($result->contentLength).
     * The emitter will read exactly that many bytes — no more, even if the
     * underlying file handle is positioned at an earlier offset or the file is
     * larger. The controller must NOT close $result->stream — LimitedResourceStream
     * owns the resource lifecycle from this point.
     *
     * @param FileServeResult $result DTO returned by FileServeService::serve().
     *
     * @return ResponseInterface Ready-to-return PSR-7 response.
     */
    public function respond(FileServeResult $result): ResponseInterface
    {
        $status = $result->isPartial ? 206 : 200;

        // LimitedResourceStream enforces the byte budget: the emitter will read
        // exactly $result->contentLength bytes, stopping at rangeEnd even though
        // the underlying file handle is open until EOF.
        $body = new LimitedResourceStream($result->stream, $result->contentLength);

        $response = $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type',   $result->mimeType)
            ->withHeader('Content-Length', (string) $result->contentLength)
            ->withHeader('Accept-Ranges',  'bytes')
            ->withHeader('ETag',           $result->eTag)
            ->withHeader('Last-Modified',  $result->lastModified->format('D, d M Y H:i:s \G\M\T'))
            ->withBody($body);

        if ($result->isPartial) {
            $response = $response->withHeader(
                'Content-Range',
                "bytes {$result->rangeStart}-{$result->rangeEnd}/{$result->size}"
            );
        }

        return $response;
    }
}
