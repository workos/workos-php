<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\Exception;

/** @phpstan-consistent-constructor */
class ApiException extends \Exception implements WorkOSException
{
    public function __construct(
        string $message = '',
        public readonly ?int $statusCode = null,
        public readonly ?string $requestId = null,
        ?\Throwable $previous = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $error = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public static function fromResponse(int $statusCode, array $body, ?string $requestId = null): static
    {
        $message = $body['message'] ?? 'Unknown error';
        $errorCode = $body['code'] ?? null;
        $error = $body['error'] ?? null;
        return new static($message, $statusCode, $requestId, null, $errorCode, $error);
    }
}
