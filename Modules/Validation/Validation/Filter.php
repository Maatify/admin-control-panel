<?php

declare(strict_types=1);

namespace Maatify\Validation\Validation;

/**
 * 🧹 **Filter**
 *
 * 🧩 Provides array-level filtering and sanitization helpers.
 * Designed to simplify data cleaning before validation or database operations.
 *
 * Includes:
 * - Removing empty/null/blank values
 * - Trimming whitespace
 * - Input normalization and optional HTML escaping for output
 *
 * @package Maatify\Validation\Validation
 *
 * @example
 * ```php
 * use Maatify\Validation\Validation\Filter;
 *
 * $data = [
 *     'name' => '  Mohamed  ',
 *     'email' => '',
 *     'bio' => '<script>alert("x")</script>',
 *     'tags' => [],
 * ];
 *
 * $cleaned = Filter::removeEmptyValues($data);
 * $trimmed = Filter::trimArray($cleaned);
 * $sanitized = Filter::sanitizeArray($trimmed);
 * $sanitizedEscapeHtml = Filter::escapeHtmlArray($trimmed);
 * ```
 */
final class Filter
{
    /**
     * 🧽 **Removes empty or null values from an array.**
     *
     * Filters out:
     * - `null`
     * - Empty strings (`''`)
     * - Empty arrays (`[]`)
     *
     * @param array<string,mixed> $data  Input array to filter.
     * @return array<string,mixed>       Filtered array without empty or null values.
     *
     * @example
     * ```php
     * Filter::removeEmptyValues(['a' => 1, 'b' => '', 'c' => null]);
     * // ➜ ['a' => 1]
     * ```
     */
    public static function removeEmptyValues(array $data): array
    {
        return array_filter($data, fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /**
     * ✂️ **Trims whitespace from all string values in an array.**
     *
     * Non-string values are left unchanged.
     *
     * @param array<string,mixed> $data  Input array to process.
     * @return array<string,mixed>       Array with all string elements trimmed.
     *
     * @example
     * ```php
     * Filter::trimArray([' name ' => ' Mohamed ', 'age' => 30]);
     * // ➜ [' name ' => 'Mohamed', 'age' => 30]
     * ```
     */
    public static function trimArray(array $data): array
    {
        return array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
    }

    /**
     * Sanitizes string values for input normalization.
     *
     * - Trims whitespace.
     * - Leaves non-string values untouched.
     * - Does not escape HTML; use escapeHtmlArray() before HTML output.
     *
     * @param   array<string,mixed>  $data  Input array to sanitize.
     * @return array<string,mixed> Input-normalized array.
     *
     * @example
     * Filter::sanitizeArray(['bio' => '  <b>Hi</b>  ']);
     * // ➜ ['bio' => '<b>Hi</b>']
     */
    public static function sanitizeArray(array $data): array
    {
        return array_map(
            static fn (mixed $v): mixed => is_string($v)
                ? trim($v)
                : $v,
            $data
        );
    }

    /**
     * 🧼 **Sanitizes all string values in an array for safe output or storage.**
     *
     * - Trims whitespace.
     * - Encodes HTML entities to prevent XSS attacks.
     * - Leaves non-string values untouched.
     *
     * @param array<string,mixed> $data  Input array to sanitize.
     * @return array<string,mixed>       Sanitized array safe for HTML output.
     *
     * @example
     * ```php
     * Filter::escapeHtmlArray(['bio' => '<b>Hi</b>']);
     * // ➜ ['bio' => '&lt;b&gt;Hi&lt;/b&gt;']
     * ```
     */
    public static function escapeHtmlArray(array $data): array
    {
        return array_map(
            static fn (mixed $v): mixed => is_string($v)
                ? htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $v,
            $data
        );
    }
}
