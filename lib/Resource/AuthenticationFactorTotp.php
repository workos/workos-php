<?php

namespace WorkOS\Resource;

/**
 * Class AuthenticationFactor.
 */
class AuthenticationFactorTotp extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "authentication_factor";

    const RESOURCE_ATTRIBUTES = [
        "object",
        "id",
        "createdAt",
        "updatedAt",
        "type",
        "environmentId",
        "totp"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "type" => "type",
        "environment_id" => "environmentId",
        "totp" => "totp"
    ];
}
