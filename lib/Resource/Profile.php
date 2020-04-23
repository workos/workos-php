<?php

namespace WorkOS\Resource;

/**
 * Class Profile.
 *
 * Representation of a WorkOS Profile.
 */
class Profile extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "profile";
    
    const RESOURCE_ATTRIBUTES = [
        "id",
        "email",
        "firstName",
        "lastName",
        "connectionType",
        "idpId"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "email" => "email",
        "first_name" => "firstName",
        "last_name" => "lastName",
        "connection_type" => "connectionType",
        "idp_id" => "idpId"
    ];
}
