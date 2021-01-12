<?php

namespace WorkOS\Resource;

/**
 * Class EventAction.
 */
class EventAction extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "event_action";

    const RESOURCE_ATTRIBUTES = [
        "id",
        "environmentId",
        "name"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "environment_id" => "environmentId",
        "name" => "name"
    ];
}
