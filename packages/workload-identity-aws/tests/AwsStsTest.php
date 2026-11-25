<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace Tests;

use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sts\Exception\StsException;
use Aws\Sts\StsClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkOS\WorkloadIdentity\Aws\AwsSts;
use WorkOS\WorkloadIdentity\IdentityTokenProvider;

/**
 * The AWS provider cases, WIF-AWS-01 and WIF-AWS-02, and the failure paths.
 * The STS client's handler is the AWS SDK's mock handler, so the real client
 * builds and signs each request and nothing is sent.
 */
final class AwsStsTest extends TestCase
{
    /** Everything the AWS SDK could resolve a region or credentials from. */
    private const ENV_VARS = [
        'AWS_REGION',
        'AWS_DEFAULT_REGION',
        'AWS_PROFILE',
        'AWS_CONFIG_FILE',
        'AWS_SHARED_CREDENTIALS_FILE',
        'AWS_ACCESS_KEY_ID',
        'AWS_SECRET_ACCESS_KEY',
        'AWS_SESSION_TOKEN',
        'AWS_EC2_METADATA_DISABLED',
        'AWS_MAX_ATTEMPTS',
        'AWS_RETRY_MODE',
    ];

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        foreach (self::ENV_VARS as $name) {
            $this->savedEnv[$name] = getenv($name);
            putenv($name);
        }

