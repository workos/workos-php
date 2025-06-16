<?php

namespace WorkOS\Resource;

/**
 * Class Role.
 */

class Role extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "role";

    public const RESOURCE_ATTRIBUTES = [
        "id",
        "name",
        "slug",
        "description",
        "permissions",
        "type",
        "created_at",
        "updated_at"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "name" => "name",
        "slug" => "slug",
        "description" => "description",
        "permissions" => "permissions",
        "type" => "type",
        "created_at" => "created_at",
        "updated_at" => "updated_at"
    ];
}
