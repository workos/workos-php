<?php

namespace WorkOS\Resource;

/**
 * Class Domain.
 */
class Domain extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "connection_domain";

    const RESOURCE_ATTRIBUTES = [
        "id",
        "domain"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "domain" => "domain"
    ];
}
