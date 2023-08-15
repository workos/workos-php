<?php

namespace WorkOS\Resource;

/**
 * Class AuthenticationFactorSms.
 */
class User extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "authentication_factor";

    public const RESOURCE_ATTRIBUTES = [
        "object",
        "id",
        "userType",
        "email",
        "firstName",
        "lastName",
        "emailVerifiedAt",
        "googleOauthProfileId",
        "ssoProfileId",
        "createdAt",
        "updatedAt"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id",
        "user_type" => "userType",
        "email" => "email",
        "first_name" => "firstName",
        "last_name" => "lastName",
        "email_verified_at" => "emailVerifiedAt",
        "google_oauth_profile_id" => "googleOauthProfileId",
        "sso_profile_id" => "ssoProfileId",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt"
    ];
}
