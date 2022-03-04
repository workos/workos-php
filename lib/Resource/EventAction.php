<?php

namespace WorkOS\Resource;

/**
 * Class EventAction.
 */
class EventAction extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "event_action";

    public const RESOURCE_ATTRIBUTES = [
        "id",
        "environmentId",
        "name"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "environment_id" => "environmentId",
        "name" => "name"
    ];
}
