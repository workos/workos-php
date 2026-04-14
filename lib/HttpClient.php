<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use WorkOS\Exception\ApiException;
use WorkOS\Exception\AuthenticationException;
use WorkOS\Exception\AuthorizationException;
use WorkOS\Exception\BadRequestException;
use WorkOS\Exception\BaseRequestException;
use WorkOS\Exception\ConfigurationException;
use WorkOS\Exception\ConflictException;
use WorkOS\Exception\ConnectionException;
use WorkOS\Exception\NotFoundException;
use WorkOS\Exception\RateLimitExceededException;
use WorkOS\Exception\ServerException;
use WorkOS\Exception\TimeoutException;
use WorkOS\Exception\UnprocessableEntityException;

class HttpClient
{
    private const RETRY_STATUS_CODES = [429, 500, 502, 503, 504];

    private Client $client;

    public function __construct(
        private readonly string $apiKey,
        private readonly ?string $clientId,
        private readonly string $baseUrl,
        private readonly int $timeout,
        private readonly int $maxRetries,
        ?\GuzzleHttp\HandlerStack $handler = null,
        private readonly ?string $userAgent = null,
    ) {
        $this->client = new Client([
            'handler' => $handler,
        ]);
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey !== '' ? $this->apiKey : null;
    }

    public function requireApiKey(): string
    {
        $apiKey = $this->getApiKey();
        if ($apiKey === null) {
            throw new ConfigurationException(
                'This operation requires a WorkOS API key. Provide apiKey when instantiating WorkOS or via WORKOS_API_KEY.',
            );
        }

        return $apiKey;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function requireClientId(): string
    {
        if ($this->clientId === null || $this->clientId === '') {
            throw new ConfigurationException(
                'This operation requires a WorkOS client ID. Provide clientId when instantiating WorkOS or via WORKOS_CLIENT_ID.',
            );
        }

        return $this->clientId;
    }

    /**
     * Build a fully-qualified URL without making an HTTP request.
     *
     * Used for redirect endpoints (e.g., SSO authorize, logout) where the
     * caller needs a URL to redirect the user's browser to.
     *
     * @param array<string, mixed> $query
     */
    public function buildUrl(string $path, array $query = [], ?RequestOptions $options = null): string
    {
        $url = $this->resolveUrl($path, $options);
        $queryString = http_build_query($query);
        if ($queryString !== '') {
            $url .= '?' . $queryString;
        }

        return $url;
    }

    public function request(
        string $method,
        string $path,
        ?array $query = null,
        ?array $body = null,
        ?RequestOptions $options = null,
    ): ?array {
        $maxRetries = $this->resolveMaxRetries($options);

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $this->client->request(
                    $method,
                    $this->resolveUrl($path, $options),
                    $this->buildRequestOptions($method, $query, $body, $options),
                );
            } catch (ConnectException $e) {
                if ($attempt < $maxRetries) {
                    $this->sleep($attempt);
                    continue;
                }

                throw $this->mapTransportException($e);
            } catch (RequestException $e) {
                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    if ($response !== null) {
                        $statusCode = $response->getStatusCode();
                        if (in_array($statusCode, self::RETRY_STATUS_CODES, true) && $attempt < $maxRetries) {
                            $this->sleep($attempt, $response->getHeaderLine('Retry-After'));
                            continue;
                        }

                        throw $this->mapApiException($response, $e);
                    }
                }

                if ($attempt < $maxRetries) {
                    $this->sleep($attempt);
                    continue;
                }

                throw $this->mapTransportException($e);
            }

            $statusCode = $response->getStatusCode();
            if (in_array($statusCode, self::RETRY_STATUS_CODES, true) && $attempt < $maxRetries) {
                $this->sleep($attempt, $response->getHeaderLine('Retry-After'));
                continue;
            }

            if ($statusCode >= 400) {
                throw $this->mapApiException($response);
            }

