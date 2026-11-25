<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use WorkOS\Credentials as CredentialsContract;
use WorkOS\CredentialSnapshot;
use WorkOS\Exception\WorkloadIdentityException;
use WorkOS\HttpClient;
use WorkOS\Version;

/**
 * Workload identity federation credentials: exchanges the workload's
 * platform-issued JWT for a short-lived WorkOS access token, so no API key
 * is stored.
 *
 * The access token is cached on this instance, in memory only, and replaced
 * on demand when less than `min(60 seconds, 20% of its lifetime)` remains.
 * Two instances never share a token. Settings cannot change after
 * construction; build a new instance instead.
 *
 * Pass the instance to {@see Client::create()} to get a WorkOS client.
 */
final class Credentials implements CredentialsContract
{
    /** Sent with the access token on every API request, when an environment ID is set. */
    public const ENVIRONMENT_HEADER = 'x-url-environment-id';

    public const GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    public const DEFAULT_RESOURCE = 'https://api.workos.com';

    private const REFRESH_THRESHOLD_SECONDS = 60.0;

    private const REFRESH_THRESHOLD_FRACTION = 0.2;

    /** Used when the token response has no usable `expires_in`. */
    private const DEFAULT_LIFETIME_SECONDS = 300.0;

    /** Bounds one whole exchange, including its retries and backoff. */
    private const EXCHANGE_DEADLINE_SECONDS = 30.0;

    /** Stands in for the identity token where the token endpoint's error text repeats it. */
    private const REDACTED = '[redacted]';

    public readonly string $clientId;

    public readonly string $tokenUrl;

    /** Null when no environment ID is set; the environment header is then not sent. */
    public readonly ?string $environmentId;

    public readonly string $resource;

    public readonly ?string $scope;

    /** @var \Closure(): string */
    private readonly \Closure $identityTokenProvider;

    /**
     * A client of its own, so the exchange never passes through middleware
     * installed on the API client's handler stack. Not an option: the tests
     * replace it with one built on a mock handler.
     */
    private GuzzleClient $http;

    private readonly \Closure $clock;

    private readonly \Closure $sleeper;

    private ?string $token = null;

    private float $expiresAt = 0.0;

    private float $lifetime = 0.0;

    private int $generation = 0;

    /**
     * @param IdentityTokenProvider|callable(): string $identityTokenProvider Returns the workload's JWT. Called for every exchange attempt.
     * @param string|null                              $clientId              The M2M application's client ID. Defaults to `WORKOS_CLIENT_ID`.
     * @param string|null                              $tokenUrl              The token exchange endpoint, an `https` URL. Required: there is no built-in default, and no environment variable is read for it.
     * @param string|null                              $environmentId         Optional, and set only here: no environment variable is read for it. When set, it is sent as the `x-url-environment-id` header on every API request; when left out, null or empty, the header is not sent. The server binds a workload identity client to exactly one environment, so the header selects nothing: it is a safety check, and a value that names a different environment gets `403 environment_mismatch`.
     * @param string|null                              $resource              The `resource` form field. Defaults to `https://api.workos.com`.
     * @param string|null                              $scope                 The `scope` form field. Optional, and set only here: no environment variable is read for it. Left out of the form when not set or empty.
     * @param (callable(): float)|null                 $clock                 Seconds on a monotonic clock. Leave unset outside tests.
     * @param (callable(int): void)|null               $sleeper               Waits the given number of microseconds between exchange attempts. Leave unset outside tests.
     *
     * @throws WorkloadIdentityException with `invalid_configuration` when the client ID is missing from both the argument and `WORKOS_CLIENT_ID`, when the token URL is missing, or when the token URL is not an absolute `https` URL.
     */
    public function __construct(
        IdentityTokenProvider|callable $identityTokenProvider,
        ?string $clientId = null,
        ?string $tokenUrl = null,
        ?string $environmentId = null,
        ?string $resource = null,
        ?string $scope = null,
        ?callable $clock = null,
        ?callable $sleeper = null,
    ) {
        $this->identityTokenProvider = $identityTokenProvider instanceof IdentityTokenProvider
            ? $identityTokenProvider->getIdentityToken(...)
            // The return type makes PHP reject a callable that returns anything but a string.
            : static fn (): string => $identityTokenProvider();

        $this->clientId = self::required($clientId, 'clientId', 'WORKOS_CLIENT_ID');
        $this->tokenUrl = self::httpsUrl(self::required($tokenUrl, 'tokenUrl'));
        $this->environmentId = $environmentId !== '' ? $environmentId : null;
        $this->resource = $resource !== null && $resource !== '' ? $resource : self::DEFAULT_RESOURCE;
        $this->scope = $scope !== '' ? $scope : null;

        $this->http = new GuzzleClient();
        $this->clock = $clock !== null ? \Closure::fromCallable($clock) : static fn (): float => hrtime(true) / 1_000_000_000;
        $this->sleeper = $sleeper !== null ? \Closure::fromCallable($sleeper) : static function (int $microseconds): void {
            usleep($microseconds);
        };
    }

