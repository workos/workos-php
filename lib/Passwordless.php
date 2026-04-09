<?php

declare(strict_types=1);
// @oagen-ignore-file
// This file is hand-maintained. The passwordless API endpoints are not yet in
// the OpenAPI spec, so this module provides the functionality until they are.

namespace WorkOS;

class Passwordless
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    /**
     * Create a Passwordless Session.
     *
     * @param string $email The email of the user to authenticate.
     * @param string $type The type of Passwordless Session ('MagicLink').
     * @param string|null $redirectUri The redirect endpoint for the callback. (Optional)
     * @param string|null $state Arbitrary state to pass through the redirect. (Optional)
     * @param int|null $expiresIn Seconds until expiry (900-86400). (Optional)
     * @return array The passwordless session data.
     */
    public function createSession(
        string $email,
        string $type = 'MagicLink',
        ?string $redirectUri = null,
        ?string $state = null,
        ?int $expiresIn = null,
    ): array {
        $body = array_filter([
            'email' => $email,
            'type' => $type,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'expires_in' => $expiresIn,
        ], fn ($v) => $v !== null);

        return $this->client->request(
            method: 'POST',
            path: 'passwordless/sessions',
            body: $body,
        );
    }

    /**
     * Send a Passwordless Session via email.
     *
     * @param string $sessionId The unique identifier of the Passwordless Session.
     */
    public function sendSession(string $sessionId): void
    {
        $this->client->request(
            method: 'POST',
            path: "passwordless/sessions/{$sessionId}/send",
            body: [],
        );
    }
}
