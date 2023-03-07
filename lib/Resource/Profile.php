<?php

namespace WorkOS\Resource;

/**
 * Class Profile.
 *
 * @property string $id
 * @property string $email
 * @property string $firstName
 * @property string $lastName
 * @property string $organizationId
 * @property string $connectionId
 * @property string $connectionType
 * @property string $idpId
 * @property array  $groups
 * @property array  $rawAttributes
 */
class Profile extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "profile";

    public const RESOURCE_ATTRIBUTES = [
        "id",
        "email",
        "firstName",
        "lastName",
        "organizationId",
        "connectionId",
        "connectionType",
        "idpId",
        "groups",
        "rawAttributes"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "email" => "email",
        "first_name" => "firstName",
        "last_name" => "lastName",
        "organization_id" => "organizationId",
        "connection_id" => "connectionId",
        "connection_type" => "connectionType",
        "idp_id" => "idpId",
        "groups" => "groups",
        "raw_attributes" => "rawAttributes"
    ];
}
