<?php

declare(strict_types=1);
// @oagen-ignore-file

namespace Tests;

use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkOS\SourceScanner;

/**
 * Composer installs a package on any PHP build that has the extensions its
 * composer.json requires. A call to a function from any other extension is a
 * fatal "Call to undefined function" error on a build without it.
 *
 * This test reads every function the code that ships calls and asks PHP where
 * it comes from. It fails unless the function belongs to an extension every
 * PHP build has, to an extension composer.json requires, or to a package
 * composer.json requires. The SDK and each provider package are checked
 * against their own composer.json. Generated files are read too.
 *
 * mbstring has its own rule, in NoMbstringCallsTest: it is never required.
 */
class ExtensionRequirementsTest extends TestCase
{
    /**
     * Extensions taken to be part of every PHP build. Since PHP 8.2 all of
     * them are always compiled in, except filter and ctype, which are on
     * unless a build turns them off.
     */
    private const ALWAYS_AVAILABLE = [
        'Core',
        'standard',
        'SPL',
        'pcre',
        'date',
        'json',
        'hash',
        'random',
        'Reflection',
        'filter',
        'ctype',
    ];

    /**
     * Extensions the SDK calls and does not require, each with the package
     * that defines the extension's functions in PHP when it is not loaded.
     *
     * lib/SessionManager.php calls `sodium_crypto_secretbox()`.
     * paragonie/sodium_compat comes with paragonie/halite.
     */
    private const REPLACED_BY = [
        'sodium' => 'paragonie/sodium_compat',
    ];

    #[DataProvider('units')]
    public function testEveryFunctionTheCodeCallsIsOneTheInstallGuarantees(string $directory, string $composerFile): void
    {
        $guaranteed = self::guaranteedExtensions($composerFile);

        $files = SourceScanner::files($directory);
        $this->assertNotEmpty($files, "No source file was read from {$directory}.");

        $problems = [];
        foreach ($files as $file) {
            $source = (string) file_get_contents(SourceScanner::root() . '/' . $file);
            foreach (self::problems($source, $guaranteed) as $problem) {
                $problems[] = $file . ': ' . $problem;
            }
        }

        $this->assertSame([], $problems, sprintf(
            'Composer installs this code on any PHP build that has the extensions %1$s requires. On a build '
            . 'without an extension named here, the call is a fatal "Call to undefined function" error. Add the '
            . 'extension to "require" in %1$s, or stop calling the function. Never add ext-mbstring: see '
            . 'NoMbstringCallsTest.',
            $composerFile,
        ));
    }

    /** @return array<string, array{string, string}> The directory to read and the composer.json it ships with. */
    public static function units(): array
    {
        $units = ['the SDK' => ['lib', 'composer.json']];
        foreach (SourceScanner::packageDirectories() as $package) {
            $units[$package] = [$package, $package . '/composer.json'];
        }

        return $units;
    }

    public function testAProviderPackageCanRelyOnTheExtensionsTheSdkRequires(): void
    {
        $sdk = self::guaranteedExtensions('composer.json');
        $this->assertContains('ext-curl', $sdk);
        $this->assertContains('ext-openssl', $sdk);

        $packages = SourceScanner::packageDirectories();
        $this->assertNotEmpty($packages, 'No provider package was found under packages/.');
        foreach ($packages as $package) {
            $this->assertSame(
                [],
                array_values(array_diff($sdk, self::guaranteedExtensions($package . '/composer.json'))),
                "{$package} requires workos/workos-php, so it is installed only where the SDK's extensions are.",
            );
        }
    }

    public function testTheSodiumFunctionsComeFromARequiredPackage(): void
    {
        $this->assertTrue(
            InstalledVersions::isInstalled('paragonie/sodium_compat', false),
            'lib/SessionManager.php calls sodium_crypto_secretbox(), and composer.json does not require '
            . 'ext-sodium. paragonie/sodium_compat defines the function on a PHP build without the extension. '
            . 'It is installed with paragonie/halite and must stay a dependency that is not only for development.',
        );

        $this->assertSame([], self::problems(
            '<?php $box = sodium_crypto_secretbox($message, $nonce, $key);',
            self::guaranteedExtensions('composer.json'),
        ));
    }

    /**
     * @param list<string> $guaranteed
     * @param list<string> $expected
     */
    #[DataProvider('snippets')]
    public function testACallIsJudgedByWhereItsFunctionComesFrom(string $source, array $guaranteed, array $expected): void
    {
        $this->assertSame($expected, self::problems("<?php\n" . $source, $guaranteed));
    }

