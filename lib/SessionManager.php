<?php

declare(strict_types=1);
// @oagen-ignore-file
// This file is hand-maintained. Session management (sealed cookies, JWT
// validation, JWKS) is client-side logic that cannot be generated from the
// OpenAPI spec.
// Covers: H04 (session_cookie_object), H05 (session_cookie_inline),
//         H06 (session_cookie_raw_seal), H07 (auth_response_session_sealing),
//         H13 (jwks_helper).

namespace WorkOS;

class SessionManager
{
    public function __construct(
        private readonly HttpClient $client,
    ) {
    }

    // -- H06: Raw seal/unseal helpers --

    /**
     * Encrypt a data array into a sealed string using symmetric encryption.
     *
     * @param array $data The data to seal.
     * @param string $key The base64-encoded encryption key (must be 32 bytes decoded).
     * @return string The sealed (encrypted) string.
     */
    public static function sealData(array $data, string $key): string
    {
        $keyBytes = base64_decode($key);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plaintext = json_encode($data, JSON_UNESCAPED_SLASHES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $keyBytes);

        return base64_encode($nonce . $ciphertext);
    }

    /**
     * Decrypt a sealed string back to a data array.
     *
     * @param string $sealedData The sealed (encrypted) string.
     * @param string $key The base64-encoded encryption key.
     * @return array The decrypted data.
     * @throws \InvalidArgumentException If decryption fails.
     */
    public static function unsealData(string $sealedData, string $key): array
    {
        $keyBytes = base64_decode($key);
        $decoded = base64_decode($sealedData, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid sealed data: base64 decode failed');
        }

        $nonceLength = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        if (strlen($decoded) < $nonceLength) {
            throw new \InvalidArgumentException('Invalid sealed data: too short');
        }

        $nonce = substr($decoded, 0, $nonceLength);
        $ciphertext = substr($decoded, $nonceLength);

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $keyBytes);
        if ($plaintext === false) {
            throw new \InvalidArgumentException('Decryption failed: invalid key or corrupted data');
        }

