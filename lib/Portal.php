<?php

namespace WorkOS;

/**
 * Class Portal.
 *
 * This class facilitates the use of the WorkOS Admin Portal.
 */
class Portal
{
    const DEFAULT_PAGE_SIZE = 10;

    /**
     * Create Organization.
     *
     * @param string $name The name of the Organization.
     * @param array $domains The domains of the Organization.
     *
     * @return \WorkOS\Resource\Organization
     */
    public function createOrganization($name, $domains)
    {
        $organizationsPath = "organizations";
        $params = [
            "name" => $name,
            "domains" => $domains
        ];

        $response = Client::request(Client::METHOD_POST, $organizationsPath, null, $params, true);

        return Resource\Organization::constructFromResponse($response);
    }

    /**
     * Generate a Portal Link scoped to an Organization.
     *
     * @param string $organization An Organization identifier.
     * @param string $intent The intent of the Admin Portal. Possible values are ["sso"].
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

    /**
     * List Organizations.
     *
     * @param null|array $domain Filter organizations to only return those that are associated with
     *      the provided domain.
     * @param int $limit Maximum number of records to return
     * @param null|string $before Organization ID to look before
     * @param null|string $after Organization ID to look after
     *
     * @return array An array containing the following:
     *      null|string Organization ID to use as before cursor
     *      null|string Organization ID to use as after cursor
     *      array \WorkOS\Resource\Organization instances
     */
    public function listOrganizations(
        $domains = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null
    ) {
        $organizationsPath = "organizations";
        $params = [
          "limit" => $limit,
          "before" => $before,
          "after" => $after,
          "domains" => $domains
        ];

        $response = Client::request(
            Client::METHOD_GET,
            $organizationsPath,
            null,
            $params,
            true
        );

        $organizations = [];
        list($before, $after) = Util\Request::parsePaginationArgs($response);
        foreach ($response["data"] as $responseData) {
            \array_push($organizations, Resource\Organization::constructFromResponse($responseData));
        }

        return [$before, $after, $organizations];
    }
}
