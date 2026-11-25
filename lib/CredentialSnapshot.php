<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS;

/**
 * The authorization headers for one HTTP attempt, and where they came from.
 */
final class CredentialSnapshot
{
    /**
     * @param array<string, string> $headers     Headers to set on the attempt. They replace
     *                                           any header of the same name already on the request.
     * @param int                   $generation  Identifies the credential these headers came from.
     * @param bool                  $recoverable Whether a 401 on this snapshot is worth one re-resolve.
     */
    public function __construct(
        #[\SensitiveParameter]
        public readonly array $headers,
        public readonly int $generation,
        public readonly bool $recoverable,
    ) {
    }
}
