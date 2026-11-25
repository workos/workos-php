<?php

declare(strict_types=1);
// @oagen-ignore-file

namespace WorkOS;

/**
 * Reads the PHP code that ships, for the tests that check which PHP
 * extensions it needs: `lib/` and each provider package under `packages/`,
 * without the packages' `vendor/` and `tests/` directories.
 *
 * It reads PHP tokens, not text, so a comment or a sentence in a string that
 * names a function is not a call. Generated files are read like any other.
 *
 * @internal
 */
final class SourceScanner
{
    private const NAME_TOKENS = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE];

    /** A name after one of these is a method or a declaration, not a function call. */
    private const NOT_A_FUNCTION_CALL = [
        T_OBJECT_OPERATOR,
        T_NULLSAFE_OBJECT_OPERATOR,
        T_DOUBLE_COLON,
        T_FUNCTION,
        T_NEW,
    ];

    /** The repository root, as an absolute path. */
    public static function root(): string
    {
        return dirname(__DIR__);
    }

    /** `$path` without the repository root in front. */
    public static function relative(string $path): string
    {
        return substr($path, strlen(self::root()) + 1);
    }

    /** @return list<string> The provider packages, such as `packages/workload-identity-gcp`. */
    public static function packageDirectories(): array
    {
        return array_map(self::relative(...), glob(self::root() . '/packages/*', GLOB_ONLYDIR) ?: []);
    }

    /**
     * The PHP files under `$directory` that ship.
     *
     * @param string $directory `lib` or a provider package, from the repository root.
     * @return list<string> Paths from the repository root, sorted.
     */
    public static function files(string $directory): array
    {
        $tree = new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator(self::root() . '/' . $directory, \FilesystemIterator::SKIP_DOTS),
            static fn (\SplFileInfo $entry): bool => $entry->isDir()
                ? !in_array($entry->getFilename(), ['vendor', 'tests'], true)
                : $entry->getExtension() === 'php',
        );

        $files = [];
        foreach (new \RecursiveIteratorIterator($tree) as $entry) {
            if ($entry->isFile()) {
                $files[] = self::relative($entry->getPathname());
            }
        }

        sort($files);

        return $files;
    }

    /**
     * The `require` section of a composer.json.
     *
     * @param string $composerFile A path from the repository root.
     * @return array<string, string> Version constraints, keyed by package name.
     */
    public static function requirements(string $composerFile): array
    {
        $json = (string) file_get_contents(self::root() . '/' . $composerFile);

        return json_decode($json, true, flags: JSON_THROW_ON_ERROR)['require'] ?? [];
    }

    /**
     * Every function `$source` calls by name, in the order written. The name
     * is as written, so it may start with a backslash or have a namespace.
     *
     * A quoted function name, as in `array_map('trim', ...)`, is listed with
     * `quoted` set. It may be a callable or only a string that looks like
     * one, and the caller decides.
     *
     * @return list<array{name: string, line: int, quoted: bool}>
     */
    public static function functionCalls(string $source): array
    {
        $tokens = array_values(array_filter(
            \PhpToken::tokenize($source),
            static fn (\PhpToken $token): bool => !$token->isIgnorable(),
        ));

        $calls = [];
        $count = count($tokens);
        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];

            // An attribute with arguments looks like a call and is not one.
            if ($token->is(T_ATTRIBUTE)) {
                $index = self::endOfAttribute($tokens, $index);

                continue;
            }

            if ($token->is(T_CONSTANT_ENCAPSED_STRING)) {
                $name = substr($token->text, 1, -1);
                if (preg_match('/^\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*$/', $name) === 1) {
                    $calls[] = ['name' => $name, 'line' => $token->line, 'quoted' => true];
                }

                continue;
            }

            if (!$token->is(self::NAME_TOKENS) || ($tokens[$index + 1] ?? null)?->text !== '(') {
                continue;
            }

            // `function &name()` returns by reference and is still a declaration.
            $before = $tokens[$index - 1] ?? null;
            if ($before?->text === '&') {
                $before = $tokens[$index - 2] ?? null;
            }

            if ($before === null || !$before->is(self::NOT_A_FUNCTION_CALL)) {
                $calls[] = ['name' => $token->text, 'line' => $token->line, 'quoted' => false];
            }
        }

        return $calls;
    }

    /**
     * The index of the `]` that closes the attribute opened at `$index`.
     *
     * @param list<\PhpToken> $tokens
     */
    private static function endOfAttribute(array $tokens, int $index): int
    {
        $depth = 1;
        while ($depth > 0 && isset($tokens[$index + 1])) {
            $index++;
            $depth += match ($tokens[$index]->text) {
                '[', '#[' => 1,
                ']' => -1,
                default => 0,
            };
        }

        return $index;
    }
}