            return $this->decodeResponse($response);
        }

        throw new ConnectionException('Request failed after exhausting retries.');
    }

    public function requestPage(
        string $method,
        string $path,
        ?array $query = null,
        ?array $body = null,
        ?string $modelClass = null,
        ?RequestOptions $options = null,
    ): PaginatedResponse {
        $response = $this->request($method, $path, $query, $body, $options) ?? [];

        return PaginatedResponse::fromArray(
            $response,
            $modelClass,
            function (array $cursorParams) use ($method, $path, $query, $body, $modelClass, $options): PaginatedResponse {
                $nextQuery = array_filter(
                    array_merge($query ?? [], $cursorParams),
                    fn ($value) => $value !== null,
                );

                return $this->requestPage(
                    $method,
                    $path,
                    $nextQuery,
                    $body,
                    $modelClass,
                    $options,
                );
            },
        );
    }

    private function buildRequestOptions(
        string $method,
        ?array $query,
        ?array $body,
        ?RequestOptions $options,
    ): array {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->getApiKey() !== null) {
            $headers['Authorization'] = sprintf('Bearer %s', $this->requireApiKey());
        }

        if ($options?->extraHeaders !== null) {
            $headers = array_merge($headers, $options->extraHeaders);
        }

        if ($options?->idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $options->idempotencyKey;
        }

        // Always set User-Agent last so callers cannot override it via extraHeaders.
        $headers['User-Agent'] = $this->userAgent ?? sprintf('%s/%s', Version::SDK_IDENTIFIER, Version::SDK_VERSION);

        $requestOptions = [
            'headers' => $headers,
            'http_errors' => false,
            'timeout' => $this->resolveTimeout($options),
        ];

        if ($query !== null) {
            $requestOptions['query'] = $query;
        }

        if ($body !== null) {
            $requestOptions['json'] = $body;
        }

        return $requestOptions;
    }

    private function resolveUrl(string $path, ?RequestOptions $options): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $baseUrl = $options !== null && $options->baseUrl !== null ? $options->baseUrl : $this->baseUrl;
        $baseUrl = rtrim($baseUrl, '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }

    private function resolveTimeout(?RequestOptions $options): int
    {
        return $options !== null && $options->timeout !== null ? $options->timeout : $this->timeout;
    }

    private function resolveMaxRetries(?RequestOptions $options): int
    {
        return $options !== null && $options->maxRetries !== null ? $options->maxRetries : $this->maxRetries;
    }

    private function decodeResponse(ResponseInterface $response): ?array
    {
        if ($response->getStatusCode() === 204) {
            return null;
        }

        $contents = $response->getBody()->getContents();
        if ($contents === '') {
            return null;
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            $statusCode = $response->getStatusCode();
            $requestId = $response->getHeaderLine('X-Request-ID') ?: null;
            $preview = mb_substr($contents, 0, 200);

            throw new Exception\ApiException(
                sprintf('Expected JSON response but received non-JSON body (HTTP %d): %s', $statusCode, $preview),
                $statusCode,
                $requestId,
            );
        }

        return $decoded;
    }

    private function mapApiException(ResponseInterface $response, ?\Throwable $previous = null): ApiException
    {
        $statusCode = $response->getStatusCode();
        $requestId = $response->getHeaderLine('X-Request-ID') ?: null;
        $body = $this->decodeErrorBody($response);

        return match ($statusCode) {
            400 => new BadRequestException($body['message'], $statusCode, $requestId, $previous),
            401 => new AuthenticationException($body['message'], $statusCode, $requestId, $previous),
            403 => new AuthorizationException($body['message'], $statusCode, $requestId, $previous),
            404 => new NotFoundException($body['message'], $statusCode, $requestId, $previous),
            409 => new ConflictException($body['message'], $statusCode, $requestId, $previous),
            422 => new UnprocessableEntityException($body['message'], $statusCode, $requestId, $previous),
            429 => new RateLimitExceededException(
                $body['message'],
                $statusCode,
                $requestId,
                $previous,
                $this->parseRetryAfter($response->getHeaderLine('Retry-After')),
            ),
            500, 502, 503, 504 => new ServerException($body['message'], $statusCode, $requestId, $previous),
            default => new BaseRequestException($body['message'], $statusCode, $requestId, $previous),
        };
    }

    /**
     * @return array{message: string}
     */
    private function decodeErrorBody(ResponseInterface $response): array
    {
        $contents = (string) $response->getBody();
        if ($contents === '') {
            return ['message' => sprintf('WorkOS request failed with status %d.', $response->getStatusCode())];
        }

        $decoded = json_decode($contents, true);
        if (is_array($decoded)) {
            $message = $decoded['message'] ?? $decoded['error_description'] ?? $decoded['error'] ?? null;
            if (is_string($message) && $message !== '') {
                return ['message' => $message];
            }
        }

        return ['message' => $contents];
    }

    private function mapTransportException(\Throwable $exception): \Exception
    {
        if ($this->isTimeoutException($exception)) {
            return new TimeoutException(sprintf('Request timed out: %s', $exception->getMessage()), 0, $exception);
        }

        return new ConnectionException(sprintf('Connection failed: %s', $exception->getMessage()), 0, $exception);
    }

    private function isTimeoutException(\Throwable $exception): bool
    {
        if ($exception instanceof ConnectException || $exception instanceof RequestException) {
            $errno = $exception->getHandlerContext()['errno'] ?? null;
            if ($errno === 28) {
                return true;
            }
        }

        return str_contains(strtolower($exception->getMessage()), 'timed out');
    }

    private function parseRetryAfter(?string $retryAfter): ?int
    {
        if ($retryAfter === null || trim($retryAfter) === '') {
            return null;
        }

        if (is_numeric($retryAfter)) {
            return max(0, (int) $retryAfter);
        }

        $timestamp = strtotime($retryAfter);
        if ($timestamp === false) {
            return null;
        }

        return max(0, $timestamp - time());
    }

    private function sleep(int $attempt, ?string $retryAfter = null): void
    {
        $retryAfterSeconds = $this->parseRetryAfter($retryAfter);
        if ($retryAfterSeconds !== null) {
            usleep($retryAfterSeconds * 1000000);
            return;
        }

        $delay = min((2 ** $attempt) * 1000, 30000);
        $jitter = random_int(0, (int) ($delay * 0.1));
        usleep(($delay + $jitter) * 1000);
    }
}
