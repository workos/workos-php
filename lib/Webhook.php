<?php

namespace WorkOS;

/**
 * Class Passwordless.
 *
 * This class will include functions
 */
class Webhook
{
   
    /** Initializes an Event object from a JSON payload
     *  Splits WorkOS Signature Header into timestamp and signature, and returns event type.
     * @param \WorkOS\Resource\PasswordlessSession $session Passwordless session generated through Passwordless->createSession
     *
     * @return boolean true
     */
    public function constructEvent($sigHeader, $payload, $secret, $tolerance)
    {
        $eventResult = $this->verifyHeader($sigHeader, $payload, $secret, $tolerance);

        if ($eventResult == "pass"):
            return Resource\Webhook::constructFromPayload($payload); else:
            return $eventResult;
        endif;
    }

    
    /**
     *  verifyHeader verifies the header returned from WorkOS contains a valid timestamp
     *  no older than 3 minutes, and computes the signature.
     * @param \WorkOS\Resource\PasswordlessSession $session Passwordless session generated through Passwordless->createSession
     *
     * @return boolean true
     */

    private function verifyHeader($sigHeader, $payload, $secret, $tolerance)
    {
        $timestamp = (int)$this->getTimeStamp($sigHeader);
        $signature = $this->getSignature($sigHeader);

        $currentTime = time();
        $SECONDS_SINCE_ISSUED = ($timestamp - $currentTime);
        $decodedBody = utf8_decode($payload);
        $signedPayload = $timestamp . "." . $decodedBody;
        $expectedSignature = hash_hmac("sha256", $signedPayload, $secret, false);

        if (empty($timestamp)):
            return "No Timestamp available"; elseif (empty($signature)):
            return "No signature hash found with expected scheme v1"; elseif ($timestamp < $currentTime - $tolerance):
            return $timestamp; elseif ($signature != $expectedSignature):
            return "Constructed signature " . $expectedSignature . "Does not match WorkOS Header Signature " . $signature; else:
            return "pass";
        endif;
    }

    /**
    *  Splits WorkOS Signature Header into timestamp and signature, and returns event type.
    * @param \WorkOS\Resource\PasswordlessSession $session Passwordless session generated through Passwordless->createSession
    *
    * @return boolean true
    */
    private function getTimeStamp($sigHeader)
    {
        $workosHeadersSplit = explode(',', $sigHeader, 2);
        $timestamp = substr($workosHeadersSplit[0], 2);
        return $timestamp;
    }

    private function getSignature($sigHeader)
    {
        $workosHeadersSplit = explode(',', $sigHeader, 2);
        $signature = substr($workosHeadersSplit[1], 4);
        return $signature;
    }
}
