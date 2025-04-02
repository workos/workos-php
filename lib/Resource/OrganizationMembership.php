<?php

namespace WorkOS\Resource;

/**
 * Class OrganizationMembership.
 *
 * @property 'organization_membership' $object
 * @property string $id
 * @property string $userId
 * @property string $organizationId
 * @property RoleResponse $role
 * @property 'active'|'inactive'|'pending' $status
 * @property string $createdAt
 * @property string $updatedAt
 */
class OrganizationMembership extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "organization_membership";

    public const RESOURCE_ATTRIBUTES = [
        "object",
        "id",
        "userId",
        "organizationId",
        "role",
        "status",
        "createdAt",
        "updatedAt"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id",
        "user_id" => "userId",
        "organization_id" => "organizationId",
        "role" => "role",
        "status" => "status",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt"
    ];
}
