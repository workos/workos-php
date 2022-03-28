<?php

namespace WorkOS\Resource;

/**
 * Class AuthenticationChallenge.
 */
class AuthenticationChallenge extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "authentication_challenge";

    public const RESOURCE_ATTRIBUTES = [
        "object",
        "id",
        "createdAt",
        "updatedAt",
        "expiresAt",
        "authenticationFactorId"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "expires_at" => "expiresAt",
        "authentication_factor_id" => "authenticationFactorId"
    ];
}
