<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity;

use GuzzleHttp\HandlerStack;
use WorkOS\Credentials as CredentialsContract;
use WorkOS\HttpClient;
use WorkOS\WorkOS;

/**
 * Builds a WorkOS client that authenticates with credentials instead of an
 * API key.
 *
 *     $workos = \WorkOS\WorkloadIdentity\Client::create(
 *         new \WorkOS\WorkloadIdentity\Credentials(identityTokenProvider: $provider),
 *     );
 *
 * The factory takes no API key, and the client it returns never sends one:
 * `WORKOS_API_KEY` and `WorkOS::setApiKey()` are ignored. Operations that need
 * the API key itself, such as exchanging an authorization code, throw a
 * `ConfigurationException` on this client.
 */
final class Client
{
    private const MIDDLEWARE_NAME = 'workos_credentials';

    /**
     * The arguments after `$credentials` mean what they mean on
     * {@see WorkOS::__construct()} and have the same defaults.
     *
     * @param HandlerStack|null $handler A Guzzle handler stack to build on. It is copied, not modified.
     */
    public static function create(
        CredentialsContract $credentials,
        ?string $clientId = null,
        string $baseUrl = 'https://api.workos.com',
        int $timeout = 60,
        int $maxRetries = HttpClient::DEFAULT_MAX_RETRIES,
        ?HandlerStack $handler = null,
        ?string $userAgent = null,
    ): WorkOS {
        $stack = $handler !== null ? clone $handler : HandlerStack::create();

        // Outermost, so it sees the response after Guzzle's own middleware and
        // so Guzzle still strips `Authorization` from a cross-origin redirect.
        $stack->unshift(new CredentialsMiddleware($credentials), self::MIDDLEWARE_NAME);

        return new WorkOS(
            // The empty string, not null: null falls back to WORKOS_API_KEY.
            apiKey: '',
            clientId: $clientId,
            baseUrl: $baseUrl,
            timeout: $timeout,
            maxRetries: $maxRetries,
            handler: $stack,
            userAgent: $userAgent,
        );
    }
}
