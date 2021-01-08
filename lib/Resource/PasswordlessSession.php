<?php

namespace WorkOS\Resource;

/**
 * class PasswordlessSession.
 */
class PasswordlessSession extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "passwordless_session";

    const RESOURCE_ATTRIBUTES = [
        "id",
        "email",
        "expiresAt",
        "link",
        "object"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "email" => "email",
        "expires_at" => "expiresAt",
        "link" => "link",
        "object" => "object"
    ];
}
