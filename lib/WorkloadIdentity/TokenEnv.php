<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity;

/**
 * Reads the identity token from an environment variable. The variable is
 * read again on every call.
 */
final class TokenEnv implements IdentityTokenProvider
{
    private function __construct(private readonly string $name)
    {
    }

    public static function create(string $name): self
    {
        return new self($name);
    }

    public function getIdentityToken(): string
    {
        $token = getenv($this->name);
        if ($token === false || $token === '') {
            throw new \RuntimeException(sprintf('The identity token environment variable is not set: %s', $this->name));
        }

        return $token;
    }
}
