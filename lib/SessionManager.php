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
    /**
     * In-memory JWKS cache, keyed by client ID. Values are
     * `['keys' => array, 'fetched_at' => int]`. Cache lives for the
     * lifetime of the SessionManager instance and is bypassed when a
     * token's `kid` isn't found, so key rotation still resolves quickly.
     *
     * @var array<string, array{keys: array, fetched_at: int}>
     */
    private array $jwksCache = [];

    /**
     * JWKS cache TTL in seconds. WorkOS rotates signing keys on the order
     * of weeks, so a few minutes is plenty to absorb traffic spikes
     * without making session checks dependent on a live JWKS round-trip.
     */
    private const JWKS_CACHE_TTL_SECONDS = 300;

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
            $decoded = $this->decodeAccessToken($session['access_token'], $clientId);
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
                'client_secret' => $this->client->requireApiKey(),
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
        $response = $this->client->request(
            method: 'GET',
            path: "sso/jwks/{$clientId}",
        );
        if ($response === null) {
            throw new \RuntimeException('Failed to fetch JWKS: empty response');
        }
        return $response;
    }

    /**
     * Return the JWKS for `$clientId`, served from an in-memory cache
     * with a {@see JWKS_CACHE_TTL_SECONDS}-second TTL. Set
     * `$forceRefresh` to bypass the cache after a `kid` miss, which
     * lets newly-rotated keys be discovered without waiting for TTL
     * expiry.
     *
     * @return array<string, mixed>
     */
    private function getCachedJwks(string $clientId, bool $forceRefresh = false): array
    {
        $now = time();
        $entry = $this->jwksCache[$clientId] ?? null;
        if (
            !$forceRefresh
            && $entry !== null
            && ($now - $entry['fetched_at']) < self::JWKS_CACHE_TTL_SECONDS
        ) {
            return $entry['keys'];
        }

        $keys = $this->fetchJwks($clientId);
        $this->jwksCache[$clientId] = ['keys' => $keys, 'fetched_at' => $now];

        return $keys;
    }

    /**
     * Algorithms permitted on the JWS header. WorkOS access tokens are signed
     * with RS256; no other algorithm is accepted, in particular `none` is
     * always rejected.
     */
    private const ALLOWED_JWS_ALGORITHMS = ['RS256'];

    /**
     * Decode and validate an access token JWT.
     *
     * Verifies the JWS signature against the JWKS published for `$clientId`,
     * enforces an algorithm allow-list, and rejects expired tokens. This is
     * the only path used by {@see authenticate()}; callers must not bypass it.
     *
     * @param string $accessToken The JWT access token.
     * @param string $clientId The WorkOS client ID (used to fetch JWKS).
     * @return array The decoded JWT claims.
     * @throws \InvalidArgumentException If the token cannot be decoded or fails verification.
     */
    private function decodeAccessToken(
        string $accessToken,
        string $clientId,
    ): array {
        $parts = explode('.', $accessToken);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid JWT format');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $headerJson = self::base64UrlDecode($headerB64);
        if ($headerJson === false) {
            throw new \InvalidArgumentException('Invalid JWT header encoding');
        }
        $header = json_decode($headerJson, true);
        if (!is_array($header)) {
            throw new \InvalidArgumentException('Invalid JWT header JSON');
        }

        $alg = $header['alg'] ?? null;
        if (!is_string($alg) || !in_array($alg, self::ALLOWED_JWS_ALGORITHMS, true)) {
            throw new \InvalidArgumentException('Unsupported JWT algorithm');
        }

        $payloadJson = self::base64UrlDecode($payloadB64);
        if ($payloadJson === false) {
            throw new \InvalidArgumentException('Invalid JWT payload encoding');
        }
        $decoded = json_decode($payloadJson, true);
        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('Invalid JWT payload JSON');
        }

        $signature = self::base64UrlDecode($signatureB64);
        if ($signature === false || $signature === '') {
            throw new \InvalidArgumentException('Invalid JWT signature encoding');
        }

        // Resolve a JWK matching the header `kid`. Without a `kid` we won't
        // guess — refuse rather than try every key, which would mask key
        // rotation bugs.
        $kid = $header['kid'] ?? null;
        if (!is_string($kid) || $kid === '') {
            throw new \InvalidArgumentException('JWT header missing kid');
        }

        // Try the cached JWKS first; if the `kid` isn't present, force a
        // refresh once to handle key rotation, then fail if still unknown.
        $jwks = $this->getCachedJwks($clientId);
        $jwk = self::findJwkByKid($jwks, $kid);
        if ($jwk === null) {
            $jwks = $this->getCachedJwks($clientId, forceRefresh: true);
            $jwk = self::findJwkByKid($jwks, $kid);
        }
        if ($jwk === null) {
            throw new \InvalidArgumentException('No JWKS key matches JWT kid');
        }

        $publicKeyPem = self::jwkToRsaPublicKeyPem($jwk);
        $signingInput = $headerB64 . '.' . $payloadB64;

        $verified = openssl_verify($signingInput, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new \InvalidArgumentException('JWT signature verification failed');
        }

        // Expiration check (after signature verification).
        if (isset($decoded['exp']) && is_numeric($decoded['exp']) && (int) $decoded['exp'] < time()) {
            throw new \InvalidArgumentException('JWT has expired');
        }

        // TODO(security-fix-plan.md, finding #60): enforce documented WorkOS
        // `iss` and `aud` values once empirically confirmed. The other WorkOS
        // SDKs (Ruby, Python) currently skip `aud` verification, so the
        // canonical values are not authoritatively documented in this repo.
        // Track resolution under "Open questions / follow-ups" in the plan.

        return $decoded;
    }

    /**
     * Decode a base64url-encoded segment, tolerating missing padding.
     *
     * @return string|false The decoded bytes, or false on malformed input.
     */
    private static function base64UrlDecode(string $segment): string|false
    {
        $remainder = strlen($segment) % 4;
        if ($remainder !== 0) {
            $segment .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($segment, '-_', '+/'), true);
    }

    /**
     * Locate a JWK in the JWKS response by `kid`.
     *
     * @param array<string, mixed> $jwks
     * @return array<string, mixed>|null
     */
    private static function findJwkByKid(array $jwks, string $kid): ?array
    {
        $keys = $jwks['keys'] ?? null;
        if (!is_array($keys)) {
            return null;
        }
        foreach ($keys as $jwk) {
            if (is_array($jwk) && ($jwk['kid'] ?? null) === $kid) {
                return $jwk;
            }
        }
        return null;
    }

    /**
     * Convert an RSA JWK (`kty=RSA`, base64url `n`/`e`) to a PEM-encoded
     * public key suitable for {@see openssl_verify()}.
     *
     * @param array<string, mixed> $jwk
     */
    private static function jwkToRsaPublicKeyPem(array $jwk): string
    {
        if (($jwk['kty'] ?? null) !== 'RSA') {
            throw new \InvalidArgumentException('Unsupported JWK key type');
        }
        $n = $jwk['n'] ?? null;
        $e = $jwk['e'] ?? null;
        if (!is_string($n) || !is_string($e)) {
            throw new \InvalidArgumentException('Malformed RSA JWK');
        }

        $modulus = self::base64UrlDecode($n);
        $exponent = self::base64UrlDecode($e);
        if ($modulus === false || $exponent === false) {
            throw new \InvalidArgumentException('Malformed RSA JWK encoding');
        }

        // Build a DER-encoded SubjectPublicKeyInfo for an RSA public key, then
        // wrap it as a PEM document. Avoids a hard dependency on a JWT library.
        $modulusDer = self::derEncodeUnsignedInteger($modulus);
        $exponentDer = self::derEncodeUnsignedInteger($exponent);
        $rsaPublicKey = self::derEncodeSequence($modulusDer . $exponentDer);
        $bitString = self::derEncodeBitString($rsaPublicKey);

        // AlgorithmIdentifier: SEQUENCE { OID 1.2.840.113549.1.1.1, NULL }.
        $rsaOid = "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01";
        $algorithmIdentifier = self::derEncodeSequence($rsaOid . "\x05\x00");
        $spki = self::derEncodeSequence($algorithmIdentifier . $bitString);

        $pem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($spki), 64, "\n")
            . "-----END PUBLIC KEY-----\n";

        return $pem;
    }

    private static function derEncodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private static function derEncodeSequence(string $contents): string
    {
        return "\x30" . self::derEncodeLength(strlen($contents)) . $contents;
    }

    private static function derEncodeUnsignedInteger(string $bytes): string
    {
        // Strip leading zero bytes, then re-prepend a single 0x00 if the
        // high bit of the first byte is set so the value remains positive.
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '') {
            $bytes = "\x00";
        } elseif ((ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }
        return "\x02" . self::derEncodeLength(strlen($bytes)) . $bytes;
    }

    private static function derEncodeBitString(string $bytes): string
    {
        // 0x00 = number of unused bits in the final octet (always zero here).
        $contents = "\x00" . $bytes;
        return "\x03" . self::derEncodeLength(strlen($contents)) . $contents;
    }
}
