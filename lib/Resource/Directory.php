<?php

namespace WorkOS\Resource;

/**
 * Class Directory.
 */
class Directory extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "directory";

    const RESOURCE_ATTRIBUTES = [
        "id",
        "environmentId",
        "externalKey",
        "state",
        "type",
        "name",
        "domain"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "environment_id" => "environmentId",
        "external_key" => "externalKey",
        "state" => "state",
        "type" => "type",
        "name" => "name",
        "domain" => "domain"
    ];
}
