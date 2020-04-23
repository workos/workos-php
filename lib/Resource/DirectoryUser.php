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
        "firstName",
        "emails",
        "username",
        "lastName"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "raw_attributes" => "rawAttributes",
        "first_name" => "firstName",
        "emails" => "emails",
        "username" => "username",
        "last_name" => "lastName"
    ];
}
