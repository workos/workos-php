<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace Tests;

/**
 * A mock HTTP server on the loopback interface: PHP's built-in web server
 * running `server.php`. It answers every request the same way and records
 * what it received, so a test can assert on the real request the provider
 * sent, and on requests that must never arrive.
 */
final class MockServer
{
    public readonly string $url;

    private readonly string $dir;

    /** @var resource */
    private $process;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(int $status = 200, string $body = '', array $headers = [])
    {
        $this->dir = sys_get_temp_dir() . '/wif-mock-' . bin2hex(random_bytes(8));
        mkdir($this->dir);
        file_put_contents($this->dir . '/requests.log', '');
        file_put_contents(
            $this->dir . '/response.json',
            json_encode(['status' => $status, 'headers' => $headers, 'body' => $body]),
        );

        $port = self::freePort();
        $this->url = 'http://127.0.0.1:' . $port;

        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, __DIR__ . '/server.php'],
            [['file', $null, 'r'], ['file', $null, 'w'], ['file', $null, 'w']],
            $pipes,
            null,
            ['MOCK_DIR' => $this->dir] + getenv(),
        );
        if ($process === false) {
            throw new \RuntimeException('The mock server could not be started.');
        }
        $this->process = $process;

        $this->waitUntilListening($port);
    }

    /**
     * @return list<array{method: string, uri: string, headers: array<string, string>}>
     */
    public function requests(): array
    {
        $lines = array_filter(explode("\n", (string) file_get_contents($this->dir . '/requests.log')));

        return array_values(array_map(static fn (string $line): array => json_decode($line, true), $lines));
    }

    public function stop(): void
    {
        proc_terminate($this->process);
        proc_close($this->process);
        @unlink($this->dir . '/requests.log');
        @unlink($this->dir . '/response.json');
        @rmdir($this->dir);
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        if ($socket === false) {
            throw new \RuntimeException('No free port for the mock server.');
        }
        $name = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, (int) strrpos($name, ':') + 1);
    }

    private function waitUntilListening(int $port): void
    {
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            $connection = @fsockopen('127.0.0.1', $port, $errorCode, $errorMessage, 0.1);
            if ($connection !== false) {
                fclose($connection);

                return;
            }
            if (!proc_get_status($this->process)['running']) {
                break;
            }
            usleep(20_000);
        }

        throw new \RuntimeException('The mock server did not start listening.');
    }
}
