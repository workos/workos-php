<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity;

/**
 * Reads the identity token from a file, such as a projected service account
 * token. The file is read again on every call, so a rotated token is picked
 * up without a restart.
 */
final class TokenFile implements IdentityTokenProvider
{
    private function __construct(private readonly string $path)
    {
    }

    public static function create(string $path): self
    {
        return new self($path);
    }

    public function getIdentityToken(): string
    {
        $contents = is_file($this->path) ? @file_get_contents($this->path) : false;
        if ($contents === false) {
            throw new \RuntimeException(sprintf('The identity token file could not be read: %s', $this->path));
        }

        $token = trim($contents);
        if ($token === '') {
            throw new \RuntimeException(sprintf('The identity token file is empty: %s', $this->path));
        }

        return $token;
    }
}
