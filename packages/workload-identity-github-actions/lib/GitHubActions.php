<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity\GitHubActions;

use WorkOS\WorkloadIdentity\IdentityTokenProvider;

/**
 * Gets the workflow's OIDC token from GitHub Actions.
 *
 *     $credentials = new \WorkOS\WorkloadIdentity\Credentials(
 *         identityTokenProvider: GitHubActions::create('https://api.workos.com'),
 *     );
 *
 * The workflow or job needs `permissions: id-token: write`.
 *
 * Holds no cache and does no retries: the SDK calls it again for every
 * exchange attempt.
 */
final class GitHubActions implements IdentityTokenProvider
{
    private const TIMEOUT_SECONDS = 10;

    private function __construct(private readonly string $audience)
    {
    }

    /**
     * @param string $audience The audience to request the token for. It must match the trust rule; the conventional value is `https://api.workos.com`.
     */
    public static function create(string $audience): self
    {
        if ($audience === '') {
            throw new \InvalidArgumentException('An audience is required.');
        }

        return new self($audience);
    }

    public function getIdentityToken(): string
    {
        // Read on every call.
        $url = getenv('ACTIONS_ID_TOKEN_REQUEST_URL');
        $requestToken = getenv('ACTIONS_ID_TOKEN_REQUEST_TOKEN');
        if (!is_string($url) || $url === '' || !is_string($requestToken) || $requestToken === '') {
            throw new \RuntimeException(
                'GitHub Actions did not provide an OIDC token endpoint. Add `permissions: id-token: write` to the workflow or job.',
            );
        }

        // The URL already has a query string; add to it.
        $url .= (str_contains($url, '?') ? '&' : '?') . 'audience=' . rawurlencode($this->audience);

        [$status, $body] = self::get($url, [
            'Authorization: Bearer ' . $requestToken,
            'Accept: application/json',
            'User-Agent: ' . Version::PACKAGE_NAME . '/' . Version::PACKAGE_VERSION,
        ]);
        if ($status !== 200) {
            throw new \RuntimeException(sprintf('The GitHub Actions OIDC token endpoint returned HTTP %d.', $status));
        }

        $decoded = json_decode($body, true);
        $token = is_array($decoded) ? ($decoded['value'] ?? null) : null;
        if (!is_string($token) || $token === '') {
            throw new \RuntimeException('The GitHub Actions OIDC token response had no token.');
        }

        return $token;
    }

    /**
     * One GET: 10-second timeout, redirects not followed.
     *
     * @param list<string> $headers
     *
     * @return array{int, string} The status and the body.
     */
    private static function get(
        string $url,
        #[\SensitiveParameter]
        array $headers,
    ): array {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new \RuntimeException('The GitHub Actions OIDC token endpoint URL is not valid.');
        }

        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
        ]);

        $body = curl_exec($curl);
        if (!is_string($body)) {
            throw new \RuntimeException(sprintf(
                'The GitHub Actions OIDC token endpoint could not be reached: %s',
                curl_error($curl),
            ));
        }

        return [(int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE), $body];
    }
}
