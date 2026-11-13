<?php

declare(strict_types=1);
// @oagen-ignore-file

namespace Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use WorkOS\Credentials as CredentialsContract;
use WorkOS\Exception\AuthenticationException;
use WorkOS\Exception\WorkloadIdentityException;
use WorkOS\HttpClient;
use WorkOS\WorkloadIdentity\Client;
use WorkOS\WorkloadIdentity\Credentials;
use WorkOS\WorkloadIdentity\CredentialsMiddleware;
use WorkOS\WorkloadIdentity\IdentityTokenProvider;
use WorkOS\WorkloadIdentity\RecoveryState;
use WorkOS\WorkloadIdentity\TokenEnv;
use WorkOS\WorkloadIdentity\TokenFile;
use WorkOS\WorkOS;

/**
 * The workload identity conformance cases, WIF-01 to WIF-24. Each case ID is
 * in the name of the test that covers it.
 *
 * The token endpoint and the WorkOS API are both Guzzle mock handlers. Time is
 * an injected clock and backoff is an injected sleeper, so no test waits.
 */
class WorkloadIdentityTest extends TestCase
{
    private const TOKEN_URL = 'https://auth.workos.test/oauth2/token';

    private const ENV_VARS = [
        'WORKOS_API_KEY',
        'WORKOS_CLIENT_ID',
        'WORKOS_WIF_TOKEN_URL',
        'WORKOS_WIF_SCOPE',
        'WORKOS_ENVIRONMENT_ID',
        'MY_JWT',
    ];

    /** @var list<RequestInterface> Every POST the mock token endpoint received. */
    private array $tokenRequests = [];

    /** @var list<float|int|null> The timeout each exchange POST was sent with. */
    private array $tokenTimeouts = [];

    /** @var list<RequestInterface> Every request the mock API received, as the handler got it. */
    private array $apiHistory = [];

    private ?MockHandler $apiMock = null;

    private int $providerCalls = 0;

    private float $now = 0.0;

