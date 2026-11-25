<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS;

/**
 * A static API key as credentials: one unchanging bearer header and no
 * recovery. An empty key resolves to no headers, which is how a client built
 * with other credentials leaves `Authorization` to them.
 *
 * @internal
 */
final class StaticKeyCredentials implements Credentials
{
    public function __construct(
        #[\SensitiveParameter]
        private readonly string $apiKey,
    ) {
    }

    public function resolve(): CredentialSnapshot
    {
        $headers = $this->apiKey === '' ? [] : ['Authorization' => sprintf('Bearer %s', $this->apiKey)];

        return new CredentialSnapshot($headers, 0, false);
    }

    public function invalidate(int $generation): void
    {
    }
}
