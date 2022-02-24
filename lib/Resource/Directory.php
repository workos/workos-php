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
        "organizationId",
        "state",
        "type",
        "name",
        "domain"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "external_key" => "externalKey",
        "organization_id" => "organizationId",
        "state" => "state",
        "type" => "type",
        "name" => "name",
        "domain" => "domain"
    ];
}
