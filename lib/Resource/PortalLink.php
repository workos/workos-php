<?php

namespace WorkOS\Resource;

/**
 * Class PortalLink.
 */
class PortalLink extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "portal_link";

    const RESOURCE_ATTRIBUTES = [
        "link"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "link" => "link"
    ];
}
