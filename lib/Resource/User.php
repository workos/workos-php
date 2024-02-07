<?php

namespace WorkOS\Resource;

/**
 * Class User.
 */
class User extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "user";

    public const RESOURCE_ATTRIBUTES = [
        "object",
        "id",
        "email",
        "firstName",
        "lastName",
        "emailVerified",
        "profilePictureUrl",
        "createdAt",
        "updatedAt"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id",
        "email" => "email",
        "first_name" => "firstName",
        "last_name" => "lastName",
        "email_verified" => "emailVerified",
        "profile_picture_url" => "profilePictureUrl",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt"
    ];
}