    /** @var list<int> Microseconds the exchange asked to wait. */
    private array $sleeps = [];

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    private ?string $savedStaticKey = null;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        foreach (self::ENV_VARS as $name) {
            $this->savedEnv[$name] = getenv($name);
            putenv($name);
        }
        $this->savedStaticKey = WorkOS::getApiKey();
        WorkOS::setApiKey(null);
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $name => $value) {
            putenv($value === false ? $name : sprintf('%s=%s', $name, $value));
        }
        WorkOS::setApiKey($this->savedStaticKey);
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    public function testWIF_01_FirstRequestSendsOneExchangePostWithTheRightShape(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));
        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertCount(1, $this->tokenRequests);
        $post = $this->tokenRequests[0];
        $this->assertSame('POST', $post->getMethod());
        $this->assertSame(self::TOKEN_URL, (string) $post->getUri());
        $this->assertSame('application/x-www-form-urlencoded', $post->getHeaderLine('Content-Type'));
        $this->assertFalse($post->hasHeader('Authorization'));
        $this->assertSame(
            [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => 'assertion-1',
                'client_id' => 'client_wif_test',
                'resource' => 'https://api.workos.com',
            ],
            self::form($post),
        );

        $this->tokenRequests = [];
        $scoped = $this->credentials(
            $this->tokenEndpoint([self::tokenResponse('access-1')]),
            scope: 'orgs:write',
        );
        $this->client($scoped, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertCount(1, $this->tokenRequests);
        $form = self::form($this->tokenRequests[0]);
        $this->assertSame('orgs:write', $form['scope']);
        $this->assertArrayNotHasKey('client_secret', $form);
        $this->assertFalse($this->tokenRequests[0]->hasHeader('Authorization'));
    }

    public function testWIF_02_a_ApiRequestCarriesTheAccessTokenAndTheEnvironmentHeader(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));
        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertSame(['Bearer access-1'], $this->apiAuthorizations());
        $this->assertSame('environment_wif_test', $this->apiRequest(0)->getHeaderLine('x-url-environment-id'));
    }

    public function testWIF_02_b_WithNoEnvironmentIdTheEnvironmentHeaderIsNotSent(): void
    {
        // Unset here as well as in setUp(), which also saves the value that tearDown() restores.
        putenv('WORKOS_ENVIRONMENT_ID');

        // Constructing the credentials with no environment ID is not an error.
        $credentials = $this->credentials(
            $this->tokenEndpoint([self::tokenResponse('access-1')]),
            environmentId: null,
        );
        $this->assertNull($credentials->environmentId);

        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertSame(['Bearer access-1'], $this->apiAuthorizations());
        $this->assertFalse($this->apiRequest(0)->hasHeader('x-url-environment-id'));
    }

    public function testAnEmptyEnvironmentIdCountsAsNotSet(): void
    {
        // An empty variable, then an empty argument.
        putenv('WORKOS_ENVIRONMENT_ID=');
        $fromEnvironment = $this->credentials(
            $this->tokenEndpoint([self::tokenResponse('access-1')]),
            environmentId: null,
        );
        $explicit = $this->credentials(
            $this->tokenEndpoint([self::tokenResponse('access-2')]),
            environmentId: '',
        );

        foreach ([$fromEnvironment, $explicit] as $index => $credentials) {
            $this->assertNull($credentials->environmentId);
            $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');
            $this->assertFalse($this->apiRequest($index)->hasHeader('x-url-environment-id'));
        }
        $this->assertSame(['Bearer access-1', 'Bearer access-2'], $this->apiAuthorizations());
    }

    public function testWIF_03_SecondRequestWithinTheLifetimeReusesTheCachedToken(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));
        $workos = $this->client($credentials, [self::organization(), self::organization()]);

        $workos->organizations()->getOrganization('org_01');
        $workos->organizations()->getOrganization('org_01');

        $this->assertCount(1, $this->tokenRequests);
        $this->assertSame(['Bearer access-1', 'Bearer access-1'], $this->apiAuthorizations());
    }

    public function testWIF_04_TokenIsRefreshedWhenLessThanSixtySecondsRemain(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::tokenResponse('access-1', 300),
            self::tokenResponse('access-2', 300),
        ]));
        $workos = $this->client($credentials, [self::organization(), self::organization(), self::organization()]);

        $workos->organizations()->getOrganization('org_01');
        $this->now = 239.0;
        $workos->organizations()->getOrganization('org_01');
        $this->assertCount(1, $this->tokenRequests);

        $this->now = 241.0;
        $workos->organizations()->getOrganization('org_01');
        $this->assertCount(2, $this->tokenRequests);
        $this->assertSame('Bearer access-2', $this->apiAuthorizations()[2]);
    }

    public function testWIF_05_RefreshThresholdIsTwentyPercentOfAShortLifetime(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::tokenResponse('access-1', 100),
            self::tokenResponse('access-2', 100),
        ]));
        $workos = $this->client($credentials, [self::organization(), self::organization(), self::organization()]);

        $workos->organizations()->getOrganization('org_01');
        $this->now = 79.0;
        $workos->organizations()->getOrganization('org_01');
        $this->assertCount(1, $this->tokenRequests);

        $this->now = 81.0;
        $workos->organizations()->getOrganization('org_01');
        $this->assertCount(2, $this->tokenRequests);
    }

    public function testWIF_06_MissingExpiresInIsTreatedAsThreeHundredSeconds(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::tokenResponse('access-1', null),
            self::tokenResponse('access-2', null),
        ]));
        $workos = $this->client($credentials, [self::organization(), self::organization(), self::organization()]);

        $workos->organizations()->getOrganization('org_01');
        $this->now = 239.0;
        $workos->organizations()->getOrganization('org_01');
        $this->assertCount(1, $this->tokenRequests);

        $this->now = 241.0;
        $workos->organizations()->getOrganization('org_01');
        $this->assertCount(2, $this->tokenRequests);
    }

    public function testWIF_07_TwoCredentialsInstancesNeverShareACachedToken(): void
    {
        $endpoint = $this->tokenEndpoint([self::tokenResponse('access-1'), self::tokenResponse('access-2')]);
        $first = $this->client($this->credentials($endpoint), [self::organization()]);
        $second = $this->client($this->credentials($endpoint), [self::organization()]);

        $first->organizations()->getOrganization('org_01');
        $second->organizations()->getOrganization('org_01');

        $this->assertCount(2, $this->tokenRequests);
        $this->assertSame(['Bearer access-1', 'Bearer access-2'], $this->apiAuthorizations());
    }

    /**
     * PHP handles one request at a time, so "N concurrent first requests"
     * becomes two resolves one after the other.
     */
    public function testWIF_08_TwoSequentialResolvesWithinTheLifetimeProduceOneExchange(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));

        $first = $credentials->resolve();
        $second = $credentials->resolve();

        $this->assertCount(1, $this->tokenRequests);
        $this->assertSame(1, $this->providerCalls);
        $this->assertSame('Bearer access-1', $first->headers['Authorization']);
        $this->assertSame('Bearer access-1', $second->headers['Authorization']);
        $this->assertSame($first->generation, $second->generation);
    }

    public function testWIF_09_A401InvalidatesTheGenerationExchangesAgainAndRetriesOnce(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::tokenResponse('access-1'),
            self::tokenResponse('access-2'),
        ]));
        $workos = $this->client($credentials, [self::unauthorized(), self::organization()]);

        $organization = $workos->organizations()->getOrganization('org_01');

        $this->assertSame('org_01EHWNCE74X7JSDV0X3SZ3KJNY', $organization->id);
        $this->assertCount(2, $this->tokenRequests);
        $this->assertSame(['Bearer access-1', 'Bearer access-2'], $this->apiAuthorizations());
    }

    public function testWIF_10_ASecond401AfterRecoveryIsReturnedToTheCaller(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::tokenResponse('access-1'),
            self::tokenResponse('access-2'),
        ]));
        // The third response would be a success; it must never be asked for.
        $workos = $this->client($credentials, [self::unauthorized(), self::unauthorized(), self::organization()]);

        try {
            $workos->organizations()->getOrganization('org_01');
            $this->fail('Expected AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->statusCode);
        }

        $this->assertCount(2, $this->tokenRequests);
        $this->assertCount(2, $this->apiHistory);
        $this->assertSame(1, $this->apiMock?->count());
    }

    public function testWIF_11_ALate401ForAnOldGenerationDoesNotDiscardANewerToken(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::tokenResponse('access-1'),
            self::tokenResponse('access-2'),
        ]));

        $first = $credentials->resolve();
        $credentials->invalidate($first->generation);
        $second = $credentials->resolve();
        $this->assertSame('Bearer access-2', $second->headers['Authorization']);
        $this->assertNotSame($first->generation, $second->generation);

        // What a late 401 for the first token would do.
        $credentials->invalidate($first->generation);
        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertCount(2, $this->tokenRequests);
        $this->assertSame(['Bearer access-2'], $this->apiAuthorizations());
        $this->assertSame($second->generation, $credentials->resolve()->generation);
    }

    public function testWIF_12_TransportRetriesDoNotResetTheSingle401RecoveryAllowance(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::tokenResponse('access-1'),
            self::tokenResponse('access-2'),
        ]));
        $workos = $this->client($credentials, [
            self::unauthorized(),
            new Response(503, ['Content-Type' => 'application/json', 'Retry-After' => '0'], '{"message":"unavailable"}'),
            self::unauthorized(),
            // Would succeed, but a fourth attempt must never be made.
            self::organization(),
        ]);

        try {
            $workos->organizations()->getOrganization('org_01');
            $this->fail('Expected AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->statusCode);
        }

        $this->assertCount(2, $this->tokenRequests);
        $this->assertCount(3, $this->apiHistory);
        $this->assertSame(1, $this->apiMock?->count());
        $this->assertSame(['Bearer access-1', 'Bearer access-2', 'Bearer access-2'], $this->apiAuthorizations());
    }

    public function testWIF_13_The401RetrySendsTheSameBodyAndTheSameIdempotencyKey(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::tokenResponse('access-1'),
            self::tokenResponse('access-2'),
        ]));
        $workos = $this->client($credentials, [self::unauthorized(), self::organization()]);

        $workos->organizations()->createOrganization(name: 'Acme Inc.');

        $this->assertCount(2, $this->apiHistory);
        $first = $this->apiRequest(0);
        $second = $this->apiRequest(1);
        $this->assertSame('{"name":"Acme Inc."}', (string) $first->getBody());
        $this->assertSame((string) $first->getBody(), (string) $second->getBody());
        $this->assertNotSame('', $first->getHeaderLine('Idempotency-Key'));
        $this->assertSame($first->getHeaderLine('Idempotency-Key'), $second->getHeaderLine('Idempotency-Key'));
        $this->assertSame($first->getMethod(), $second->getMethod());
        $this->assertSame((string) $first->getUri(), (string) $second->getUri());
    }

    public function testWIF_14_a_ExchangeIsRetriedOn503And429AndCallsTheProviderEachAttempt(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            new Response(503, [], 'unavailable'),
            new Response(429, ['Retry-After' => '0'], 'slow down'),
            self::tokenResponse('access-1'),
        ]));

        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertCount(3, $this->tokenRequests);
        $this->assertSame(3, $this->providerCalls);
        $this->assertSame(['Bearer access-1'], $this->apiAuthorizations());
        // Retry-After: 0 is honored: no wait before the third attempt.
        $this->assertCount(2, $this->sleeps);
        $this->assertSame(0, $this->sleeps[1]);
    }

    public function testWIF_14_b_ExchangeIsRetriedOnConnectionFailure(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            new ConnectException('cURL error 7: Failed to connect', new Request('POST', self::TOKEN_URL)),
            self::tokenResponse('access-1'),
        ]));

        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertSame(2, $this->providerCalls);
        $this->assertSame(['Bearer access-1'], $this->apiAuthorizations());
    }

    public function testWIF_14_c_ProviderFailureIsRetriedAndTheProviderIsCalledAgain(): void
    {
        $provider = function (): string {
            $this->providerCalls++;
            if ($this->providerCalls === 1) {
                throw new \RuntimeException('metadata service unavailable');
            }

            return 'assertion-2';
        };
        $credentials = $this->credentials(
            $this->tokenEndpoint([self::tokenResponse('access-1')]),
            identityTokenProvider: $provider,
        );

        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertSame(2, $this->providerCalls);
        $this->assertCount(1, $this->tokenRequests);
        $this->assertSame('assertion-2', self::form($this->tokenRequests[0])['assertion']);
    }

    public function testWIF_14_d_ExchangeWaitsForRetryAfterBeforeTheNextAttempt(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            new Response(429, ['Retry-After' => '1'], 'slow down'),
            self::tokenResponse('access-1'),
        ]));

        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertCount(2, $this->tokenRequests);
        $this->assertCount(1, $this->sleeps);
        $this->assertGreaterThanOrEqual(1_000_000, $this->sleeps[0]);
    }

    #[DataProvider('oauthErrorCodes')]
    public function testWIF_15_AnOAuthErrorIsNotRetriedAndSurfacesAsATypedError(string $code): void
    {
        $description = sprintf('The server said %s.', $code);
        $credentials = $this->credentials($this->tokenEndpoint([
            self::oauthError($code, $description),
            // Would succeed, but an OAuth error must not be retried.
            self::tokenResponse('access-1'),
        ]));
        $workos = $this->client($credentials, [self::organization()]);

        try {
            $workos->organizations()->getOrganization('org_01');
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame($code, $e->errorCode);
            $this->assertSame($description, $e->description);
            $this->assertNotSame('', $e->hint);
            $this->assertSame(sprintf('%s: %s %s', $code, $e->hint, $description), $e->getMessage());
        }

        $this->assertCount(1, $this->tokenRequests);
        $this->assertCount(0, $this->apiHistory);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function oauthErrorCodes(): array
    {
        return [
            'invalid_grant' => ['invalid_grant'],
            'invalid_target' => ['invalid_target'],
            'invalid_client' => ['invalid_client'],
            'unauthorized_client' => ['unauthorized_client'],
            'invalid_scope' => ['invalid_scope'],
            'invalid_request' => ['invalid_request'],
        ];
    }

    public function testWIF_16_AfterAFailedExchangeTheNextRequestExchangesAgain(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::oauthError('invalid_grant'),
            self::tokenResponse('access-1'),
        ]));
        $workos = $this->client($credentials, [self::organization()]);

        try {
            $workos->organizations()->getOrganization('org_01');
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame('invalid_grant', $e->errorCode);
        }

        $workos->organizations()->getOrganization('org_01');

        $this->assertCount(2, $this->tokenRequests);
        $this->assertSame(['Bearer access-1'], $this->apiAuthorizations());
    }

    public function testWIF_17_NeitherTokenAppearsInAnyErrorMessageOrLogLine(): void
    {
        $assertion = 'SECRET-ASSERTION-VALUE';
        $access = 'SECRET-ACCESS-VALUE';
        $provider = static fn (): string => $assertion;

        // Stack traces carry call arguments unless this is on; turn it off so
        // the test sees what a default development setup would print.
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        // The SDK has no logger. Capture everything it could write to instead.
        $logFile = $this->tempFile();
        $errorLog = ini_set('error_log', $logFile);
        ob_start();

        try {
            $errors = [
                'invalid_grant' => $this->failureOf(function () use ($provider): void {
                    $credentials = $this->credentials(
                        $this->tokenEndpoint([self::oauthError('invalid_grant')]),
                        identityTokenProvider: $provider,
                    );
                    $this->client($credentials, [])->organizations()->getOrganization('org_01');
                }),
                '500 until retries run out' => $this->failureOf(function () use ($provider, $assertion): void {
                    $responses = [];
                    for ($i = 0; $i <= HttpClient::DEFAULT_MAX_RETRIES; $i++) {
                        // A body that echoes the assertion must never be repeated.
                        $responses[] = new Response(500, ['Retry-After' => '0'], 'rejected ' . $assertion);
                    }
                    $credentials = $this->credentials($this->tokenEndpoint($responses), identityTokenProvider: $provider);
                    $this->client($credentials, [])->organizations()->getOrganization('org_01');
                }),
                'exchange cannot connect' => $this->failureOf(function () use ($provider): void {
                    // Created while the exchange POST is on the stack, as a real
                    // connection failure is, so its trace has the frames that carry the assertion.
                    $refuse = static fn (RequestInterface $request): ConnectException => new ConnectException(
                        'cURL error 7: Failed to connect',
                        $request,
                    );
                    $credentials = $this->credentials(
                        $this->tokenEndpoint(array_fill(0, HttpClient::DEFAULT_MAX_RETRIES + 1, $refuse)),
                        identityTokenProvider: $provider,
                    );
                    $this->client($credentials, [])->organizations()->getOrganization('org_01');
                }),
                'provider raises' => $this->failureOf(function (): void {
                    $credentials = $this->credentials(
                        $this->tokenEndpoint([]),
                        identityTokenProvider: static fn (): string => throw new \RuntimeException('metadata service unavailable'),
                    );
                    $this->client($credentials, [])->organizations()->getOrganization('org_01');
                }),
                'second 401 from the API' => $this->failureOf(function () use ($provider, $access): void {
                    $credentials = $this->credentials(
                        $this->tokenEndpoint([self::tokenResponse($access), self::tokenResponse($access)]),
                        identityTokenProvider: $provider,
                    );
                    $workos = $this->client($credentials, [self::unauthorized(), self::unauthorized()]);
                    $workos->organizations()->getOrganization('org_01');
                }),
            ];
        } finally {
            $output = (string) ob_get_clean();
            if ($ignoreArgs !== false) {
                ini_set('zend.exception_ignore_args', $ignoreArgs);
            }
            if ($errorLog !== false) {
                ini_set('error_log', $errorLog);
            }
        }

        $this->assertInstanceOf(WorkloadIdentityException::class, $errors['invalid_grant']);
        $this->assertInstanceOf(WorkloadIdentityException::class, $errors['500 until retries run out']);
        $this->assertInstanceOf(WorkloadIdentityException::class, $errors['exchange cannot connect']);
        $this->assertInstanceOf(ConnectException::class, $errors['exchange cannot connect']->getPrevious());
        $this->assertInstanceOf(WorkloadIdentityException::class, $errors['provider raises']);
        $this->assertInstanceOf(AuthenticationException::class, $errors['second 401 from the API']);

        $written = $output . (string) file_get_contents($logFile);
        foreach ([$assertion, $access] as $secret) {
            $this->assertStringNotContainsString($secret, $written);
            foreach ($errors as $label => $error) {
                $this->assertNoSecret($secret, $error, $label);
            }
        }
    }

    public function testWIF_18_TheExchangeDoesNotFollowRedirects(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            new Response(307, ['Location' => 'https://elsewhere.test/oauth2/token']),
            // What a followed redirect would receive.
            self::tokenResponse('access-1'),
        ]));
        $workos = $this->client($credentials, [self::organization()]);

        try {
            $workos->organizations()->getOrganization('org_01');
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(WorkloadIdentityException::EXCHANGE_FAILED, $e->errorCode);
        }

        $this->assertCount(1, $this->tokenRequests);
        $this->assertSame('POST', $this->tokenRequests[0]->getMethod());
        $this->assertSame('auth.workos.test', $this->tokenRequests[0]->getUri()->getHost());
        $this->assertCount(0, $this->apiHistory);
    }

    /**
     * The credentials entry point is a separate factory, so a key and
     * credentials can never be passed together. The case asserts that
     * instead, and that no static key is ever sent.
     */
    public function testWIF_19_TheFactoryAcceptsNoApiKeyAndItsClientNeverSendsOne(): void
    {
        $parameters = array_map(
            static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
            (new \ReflectionMethod(Client::class, 'create'))->getParameters(),
        );
        $this->assertNotContains('apiKey', $parameters);

        putenv('WORKOS_API_KEY=sk_test_env');
        WorkOS::setApiKey('sk_test_static');
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));

        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertSame(['Bearer access-1'], $this->apiAuthorizations());
    }

    public function testWIF_20_CredentialsWinOverTheEnvironmentKeyAndNeverFallBackToIt(): void
    {
        putenv('WORKOS_API_KEY=sk_test_env');

        // (a) Valid credentials.
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));
        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');
        $this->assertSame(['Bearer access-1'], $this->apiAuthorizations());

        // (b) No token URL, explicitly or in the environment.
        try {
            new Credentials(
                identityTokenProvider: $this->provider('assertion-1'),
                clientId: 'client_wif_test',
                environmentId: 'environment_wif_test',
            );
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(WorkloadIdentityException::INVALID_CONFIGURATION, $e->errorCode);
            $this->assertSame('tokenUrl is required (or set WORKOS_WIF_TOKEN_URL)', $e->description);
        }

        // (c) An exchange that fails.
        $failing = $this->credentials($this->tokenEndpoint([self::oauthError('invalid_client')]));
        try {
            $this->client($failing, [self::organization()])->organizations()->getOrganization('org_01');
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame('invalid_client', $e->errorCode);
        }

        // Only (a) reached the API, and nothing carried the environment key.
        $this->assertCount(1, $this->apiHistory);
        foreach ($this->apiAuthorizations() as $authorization) {
            $this->assertStringNotContainsString('sk_test_env', $authorization);
        }
        foreach ($this->tokenRequests as $post) {
            $this->assertFalse($post->hasHeader('Authorization'));
            $this->assertStringNotContainsString('sk_test_env', (string) $post->getBody());
        }
    }

    public function testWIF_21_TokenFileRereadsTheFileOnEveryExchange(): void
    {
        $path = $this->tempFile();
        file_put_contents($path, "assertion-1\n");
        $credentials = $this->credentials(
            $this->tokenEndpoint([self::tokenResponse('access-1'), self::tokenResponse('access-2')]),
            identityTokenProvider: TokenFile::create($path),
        );
        $workos = $this->client($credentials, [self::organization(), self::organization()]);

        $workos->organizations()->getOrganization('org_01');
        file_put_contents($path, "  assertion-2\n");
        $this->now = 241.0;
        $workos->organizations()->getOrganization('org_01');

        $this->assertCount(2, $this->tokenRequests);
        $this->assertSame('assertion-1', self::form($this->tokenRequests[0])['assertion']);
        $this->assertSame('assertion-2', self::form($this->tokenRequests[1])['assertion']);
    }

    public function testWIF_22_a_TokenEnvSuppliesTheAssertion(): void
    {
        putenv('MY_JWT=assertion-env');
        $credentials = $this->credentials(
            $this->tokenEndpoint([self::tokenResponse('access-1')]),
            identityTokenProvider: TokenEnv::create('MY_JWT'),
        );

        $credentials->resolve();

        $this->assertSame('assertion-env', self::form($this->tokenRequests[0])['assertion']);
    }

    public function testWIF_22_b_ACallbackSuppliesTheAssertion(): void
    {
        $credentials = $this->credentials(
            $this->tokenEndpoint([self::tokenResponse('access-1')]),
            identityTokenProvider: static fn (): string => 'assertion-cb',
        );

        $credentials->resolve();

        $this->assertSame('assertion-cb', self::form($this->tokenRequests[0])['assertion']);
    }

    public function testWIF_22_c_OmittedFieldsDefaultFromTheEnvironment(): void
    {
        putenv('WORKOS_CLIENT_ID=client_env');
        putenv('WORKOS_WIF_TOKEN_URL=https://env.workos.test/oauth2/token');
        putenv('WORKOS_WIF_SCOPE=scope_env');
        putenv('WORKOS_ENVIRONMENT_ID=environment_env');

        $credentials = $this->exchangingWith(
            $this->tokenEndpoint([self::tokenResponse('access-1')]),
            new Credentials($this->provider('assertion-1'), ...$this->seams()),
        );
        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $this->assertSame('https://env.workos.test/oauth2/token', (string) $this->tokenRequests[0]->getUri());
        $form = self::form($this->tokenRequests[0]);
        $this->assertSame('client_env', $form['client_id']);
        $this->assertSame('scope_env', $form['scope']);
        $this->assertSame('environment_env', $this->apiRequest(0)->getHeaderLine('x-url-environment-id'));
    }

    public function testWIF_22_d_ExplicitValuesWinOverTheEnvironment(): void
    {
        putenv('WORKOS_CLIENT_ID=client_env');
        putenv('WORKOS_WIF_TOKEN_URL=https://env.workos.test/oauth2/token');
        putenv('WORKOS_WIF_SCOPE=scope_env');
        putenv('WORKOS_ENVIRONMENT_ID=environment_env');

        $credentials = $this->exchangingWith(
            $this->tokenEndpoint([self::tokenResponse('access-1')]),
            new Credentials(
                $this->provider('assertion-1'),
                ...$this->seams(),
                clientId: 'client_explicit',
                scope: 'scope_explicit',
                environmentId: 'environment_explicit',
            ),
        );
        $this->client($credentials, [self::organization()])->organizations()->getOrganization('org_01');

        $form = self::form($this->tokenRequests[0]);
        $this->assertSame('client_explicit', $form['client_id']);
        $this->assertSame('scope_explicit', $form['scope']);
        $this->assertSame('environment_explicit', $this->apiRequest(0)->getHeaderLine('x-url-environment-id'));
    }

    /**
     * PHP has no cancellation, so "canceling one waiting caller" becomes:
     * caller A's exchange times out, and caller B then finds nothing of A's
     * failure in its way.
     */
    public function testWIF_23_ARequestTimeoutLeavesTheCacheUsableForTheNextCall(): void
    {
        $timeouts = [];
        for ($i = 0; $i <= HttpClient::DEFAULT_MAX_RETRIES; $i++) {
            $timeouts[] = new ConnectException(
                'cURL error 28: Operation timed out after 30001 milliseconds',
                new Request('POST', self::TOKEN_URL),
            );
        }
        $credentials = $this->credentials($this->tokenEndpoint([...$timeouts, self::tokenResponse('access-1')]));
        $workos = $this->client($credentials, [self::organization(), self::organization()]);

        // Caller A: the slow exchange.
        try {
            $workos->organizations()->getOrganization('org_01');
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(WorkloadIdentityException::EXCHANGE_FAILED, $e->errorCode);
            $this->assertInstanceOf(ConnectException::class, $e->getPrevious());
            $this->assertStringContainsString('timed out', $e->getMessage());
        }
        $this->assertCount(0, $this->apiHistory);

        // Caller B: a fast endpoint.
        $workos->organizations()->getOrganization('org_01');
        $this->assertSame(['Bearer access-1'], $this->apiAuthorizations());

        // One successful exchange, and its token is cached like any other.
        $this->assertCount(count($timeouts) + 1, $this->tokenRequests);
        $workos->organizations()->getOrganization('org_01');
        $this->assertCount(count($timeouts) + 1, $this->tokenRequests);
        $this->assertSame(1, $credentials->resolve()->generation);
    }

    public function testWIF_24_StaticApiKeyClientsAreUnchanged(): void
    {
        $mock = new MockHandler([
            self::organization(),
            self::unauthorized(),
            // Would succeed, but a static key never retries a 401.
            self::organization(),
        ]);
        $stack = HandlerStack::create($this->recording($mock));
        $workos = new WorkOS(apiKey: 'sk_test_static', clientId: 'client_test', handler: $stack);

        $workos->organizations()->getOrganization('org_01');

        $this->assertSame(['Bearer sk_test_static'], $this->apiAuthorizations());
        $this->assertFalse($this->apiRequest(0)->hasHeader('x-url-environment-id'));

        try {
            $workos->organizations()->getOrganization('org_01');
            $this->fail('Expected AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->statusCode);
        }

        $this->assertCount(2, $this->apiHistory);
        $this->assertSame(1, $mock->count());
        $this->assertCount(0, $this->tokenRequests);
    }

    public function testExchangeRetryCountIsTheClientDefault(): void
    {
        $this->assertSame(
            self::defaults(new \ReflectionMethod(WorkOS::class, '__construct'))['maxRetries'],
            HttpClient::DEFAULT_MAX_RETRIES,
        );
    }

    public function testFactoryDefaultsMatchTheClientConstructor(): void
    {
        $client = self::defaults(new \ReflectionMethod(WorkOS::class, '__construct'));
        unset($client['apiKey']);

        $this->assertSame($client, self::defaults(new \ReflectionMethod(Client::class, 'create')));
    }

    public function testFactoryDoesNotModifyTheCallersHandlerStack(): void
    {
        $mock = new MockHandler([new Response(200)]);
        $stack = HandlerStack::create($mock);

        Client::create($this->credentials($this->tokenEndpoint([])), handler: $stack);

        // A request sent through the caller's own stack is not authenticated.
        (new GuzzleClient(['handler' => $stack]))->request('GET', 'https://api.workos.test/widgets');
        $this->assertFalse($mock->getLastRequest()?->hasHeader('Authorization'));
        $this->assertCount(0, $this->tokenRequests);
    }

    public function testA401OnANonReplayableBodyIsReturnedWithoutARetry(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));
        $mock = new MockHandler([self::unauthorized(), self::organization()]);
        $stack = HandlerStack::create($mock);
        $stack->unshift(new CredentialsMiddleware($credentials));

        $response = (new GuzzleClient(['handler' => $stack]))->request('POST', 'https://api.workos.test/widgets', [
            'http_errors' => false,
            'body' => new NoSeekStream(Utils::streamFor('payload')),
            RecoveryState::REQUEST_OPTION => new RecoveryState(),
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(1, $mock->count());
        $this->assertCount(1, $this->tokenRequests);
    }

    #[DataProvider('crossOriginLocations')]
    public function testACrossOriginRedirectCarriesNoCredentialHeader(string $location): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));
        $workos = $this->client($credentials, [
            new Response(302, ['Location' => $location]),
            self::organization(),
        ]);

        $workos->organizations()->getOrganization('org_01');

        // The first hop, to the API, carries both headers.
        $this->assertCount(2, $this->apiHistory);
        $this->assertSame('api.workos.com', $this->apiRequest(0)->getUri()->getHost());
        $this->assertSame('Bearer access-1', $this->apiRequest(0)->getHeaderLine('Authorization'));
        $this->assertSame('environment_wif_test', $this->apiRequest(0)->getHeaderLine('x-url-environment-id'));

        // The second hop carries neither.
        $this->assertSame($location, (string) $this->apiRequest(1)->getUri());
        $this->assertFalse($this->apiRequest(1)->hasHeader('Authorization'));
        $this->assertFalse($this->apiRequest(1)->hasHeader('x-url-environment-id'));
        // Headers the credentials did not add are left alone.
        $this->assertTrue($this->apiRequest(1)->hasHeader('User-Agent'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function crossOriginLocations(): array
    {
        return [
            'another host' => ['https://elsewhere.test/organizations/org_01'],
            'a subdomain' => ['https://eu.api.workos.com/organizations/org_01'],
            'another scheme' => ['http://api.workos.com/organizations/org_01'],
            'another port' => ['https://api.workos.com:8443/organizations/org_01'],
        ];
    }

    #[DataProvider('sameOriginLocations')]
    public function testASameOriginRedirectKeepsTheCredentialHeaders(string $location): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));
        $workos = $this->client($credentials, [
            new Response(302, ['Location' => $location]),
            self::organization(),
        ]);

        $workos->organizations()->getOrganization('org_01');

        $this->assertCount(2, $this->apiHistory);
        $this->assertSame('/organizations/org_02', $this->apiRequest(1)->getUri()->getPath());
        $this->assertSame('Bearer access-1', $this->apiRequest(1)->getHeaderLine('Authorization'));
        $this->assertSame('environment_wif_test', $this->apiRequest(1)->getHeaderLine('x-url-environment-id'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sameOriginLocations(): array
    {
        return [
            'a relative path' => ['/organizations/org_02'],
            'the same origin' => ['https://api.workos.com/organizations/org_02'],
            'the default port and another case' => ['https://API.WorkOS.com:443/organizations/org_02'],
        ];
    }

    public function testCredentialHeadersStayOffEveryHopAfterACrossOriginRedirect(): void
    {
        // Guzzle compares each hop with the one before it, so the second
        // redirect, within the other host, looks same-origin to Guzzle. The
        // guard compares with the URL the headers were added for.
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));
        $workos = $this->client($credentials, [
            new Response(302, ['Location' => 'https://elsewhere.test/organizations/org_01']),
            new Response(302, ['Location' => '/organizations/org_02']),
            self::organization(),
        ]);

        $workos->organizations()->getOrganization('org_01');

        $this->assertCount(3, $this->apiHistory);
        $this->assertSame('Bearer access-1', $this->apiRequest(0)->getHeaderLine('Authorization'));
        foreach ([1, 2] as $hop) {
            $this->assertSame('elsewhere.test', $this->apiRequest($hop)->getUri()->getHost());
            $this->assertFalse($this->apiRequest($hop)->hasHeader('Authorization'));
            $this->assertFalse($this->apiRequest($hop)->hasHeader('x-url-environment-id'));
        }
    }

    public function testTheRedirectGuardHoldsWithoutGuzzlesRedirectHandling(): void
    {
        // A request for another origin than the one the headers were added
        // for, reaching the guard with every credential header still on it.
        $credentials = $this->credentials($this->tokenEndpoint([self::tokenResponse('access-1')]));
        $mock = new MockHandler([new Response(200), new Response(200)]);
        $forward = static fn (callable $handler): callable => static fn (RequestInterface $request, array $options) => $handler(
            $request->withUri(new Uri('https://elsewhere.test/organizations/org_01')),
            $options,
        );
        $stack = new HandlerStack($mock);
        $stack->push(new CredentialsMiddleware($credentials));
        $stack->push($forward);
        $stack->push(CredentialsMiddleware::redirectGuard());

        (new GuzzleClient(['handler' => $stack]))->get('https://api.workos.com/organizations/org_01');

        $sent = $mock->getLastRequest();
        $this->assertNotNull($sent);
        $this->assertSame('elsewhere.test', $sent->getUri()->getHost());
        $this->assertFalse($sent->hasHeader('Authorization'));
        $this->assertFalse($sent->hasHeader('x-url-environment-id'));

        // A request that never passed the credentials middleware is left alone.
        $bare = new HandlerStack($mock);
        $bare->push(CredentialsMiddleware::redirectGuard());
        (new GuzzleClient(['handler' => $bare]))->get('https://elsewhere.test/', [
            'headers' => ['x-url-environment-id' => 'environment_other'],
        ]);

        $this->assertSame('environment_other', $mock->getLastRequest()?->getHeaderLine('x-url-environment-id'));
    }

    public function testTheExchangeStopsRetryingAtItsDeadline(): void
    {
        // Each backoff takes 20 seconds of the 30 the exchange has.
        $credentials = $this->credentials(
            $this->tokenEndpoint([
                new Response(503, [], 'unavailable'),
                new Response(503, [], 'unavailable'),
                self::tokenResponse('access-1'),
            ]),
            sleeper: function (int $microseconds): void {
                $this->sleeps[] = $microseconds;
                $this->now += 20.0;
            },
        );

        try {
            $credentials->resolve();
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(WorkloadIdentityException::EXCHANGE_FAILED, $e->errorCode);
            $this->assertSame('The token endpoint returned HTTP 503.', $e->description);
        }

        // The second attempt gets what is left of the deadline; a third is never made.
        $this->assertCount(2, $this->tokenRequests);
        $this->assertSame([30.0, 10.0], $this->tokenTimeouts);
    }

    public function testTheExchangeDoesNotWaitPastItsDeadline(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            new Response(429, ['Retry-After' => '60'], 'slow down'),
            self::tokenResponse('access-1'),
        ]));

        try {
            $credentials->resolve();
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(WorkloadIdentityException::EXCHANGE_FAILED, $e->errorCode);
        }

        $this->assertCount(1, $this->tokenRequests);
        $this->assertSame([], $this->sleeps);
    }

    public function testAProviderThatUsesUpTheDeadlineFailsTheExchange(): void
    {
        $credentials = $this->credentials(
            $this->tokenEndpoint([self::tokenResponse('access-1')]),
            identityTokenProvider: function (): string {
                $this->now += 31.0;

                return 'assertion-1';
            },
        );

        try {
            $credentials->resolve();
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(WorkloadIdentityException::EXCHANGE_FAILED, $e->errorCode);
            $this->assertSame('The exchange did not finish before its deadline.', $e->description);
        }

        $this->assertCount(0, $this->tokenRequests);
    }

    public function testATokenUrlThatIsNotAnAbsoluteHttpsUrlIsRejectedAtConstruction(): void
    {
        $rejected = [
            'auth.workos.test/oauth2/token',
            '/oauth2/token',
            'ftp://auth.workos.test/oauth2/token',
            'https://',
            // Plain http is refused everywhere, the loopback interface included.
            'http://auth.workos.test/oauth2/token',
            'http://127.0.0.1:8080/oauth2/token',
        ];
        foreach ($rejected as $tokenUrl) {
            try {
                new Credentials(
                    $this->provider('assertion-1'),
                    clientId: 'client_wif_test',
                    tokenUrl: $tokenUrl,
                    environmentId: 'environment_wif_test',
                );
                $this->fail(sprintf('Expected WorkloadIdentityException for %s', $tokenUrl));
            } catch (WorkloadIdentityException $e) {
                $this->assertSame(WorkloadIdentityException::INVALID_CONFIGURATION, $e->errorCode);
                $this->assertSame('tokenUrl must be an absolute https URL (check WORKOS_WIF_TOKEN_URL)', $e->description);
            }
        }

        // The scheme is not case-sensitive.
        $credentials = new Credentials(
            $this->provider('assertion-1'),
            clientId: 'client_wif_test',
            tokenUrl: 'HTTPS://auth.workos.test:8443/oauth2/token',
            environmentId: 'environment_wif_test',
        );
        $this->assertSame('HTTPS://auth.workos.test:8443/oauth2/token', $credentials->tokenUrl);
    }

    public function testAnHttpTokenUrlFromTheEnvironmentIsRejected(): void
    {
        putenv('WORKOS_WIF_TOKEN_URL=http://auth.workos.test/oauth2/token');

        try {
            new Credentials(
                $this->provider('assertion-1'),
                clientId: 'client_wif_test',
                environmentId: 'environment_wif_test',
            );
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(WorkloadIdentityException::INVALID_CONFIGURATION, $e->errorCode);
        }
    }

    public function testExhaustedRetriesReportTheLastFailure(): void
    {
        $responses = [];
        for ($i = 0; $i <= HttpClient::DEFAULT_MAX_RETRIES; $i++) {
            $responses[] = new Response(503, ['Retry-After' => '0'], 'unavailable');
        }
        try {
            $this->credentials($this->tokenEndpoint($responses))->resolve();
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(WorkloadIdentityException::EXCHANGE_FAILED, $e->errorCode);
            $this->assertSame('The token endpoint returned HTTP 503.', $e->description);
        }
        $this->assertCount(HttpClient::DEFAULT_MAX_RETRIES + 1, $this->tokenRequests);

        // A 5xx body that carries an OAuth error is still retried, and its code is reported at the end.
        $this->tokenRequests = [];
        $responses = [];
        for ($i = 0; $i <= HttpClient::DEFAULT_MAX_RETRIES; $i++) {
            $responses[] = self::oauthError('temporarily_unavailable', 'Try again.', 503);
        }
        try {
            $this->credentials($this->tokenEndpoint($responses))->resolve();
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame('temporarily_unavailable', $e->errorCode);
            $this->assertSame('The token endpoint rejected the exchange.', $e->hint);
        }
        $this->assertCount(HttpClient::DEFAULT_MAX_RETRIES + 1, $this->tokenRequests);
    }

    public function testAProviderThatKeepsFailingSurfacesAsProviderFailed(): void
    {
        $failure = new \RuntimeException('metadata service unavailable');
        $credentials = $this->credentials(
            $this->tokenEndpoint([]),
            identityTokenProvider: function () use ($failure): string {
                $this->providerCalls++;

                throw $failure;
            },
        );

        try {
            $credentials->resolve();
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(WorkloadIdentityException::PROVIDER_FAILED, $e->errorCode);
            $this->assertSame($failure, $e->getPrevious());
        }

        $this->assertSame(HttpClient::DEFAULT_MAX_RETRIES + 1, $this->providerCalls);
        $this->assertCount(0, $this->tokenRequests);
    }

    public function testAProviderThatReturnsNoStringIsAProviderFailure(): void
    {
        foreach ([static fn () => '', static fn () => null] as $provider) {
            try {
                $this->credentials($this->tokenEndpoint([]), identityTokenProvider: $provider)->resolve();
                $this->fail('Expected WorkloadIdentityException');
            } catch (WorkloadIdentityException $e) {
                $this->assertSame(WorkloadIdentityException::PROVIDER_FAILED, $e->errorCode);
            }
        }
        $this->assertCount(0, $this->tokenRequests);
    }

    public function testAnIdentityTokenProviderObjectIsAccepted(): void
    {
        $provider = new class () implements IdentityTokenProvider {
            public function getIdentityToken(): string
            {
                return 'assertion-object';
            }
        };
        $credentials = $this->credentials(
            $this->tokenEndpoint([self::tokenResponse('access-1')]),
            identityTokenProvider: $provider,
        );

        $credentials->resolve();

        $this->assertSame('assertion-object', self::form($this->tokenRequests[0])['assertion']);
    }

    #[DataProvider('unreadableResponses')]
    public function testAnUnreadableTokenResponseIsInvalidResponse(Response $response): void
    {
        try {
            $this->credentials($this->tokenEndpoint([$response, self::tokenResponse('access-1')]))->resolve();
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(WorkloadIdentityException::INVALID_RESPONSE, $e->errorCode);
        }

        $this->assertCount(1, $this->tokenRequests);
    }

    /**
     * @return array<string, array{Response}>
     */
    public static function unreadableResponses(): array
    {
        return [
            'not JSON' => [new Response(200, [], '<html>login</html>')],
            'no access_token' => [new Response(200, [], '{"token_type":"Bearer"}')],
            'empty access_token' => [new Response(200, [], '{"access_token":""}')],
            'a 404 without an OAuth error' => [new Response(404, [], 'Not Found')],
        ];
    }

    public function testANonPositiveExpiresInIsTreatedAsThreeHundredSeconds(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::tokenResponse('access-1', 0),
            self::tokenResponse('access-2', 0),
        ]));

        $credentials->resolve();
        $this->now = 239.0;
        $credentials->resolve();
        $this->assertCount(1, $this->tokenRequests);

        $this->now = 241.0;
        $credentials->resolve();
        $this->assertCount(2, $this->tokenRequests);
    }

    public function testTheDescriptionIsCutToFiveHundredCharacters(): void
    {
        $credentials = $this->credentials($this->tokenEndpoint([
            self::oauthError('invalid_grant', str_repeat('x', 600)),
        ]));

        try {
            $credentials->resolve();
            $this->fail('Expected WorkloadIdentityException');
        } catch (WorkloadIdentityException $e) {
            $this->assertSame(500, mb_strlen($e->description));
        }
    }

    public function testMissingRequiredSettingsNameTheFieldAndItsVariable(): void
    {
        $cases = [
            'clientId is required (or set WORKOS_CLIENT_ID)' => ['tokenUrl' => self::TOKEN_URL, 'environmentId' => 'environment_wif_test'],
        ];

        foreach ($cases as $description => $arguments) {
            try {
                new Credentials($this->provider('assertion-1'), ...$arguments);
                $this->fail('Expected WorkloadIdentityException');
            } catch (WorkloadIdentityException $e) {
                $this->assertSame(WorkloadIdentityException::INVALID_CONFIGURATION, $e->errorCode);
                $this->assertSame($description, $e->description);
            }
        }
    }

    public function testTokenFileAndTokenEnvFailWhenThereIsNoToken(): void
    {
        $empty = $this->tempFile();
        file_put_contents($empty, "  \n");

        foreach ([TokenFile::create($empty), TokenFile::create($empty . '.missing'), TokenEnv::create('MY_JWT')] as $provider) {
            try {
                $provider->getIdentityToken();
                $this->fail('Expected RuntimeException');
            } catch (\RuntimeException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    /**
     * A mock token endpoint: answers from the queue and records every POST.
     *
     * @param array<int, Response|\Throwable|callable> $queue
     */
    private function tokenEndpoint(array $queue): callable
    {
        $mock = new MockHandler($queue);

        return function (RequestInterface $request, array $options) use ($mock) {
            $this->tokenRequests[] = $request;
            $this->tokenTimeouts[] = $options['timeout'] ?? null;

            return $mock($request, $options);
        };
    }

    /**
     * Credentials with the fixture values, the mock token endpoint, the test
     * clock and a sleeper that records instead of waiting.
     */
    private function credentials(callable $tokenEndpoint, mixed ...$overrides): Credentials
    {
        return $this->exchangingWith($tokenEndpoint, new Credentials(...array_merge(
            [
                'identityTokenProvider' => $this->provider('assertion-1'),
                'clientId' => 'client_wif_test',
                'tokenUrl' => self::TOKEN_URL,
                'environmentId' => 'environment_wif_test',
            ],
            $this->seams(),
            $overrides,
        )));
    }

    /**
     * Point the credentials' exchange at a mock token endpoint. The exchange
     * client is a private property, not an option, so it is set by reflection.
     */
    private function exchangingWith(callable $tokenEndpoint, Credentials $credentials): Credentials
    {
        (new \ReflectionProperty(Credentials::class, 'http'))->setValue(
            $credentials,
            new GuzzleClient(['handler' => HandlerStack::create($tokenEndpoint)]),
        );

        return $credentials;
    }

    /**
     * @return array{clock: callable, sleeper: callable}
     */
    private function seams(): array
    {
        return [
            'clock' => fn (): float => $this->now,
            'sleeper' => function (int $microseconds): void {
                $this->sleeps[] = $microseconds;
            },
        ];
    }

    /**
     * A callback provider that counts its calls.
     */
    private function provider(string $assertion): callable
    {
        return function () use ($assertion): string {
            $this->providerCalls++;

            return $assertion;
        };
    }

    /**
     * A client built by the factory on a mock API that records every attempt.
     *
     * @param array<int, Response|\Throwable> $apiQueue
     */
    private function client(CredentialsContract $credentials, array $apiQueue): WorkOS
    {
        $this->apiMock = new MockHandler($apiQueue);
        $stack = HandlerStack::create($this->recording($this->apiMock));

        return Client::create($credentials, clientId: 'client_test', handler: $stack);
    }

    /**
     * The mock API as a handler that records each request it receives. It
     * records in the handler, below every middleware, so a test sees what
     * would go on the wire and not what a middleware was handed.
     */
    private function recording(MockHandler $mock): callable
    {
        return function (RequestInterface $request, array $options) use ($mock) {
            $this->apiHistory[] = $request;

            return $mock($request, $options);
        };
    }

    private function apiRequest(int $index): RequestInterface
    {
        return $this->apiHistory[$index];
    }

    /**
     * @return list<string>
     */
    private function apiAuthorizations(): array
    {
        return array_map(
            static fn (RequestInterface $request): string => $request->getHeaderLine('Authorization'),
            $this->apiHistory,
        );
    }

    private function failureOf(callable $action): \Throwable
    {
        try {
            $action();
        } catch (\Throwable $e) {
            return $e;
        }

        $this->fail('Expected the action to fail.');
    }

    /**
     * Assert a secret is in no message, no string form and no stack-trace
     * argument of the error or of anything it wraps.
     */
    private function assertNoSecret(string $secret, \Throwable $error, string $label): void
    {
        for ($e = $error; $e !== null; $e = $e->getPrevious()) {
            $texts = [$e->getMessage(), (string) $e, $e->getTraceAsString(), self::traceArguments($e->getTrace())];
            if ($e instanceof WorkloadIdentityException) {
                array_push($texts, $e->errorCode, $e->description, $e->hint);
            }

            foreach ($texts as $text) {
                $this->assertStringNotContainsString($secret, $text, $label);
                // A trace printed as a string cuts each argument to 15 characters.
                $this->assertStringNotContainsString(substr($secret, 0, 15), $text, $label);
            }
        }
    }

    /**
     * Every string argument in a stack trace, including those nested in
     * arrays. Objects are not followed: a PHPUnit frame holds the test case.
     *
     * @param array<int, array<string, mixed>> $trace
     */
    private static function traceArguments(array $trace): string
    {
        $strings = [];
        $collect = static function (mixed $value) use (&$collect, &$strings): void {
            if (is_string($value)) {
                $strings[] = $value;
            } elseif (is_array($value)) {
                foreach ($value as $key => $item) {
                    $collect($key);
                    $collect($item);
                }
            }
        };
        foreach ($trace as $frame) {
            $collect($frame['args'] ?? []);
        }

        return implode("\n", $strings);
    }

    private function tempFile(): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'wif');
        $this->tempFiles[] = $path;

        return $path;
    }

    private static function tokenResponse(string $accessToken, ?int $expiresIn = 300): Response
    {
        $body = ['access_token' => $accessToken, 'token_type' => 'Bearer'];
        if ($expiresIn !== null) {
            $body['expires_in'] = $expiresIn;
        }

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($body));
    }

    private static function oauthError(string $code, string $description = 'The exchange was rejected.', int $status = 400): Response
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json', 'Retry-After' => '0'],
            (string) json_encode(['error' => $code, 'error_description' => $description]),
        );
    }

    private static function organization(): Response
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            (string) file_get_contents(__DIR__ . '/Fixtures/organization.json'),
        );
    }

    private static function unauthorized(): Response
    {
        return new Response(401, ['Content-Type' => 'application/json'], '{"message":"Unauthorized"}');
    }

    /**
     * @return array<string, string>
     */
    private static function form(RequestInterface $request): array
    {
        parse_str((string) $request->getBody(), $form);

        return $form;
    }

    /**
     * @return array<string, mixed> Parameter name to default value, for parameters that have one.
     */
    private static function defaults(\ReflectionMethod $method): array
    {
        $defaults = [];
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                $defaults[$parameter->getName()] = $parameter->getDefaultValue();
            }
        }

        return $defaults;
    }
}
