<?php

declare(strict_types=1);
// @oagen-ignore-file
// This file is hand-maintained. PKCE (Proof Key for Code Exchange) utilities
// for OAuth 2.0 public client flows. These are client-side cryptographic
// helpers that will always be hand-maintained.
// Covers: H08 (pkce_utilities), H10 (authkit_pkce_authorization_url),
//         H11 (authkit_pkce_code_exchange), H15 (sso_pkce_authorization_url),
//         H16 (sso_pkce_code_exchange), H19 (public_client_factory).

namespace WorkOS;

class PKCEHelper
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    // -- H08: Core PKCE utilities --

    /**
     * Generate a cryptographically random code verifier.
     *
     * @param int $length Length of the verifier string (43-128 per RFC 7636).
     * @return string A base64url-encoded random string of the requested length.
     * @throws \InvalidArgumentException If length is outside the 43-128 range.
     */
    public static function generateCodeVerifier(int $length = 43): string
    {
        if ($length < 43 || $length > 128) {
            throw new \InvalidArgumentException(
                "Code verifier length must be between 43 and 128, got {$length}"
            );
        }

        $numBytes = intdiv($length * 3 + 3, 4);
        $raw = random_bytes($numBytes);
        return substr(self::base64UrlEncode($raw), 0, $length);
    }

    /**
     * Compute the S256 code challenge for a given verifier.
     *
     * @param string $verifier The code verifier string.
     * @return string The base64url-encoded SHA-256 hash of the verifier.
     */
    public static function generateCodeChallenge(string $verifier): string
    {
        $digest = hash('sha256', $verifier, true);
        return self::base64UrlEncode($digest);
    }

    /**
     * Generate a complete PKCE pair (verifier + challenge).
     *
     * @return array{code_verifier: string, code_challenge: string, code_challenge_method: string}
     */
    public static function generate(): array
    {
        $verifier = self::generateCodeVerifier();
        $challenge = self::generateCodeChallenge($verifier);

        return [
            'code_verifier' => $verifier,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ];
    }

    // -- H10: AuthKit PKCE authorization URL --

    /**
     * Generate an AuthKit authorization URL with auto-generated PKCE parameters and state.
     *
     * @param string $redirectUri The redirect URI.
     * @param string $clientId The WorkOS client ID.
     * @param string|null $state Optional state parameter. Auto-generated if null.
     * @param string|null $provider Optional auth provider.
     * @param string|null $connectionId Optional connection ID.
     * @param string|null $organizationId Optional organization ID.
     * @param string|null $domainHint Optional domain hint.
     * @param string|null $loginHint Optional login hint.
     * @param string|null $screenHint Optional screen hint.
     * @return array{url: mixed, code_verifier: string, state: string}
     */
    public function getAuthKitAuthorizationUrl(
        string $redirectUri,
        string $clientId,
        ?string $state = null,
        ?string $provider = null,
        ?string $connectionId = null,
        ?string $organizationId = null,
        ?string $domainHint = null,
        ?string $loginHint = null,
        ?string $screenHint = null,
    ): array {
        $pkce = self::generate();
        $state ??= bin2hex(random_bytes(16));

        $query = array_filter([
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'client_id' => $clientId,
            'code_challenge_method' => $pkce['code_challenge_method'],
            'code_challenge' => $pkce['code_challenge'],
            'state' => $state,
            'provider' => $provider,
            'connection_id' => $connectionId,
            'organization_id' => $organizationId,
            'domain_hint' => $domainHint,
            'login_hint' => $loginHint,
            'screen_hint' => $screenHint,
        ], fn ($v) => $v !== null);

        $url = $this->client->request(
            method: 'GET',
            path: 'user_management/authorize',
            query: $query,
        );

        return [
            'url' => $url,
            'code_verifier' => $pkce['code_verifier'],
            'state' => $state,
        ];
    }

    // -- H11: AuthKit PKCE code exchange --

    /**
     * Exchange an authorization code with a PKCE code verifier.
     *
     * @param string $code The authorization code.
     * @param string $codeVerifier The PKCE code verifier.
     * @param string $clientId The WorkOS client ID.
     * @return array The authentication response.
     */
    public function authKitCodeExchange(
        string $code,
        string $codeVerifier,
        string $clientId,
    ): array {
        return $this->client->request(
            method: 'POST',
            path: 'user_management/authenticate',
            body: [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'code' => $code,
                'code_verifier' => $codeVerifier,
            ],
        );
    }

    // -- H15: SSO PKCE authorization URL --

    /**
     * Generate an SSO authorization URL with auto-generated PKCE parameters and state.
     *
     * @param string $redirectUri The redirect URI.
     * @param string $clientId The WorkOS client ID.
     * @param string|null $state Optional state parameter. Auto-generated if null.
     * @param string|null $domain Optional SSO domain.
     * @param string|null $provider Optional SSO provider.
     * @param string|null $connection Optional connection ID.
     * @param string|null $organization Optional organization ID.
     * @param string|null $domainHint Optional domain hint.
     * @param string|null $loginHint Optional login hint.
     * @return array{url: mixed, code_verifier: string, state: string}
     */
    public function getSsoAuthorizationUrl(
        string $redirectUri,
        string $clientId,
        ?string $state = null,
        ?string $domain = null,
        ?string $provider = null,
        ?string $connection = null,
        ?string $organization = null,
        ?string $domainHint = null,
        ?string $loginHint = null,
    ): array {
        $pkce = self::generate();
        $state ??= bin2hex(random_bytes(16));

        $query = array_filter([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'code_challenge_method' => $pkce['code_challenge_method'],
            'code_challenge' => $pkce['code_challenge'],
            'state' => $state,
            'domain' => $domain,
            'provider' => $provider,
            'connection' => $connection,
            'organization' => $organization,
            'domain_hint' => $domainHint,
            'login_hint' => $loginHint,
        ], fn ($v) => $v !== null);

        $url = $this->client->request(
            method: 'GET',
            path: 'sso/authorize',
            query: $query,
        );

        return [
            'url' => $url,
            'code_verifier' => $pkce['code_verifier'],
            'state' => $state,
        ];
    }

    // -- H16: SSO PKCE code exchange --

    /**
     * Exchange an SSO authorization code with a PKCE code verifier.
     *
     * @param string $code The authorization code.
     * @param string $codeVerifier The PKCE code verifier.
     * @param string $clientId The WorkOS client ID.
     * @return array The SSO token response.
     */
    public function ssoCodeExchange(
        string $code,
        string $codeVerifier,
        string $clientId,
    ): array {
        return $this->client->request(
            method: 'POST',
            path: 'sso/token',
            body: [
                'client_id' => $clientId,
                'code' => $code,
                'code_verifier' => $codeVerifier,
                'grant_type' => 'authorization_code',
            ],
        );
    }

    // -- H19: Public client factory --

    /**
     * Create a WorkOS client configured for PKCE-only / public-client usage.
     *
     * Public clients do not use an API key (client_secret). This factory creates
     * a WorkOS instance suitable for browser-side or mobile flows where the
     * client secret cannot be safely stored.
     *
     * @param string $clientId The WorkOS client ID.
     * @param string $baseUrl The WorkOS API base URL. Defaults to production.
     * @return WorkOS A WorkOS client configured for public-client usage.
     */
    public static function createPublicClient(
        string $clientId,
        string $baseUrl = 'https://api.workos.com',
    ): WorkOS {
        return new WorkOS(
            apiKey: '',
            clientId: $clientId,
            baseUrl: $baseUrl,
        );
    }

    // -- Internal helpers --

    /**
     * Base64url-encode without padding, per RFC 7636.
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
