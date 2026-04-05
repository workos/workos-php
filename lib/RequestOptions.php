<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS;

class RequestOptions
{
    public function __construct(
        public readonly ?array $extraHeaders = null,
        public readonly ?string $idempotencyKey = null,
        public readonly ?int $timeout = null,
    ) {
    }
}
