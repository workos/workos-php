<?php

namespace WorkOS\Resource;

/**
 * Class Event.
 */
class Event extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "event";

    const RESOURCE_ATTRIBUTES = [
        "id",
        "action",
        "group",
        "location",
        "latitude",
        "longitude",
        "type",
        "actorName",
        "actorId",
        "targetName",
        "targetId",
        "metadata",
        "occurredAt",
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "group" => "group",
        "location" => "location",
        "latitude" => "latitude",
        "longitude" => "longitude",
        "type" => "type",
        "actor_name" => "actorName",
        "actor_id" => "actorId",
        "target_name" => "targetName",
        "target_id" => "targetId",
        "metadata" => "metadata",
        "occurred_at" => "occurredAt"
    ];

    public static function constructFromResponse($response)
    {
        $instance = parent::constructFromResponse($response);

        $rawEventAction = $response["action"];
        $instance->values["action"] = EventAction::constructFromResponse($rawEventAction);

        return $instance;
    }

    public function toArray()
    {
        $eventArray = parent::toArray();

        $eventAction = $eventArray["action"];
        $eventArray["action"] = $eventAction->toArray();
        
        return $eventArray;
    }
}
