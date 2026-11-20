<?php

declare(strict_types=1);
// @oagen-ignore-file

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * composer.json does not require ext-mbstring, so the SDK and the provider
 * packages must not call a multibyte string function. On a PHP build without
 * that extension the call is a fatal "Call to undefined function" error.
 *
 * This test reads the source that ships and fails on any such call. It reads
 * PHP tokens, not text, so a comment or a sentence in a string that names
 * `mb_substr()` is not a call.
 */
class NoMbstringCallsTest extends TestCase
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

    public function testNoSourceFileCallsAMultibyteStringFunction(): void
    {
        $found = [];
        foreach (self::sourceFiles() as $path) {
            foreach (self::multibyteCalls((string) file_get_contents($path)) as $call) {
                $found[] = self::relative($path) . ': ' . $call;
            }
        }

        $this->assertSame(
            [],
            $found,
            'composer.json does not require ext-mbstring, so a PHP build without that extension has no mb_* '
            . 'functions, and each call listed here is a fatal "Call to undefined function" error there. '
            . 'Use a function every PHP build has. WorkOS\Internal\Utf8 cuts text by character without mbstring.',
        );
    }

    public function testTheScanReadsTheSdkAndEveryProviderPackage(): void
    {
        $files = array_map(self::relative(...), self::sourceFiles());

        $this->assertContains('lib/HttpClient.php', $files);
        $this->assertContains('lib/Exception/WorkloadIdentityException.php', $files);

        $packages = self::packageDirectories();
        $this->assertNotEmpty($packages, 'No provider package was found under packages/.');
        foreach ($packages as $package) {
            $prefix = self::relative($package) . '/';
            $this->assertNotEmpty(
                array_filter($files, static fn (string $file): bool => str_starts_with($file, $prefix)),
                "No source file was read from {$prefix}",
            );
        }

        $this->assertSame([], array_values(preg_grep('#(^|/)(vendor|tests)/#', $files)));
    }

    /** @param list<string> $expected */
    #[DataProvider('snippets')]
    public function testTheScanTellsACallFromAMention(string $source, array $expected): void
    {
        $this->assertSame($expected, self::multibyteCalls("<?php\n" . $source));
    }

    /** @return array<string, array{string, list<string>}> */
    public static function snippets(): array
    {
        return [
            'a call' => ['$a = mb_substr($b, 0, 5);', ['mb_substr() on line 2']],
            'a fully qualified call' => ['$a = \mb_strlen($b);', ['mb_strlen() on line 2']],
            'a call in another case' => ['$a = MB_StrToUpper($b);', ['MB_StrToUpper() on line 2']],
            'a first-class callable' => ['$a = mb_strlen(...);', ['mb_strlen() on line 2']],
            'a callable given by name' => ["\$a = array_map('mb_strtolower', \$b);", ["'mb_strtolower' on line 2"]],
            'two calls' => ["\$a = mb_strlen(\$b);\n\$c = mb_strtoupper(\$d);", [
                'mb_strlen() on line 2',
                'mb_strtoupper() on line 3',
            ]],
            'a line comment' => ['// mb_substr($b, 0, 5) is not available everywhere', []],
            'a hash comment' => ['# mb_substr($b, 0, 5)', []],
            'a doc comment' => ["/**\n * What mb_substr(\$b, 0, 5) returns.\n */\nfunction cut() {}", []],
            'a sentence in a string' => ["\$a = 'Do not call mb_substr() here.';", []],
            'a heredoc' => ["\$a = <<<TEXT\nmb_substr(\$b, 0, 5)\nTEXT;", []],
            'a string that is not a function name' => ["\$a = ['mb_used' => 1];", []],
            'a method call' => ['$a = $b->mb_substr(1);', []],
            'a nullsafe method call' => ['$a = $b?->mb_substr(1);', []],
            'a static method call' => ['$a = Text::mb_substr(1);', []],
            'a method declaration' => ['class Text { public function mb_substr() {} }', []],
            'another prefix' => ['$a = dumb_substr($b); $c = substr($b, 0, 5);', []],
        ];
    }

    /**
     * The multibyte string functions `$source` calls, each as
     * `name() on line N`. A quoted function name, as in
     * `array_map('mb_strtolower', ...)`, counts as a call.
     *
     * @return list<string>
     */
    private static function multibyteCalls(string $source): array
    {
        // Most files never mention the prefix.
        if (stripos($source, 'mb_') === false) {
            return [];
        }

        $tokens = array_values(array_filter(
            \PhpToken::tokenize($source),
            static fn (\PhpToken $token): bool => !$token->isIgnorable(),
        ));

        $calls = [];
        foreach ($tokens as $index => $token) {
            if ($token->is(T_CONSTANT_ENCAPSED_STRING)) {
                $name = ltrim(substr($token->text, 1, -1), '\\');
                if (stripos($name, 'mb_') === 0 && function_exists($name)) {
                    $calls[] = sprintf('%s on line %d', $token->text, $token->line);
                }

                continue;
            }

            if (!$token->is(self::NAME_TOKENS)) {
                continue;
            }

            $separator = strrpos($token->text, '\\');
            $name = $separator === false ? $token->text : substr($token->text, $separator + 1);
            $isCalled = ($tokens[$index + 1] ?? null)?->text === '(';
            $isFunction = $index === 0 || !$tokens[$index - 1]->is(self::NOT_A_FUNCTION_CALL);

            if (stripos($name, 'mb_') === 0 && $isCalled && $isFunction) {
                $calls[] = sprintf('%s() on line %d', $name, $token->line);
            }
        }

        return $calls;
    }

    /**
     * The PHP files that ship: `lib/` and each provider package, without the
     * packages' `vendor/` and `tests/` directories.
     *
     * @return list<string> Absolute paths.
     */
    private static function sourceFiles(): array
    {
        $files = [];
        foreach ([self::root() . '/lib', ...self::packageDirectories()] as $directory) {
            $tree = new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                static fn (\SplFileInfo $entry): bool => $entry->isDir()
                    ? !in_array($entry->getFilename(), ['vendor', 'tests'], true)
                    : $entry->getExtension() === 'php',
            );

            foreach (new \RecursiveIteratorIterator($tree) as $entry) {
                if ($entry->isFile()) {
                    $files[] = $entry->getPathname();
                }
            }
        }

        sort($files);

        return $files;
    }

    /** @return list<string> Absolute paths. */
    private static function packageDirectories(): array
    {
        return glob(self::root() . '/packages/*', GLOB_ONLYDIR) ?: [];
    }

    private static function root(): string
    {
        return dirname(__DIR__);
    }

    private static function relative(string $path): string
    {
        return substr($path, strlen(self::root()) + 1);
    }
}
