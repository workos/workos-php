<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity\Gcp;

use WorkOS\WorkloadIdentity\IdentityTokenProvider;

/**
 * Gets an identity token for the workload's default service account from the
 * GCP metadata server. Works wherever that server is reachable: Compute
 * Engine, GKE, Cloud Run, Cloud Functions and App Engine.
 *
 *     $credentials = new \WorkOS\WorkloadIdentity\Credentials(
 *         identityTokenProvider: GcpMetadata::create('https://api.workos.com'),
 *     );
 *
 * Holds no cache and does no retries: the SDK calls it again for every
 * exchange attempt.
 */
final class GcpMetadata implements IdentityTokenProvider
{
    private const IDENTITY_PATH = '/computeMetadata/v1/instance/service-accounts/default/identity';

    private const TIMEOUT_SECONDS = 10;

    /**
     * Not an option: the metadata server has one address. The tests point
     * this at a mock server.
     */
    private string $baseUrl = 'http://metadata.google.internal';

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
        $url = sprintf(
            '%s%s?audience=%s&format=full',
            $this->baseUrl,
            self::IDENTITY_PATH,
            rawurlencode($this->audience),
        );

        [$status, $body] = self::get($url, [
            'Metadata-Flavor: Google',
            'User-Agent: ' . Version::PACKAGE_NAME . '/' . Version::PACKAGE_VERSION,
        ]);
        if ($status !== 200) {
            throw new \RuntimeException(sprintf('The GCP metadata server returned HTTP %d.', $status));
        }

        $token = trim($body);
        if ($token === '') {
            throw new \RuntimeException('The GCP metadata server returned an empty identity token.');
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
    private static function get(string $url, array $headers): array
    {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new \RuntimeException('The GCP metadata server URL is not valid.');
        }

        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            // The metadata server is link-local. Never send the request to a
            // proxy named by http_proxy or ALL_PROXY.
            CURLOPT_PROXY => '',
        ]);

        $body = curl_exec($curl);
        if (!is_string($body)) {
            throw new \RuntimeException(sprintf(
                'The GCP metadata server could not be reached: %s',
                curl_error($curl),
            ));
        }

        return [(int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE), $body];
    }
}
