<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity;

/**
 * A source of the workload's identity token: the JWT its platform issued.
 *
 * Implementations hold no cache and do no retries; {@see Credentials} calls
 * the provider again for every exchange attempt. They never log the token
 * and never put it in an exception.
 *
 * A plain callable that returns the token works wherever a provider is
 * accepted, so a class is only needed for something reusable.
 */
interface IdentityTokenProvider
{
    /**
     * @return string The JWT. An empty string is treated as a failure.
     *
     * @throws \Throwable when the token cannot be obtained.
     */
    public function getIdentityToken(): string;
}
