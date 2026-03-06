<?php

namespace WorkOS\Resource;

/**
 * Class OrganizationRole.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property array<string> $permissions
 * @property string $resource_type_slug
 * @property string $type
 * @property string $created_at
 * @property string $updated_at
 */

class OrganizationRole extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "organization_role";

    public const RESOURCE_ATTRIBUTES = [
        "id",
        "name",
        "slug",
        "description",
        "permissions",
        "resource_type_slug",
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
        "resource_type_slug" => "resource_type_slug",
        "type" => "type",
        "created_at" => "created_at",
        "updated_at" => "updated_at"
    ];
}
