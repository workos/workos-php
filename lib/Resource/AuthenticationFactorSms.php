<?php

namespace WorkOS\Resource;

/**
 * Class AuthenticationFactor.
 */
class AuthenticationFactorSms extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "authentication_factor";

    const RESOURCE_ATTRIBUTES = [
        "object",
        "id",
        "createdAt",
        "updatedAt",
        "type",
        "environmentId",
        "sms"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "type" => "type",
        "environment_id" => "environmentId",
        "sms" => "sms"
    ];
}
