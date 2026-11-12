<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\WorkloadIdentity;

/**
 * The single 401-recovery allowance of one logical request.
 *
 * The HTTP client's retry loop makes a new Guzzle call per attempt, so the
 * credentials middleware runs once per attempt and cannot own the allowance.
 * The loop creates one of these before its first attempt and passes it in
 * every attempt's request options; the middleware sets `used` and never
 * clears it, so a transport retry does not grant a second recovery.
 *
 * @internal
 */
final class RecoveryState
{
    /** The Guzzle request option that carries the state. */
    public const REQUEST_OPTION = 'workos_wif_recovery';

    public bool $used = false;
}
