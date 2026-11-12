<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity\Aws;

use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
use Aws\Sts\StsClient;
use WorkOS\WorkloadIdentity\IdentityTokenProvider;

/**
 * Gets an identity token for the workload's IAM principal from AWS STS
 * (`GetWebIdentityToken`, outbound identity federation).
 *
 *     $credentials = new \WorkOS\WorkloadIdentity\Credentials(
 *         identityTokenProvider: AwsSts::create('https://api.workos.com', 'us-east-1'),
 *     );
 *
 * The principal needs `sts:GetWebIdentityToken`, and outbound identity
 * federation must be enabled for the account. AWS credentials come from the
 * AWS SDK's default credential chain.
 *
 * Holds no cache and does no retries: the SDK calls it again for every
 * exchange attempt.
 */
final class AwsSts implements IdentityTokenProvider
{
    private const SIGNING_ALGORITHM = 'RS256';

    /** The pseudo-region that selects the global STS endpoint, which does not serve this call. */
    private const GLOBAL_REGION = 'aws-global';

    private function __construct(
        private readonly string $audience,
        private readonly StsClient $client,
    ) {
    }

    /**
     * @param string      $audience The audience to request the token for. It must match the trust rule; the conventional value is `https://api.workos.com`.
     * @param string|null $region   The AWS region whose STS endpoint to call. Optional when the AWS SDK can resolve one, from `AWS_REGION` or the shared config file.
     *
     * @throws \InvalidArgumentException when there is no audience or no region.
     */
    public static function create(string $audience, ?string $region = null): self
    {
        if ($audience === '') {
            throw new \InvalidArgumentException('An audience is required.');
        }

        // Regional, never the global endpoint. Built once, not per call.
        $config = ['sts_regional_endpoints' => 'regional'];
        if ($region !== null && $region !== '') {
            $config['region'] = $region;
        }

        try {
            $client = new StsClient($config);
        } catch (\InvalidArgumentException $e) {
            if (isset($config['region'])) {
                throw $e;
            }

            throw new \InvalidArgumentException(
                'An AWS region is required: pass one to AwsSts::create(), or set AWS_REGION.',
                0,
                $e,
            );
        }

        if ($client->getRegion() === self::GLOBAL_REGION) {
            throw new \InvalidArgumentException(
                'sts:GetWebIdentityToken needs a regional STS endpoint. Use a region such as us-east-1, not aws-global.',
            );
        }

        return new self($audience, $client);
    }

    public function getIdentityToken(): string
    {
        try {
            // DurationSeconds is left unset, so STS applies its default.
            $result = $this->client->getWebIdentityToken([
                'Audience' => [$this->audience],
                'SigningAlgorithm' => self::SIGNING_ALGORITHM,
            ]);
        } catch (AwsException $e) {
            throw new \RuntimeException(
                sprintf('sts:GetWebIdentityToken failed with %s.', $e->getAwsErrorCode() ?? 'an unknown error'),
                0,
                $e,
            );
        } catch (CredentialsException $e) {
            throw new \RuntimeException(
                'sts:GetWebIdentityToken could not be called: no AWS credentials were found.',
                0,
                $e,
            );
        }

        $token = $result['WebIdentityToken'] ?? null;
        if (!is_string($token) || $token === '') {
            throw new \RuntimeException('sts:GetWebIdentityToken returned no token.');
        }

        return $token;
    }
}
