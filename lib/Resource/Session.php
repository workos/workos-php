<?php

namespace WorkOS\Resource;

/**
 * Class Session.
 *
 * @property string $id
 * @property string $created_at
 * @property string $expires_at
 * @property string $token
 * @property string $authorized_organizations
 * @property string $unauthorized_organizations
 */
class Session extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "profile";

    public const RESOURCE_ATTRIBUTES = [
        "object",
        "id",
        "token",
        "authorizedOrganizations",
        "unauthorizedOrganizations",
        "createdAt",
        "expiresAt"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id",
        "token" => "token",
        "authorized_organizations" => "authorizedOrganizations",
        "unauthorized_organizations" => "unauthorizedOrganizations",
        "created_at" => "createdAt",
        "expires_at" => "expiresAt"
    ];
}
