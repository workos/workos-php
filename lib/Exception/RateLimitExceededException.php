<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\Exception;

/**
 * Thrown when the WorkOS API returns HTTP 429 (Too Many Requests).
 *
 * If the response includes a `Retry-After` header, its parsed value (in seconds)
 * is exposed on {@see self::$retryAfter} so callers can implement backoff.
 */
class RateLimitExceededException extends BaseRequestException
{
    /**
     * Seconds to wait before retrying, parsed from the `Retry-After` response
     * header. Null if the header was absent or unparseable.
     */
    public ?int $retryAfter = null;

    /**
     * @param array<string, mixed>|null $rawBody    Full decoded JSON error body. See {@see ApiException::__construct}.
     * @param int|null                  $retryAfter Seconds to wait before retrying.
     */
    public function __construct(
        string $message = '',
        ?int $statusCode = 429,
        ?string $requestId = null,
        ?\Throwable $previous = null,
        ?string $errorCode = null,
        ?string $error = null,
        ?array $rawBody = null,
        ?int $retryAfter = null,
    ) {
        parent::__construct($message, $statusCode, $requestId, $previous, $errorCode, $error, $rawBody);
        $this->retryAfter = $retryAfter;
    }
}
