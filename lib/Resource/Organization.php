<?php

namespace WorkOS\Resource;

/**
 * Class Organization.
 */

class Organization extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "organization";

    const RESOURCE_ATTRIBUTES = [
        "id",
        "name",
        "allow_profiles_outside_organization",
        "domains"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "name" => "name",
        "allow_profiles_outside_organization" => "allowProfilesOutsideOrganization",
        "domains" => "domains"
    ];
}
