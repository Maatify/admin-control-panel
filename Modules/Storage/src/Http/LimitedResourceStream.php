<?php

declare(strict_types=1);

namespace Maatify\Storage\Http;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * A read-only, forward-only PSR-7 StreamInterface that limits reads to exactly
 * $length bytes from an already-seeked PHP file resource.
 *
 * **Why this exists:**
 * PHP's native `fopen()` handle (and any StreamInterface wrapping it via
 * `StreamFactory::createStreamFromResource()`) reads until EOF regardless of
 * `Content-Length`. For HTTP Range responses (206 Partial Content) this means
 * the emitter would stream bytes beyond `rangeEnd`, sending more data than the
 * `Content-Length` header advertises and breaking video/audio seeking.
 *
 * This wrapper solves that by tracking remaining bytes and stopping at the limit.
 *
 * **Contract:**
 * - Readable  : yes — `read()` returns up to `min($length, $remaining)` bytes.
 * - Seekable  : no  — the resource is already positioned; backward seeks are meaningless.
 * - Writable  : no  — serving only, never writing.
 * - `eof()`   : true when remaining reaches 0 OR the underlying resource hits EOF.
 * - `getSize()`: returns the initial $length (the byte-count being served, not the file size).
 *
 * @example
 * // In FileServeResponder:
 * $body = new LimitedResourceStream($result->stream, $result->contentLength);
 * $response = $responseFactory->createResponse(206)->withBody($body);
 */
final class LimitedResourceStream implements StreamInterface
{
    /** @var resource|null */
    private mixed $resource;

    /** Bytes still available to read from this stream. */
    private int $remaining;

    /** Original limit — used by getSize(). */
    private readonly int $length;

    /**
     * @param resource $resource PHP file resource, already seeked to rangeStart.
     * @param int      $length   Maximum bytes to read (== FileServeResult::$contentLength).
     */
    public function __construct(mixed $resource, int $length)
    {
        if (! is_resource($resource)) {
            throw new RuntimeException('Invalid stream resource.');
        }

        $this->resource  = $resource;
        $this->length    = max(0, $length);
        $this->remaining = $this->length;
    }

    // -------------------------------------------------------------------------
    // PSR-7 StreamInterface
    // -------------------------------------------------------------------------

    /**
     * Returns all remaining bytes as a string.
     *
     * Per PSR-7: MUST NOT throw. Errors are silently swallowed.
     */
    public function __toString(): string
    {
        try {
            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
            $this->resource  = null;
            $this->remaining = 0;
        }
    }

    /**
     * Detaches the underlying resource and resets state.
     *
     * @return resource|null
     */
    public function detach(): mixed
    {
        $resource        = $this->resource;
        $this->resource  = null;
        $this->remaining = 0;
        return $resource;
    }

    /**
     * Returns the byte-limit passed at construction (the number of bytes being served).
     * NOT the underlying file size.
     */
    public function getSize(): ?int
    {
        return $this->length;
    }

    /**
     * Returns the current position of the underlying resource pointer.
     */
    public function tell(): int
    {
        $this->assertOpen();
        $pos = ftell($this->resource);
        if ($pos === false) {
            throw new RuntimeException('Unable to determine stream position.');
        }
        return $pos;
    }

    /**
     * Returns true once all $length bytes have been consumed, or the resource hits EOF.
     */
    public function eof(): bool
    {
        if ($this->resource === null) {
            return true;
        }
        return $this->remaining <= 0 || feof($this->resource);
    }

    /** Not seekable — the resource is already positioned at rangeStart. */
    public function isSeekable(): bool
    {
        return false;
    }

    /** @throws RuntimeException Always — this stream is not seekable. */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new RuntimeException('LimitedResourceStream is not seekable.');
    }

    /** @throws RuntimeException Always — rewinding a range stream is not supported. */
    public function rewind(): void
    {
        throw new RuntimeException('LimitedResourceStream is not rewindable.');
    }

    public function isWritable(): bool
    {
        return false;
    }

    /** @throws RuntimeException Always — this stream is read-only. */
    public function write(string $string): int
    {
        throw new RuntimeException('LimitedResourceStream is not writable.');
    }

    public function isReadable(): bool
    {
        return $this->resource !== null;
    }

    /**
     * Reads up to $length bytes, capped at the remaining byte budget.
     *
     * Returns an empty string when the budget is exhausted (eof() === true).
     *
     * @throws RuntimeException If the stream is detached or fread() fails.
     */
    public function read(int $length): string
    {
        $this->assertOpen();

        if ($length <= 0 || $this->remaining <= 0) {
            return '';
        }

        $toRead = min($length, $this->remaining);
        $data   = fread($this->resource, $toRead);

        if ($data === false) {
            throw new RuntimeException('Failed to read from stream resource.');
        }

        $this->remaining -= strlen($data);

        return $data;
    }

    /**
     * Returns all remaining bytes in the budget as a single string.
     *
     * @throws RuntimeException If the stream is detached.
     */
    public function getContents(): string
    {
        $this->assertOpen();

        $buffer = '';
        while (!$this->eof()) {
            $chunk = $this->read(8192);
            if ($chunk === '') {
                break;
            }
            $buffer .= $chunk;
        }
        return $buffer;
    }

    /**
     * Returns stream metadata, or a single key if $key is provided.
     *
     * @return array<string, mixed>|mixed|null
     */
    public function getMetadata(?string $key = null): mixed
    {
        if ($this->resource === null) {
            return $key !== null ? null : [];
        }

        $meta = stream_get_meta_data($this->resource);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @throws RuntimeException If the resource has been detached or closed.
     * @phpstan-assert resource $this->resource
     */
    private function assertOpen(): void
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream has been detached or closed.');
        }
    }
}
