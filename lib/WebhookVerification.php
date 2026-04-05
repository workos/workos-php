<?php

declare(strict_types=1);
// @oagen-ignore-file
// This file is hand-maintained. It provides webhook signature verification
// utilities that complement the auto-generated webhook CRUD operations.
// Covers: H01 (webhook_verify), H02 (webhook_signature_primitives).

namespace WorkOS;

class WebhookVerification
{
    private const DEFAULT_TOLERANCE = 180; // seconds

    /**
     * @param HttpClient $client Reserved for future use.
     */
    public function __construct(
        private readonly HttpClient $client, // @phpstan-ignore property.onlyWritten
    ) {
    }

    /**
     * Verify a webhook signature and return the deserialized event payload.
     *
     * @param string $eventBody The raw webhook body (string).
     * @param string $eventSignature The 'WorkOS-Signature' header value.
     * @param string $secret The webhook endpoint secret from the WorkOS dashboard.
     * @param int|null $tolerance Seconds the event is valid for. Defaults to 180.
     * @return array The deserialized webhook event as an associative array.
     * @throws \InvalidArgumentException If the signature cannot be verified.
     */
    public function verifyEvent(
        string $eventBody,
        string $eventSignature,
        string $secret,
        ?int $tolerance = null,
    ): array {
        $this->verifyHeader(
            eventBody: $eventBody,
            eventSignature: $eventSignature,
            secret: $secret,
            tolerance: $tolerance,
        );

        return json_decode($eventBody, true);
    }

    /**
     * Verify the signature of a Webhook. Throws if verification fails.
     *
     * @param string $eventBody The raw webhook body (string).
     * @param string $eventSignature The 'WorkOS-Signature' header value.
     * @param string $secret The webhook endpoint secret from the WorkOS dashboard.
     * @param int|null $tolerance Seconds the event is valid for. Defaults to 180.
     * @throws \InvalidArgumentException If the signature cannot be verified.
     */
    public function verifyHeader(
        string $eventBody,
        string $eventSignature,
        string $secret,
        ?int $tolerance = null,
    ): void {
        [$issuedTimestamp, $signatureHash] = self::getTimestampAndSignatureHash($eventSignature);

        $maxSeconds = $tolerance ?? self::DEFAULT_TOLERANCE;
        $currentTime = time();
        $timestampInSeconds = intval($issuedTimestamp) / 1000;
        $secondsSinceIssued = $currentTime - $timestampInSeconds;

        if ($secondsSinceIssued > $maxSeconds) {
            throw new \InvalidArgumentException('Timestamp outside the tolerance zone');
        }

        $expectedSignature = self::computeSignature($issuedTimestamp, $eventBody, $secret);

        if (!hash_equals($expectedSignature, $signatureHash)) {
            throw new \InvalidArgumentException(
                'Signature hash does not match the expected signature hash for payload'
            );
        }
    }

    /**
     * Parse the timestamp and signature hash from a WorkOS-Signature header.
     *
     * @param string $sigHeader The 'WorkOS-Signature' header value.
     * @return array{0: string, 1: string} [timestamp, signatureHash]
     * @throws \InvalidArgumentException If the header cannot be parsed.
     */
    public static function getTimestampAndSignatureHash(string $sigHeader): array
    {
        $parts = explode(', ', $sigHeader);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(
                'Unable to extract timestamp and signature hash from header'
            );
        }

        // Format: "t=<timestamp>, v1=<hash>"
        $timestamp = substr($parts[0], 2);  // strip "t="
        $hash = substr($parts[1], 3);       // strip "v1="

        return [$timestamp, $hash];
    }

    /**
     * Compute the expected HMAC-SHA256 signature for a webhook payload.
     *
     * @param string $timestamp The issued timestamp (milliseconds).
     * @param string $payload The raw webhook body.
     * @param string $secret The webhook secret.
     * @return string The hex-encoded HMAC signature.
     */
    public static function computeSignature(string $timestamp, string $payload, string $secret): string
    {
        $unhashedString = "{$timestamp}.{$payload}";
        return hash_hmac('sha256', $unhashedString, $secret);
    }
}
