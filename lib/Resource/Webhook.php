<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\Resource;

/**
 * Representation of a WorkOS webhook event.
 */
class Webhook
{
    /**
     * Parse a webhook payload JSON string into an object.
     *
     * @param string $payload JSON string
     * @return object
     */
    public static function constructFromPayload(string $payload): object
    {
        return (object) json_decode($payload, false);
    }

    /**
     * Compute an HMAC-SHA256 signature for webhook verification.
     *
     * @param int|string $timestamp Unix timestamp (ms)
     * @param string $payload JSON payload string
     * @param string $secret Webhook signing secret
     * @return string Hex-encoded signature
     */
    public function computeSignature(int|string $timestamp, string $payload, string $secret): string
    {
        $signedPayload = "{$timestamp}.{$payload}";
        return hash_hmac('sha256', $signedPayload, $secret);
    }
}
