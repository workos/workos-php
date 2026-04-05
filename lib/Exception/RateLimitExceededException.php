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
        ?int $retryAfter = null,
    ) {
        parent::__construct($message, $statusCode, $requestId, $previous);
        $this->retryAfter = $retryAfter;
    }
}
