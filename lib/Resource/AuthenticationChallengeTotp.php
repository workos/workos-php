<?php

namespace WorkOS\Resource;

/**
 * Class AuthenticationChallengeTotp.
 */
class AuthenticationChallengeTotp extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "authentication_challenge";

    public const RESOURCE_ATTRIBUTES = [
        "object",
        "id",
        "createdAt",
        "updatedAt",
        "authenticationFactorId"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "authentication_factor_id" => "authenticationFactorId"
    ];
}
