<?php

namespace WorkOS\Resource;

/**
 * Class AuthenticationChallenge.
 */
class VerificationChallenge extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "authentication_challenge";

    const RESOURCE_ATTRIBUTES = [
        "challenge",
        "valid"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "challenge" => "challenge",
        "valid" => "valid"
    ];
}
