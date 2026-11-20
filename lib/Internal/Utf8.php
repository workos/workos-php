<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\Internal;

/**
 * UTF-8 text handling that needs no PHP extension.
 *
 * composer.json does not require ext-mbstring, and some PHP builds ship
 * without it. On those builds a call to a multibyte string function such as
 * `mb_substr()` is a fatal "Call to undefined function" error, so the SDK
 * does not call them. PCRE is always compiled into PHP, and its `u` modifier
 * reads a string as UTF-8.
 *
 * @internal
 */
final class Utf8
{
    /** The largest repeat count PCRE accepts in a quantifier. */
    private const MAX_REPEAT = 65535;

    /**
     * The first `$limit` characters of `$value`. A character is one Unicode
     * code point, so for valid UTF-8 the result is what
     * `mb_substr($value, 0, $limit)` returns.
     *
     * A string that is not valid UTF-8 has no characters to count, and is cut
     * to `$limit` bytes instead. A limit of zero or less gives an empty
     * string. Nothing is thrown.
     */
    public static function truncate(string $value, int $limit): string
    {
        if ($limit <= 0) {
            return '';
        }

        // A character is at least one byte, so nothing needs cutting.
        if (strlen($value) <= $limit) {
            return $value;
        }

        // One pattern counts at most MAX_REPEAT characters, so a larger limit
        // takes more than one pass. Each pass starts where the last one ended.
        $end = 0;
        for ($remaining = $limit; $remaining > 0; $remaining -= self::MAX_REPEAT) {
            $pattern = '/\G.{0,' . min($remaining, self::MAX_REPEAT) . '}/us';
            if (preg_match($pattern, $value, $match, 0, $end) !== 1) {
                return substr($value, 0, $limit);
            }
            $end += strlen($match[0]);
        }

        return substr($value, 0, $end);
    }
}
