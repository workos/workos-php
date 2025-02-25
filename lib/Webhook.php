<?php

namespace WorkOS;

/**
 * Class Webhook.
 *
 * This class includes functions for users to pass in a webhook header/body and receive
 * the webhook ID, body, and event type if the webhook is valid/secure otherwise an error
 * indicating the issue.
 */
class Webhook
{
    /**
     * Initializes an Event object from a JSON payload
     *
     * @return string
     */
    public function constructEvent($sigHeader, $payload, $secret, $tolerance)
    {
        $eventResult = $this->verifyHeader($sigHeader, $payload, $secret, $tolerance);

        if ($eventResult == "pass") {
            return Resource\Webhook::constructFromPayload($payload);
        } else {
            return $eventResult;
        }
    }

    /**
     * Verifies the header returned from WorkOS contains a valid timestamp
     * no older than 3 minutes, and computes the signature.
     *
     * @param string $sigHeader WorkOS header containing v1 signature and timestamp
     * @param string $payload Body of the webhook
     * @param string $secret Webhook secret from the WorkOS dashboard
     * @param int $tolerance Number of seconds old the webhook can be before it's invalid
     *
     * @return bool true
     */
    public function verifyHeader($sigHeader, $payload, $secret, $tolerance)
    {
        $timestamp = (int)$this->getTimeStamp($sigHeader);
        $signature = $this->getSignature($sigHeader);

        $currentTime = time();
        $signedPayload = $timestamp . "." . $payload;
        $expectedSignature = hash_hmac("sha256", $signedPayload, $secret, false);

        if (empty($timestamp)) {
            return "No Timestamp available";
        } elseif (empty($signature)) {
            return "No signature hash found with expected scheme v1";
        } elseif ($timestamp < $currentTime - $tolerance) {
            return "Timestamp outside of tolerance";
        } elseif ($signature != $expectedSignature) {
            return "Constructed signature " . $expectedSignature . "Does not match WorkOS Header Signature " . $signature;
        } else {
            return "pass";
        }
    }

    /**
     * Splits WorkOS header's two values and pulls out timestamp value and returns it
     *
     * @param string $sigHeader WorkOS header containing v1 signature and timestamp
     *
     * @return $timestamp
     */
    public function getTimeStamp($sigHeader)
    {
        $workosHeadersSplit = explode(",", $sigHeader, 2);
        $timestamp = substr($workosHeadersSplit[0], 2);

        return $timestamp;
    }

    /**
     * Splits WorkOS headers two values and pulls out the signature value and returns it
     *
     * @param string $sigHeader WorkOS header containing v1 signature and timestamp
     *
     * @return string
     */
    public function getSignature($sigHeader)
    {
        $workosHeadersSplit = explode(",", $sigHeader, 2);
        $signature = substr($workosHeadersSplit[1], 4);

        return $signature;
    }
}
