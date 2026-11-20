<?php

declare(strict_types=1);
// @oagen-ignore-file

namespace Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkOS\Exception\ApiException;
use WorkOS\Exception\WorkloadIdentityException;
use WorkOS\HttpClient;
use WorkOS\Internal\Utf8;

/**
 * Utf8::truncate() cuts text by character without the mbstring extension.
 *
 * The expected values are written out, so the tests say what the result is
 * and not only that it matches another function.
 */
class Utf8Test extends TestCase
{
    #[DataProvider('validText')]
    public function testValidTextIsCutByCharacter(string $value, int $limit, string $expected): void
    {
        $this->assertSame($expected, Utf8::truncate($value, $limit));
    }

    /** @return array<string, array{string, int, string}> */
    public static function validText(): array
    {
        return [
            'ASCII longer than the limit' => ['abcdefghij', 4, 'abcd'],
            'ASCII exactly at the limit' => ['abcd', 4, 'abcd'],
            'shorter than the limit' => ['abc', 500, 'abc'],
            'empty string' => ['', 5, ''],
            'CJK cut at the limit' => ['日本語のテキスト', 3, '日本語'],
            'CJK exactly at the limit' => ['日本語', 3, '日本語'],
            'CJK with more bytes than the limit but fewer characters' => ['日本語', 5, '日本語'],
            'emoji cut at the limit' => ['😀😁😂🤣', 2, '😀😁'],
            'emoji exactly at the limit' => ['😀😁', 2, '😀😁'],
            'the limit falls just before an emoji' => ['ab😀', 2, 'ab'],
            'characters of one to four bytes' => ['aé日😀z', 4, 'aé日😀'],
            'newlines are characters' => ["one\ntwo\r\nthree", 9, "one\ntwo\r\n"],
            'only newlines' => ["\n\n\n\n", 2, "\n\n"],
            'NUL bytes are characters' => ["a\0b\0c", 3, "a\0b"],
            'a combining mark is its own character' => ["e\u{301}e\u{301}", 3, "e\u{301}e"],
            'a limit of zero' => ['abc', 0, ''],
            'a negative limit' => ['abc', -1, ''],
        ];
    }

    /**
     * Every cut point of every sample, against the function this replaces.
     * PHPUnit itself requires ext-mbstring, so the comparison runs wherever
     * the tests do.
     */
    public function testEveryCutMatchesMbSubstr(): void
    {
        if (!function_exists('mb_substr')) {
            $this->markTestSkipped('ext-mbstring is not loaded, so there is nothing to compare with.');
        }

        foreach (self::validText() as $name => [$value]) {
            for ($limit = 0; $limit <= strlen($value) + 1; $limit++) {
                $this->assertSame(
                    mb_substr($value, 0, $limit, 'UTF-8'),
                    Utf8::truncate($value, $limit),
                    sprintf('%s, limit %d', $name, $limit),
                );
            }
        }
    }

    #[DataProvider('invalidText')]
    public function testTextThatIsNotValidUtf8IsCutByByteWithoutAnError(string $value, int $limit): void
    {
        // Any warning or notice becomes an exception, as it does in an
        // application that converts PHP errors.
        set_error_handler(static function (int $severity, string $message): never {
            throw new \ErrorException($message, 0, $severity);
        });

        try {
            $result = Utf8::truncate($value, $limit);
        } finally {
            restore_error_handler();
        }

        $this->assertLessThanOrEqual($limit, strlen($result));
        $this->assertSame(substr($value, 0, $limit), $result);
    }

    /** @return array<string, array{string, int}> */
    public static function invalidText(): array
    {
        return [
            'Latin-1 text' => ["caf\xE9 au lait", 6],
            'a byte that is never valid' => ["\xFF" . str_repeat('x', 20), 5],
            'a continuation byte with no lead byte' => ["\x80" . str_repeat('x', 20), 5],
            'a sequence cut short at the end' => ['abcdefgh' . "\xE6\x97", 4],
            'an overlong encoding' => ["\xC0\xAF" . str_repeat('x', 20), 5],
            'a UTF-16 surrogate' => ["\xED\xA0\x80" . str_repeat('x', 20), 5],
            'valid characters before the bad byte' => ['日本語' . "\xFF", 2],
            'shorter than the limit' => ["\xFF\xFE", 5],
        ];
    }

    public function testALimitAbovePcresRepeatCountStillCountsCharacters(): void
    {
        // 65,535 is the largest count one PCRE quantifier takes.
        $result = Utf8::truncate(str_repeat('日', 70010), 70000);

        $this->assertSame(70000 * 3, strlen($result));
        $this->assertTrue($result === str_repeat('日', 70000));

        // More bytes than the limit, fewer characters: nothing is cut.
        $short = str_repeat('日', 40000);
        $this->assertTrue(Utf8::truncate($short, 70000) === $short);
    }

    // -- The two places the SDK cuts text --

    public function testTheWorkloadIdentityDescriptionIsCutByCharacter(): void
    {
        $error = new WorkloadIdentityException(
            WorkloadIdentityException::INVALID_CONFIGURATION,
            str_repeat('日', 600),
        );

        $this->assertTrue($error->description === str_repeat('日', 500));
    }

    public function testAWorkloadIdentityDescriptionThatIsNotValidUtf8StillBuildsTheError(): void
    {
        $error = new WorkloadIdentityException(
            WorkloadIdentityException::INVALID_CONFIGURATION,
            "\xFF" . str_repeat('x', 600),
        );

        $this->assertSame(WorkloadIdentityException::INVALID_CONFIGURATION, $error->errorCode);
        $this->assertSame("\xFF" . str_repeat('x', 499), $error->description);
        $this->assertStringStartsWith('invalid_configuration: ', $error->getMessage());
    }

    public function testTheNonJsonBodyPreviewIsCutToTwoHundredCharacters(): void
    {
        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: HandlerStack::create(new MockHandler([
                new Response(200, ['Content-Type' => 'text/html'], str_repeat('日', 300)),
            ])),
        );

        try {
            $client->request('GET', '/test');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(
                'Expected JSON response but received non-JSON body (HTTP 200): ' . str_repeat('日', 200),
                $e->getMessage(),
            );
        }
    }
}
