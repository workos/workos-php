<?php

namespace WorkOS\Resource;

/**
 * Class DirectoryGroup.
 */
class DirectoryGroup extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "directory_grp";

    public const RESOURCE_ATTRIBUTES = [
        "id",
        "name"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "name" => "name"
    ];
}
