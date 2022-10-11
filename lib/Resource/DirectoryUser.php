<?php

namespace WorkOS\Resource;

/**
 * Class DirectoryUser.
 */
class DirectoryUser extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "directory_usr";

    public const RESOURCE_ATTRIBUTES = [
        "id",
        "rawAttributes",
        "customAttributes",
        "firstName",
        "emails",
        "username",
        "lastName",
        "jobTitle",
        "state",
        "idpId",
        "groups",
        "directoryId",
        "organizationId"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "raw_attributes" => "rawAttributes",
        "custom_attributes" => "customAttributes",
        "first_name" => "firstName",
        "emails" => "emails",
        "username" => "username",
        "last_name" => "lastName",
        "job_title" => "jobTitle",
        "state" => "state",
        "idp_id" => "idpId",
        "groups" => "groups",
        "directory_id" => "directoryId",
        "organization_id" => "organizationId"
    ];

    public function primaryEmail()
    {
        $response = $this;

        if (count($response->raw["emails"]) == 0) {
            return;
        };

        foreach ($response->raw["emails"] as $value) {
            if ($value["primary"] == true) {
                return $value["value"];
            };
        };
    }
}
