<?php

namespace WorkOS\Resource;

/**
 * Class DirectoryGroup.
 */
class DirectoryGroup extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "directory_grp";

    const RESOURCE_ATTRIBUTES = [
        "id",
        "name"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "name" => "name"
    ];
}
