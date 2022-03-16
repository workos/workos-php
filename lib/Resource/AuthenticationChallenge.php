<?php

namespace WorkOS\Resource;

/**
 * Class AuthenticationChallenge.
 */
class AuthenticationChallenge extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "authentication_challenge";

    const RESOURCE_ATTRIBUTES = [
        "object",
        "id",
        "createdAt",
        "updatedAt",
        "expiresAt",
        "authenticationFactorId"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "expires_at" => "expiresAt",
        "authentication_factor_id" => "authenticationFactorId"
    ];
}