        // Static credentials, so signing needs no lookup; no config files and
        // no instance metadata, so nothing on this machine leaks into a test.
        putenv('AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE');
        putenv('AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
        putenv('AWS_CONFIG_FILE=/nonexistent/aws/config');
        putenv('AWS_SHARED_CREDENTIALS_FILE=/nonexistent/aws/credentials');
        putenv('AWS_EC2_METADATA_DISABLED=true');
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $name => $value) {
            putenv($value === false ? $name : sprintf('%s=%s', $name, $value));
        }
    }

    public function testWIF_AWS_01_CallsGetWebIdentityTokenWithTheAudienceAndRs256OnARegionalEndpoint(): void
    {
        $provider = AwsSts::create('https://api.workos.com', 'us-east-1');
        $sts = $this->stub($provider, [new Result(['WebIdentityToken' => 'aws-jwt'])]);

        $token = $provider->getIdentityToken();

        $this->assertSame('aws-jwt', $token);
        $this->assertSame(0, $sts->count());
        $command = $sts->getLastCommand();
        $this->assertSame('GetWebIdentityToken', $command->getName());
        $this->assertSame(['https://api.workos.com'], $command['Audience']);
        $this->assertSame('RS256', $command['SigningAlgorithm']);
        $this->assertNull($command['DurationSeconds']);

        // The region's own endpoint, not the global sts.amazonaws.com.
        $this->assertSame('us-east-1', self::client($provider)->getRegion());
        $this->assertSame('sts.us-east-1.amazonaws.com', $sts->getLastRequest()->getUri()->getHost());

        // With no region available, building the provider is an error.
        try {
            AwsSts::create('https://api.workos.com');
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('region is required', $e->getMessage());
        }
    }

    public function testWIF_AWS_02_AnStsErrorSurfacesAsAProviderErrorNamingTheCall(): void
    {
        $provider = AwsSts::create('https://api.workos.com', 'us-east-1');
        $this->stub($provider, [
            static fn (CommandInterface $command): StsException => new StsException(
                'User is not authorized to perform: sts:GetWebIdentityToken',
                $command,
                ['code' => 'AccessDenied'],
            ),
        ]);

        try {
            $provider->getIdentityToken();
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('sts:GetWebIdentityToken failed with AccessDenied.', $e->getMessage());
            $this->assertInstanceOf(StsException::class, $e->getPrevious());
        }
    }

    public function testTheRegionCanComeFromTheAwsSdk(): void
    {
        putenv('AWS_REGION=eu-west-1');
        $provider = AwsSts::create('https://api.workos.com');
        $sts = $this->stub($provider, [new Result(['WebIdentityToken' => 'aws-jwt'])]);

        $provider->getIdentityToken();

        $this->assertSame('eu-west-1', self::client($provider)->getRegion());
        $this->assertSame('sts.eu-west-1.amazonaws.com', $sts->getLastRequest()->getUri()->getHost());
    }

    public function testTheGlobalEndpointIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('regional STS endpoint');

        AwsSts::create('https://api.workos.com', 'aws-global');
    }

    public function testTheStsClientIsBuiltOnceAndCalledOnEveryCall(): void
    {
        $provider = AwsSts::create('https://api.workos.com', 'us-east-1');
        $client = self::client($provider);
        $sts = $this->stub($provider, [
            new Result(['WebIdentityToken' => 'aws-jwt-1']),
            new Result(['WebIdentityToken' => 'aws-jwt-2']),
        ]);

        $this->assertSame('aws-jwt-1', $provider->getIdentityToken());
        $this->assertSame('aws-jwt-2', $provider->getIdentityToken());
        $this->assertSame(0, $sts->count());
        $this->assertSame($client, self::client($provider));
    }

    /**
     * The AWS SDK retries a throttled or failed request inside the client
     * unless it is told not to. The provider must not: the SDK calls the
     * provider again for every exchange attempt.
     */
    #[DataProvider('retryableStsFailures')]
    public function testOneProviderCallIsOneStsRequest(string $code, int $status): void
    {
        // What the AWS SDK would follow if the provider left retries to it.
        putenv('AWS_MAX_ATTEMPTS=5');
        putenv('AWS_RETRY_MODE=standard');
        $provider = AwsSts::create('https://api.workos.com', 'us-east-1');
        $requests = 0;
        $fail = static function (CommandInterface $command) use (&$requests, $code, $status): StsException {
            $requests++;

            return new StsException($code, $command, ['code' => $code, 'response' => new Response($status)]);
        };
        $this->stub($provider, array_fill(0, 6, $fail));

        try {
            $provider->getIdentityToken();
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame(sprintf('sts:GetWebIdentityToken failed with %s.', $code), $e->getMessage());
        }

        $this->assertSame(1, $requests);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function retryableStsFailures(): array
    {
        return [
            'throttling' => ['Throttling', 400],
            'a 500' => ['InternalFailure', 500],
            'a 503' => ['ServiceUnavailable', 503],
        ];
    }

    public function testEveryStsRequestHasAConnectAndARequestTimeout(): void
    {
        $provider = AwsSts::create('https://api.workos.com', 'us-east-1');
        $sts = $this->stub($provider, [new Result(['WebIdentityToken' => 'aws-jwt'])]);

        $provider->getIdentityToken();

        // The AWS SDK sets neither by itself, and a request without them can
        // wait on a stalled endpoint for as long as the endpoint likes.
        $http = $sts->getLastCommand()['@http'];
        $this->assertSame(10, $http['connect_timeout'] ?? null);
        $this->assertSame(10, $http['timeout'] ?? null);
    }

    public function testAResponseWithoutATokenIsAnError(): void
    {
        $provider = AwsSts::create('https://api.workos.com', 'us-east-1');
        $this->stub($provider, [new Result([])]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('sts:GetWebIdentityToken returned no token.');

        $provider->getIdentityToken();
    }

    public function testMissingAwsCredentialsNameTheCall(): void
    {
        putenv('AWS_ACCESS_KEY_ID');
        putenv('AWS_SECRET_ACCESS_KEY');
        $provider = AwsSts::create('https://api.workos.com', 'us-east-1');
        $this->stub($provider, [new Result(['WebIdentityToken' => 'aws-jwt'])]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('sts:GetWebIdentityToken could not be called');

        $provider->getIdentityToken();
    }

    public function testItIsAnIdentityTokenProviderAndRequiresAnAudience(): void
    {
        $this->assertInstanceOf(IdentityTokenProvider::class, AwsSts::create('https://api.workos.com', 'us-east-1'));

        $this->expectException(\InvalidArgumentException::class);
        AwsSts::create('', 'us-east-1');
    }

    /**
     * Replace the transport of the provider's STS client with a mock that
     * answers from the queue and records each command and signed request.
     *
     * @param array<int, Result|callable> $queue
     */
    private function stub(AwsSts $provider, array $queue): MockHandler
    {
        $mock = new MockHandler($queue);
        self::client($provider)->getHandlerList()->setHandler($mock);

        return $mock;
    }

    private static function client(AwsSts $provider): StsClient
    {
        return (new \ReflectionProperty(AwsSts::class, 'client'))->getValue($provider);
    }
}
