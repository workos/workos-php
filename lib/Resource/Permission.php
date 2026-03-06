<?php

namespace WorkOS\Resource;

/**
 * Class Permission.
 *
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property string $description
 * @property string $resource_type_slug
 * @property bool $system
 * @property string $created_at
 * @property string $updated_at
 */

class Permission extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "permission";

    public const RESOURCE_ATTRIBUTES = [
        "id",
        "slug",
        "name",
        "description",
        "resource_type_slug",
        "system",
        "created_at",
        "updated_at"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "slug" => "slug",
        "name" => "name",
        "description" => "description",
        "resource_type_slug" => "resource_type_slug",
        "system" => "system",
        "created_at" => "created_at",
        "updated_at" => "updated_at"
    ];
}
