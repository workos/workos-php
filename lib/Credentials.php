<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS;

/**
 * How a client authenticates its requests.
 *
 * `resolve()` runs before every HTTP attempt, so a retry never sends an
 * expired credential. `invalidate()` tells the credentials that the server
 * rejected one specific snapshot.
 *
 * @see WorkloadIdentity\Credentials for workload identity federation.
 */
interface Credentials
{
    /**
     * The headers for one HTTP attempt. May make a network call.
     *
     * @throws Exception\WorkloadIdentityException when no credential can be obtained.
     */
    public function resolve(): CredentialSnapshot;

    /**
     * Discard the credential a snapshot came from. Never makes a network
     * call, and has no effect when a newer credential is already held.
     *
     * @param int $generation The `generation` of the rejected snapshot.
     */
    public function invalidate(int $generation): void;
}
