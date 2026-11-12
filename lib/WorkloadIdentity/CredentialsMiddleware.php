<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use WorkOS\Credentials as CredentialsContract;
use WorkOS\CredentialSnapshot;

/**
 * Guzzle middleware that authenticates every HTTP attempt from a
 * {@see CredentialsContract}, and recovers once from a 401.
 *
 * Its second half, {@see self::redirectGuard()}, keeps the credential headers
 * from following a redirect to another origin.
 *
 * @internal Installed by {@see Client::create()}.
 */
final class CredentialsMiddleware
{
    /**
     * Request option that tells the redirect guard which URL the credential
     * headers were added for, and what those headers are called.
     */
    private const SCOPE_OPTION = 'workos_wif_credential_scope';

    private const DEFAULT_PORTS = ['http' => 80, 'https' => 443];

    public function __construct(private readonly CredentialsContract $credentials)
    {
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            // Resolved per attempt, so a transport retry never sends an expired token.
            $snapshot = $this->credentials->resolve();
            $options = self::scope($options, $request, $snapshot);

            return $handler(self::authorize($request, $snapshot), $options)->then(
                function (ResponseInterface $response) use ($handler, $request, $options, $snapshot) {
                    if ($response->getStatusCode() !== 401 || !$snapshot->recoverable) {
                        return $response;
                    }

                    $recovery = $options[RecoveryState::REQUEST_OPTION] ?? null;
                    if (!$recovery instanceof RecoveryState || $recovery->used) {
                        return $response;
                    }

                    $body = $request->getBody();
                    if ($body->getSize() !== 0 && !$body->isSeekable()) {
                        // The body cannot be sent again, so the 401 stands.
                        return $response;
                    }

                    $recovery->used = true;
                    // Invalidates the generation this attempt used, never "the current one":
                    // a newer token another request already fetched is kept.
                    $this->credentials->invalidate($snapshot->generation);
                    $renewed = $this->credentials->resolve();
                    if ($body->isSeekable()) {
                        $body->rewind();
                    }

                    // Same method, URL, body and idempotency key; only the credential changes.
                    return $handler(self::authorize($request, $renewed), self::scope($options, $request, $renewed));
                },
            );
        };
    }

    /**
     * Middleware that removes the credential headers from a request whose
     * origin is not the one they were added for. That happens when a redirect
     * is followed, and when a middleware between the two rewrites the URL.
     * Guzzle strips `Authorization` and `Cookie` from a cross-origin redirect
     * and nothing else; this strips every header the credentials added, the
     * environment ID included.
     *
     * It belongs innermost, next to the handler, so it sees each request as
     * it is about to be sent.
     */
    public static function redirectGuard(): callable
    {
        return static fn (callable $handler): callable => static function (RequestInterface $request, array $options) use ($handler) {
            $scope = $options[self::SCOPE_OPTION] ?? null;
            if (is_array($scope) && self::isCrossOrigin($scope['uri'], $request->getUri())) {
                foreach ($scope['headers'] as $name) {
                    $request = $request->withoutHeader($name);
                }
            }

            return $handler($request, $options);
        };
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private static function scope(array $options, RequestInterface $request, CredentialSnapshot $snapshot): array
    {
        $options[self::SCOPE_OPTION] = [
            'uri' => $request->getUri(),
            'headers' => array_keys($snapshot->headers),
        ];

        return $options;
    }

    /**
     * Whether the scheme, the host or the effective port differ. Written here
     * because the oldest Guzzle 7 releases the SDK supports ship no such helper.
     */
    private static function isCrossOrigin(UriInterface $original, UriInterface $current): bool
    {
        return self::origin($original) !== self::origin($current);
    }

    /**
     * @return array{string, string, int|null}
     */
    private static function origin(UriInterface $uri): array
    {
        $scheme = strtolower($uri->getScheme());

        return [$scheme, strtolower($uri->getHost()), $uri->getPort() ?? self::DEFAULT_PORTS[$scheme] ?? null];
    }

    private static function authorize(RequestInterface $request, CredentialSnapshot $snapshot): RequestInterface
    {
        foreach ($snapshot->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
