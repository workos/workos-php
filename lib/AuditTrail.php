<?php

namespace WorkOS;

/**
 * Class AuditTrail
 *
 * This class facilitates the use of WorkOS Audit Trail.
 */
class AuditTrail
{
    public const DEFAULT_EVENT_LIMIT = 10;
    public const METADATA_SIZE_LIMIT = 50;

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
            $headers = ["idempotency-key: {$idempotencyKey}"];
        }

        Client::request(Client::METHOD_POST, $eventsPath, $headers, $event, true);

        return true;
    }

    /**
     * Filter for Audit Trail Events.
     *
     * @param null|string|array $group Group or array of groups to filter for
     * @param null|string|array $action Action of array of actions to filter for
     * @param null|string|array $actionType Action type of array of action types to filter for
     * @param null|string|array $actorName Actor name or array of actor names to filter for
     * @param null|string|array $actorId Actor ID or array of action IDs to filter for
     * @param null|string|array $targetName Target name or array of target names to filter for
     * @param null|string|array $targetID Target ID or array of target IDs to filter for
     * @param string $occurredAt ISO-8601 datetime of when an event occurred
     * @param string $occurredAtGt ISO-8601 datetime of when an event occurred after
     * @param string $occurredAtGte ISO-8601 datetime of when an event occurred at or after
     * @param string $occurredAtLt ISO-8601 datetime of when an event occurred before
     * @param string $occurredAtLte ISO-8601 datetime of when an event occured at or before
     * @param string $search Keyword search
     * @param int $limit Number of Events to return
     * @param string $before Event ID to look before
     * @param string $after Event ID to look after
     * @param \WorkOS\Resource\Order $order The Order in which to paginate records
     *
     * @return array An array containing the following:
     *      null|string Event ID to use as before cursor
     *      null|string Event ID to use as after cursor
     *      array \WorkOS\Resource\Event instances
     */
    public function getEvents(
        $group = null,
        $action = null,
        $actionType = null,
        $actorName = null,
        $actorId = null,
        $targetName = null,
        $targetId = null,
        $occurredAt = null,
        $occurredAtGt = null,
        $occurredAtGte = null,
        $occurredAtLt = null,
        $occurredAtLte = null,
        $search = null,
        $limit = self::DEFAULT_EVENT_LIMIT,
        $before = null,
        $after = null,
        $order = null
    ) {
        $eventsPath = "events";

        $params = [
            "limit" => $limit,
            "before" => $before,
            "after" => $after,
            "order" => $order
        ];

        if ($group) {
            if (is_string($group)) {
                $group = array($group);
            }
            $params["group"] = $group;
        }

        if ($action) {
            if (is_string($action)) {
                $action = array($action);
            }
            $params["action"] = $action;
        }

        if ($actionType) {
            if (is_string($actionType)) {
                $actionType = array($actionType);
            }
            $params["action_type"] = $actionType;
        }

        if ($actorName) {
            if (is_string($actorName)) {
                $actorName = array($actorName);
            }
            $params["actor_name"] = $actorName;
        }

        if ($actorId) {
            if (is_string($actorId)) {
                $actorId = array($actorId);
            }
            $params["actor_id"] = $actorId;
        }

        if ($targetName) {
            if (is_string($targetName)) {
                $targetName = array($targetName);
            }
            $params["target_name"] = $targetName;
        }

        if ($targetId) {
            if (is_string($targetId)) {
                $targetId = array($targetId);
            }
            $params["target_id"] = $targetId;
        }

        if ($occurredAt) {
            $params["occurred_at"] = $occurredAt;
        } else {
            if ($occurredAtGte) {
                $params["occurred_at_gte"] = $occurredAtGte;
            } elseif ($occurredAtGt) {
                $params["occurred_at_gt"] = $occurredAtGt;
            }

            if ($occurredAtLte) {
                $params["occurred_at_lte"] = $occurredAtLte;
            } elseif ($occurredAtLt) {
                $params["occurred_at_lt"] = $occurredAtLt;
            }
        }

        if ($search) {
            $params["search"] = $search;
        }

        $response = Client::request(
            Client::METHOD_GET,
            $eventsPath,
            null,
            $params,
            true
        );

        $events = [];
        list($before, $after) = Util\Request::parsePaginationArgs($response);
        foreach ($response["data"] as $responseData) {
            \array_push($events, Resource\Event::constructFromResponse($responseData));
        }

        return [$before, $after, $events];
    }
}
