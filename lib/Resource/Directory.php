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
        "externalKey",
        "state",
        "type",
        "name",
        "bearerToken",
        "projectId",
        "domain"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "external_key" => "externalKey",
        "state" => "state",
        "type" => "type",
        "name" => "name",
        "bearer_token" => "bearerToken",
        "project_id" => "projectId",
        "domain" => "domain"
    ];
}
