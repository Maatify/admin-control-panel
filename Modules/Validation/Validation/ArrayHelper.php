<?php

declare(strict_types=1);

namespace Maatify\Validation\Validation;

/**
 * 🧩 **ArrayHelper**
 *
 * 🧠 A collection of utility methods for working with associative arrays.
 * Provides simple ways to flatten, filter, and access nested array structures
 * using dot-notation (`a.b.c`) or key-based selection.
 *
 * @package Maatify\Validation\Validation
 *
 * @example
 * ```php
 * use Maatify\Validation\Validation\ArrayHelper;
 *
 * $data = [
 *     'user' => ['name' => 'Mohamed', 'email' => 'info@maatify.dev'],
 *     'meta' => ['active' => true]
 * ];
 *
 * ArrayHelper::flatten($data);
 * // ➜ ['user.name' => 'Mohamed', 'user.email' => 'info@maatify.dev', 'meta.active' => true]
 *
 * ArrayHelper::dot($data, 'user.name'); // ➜ 'Mohamed'
 * ArrayHelper::only($data['user'], ['email']); // ➜ ['email' => 'info@maatify.dev']
 * ```
 */
final class ArrayHelper
{
    /**
     * 🧱 **Flattens a multidimensional array using dot notation.**
     *
     * Converts nested arrays into a single-level array
     * where keys are represented in "dot.path" format.
     *
     * @param array<array-key, mixed>  $array   Input array to flatten.
     * @param string $prefix  Internal prefix for recursion (do not use manually).
     *
     * @return array<string, mixed> Flattened array with dot-notated keys.
     *
     * @example
     * ```php
     * ArrayHelper::flatten(['a' => ['b' => ['c' => 1]]]);
     * // ➜ ['a.b.c' => 1]
     * ```
     */
    public static function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                // 🔁 Recursively flatten nested arrays
                $nested = self::flatten($value, $newKey);
                $result += $nested;
            } else {
                // ✅ Store leaf value with its full path
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * 🚫 **Removes specified keys from an array.**
     *
     * Returns a new array containing all keys except those listed.
     *
     * @param array<string,mixed> $array  Input array.
     * @param array<int,string> $keys   Keys to exclude.
     *
     * @return array<string,mixed> Array without the excluded keys.
     *
     * @example
     * ```php
     * ArrayHelper::except(['a' => 1, 'b' => 2], ['b']);
     * // ➜ ['a' => 1]
     * ```
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * 🎯 **Extracts only specific keys from an array.**
     *
     * Returns a subset of the array containing only the specified keys.
     *
     * @param array<string,mixed> $array  Input array.
     * @param array<int,string> $keys   Keys to retain.
     *
     * @return array<string,mixed> Array containing only the selected keys.
     *
     * @example
     * ```php
     * ArrayHelper::only(['name' => 'Mohamed', 'age' => 30], ['name']);
     * // ➜ ['name' => 'Mohamed']
     * ```
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * 🔍 **Retrieves a value from a multidimensional array using dot notation.**
     *
     * Returns a deeply nested value by specifying a "dot.path" string.
     * If any segment in the path is missing, returns the provided default.
     *
     * @param array<string,mixed>  $array     Source array.
     * @param string $path      Dot-notated path (e.g., "user.profile.name").
     * @param mixed  $default   Default value if path not found.
     *
     * @return mixed Value at the given path or the default if not found.
     *
     * @example
     * ```php
     * $data = ['user' => ['name' => 'Mohamed']];
     * ArrayHelper::dot($data, 'user.name'); // ➜ 'Mohamed'
     * ArrayHelper::dot($data, 'user.email', 'N/A'); // ➜ 'N/A'
     * ```
     */
    public static function dot(array $array, string $path, $default = null)
    {
        $segments = explode('.', $path);

        foreach ($segments as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                // 🚫 Missing key — return default
                return $default;
            }

            // ⬇️ Traverse deeper
            $array = $array[$segment];
        }

        // ✅ Final value found
        return $array;
    }
}
