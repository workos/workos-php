<?php

declare(strict_types=1);
// @oagen-ignore-file

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkOS\SourceScanner;

/**
 * composer.json does not require ext-mbstring, so the SDK and the provider
 * packages must not call a multibyte string function. On a PHP build without
 * that extension the call is a fatal "Call to undefined function" error.
 *
 * This test reads the source that ships and fails on any such call. It reads
 * PHP tokens, not text, so a comment or a sentence in a string that names
 * `mb_substr()` is not a call.
 *
 * Every other extension is covered by ExtensionRequirementsTest, which asks
 * PHP which extension a function belongs to. This rule goes by the function's
 * name, so it holds on any PHP build, and requiring the extension does not
 * satisfy it.
 */
class NoMbstringCallsTest extends TestCase
{
    public function testNoSourceFileCallsAMultibyteStringFunction(): void
    {
        $found = [];
        foreach (self::sourceFiles() as $file) {
            $source = (string) file_get_contents(SourceScanner::root() . '/' . $file);
            foreach (self::multibyteCalls($source) as $call) {
                $found[] = $file . ': ' . $call;
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

    public function testNoComposerJsonRequiresMbstring(): void
    {
        $composerFiles = ['composer.json'];
        foreach (SourceScanner::packageDirectories() as $package) {
            $composerFiles[] = $package . '/composer.json';
        }

        foreach ($composerFiles as $composerFile) {
            $this->assertArrayNotHasKey(
                'ext-mbstring',
                SourceScanner::requirements($composerFile),
                $composerFile . ' must not require ext-mbstring. Composer checks a required extension against '
                . 'the PHP that runs Composer, so installing the package would fail on a build without it. '
                . 'The code calls no mb_* function, so it has no need for the extension.',
            );
        }
    }

    public function testTheScanReadsTheSdkAndEveryProviderPackage(): void
    {
        $files = self::sourceFiles();

        $this->assertContains('lib/HttpClient.php', $files);
        $this->assertContains('lib/Exception/WorkloadIdentityException.php', $files);

        $packages = SourceScanner::packageDirectories();
        $this->assertNotEmpty($packages, 'No provider package was found under packages/.');
        foreach ($packages as $package) {
            $prefix = $package . '/';
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
            'an attribute' => ['#[mb_substr(1)] function cut() {}', []],
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

        $calls = [];
        foreach (SourceScanner::functionCalls($source) as $call) {
            if ($call['quoted']) {
                $name = ltrim($call['name'], '\\');
                if (stripos($name, 'mb_') === 0 && function_exists($name)) {
                    $calls[] = sprintf("'%s' on line %d", $call['name'], $call['line']);
                }

                continue;
            }

            $separator = strrpos($call['name'], '\\');
            $name = $separator === false ? $call['name'] : substr($call['name'], $separator + 1);
            if (stripos($name, 'mb_') === 0) {
                $calls[] = sprintf('%s() on line %d', $name, $call['line']);
            }
        }

        return $calls;
    }

    /**
     * The PHP files that ship: `lib/` and each provider package.
     *
     * @return list<string> Paths from the repository root.
     */
    private static function sourceFiles(): array
    {
        $files = SourceScanner::files('lib');
        foreach (SourceScanner::packageDirectories() as $package) {
            $files = [...$files, ...SourceScanner::files($package)];
        }

        return $files;
    }
}