    /**
     * The extension in these snippets is libxml: PHPUnit requires it, so its
     * functions exist wherever the tests run.
     *
     * @return array<string, array{string, list<string>, list<string>}>
     */
    public static function snippets(): array
    {
        $needsLibxml = ['libxml_clear_errors() on line 2 needs ext-libxml'];

        return [
            'functions every build has' => ['$a = strlen($b) + preg_match("/x/", $b) + count(json_decode($b));', [], []],
            'an extension that is not required' => ['libxml_clear_errors();', [], $needsLibxml],
            'another extension is required' => ['libxml_clear_errors();', ['ext-curl'], $needsLibxml],
            'a fully qualified call' => ['\libxml_clear_errors();', [], $needsLibxml],
            'a first-class callable' => ['$a = libxml_clear_errors(...);', [], $needsLibxml],
            'an extension that is required' => ['libxml_clear_errors();', ['ext-libxml'], []],
            'a function PHP does not have' => ['no_such_function_in_any_extension();', [], [
                'no_such_function_in_any_extension() on line 2 is not a function this PHP has, '
                . 'so the extension it needs is unknown',
            ]],
            'a function from a development dependency' => ['\PHPUnit\Framework\assertTrue(true);', [], [
                'PHPUnit\Framework\assertTrue() on line 2 is defined by phpunit/phpunit, '
                . 'which is installed only for development',
            ]],
            'a function in a namespace of this code' => ['$a = Text\cut($b);', [], []],
            'a method named like an extension function' => ['$a->libxml_clear_errors(); Xml::libxml_clear_errors();', [], []],
            'a method declaration' => ['class Xml { public function libxml_clear_errors() {} }', [], []],
            'a quoted name' => ["\$a = ['libxml_clear_errors' => 1];", [], []],
            'a comment' => ['// libxml_clear_errors() is not called here.', [], []],
        ];
    }

    /**
     * What is wrong with the function calls in `$source`, each as
     * `name() on line N ...`.
     *
     * @param list<string> $guaranteed The `ext-*` names the code can rely on.
     * @return list<string>
     */
    private static function problems(string $source, array $guaranteed): array
    {
        $problems = [];
        foreach (SourceScanner::functionCalls($source) as $call) {
            // A quoted name may be only a string, such as the array key 'session_id'.
            if ($call['quoted']) {
                continue;
            }

            $name = ltrim($call['name'], '\\');
            $problem = self::problemWith($name, $guaranteed);
            if ($problem !== null) {
                $problems[] = sprintf('%s() on line %d %s', $name, $call['line'], $problem);
            }
        }

        return $problems;
    }

    /** @param list<string> $guaranteed */
    private static function problemWith(string $name, array $guaranteed): ?string
    {
        if (!function_exists($name)) {
            // A name with a namespace that PHP cannot resolve from here is a
            // function of this code. PHP's own functions are global.
            return str_contains($name, '\\')
                ? null
                : 'is not a function this PHP has, so the extension it needs is unknown';
        }

        $function = new \ReflectionFunction($name);
        if (!$function->isInternal()) {
            $package = self::developmentPackageDefining($function);

            return $package === null
                ? null
                : sprintf('is defined by %s, which is installed only for development', $package);
        }

        $extension = (string) $function->getExtensionName();
        if (in_array($extension, self::ALWAYS_AVAILABLE, true)) {
            return null;
        }

        $requirement = self::requirementName($extension);

        return in_array($requirement, $guaranteed, true) ? null : 'needs ' . $requirement;
    }

    /**
     * The `ext-*` names the code that ships with `$composerFile` can rely on:
     * the extensions it requires, and the ones a required package replaces.
     * A provider package requires the SDK, so it also gets the SDK's.
     *
     * @return list<string>
     */
    private static function guaranteedExtensions(string $composerFile): array
    {
        $require = SourceScanner::requirements($composerFile);
        $extensions = array_values(array_filter(
            array_map(strtolower(...), array_keys($require)),
            static fn (string $name): bool => str_starts_with($name, 'ext-'),
        ));

        if ($composerFile === 'composer.json') {
            foreach (self::REPLACED_BY as $extension => $package) {
                if (InstalledVersions::isInstalled($package, false)) {
                    $extensions[] = self::requirementName($extension);
                }
            }
        } elseif (isset($require['workos/workos-php'])) {
            $extensions = [...$extensions, ...self::guaranteedExtensions('composer.json')];
        }

        return array_values(array_unique($extensions));
    }

    /** Composer's name for an extension: "Zend OPcache" is `ext-zend-opcache`. */
    private static function requirementName(string $extension): string
    {
        return 'ext-' . str_replace(' ', '-', strtolower($extension));
    }

    /**
     * The package that defines `$function`, when that package is installed
     * only for development. A consumer does not get it, so a polyfill in it
     * is no reason to call the function.
     */
    private static function developmentPackageDefining(\ReflectionFunction $function): ?string
    {
        $file = realpath((string) $function->getFileName());
        if ($file === false) {
            return null;
        }

        foreach (InstalledVersions::getInstalledPackages() as $package) {
            if ($package === InstalledVersions::getRootPackage()['name']) {
                continue;
            }

            $installed = InstalledVersions::getInstallPath($package);
            $path = $installed === null ? false : realpath($installed);
            if ($path !== false && str_starts_with($file, $path . DIRECTORY_SEPARATOR)) {
                return InstalledVersions::isInstalled($package, false) ? null : $package;
            }
        }

        return null;
    }
}
