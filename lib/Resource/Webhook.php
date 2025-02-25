<?php

namespace WorkOS\Resource;

/**
 * Class Webhook.
 *
 * Representation of a webhook resulting from a client ConstructEvent function.
 */
class Webhook
{
    /**
     * Creates a webhook object from a payload.
     *
     * @param  $payload
     *
     * @return Webhook
     */
    public static function constructFromPayload($payload)
    {
        $jsonPayload = json_decode($payload);
        $object = (object) $jsonPayload;

        return $object;
    }
}
