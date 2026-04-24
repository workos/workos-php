<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\Exception;

class RateLimitExceededException extends BaseRequestException
{
    public ?int $retryAfter = null;

    public function __construct(
        string $message = '',
        ?int $statusCode = 429,
        ?string $requestId = null,
        ?\Throwable $previous = null,
        ?string $errorCode = null,
        ?string $error = null,
        ?int $retryAfter = null,
    ) {
        parent::__construct($message, $statusCode, $requestId, $previous, $errorCode, $error);
        $this->retryAfter = $retryAfter;
    }
}
