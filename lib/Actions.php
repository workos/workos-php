<?php

declare(strict_types=1);
// @oagen-ignore-file
// This file is hand-maintained. AuthKit Actions helpers for request
// verification and response signing. These are client-side cryptographic
// helpers that will always be hand-maintained.
// Covers: H03 (actions_helper).

namespace WorkOS;

class Actions
{
    private const DEFAULT_TOLERANCE = 30; // seconds (stricter than webhooks' 180s)

    private const ACTION_TYPE_TO_RESPONSE_OBJECT = [
        'authentication' => 'authentication_action_response',
        'user_registration' => 'user_registration_action_response',
    ];

    /**
     * @param HttpClient $client Reserved for future use (e.g. server-side action endpoints).
     */
    public function __construct(
        private readonly HttpClient $client, // @phpstan-ignore property.onlyWritten
    ) {
    }

    /**
     * Verify the signature of an Actions request.
     *
     * @param string $payload The raw Actions request body.
     * @param string $sigHeader The signature header value.
     * @param string $secret The Actions secret.
     * @param int $tolerance Seconds the event is valid for. Defaults to 30.
     * @throws \InvalidArgumentException If the signature cannot be verified.
     */
    public function verifyHeader(
        string $payload,
        string $sigHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
    ): void {
        $parts = explode(', ', $sigHeader);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(
                'Unable to extract timestamp and signature hash from header'
            );
        }

        $issuedTimestamp = substr($parts[0], 2);  // strip "t="
        $signatureHash = substr($parts[1], 3);     // strip "v1="

        $currentTime = time();
        $timestampInSeconds = intval($issuedTimestamp) / 1000;
        $secondsSinceIssued = $currentTime - $timestampInSeconds;

        if ($secondsSinceIssued > $tolerance) {
            throw new \InvalidArgumentException('Timestamp outside the tolerance zone');
        }

        $expectedSignature = hash_hmac('sha256', "{$issuedTimestamp}.{$payload}", $secret);

        if (!hash_equals($expectedSignature, $signatureHash)) {
            throw new \InvalidArgumentException(
                'Signature hash does not match the expected signature hash for payload'
            );
        }
    }

    /**
     * Verify and deserialize an Actions request payload.
     *
     * @param string $payload The raw Actions request body.
     * @param string $sigHeader The signature header value.
     * @param string $secret The Actions secret.
     * @param int $tolerance Seconds the event is valid for. Defaults to 30.
     * @return array The deserialized action payload.
     * @throws \InvalidArgumentException If the signature cannot be verified.
     */
    public function constructAction(
        string $payload,
        string $sigHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
    ): array {
        $this->verifyHeader($payload, $sigHeader, $secret, $tolerance);
        return json_decode($payload, true);
    }

    /**
     * Build and sign an Actions response.
     *
     * @param string $actionType 'authentication' or 'user_registration'.
     * @param string $verdict 'Allow' or 'Deny'.
     * @param string|null $errorMessage Optional error message for Deny verdicts.
     * @param string $secret The Actions secret.
     * @return array The signed response payload.
     */
    public function signResponse(
        string $actionType,
        string $verdict,
        string $secret,
        ?string $errorMessage = null,
    ): array {
        $timestamp = (int) (microtime(true) * 1000);

        $responsePayload = [
            'timestamp' => $timestamp,
            'verdict' => $verdict,
        ];
        if ($errorMessage !== null) {
            $responsePayload['error_message'] = $errorMessage;
        }

        $payloadJson = json_encode($responsePayload, JSON_UNESCAPED_SLASHES);
        $signedPayload = "{$timestamp}.{$payloadJson}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        $objectType = self::ACTION_TYPE_TO_RESPONSE_OBJECT[$actionType]
            ?? throw new \InvalidArgumentException("Unknown action type: {$actionType}");

        return [
            'object' => $objectType,
            'payload' => $responsePayload,
            'signature' => $signature,
        ];
    }
}