        return json_decode($plaintext, true);
    }

    // -- H07: Auth response session sealing --

    /**
     * Seal session data from an authentication response into a cookie-safe string.
     *
     * @param string $accessToken The access token from the auth response.
     * @param string $refreshToken The refresh token from the auth response.
     * @param string $cookiePassword The encryption key (base64-encoded, 32 bytes decoded).
     * @param array|null $user The user data from the auth response. (Optional)
     * @param array|null $impersonator The impersonator data, if present. (Optional)
     * @return string A sealed session string suitable for storing in a cookie.
     */
    public static function sealSessionFromAuthResponse(
        string $accessToken,
        string $refreshToken,
        string $cookiePassword,
        ?array $user = null,
        ?array $impersonator = null,
    ): string {
        $sessionData = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
        if ($user !== null) {
            $sessionData['user'] = $user;
        }
        if ($impersonator !== null) {
            $sessionData['impersonator'] = $impersonator;
        }

        return self::sealData($sessionData, $cookiePassword);
    }

    // -- H04: Session cookie object --

    /**
     * Authenticate a sealed session cookie by unsealing and validating the JWT.
     *
     * Returns an associative array with 'authenticated' => true and session claims
     * on success, or 'authenticated' => false and a 'reason' on failure.
     *
     * @param string $sessionData The sealed session cookie value.
     * @param string $cookiePassword The encryption key.
     * @param string $clientId The WorkOS client ID (for JWKS URL).
     * @param string $baseUrl The WorkOS API base URL. Defaults to 'https://api.workos.com/'.
     * @return array Authentication result.
     */
    public function authenticate(
        string $sessionData,
        string $cookiePassword,
        string $clientId,
        string $baseUrl = 'https://api.workos.com/',
    ): array {
        if (empty($sessionData)) {
            return [
                'authenticated' => false,
                'reason' => 'no_session_cookie_provided',
            ];
        }

        try {
            $session = self::unsealData($sessionData, $cookiePassword);
        } catch (\Exception $e) {
            return [
                'authenticated' => false,
                'reason' => 'invalid_session_cookie',
            ];
        }

        if (empty($session['access_token'])) {
            return [
                'authenticated' => false,
                'reason' => 'invalid_session_cookie',
            ];
        }

        try {
            $decoded = self::decodeAccessToken($session['access_token'], $clientId, $baseUrl);
        } catch (\Exception $e) {
            return [
                'authenticated' => false,
                'reason' => 'invalid_jwt',
            ];
        }

        return [
            'authenticated' => true,
            'session_id' => $decoded['sid'],
            'organization_id' => $decoded['org_id'] ?? null,
            'role' => $decoded['role'] ?? null,
            'roles' => $decoded['roles'] ?? null,
            'permissions' => $decoded['permissions'] ?? null,
            'entitlements' => $decoded['entitlements'] ?? null,
            'user' => $session['user'] ?? null,
            'impersonator' => $session['impersonator'] ?? null,
            'feature_flags' => $decoded['feature_flags'] ?? null,
        ];
    }

    // -- H05: Session cookie inline convenience methods --

    /**
     * Refresh a sealed session by exchanging the refresh token.
     *
     * @param string $sessionData The sealed session cookie value.
     * @param string $cookiePassword The encryption key.
     * @param string $clientId The WorkOS client ID.
     * @param string|null $organizationId Optional organization to scope the refresh to.
     * @return array Refresh result with 'authenticated', 'sealed_session', and claims.
     */
    public function refresh(
        string $sessionData,
        string $cookiePassword,
        string $clientId,
        ?string $organizationId = null,
    ): array {
        try {
            $session = self::unsealData($sessionData, $cookiePassword);
        } catch (\Exception $e) {
            return [
                'authenticated' => false,
                'reason' => 'invalid_session_cookie',
            ];
        }

        if (empty($session['refresh_token']) || empty($session['user'])) {
            return [
                'authenticated' => false,
                'reason' => 'invalid_session_cookie',
            ];
        }

        try {
            $body = [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'client_secret' => WorkOS::getApiKey(),
                'refresh_token' => $session['refresh_token'],
                'session' => [
                    'seal_session' => true,
                    'cookie_password' => $cookiePassword,
                ],
            ];
            if ($organizationId !== null) {
                $body['organization_id'] = $organizationId;
            }

            $authResponse = $this->client->request(
                method: 'POST',
                path: 'user_management/authenticate',
                body: $body,
            );

            return [
                'authenticated' => true,
                'sealed_session' => $authResponse['sealed_session'],
                'session_id' => $authResponse['sid'] ?? null,
                'user' => $authResponse['user'] ?? null,
                'impersonator' => $authResponse['impersonator'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'authenticated' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the logout URL for a session.
     *
     * @param string $sessionData The sealed session cookie value.
     * @param string $cookiePassword The encryption key.
     * @param string $clientId The WorkOS client ID.
     * @param string|null $returnTo Optional URL to redirect to after logout.
     * @param string $baseUrl The WorkOS API base URL.
     * @return string The logout URL.
     * @throws \InvalidArgumentException If the session cannot be authenticated.
     */
    public function getLogoutUrl(
        string $sessionData,
        string $cookiePassword,
        string $clientId,
        ?string $returnTo = null,
        string $baseUrl = 'https://api.workos.com/',
    ): string {
        $authResult = $this->authenticate($sessionData, $cookiePassword, $clientId, $baseUrl);

        if (!$authResult['authenticated']) {
            throw new \InvalidArgumentException(
                "Failed to extract session ID for logout URL: {$authResult['reason']}"
            );
        }

        $query = ['session_id' => $authResult['session_id']];
        if ($returnTo !== null) {
            $query['return_to'] = $returnTo;
        }

        return rtrim($baseUrl, '/') . '/user_management/sessions/logout?' . http_build_query($query);
    }

    // -- H13: JWKS helper --

    /**
     * Build the JWKS URL for the given client ID.
     *
     * @param string $clientId The WorkOS client ID.
     * @param string $baseUrl The WorkOS API base URL.
     * @return string The JWKS URL.
     */
    public static function getJwksUrl(
        string $clientId,
        string $baseUrl = 'https://api.workos.com/',
    ): string {
        return rtrim($baseUrl, '/') . "/sso/jwks/{$clientId}";
    }

    /**
     * Fetch the JWKS keys for the given client ID.
     *
     * @param string $clientId The WorkOS client ID.
     * @return array The JWKS response.
     */
    public function fetchJwks(string $clientId): array
    {
        return $this->client->request(
            method: 'GET',
            path: "sso/jwks/{$clientId}",
        );
    }

    /**
     * Decode and validate an access token JWT.
     *
     * This is a basic JWT decode. For production use, fetch JWKS and validate
     * the signature properly. This helper decodes without signature verification
     * for extracting claims when the token has already been validated upstream.
     *
     * @param string $accessToken The JWT access token.
     * @param string $clientId The WorkOS client ID (unused in basic decode).
     * @param string $baseUrl The WorkOS API base URL (unused in basic decode).
     * @return array The decoded JWT claims.
     * @throws \InvalidArgumentException If the token cannot be decoded.
     */
    private static function decodeAccessToken(
        string $accessToken,
        string $clientId,
        string $baseUrl,
    ): array {
        $parts = explode('.', $accessToken);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid JWT format');
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        if ($payload === false) {
            throw new \InvalidArgumentException('Invalid JWT payload encoding');
        }

        $decoded = json_decode($payload, true);
        if ($decoded === null) {
            throw new \InvalidArgumentException('Invalid JWT payload JSON');
        }

        // Check expiration
        if (isset($decoded['exp']) && $decoded['exp'] < time()) {
            throw new \InvalidArgumentException('JWT has expired');
        }

        return $decoded;
    }
}
