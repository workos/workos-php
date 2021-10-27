<?php

namespace WorkOS\Resource;

/**
 * Class DirectoryUser.
 */
class DirectoryUser extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "directory_usr";

    const RESOURCE_ATTRIBUTES = [
        "id",
        "rawAttributes",
        "customAttributes",
        "firstName",
        "emails",
        "username",
        "lastName",
        "state",
        "idpId",
        "groups"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "raw_attributes" => "rawAttributes",
        "custom_attributes" => "customAttributes",
        "first_name" => "firstName",
        "emails" => "emails",
        "username" => "username",
        "last_name" => "lastName",
        "state" => "state",
        "idp_id" => "idpId",
        "groups" => "groups"
    ];
}
