<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WorkOS\Credentials as CredentialsContract;
use WorkOS\CredentialSnapshot;

/**
 * Guzzle middleware that authenticates every HTTP attempt from a
 * {@see CredentialsContract}, and recovers once from a 401.
 *
 * @internal Installed by {@see Client::create()}.
 */
final class CredentialsMiddleware
{
    public function __construct(private readonly CredentialsContract $credentials)
    {
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            // Resolved per attempt, so a transport retry never sends an expired token.
            $snapshot = $this->credentials->resolve();

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
                    $retry = self::authorize($request, $this->credentials->resolve());
                    if ($body->isSeekable()) {
                        $body->rewind();
                    }

                    // Same method, URL, body and idempotency key; only the credential changes.
                    return $handler($retry, $options);
                },
            );
        };
    }

    private static function authorize(RequestInterface $request, CredentialSnapshot $snapshot): RequestInterface
    {
        foreach ($snapshot->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
