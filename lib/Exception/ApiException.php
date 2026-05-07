<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\Exception;

/**
 * Base exception thrown for any HTTP error response from the WorkOS API.
 *
 * Subclasses are mapped 1:1 to HTTP status codes (e.g. 400 -> BadRequestException).
 * Catch this class to handle all API errors uniformly, or a specific subclass to
 * branch on status.
 *
 * @phpstan-consistent-constructor
 */
class ApiException extends \Exception implements WorkOSException
{
    /**
     * @param string                    $message    Human-readable error message, sourced from the
     *                                              response body's `message` field when present.
     * @param int|null                  $statusCode HTTP status code of the error response.
     * @param string|null               $requestId  Value of the `X-Request-ID` response header,
     *                                              if any. Useful when reporting issues to WorkOS support.
     * @param \Throwable|null           $previous   Previous throwable (e.g. the underlying Guzzle exception).
     * @param string|null               $errorCode  Machine-readable code from the response body's `code` field.
     * @param string|null               $error      Short error identifier from the response body's `error` field.
     * @param array<string, mixed>|null $rawBody    Full decoded JSON error body, or null if the response
     *                                              was empty or non-JSON. Lets callers access fields the
     *                                              SDK doesn't promote to first-class properties (e.g.
     *                                              `pending_authentication_token`, `email`,
     *                                              `email_verification_id` from headless AuthKit).
     */
    public function __construct(
        string $message = '',
        public readonly ?int $statusCode = null,
        public readonly ?string $requestId = null,
        ?\Throwable $previous = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $error = null,
        public readonly ?array $rawBody = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    /**
     * Build an exception of the called class from a parsed JSON error response.
     *
     * @param int                  $statusCode HTTP status code.
     * @param array<string, mixed> $body       Decoded JSON response body.
     * @param string|null          $requestId  Value of the `X-Request-ID` header, if any.
     */
    public static function fromResponse(int $statusCode, array $body, ?string $requestId = null): static
    {
        $message = $body['message'] ?? 'Unknown error';
        $errorCode = isset($body['code']) && is_string($body['code']) ? $body['code'] : null;
        $error = isset($body['error']) && is_string($body['error']) ? $body['error'] : null;
        return new static($message, $statusCode, $requestId, null, $errorCode, $error, $body);
    }
}
