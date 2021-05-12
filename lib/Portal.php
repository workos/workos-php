<?php

namespace WorkOS;

/**
 * Class Portal.
 *
 * This class facilitates the use of the WorkOS Admin Portal.
 */
class Portal
{
    /**
     * Generate a Portal Link scoped to an Organization.
     *
     * @param string $organization An Organization identifier.
     * @param string $intent The intent of the Admin Portal. Possible values are ["sso", "dsync"].
     * @param null|string $returnUrl The URL to which WorkOS should send users when they click on
     *      the link to return to your website. (Optional).
     *
     * @return \WorkOS\Resource\PortalLink
     */
    public function generateLink($organization, $intent, $returnUrl = null)
    {
        $generateLinkPath = "portal/generate_link";
        $params = [
            "organization" => $organization,
            "intent" => $intent,
            "return_url" => $returnUrl
        ];

        $response = Client::request(Client::METHOD_POST, $generateLinkPath, null, $params, true);

        return Resource\PortalLink::constructFromResponse($response);
    }
}