    public function resolve(): CredentialSnapshot
    {
        if (!$this->isFresh()) {
            $this->refresh();
        }

        $headers = ['Authorization' => sprintf('Bearer %s', (string) $this->token)];
        if ($this->environmentId !== null) {
            $headers[self::ENVIRONMENT_HEADER] = $this->environmentId;
        }

        return new CredentialSnapshot($headers, $this->generation, true);
    }

    public function invalidate(int $generation): void
    {
        // Conditional on the generation: a late 401 for an old token must not
        // discard a newer one.
        if ($this->generation === $generation) {
            $this->expiresAt = 0.0;
        }
    }

    private function isFresh(): bool
    {
        if ($this->token === null) {
            return false;
        }

        $threshold = min(self::REFRESH_THRESHOLD_SECONDS, self::REFRESH_THRESHOLD_FRACTION * $this->lifetime);

        return ($this->expiresAt - ($this->clock)()) >= $threshold;
    }

    /**
     * Exchange for a new token and store it. A failed exchange stores nothing,
     * so the next call starts a new one.
     */
    private function refresh(): void
    {
        [$token, $lifetime] = $this->exchange();

        $this->token = $token;
        $this->lifetime = $lifetime;
        $this->expiresAt = ($this->clock)() + $lifetime;
        $this->generation++;
    }

    /**
     * @return array{string, float} The access token and its lifetime in seconds.
     */
    private function exchange(): array
    {
        $deadline = ($this->clock)() + self::EXCHANGE_DEADLINE_SECONDS;
        $failure = null;
        $retryAfter = null;

        for ($attempt = 0; $attempt <= HttpClient::DEFAULT_MAX_RETRIES; $attempt++) {
            if ($attempt > 0) {
                $delay = HttpClient::retryDelayMicroseconds($attempt - 1, $retryAfter);
                if (($this->clock)() + $delay / 1_000_000 >= $deadline) {
                    break;
                }
                ($this->sleeper)($delay);
            }
            $retryAfter = null;

            try {
                // Called again on every attempt: the token may have rotated.
                $assertion = ($this->identityTokenProvider)();
                if ($assertion === '') {
                    throw new \UnexpectedValueException('The identity token provider returned an empty token.');
                }
            } catch (\Throwable $e) {
                $failure = new WorkloadIdentityException(WorkloadIdentityException::PROVIDER_FAILED, $e->getMessage(), $e);
                continue;
            }

            $remaining = $deadline - ($this->clock)();
            if ($remaining <= 0) {
                break;
            }

            try {
                $response = $this->postForm($assertion, $remaining);
            } catch (GuzzleException $e) {
                $failure = new WorkloadIdentityException(WorkloadIdentityException::EXCHANGE_FAILED, $e->getMessage(), $e);
                continue;
            }

            // The status is checked before the body: a 429 or 5xx is retried
            // even when its body carries an OAuth error.
            $status = $response->getStatusCode();
            if ($status === 429 || $status >= 500) {
                $retryAfter = $response->getHeaderLine('Retry-After');
                $failure = self::retryableFailure($response, $assertion);
                continue;
            }

            return self::parse($response, $assertion);
        }

        throw $failure ?? new WorkloadIdentityException(
            WorkloadIdentityException::EXCHANGE_FAILED,
            'The exchange did not finish before its deadline.',
        );
    }

    /**
     * One exchange POST. No client secret, no `Authorization` header and no
     * API key; redirects are never followed.
     */
    private function postForm(
        #[\SensitiveParameter]
        string $assertion,
        float $timeout,
    ): ResponseInterface {
        $form = [
            'grant_type' => self::GRANT_TYPE,
            'assertion' => $assertion,
            'client_id' => $this->clientId,
            'resource' => $this->resource,
        ];
        if ($this->scope !== null) {
            $form['scope'] = $this->scope;
        }

        // Sent as a prepared request, so the assertion is never an argument
        // of a Guzzle call that could surface in a stack trace.
        $request = new Request(
            'POST',
            $this->tokenUrl,
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
                'User-Agent' => sprintf('%s/%s', Version::SDK_IDENTIFIER, Version::SDK_VERSION),
            ],
            http_build_query($form, '', '&'),
        );

