<?php

namespace WorkOS;

/**
 * Class AuditTrail
 *
 * This class facilitates the use of WorkOS Audit Trail.
 */
class AuditTrail
{
    const METADATA_SIZE_LIMIT = 50;

    /**
     * Create an Audit Trail event. An event consists of the following fields:
     * $event["action_type"] (string): Corresponding CRUD category of event. Can be
     *      one of C, R, U, or D.
     * $event["actor_name"] (string): Display name of the entity performing the action.
     * $event["actor_id"] (string): Unique identifier of the entity performing the action.
     * $event["group"] (string): A single organization containing related members. This
     *      will normally be the customer of a vendor's application.
     * $event[l"ocation"] (string): Identifier for where the event originated. This
     *      will be an IP address (IPv4 or IPv6), hostname, or device ID.
     * $event["occurred_at"] (string): ISO-8601 datetime at which the event happened, with
     *      millisecond precision.
     * $event["metadata"] (string): Arbitrary key-value data containing information associated
     *      with the event. Note: There is a limit of 50 keys. Key names can be up to 40
     *      characters long, and values can be up to 500 characters long.
     * $event["target_id"] (string): Unique identifier of the object or resource being
     *      acted upon.
     * $event["target_name"] (string): Display name of the object or resource that is
     *      being acted upon.
     *
     * @param array $event Associative array containing the keys detailed above
     * @param string $idempotencyKey Unique key guaranteeing idempotency of events for 24 hours
     *
     * @return boolean true if an event was successfully created
     */
    public function createEvent($event, $idempotencyKey = null)
    {
        $eventsPath = "events";

        if (\array_key_exists("metadata", $event)
            && count($event["metadata"]) > self::METADATA_SIZE_LIMIT
        ) {
            $msg = "Number of metadata keys exceed limit: " . self::METADATA_SIZE_LIMIT;
            throw new Exception\UnexpectedValueException($msg);
        }

        $headers = null;
        if ($idempotencyKey) {
            $headers = ["idempotency-key: ${idempotencyKey}"];
        }

        Client::request(Client::METHOD_POST, $eventsPath, $headers, $event, true);

        return true;
    }
}
