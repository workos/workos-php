<?php

declare(strict_types=1);

namespace WorkOS;

// Loaded only by isolated session expiration tests.
function time(): int
{
    return 1700000000;
}