        return $this->http->send($request, [
            'allow_redirects' => false,
            'http_errors' => false,
            'timeout' => $timeout,
        ]);
    }

    /**
     * Turn a response that is not a 429 or a 5xx into a token, or throw.
     * Nothing thrown here is retried.
     *
     * @return array{string, float}
     */
    private static function parse(
        ResponseInterface $response,
        #[\SensitiveParameter]
        string $assertion,
    ): array {
        $status = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $error = self::oauthError($body, $assertion);
        if ($error !== null) {
            throw $error;
        }

        if ($status >= 300 && $status < 400) {
            throw new WorkloadIdentityException(
                WorkloadIdentityException::EXCHANGE_FAILED,
                sprintf('The token endpoint answered with a redirect (HTTP %d), which is not followed.', $status),
            );
        }

        if ($status !== 200) {
            throw new WorkloadIdentityException(
                WorkloadIdentityException::INVALID_RESPONSE,
                sprintf('The token endpoint returned HTTP %d.', $status),
            );
        }

        if (!is_array($body)) {
            throw new WorkloadIdentityException(
                WorkloadIdentityException::INVALID_RESPONSE,
                'The token response was not a JSON object.',
            );
        }

        $token = $body['access_token'] ?? null;
        if (!is_string($token) || $token === '') {
            throw new WorkloadIdentityException(
                WorkloadIdentityException::INVALID_RESPONSE,
                'The token response had no access_token.',
            );
        }

        $expiresIn = $body['expires_in'] ?? null;
        $lifetime = (is_int($expiresIn) || is_float($expiresIn)) && $expiresIn > 0
            ? (float) $expiresIn
            : self::DEFAULT_LIFETIME_SECONDS;

        return [$token, $lifetime];
    }

    /**
     * What to report if this 429 or 5xx turns out to be the last attempt.
     */
    private static function retryableFailure(
        ResponseInterface $response,
        #[\SensitiveParameter]
        string $assertion,
    ): WorkloadIdentityException {
        $body = json_decode((string) $response->getBody(), true);

        return self::oauthError($body, $assertion) ?? new WorkloadIdentityException(
            WorkloadIdentityException::EXCHANGE_FAILED,
            sprintf('The token endpoint returned HTTP %d.', $response->getStatusCode()),
        );
    }

    /**
     * The error a decoded body with a string `error` field describes, or null
     * when it has none.
     *
     * The code and the description are the token endpoint's own text, and an
     * endpoint may repeat the identity token it was sent. The token is taken
     * out here, so no error carries it into a message or a log. The body is
     * marked sensitive for the same reason: it would otherwise be an argument
     * in the error's stack trace.
     */
    private static function oauthError(
        #[\SensitiveParameter]
        mixed $body,
        #[\SensitiveParameter]
        string $assertion,
    ): ?WorkloadIdentityException {
        if (!is_array($body) || !isset($body['error']) || !is_string($body['error']) || $body['error'] === '') {
            return null;
        }

        $description = $body['error_description'] ?? null;

        // Taken out here, before the exception cuts the description to its
        // limit, so a cut cannot leave the start of a token behind.
        return new WorkloadIdentityException(
            self::withoutAssertion($body['error'], $assertion),
            is_string($description) ? self::withoutAssertion($description, $assertion) : '',
        );
    }

    /**
     * The token endpoint's text with every occurrence of the identity token
     * that this attempt sent replaced.
     */
    private static function withoutAssertion(
        #[\SensitiveParameter]
        string $text,
        #[\SensitiveParameter]
        string $assertion,
    ): string {
        return $assertion === '' ? $text : str_replace($assertion, self::REDACTED, $text);
    }

    /**
     * A required setting. Only a setting that names a variable falls back to
     * the environment; the client ID is the one that does.
     */
    private static function required(?string $value, string $field, ?string $variable = null): string
    {
        if ($variable !== null) {
            $value ??= self::env($variable);
        }
        if ($value === null || $value === '') {
            throw new WorkloadIdentityException(
                WorkloadIdentityException::INVALID_CONFIGURATION,
                $variable !== null
                    ? sprintf('%s is required (or set %s)', $field, $variable)
                    : sprintf('%s is required', $field),
            );
        }

        return $value;
    }

    /**
     * A token URL without a scheme or a host would fail on first use with an
     * error that says nothing about the setting, so it is rejected here.
     */
    private static function httpsUrl(string $tokenUrl): string
    {
        $parts = parse_url($tokenUrl);
        $scheme = is_array($parts) ? strtolower($parts['scheme'] ?? '') : '';
        // https only: the POST carries the workload's identity token and the
        // response carries the access token.
        if ($scheme !== 'https' || ($parts['host'] ?? '') === '') {
            throw new WorkloadIdentityException(
                WorkloadIdentityException::INVALID_CONFIGURATION,
                'tokenUrl must be an absolute https URL',
            );
        }

        return $tokenUrl;
    }

    private static function env(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
