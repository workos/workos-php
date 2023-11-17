<?php

namespace WorkOS\Resource;

/**
 * Class OrganizationMembership.
 */

class OrganizationMembership extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "organization_membership";

    public const RESOURCE_ATTRIBUTES = [
        "object",
        "id",
        "userId",
        "organizationId",
        "createdAt",
        "updatedAt"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id",
        "user_id" => "userId",
        "organization_id" => "organizationId",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt"
    ];
}
