<?php

namespace WorkOS;

/**
 * Class Organizations.
 *
 * This class facilitates the use of operations on WorkOS Organizations.
 */
class Organizations
{
    public const DEFAULT_PAGE_SIZE = 10;

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

    /**
     * Create Organization.
     *
     * @param string $name The name of the Organization.
     * @param array $domains The domains of the Organization.
     * @param null|boolean $allowProfilesOutsideOrganization Whether Connections within the Organization allow profiles
     *      that are outside of the Organization's configured User Email Domains.
     *
     * @return \WorkOS\Resource\Organization
     */
    public function createOrganization($name, $domains, $allowProfilesOutsideOrganization = null)
    {
        $organizationsPath = "organizations";
        $params = [
            "name" => $name,
            "domains" => $domains,
            "allow_profiles_outside_organization" => $allowProfilesOutsideOrganization
        ];

        $response = Client::request(Client::METHOD_POST, $organizationsPath, null, $params, true);

        return Resource\Organization::constructFromResponse($response);
    }

    /**
     * Update Organization.
     *
     * @param string $organization An Organization identifier.
     * @param array $domains The domains of the Organization.
     * @param string $name The name of the Organization.
     * @param null|boolean $allowProfilesOutsideOrganization Whether Connections within the Organization allow profiles
     *      that are outside of the Organization's configured User Email Domains.
     */

    public function updateOrganization($organization, $domains, $name, $allowProfilesOutsideOrganization = null)
    {
        $organizationsPath = "organizations/{$organization}";
        $params = [
          "organization" => $organization,
          "domains" => $domains,
          "name" => $name,
          "allow_profiles_outside_organization" => $allowProfilesOutsideOrganization
        ];

        $response = Client::request(Client::METHOD_PUT, $organizationsPath, null, $params, true);

        return Resource\Organization::constructFromResponse($response);
    }

    /**
     * Get a Directory Group.
     *
     * @param string $organization WorkOS organization ID
     *
     * @return \WorkOS\Resource\Organization
     */

    public function getOrganization($organization)
    {
        $organizationsPath = "organizations/${organization}";

        $response = Client::request(Client::METHOD_GET, $organizationsPath, null, null, true);

        return Resource\Organization::constructFromResponse($response);
    }

    /**
     * Delete an Organization.
     *
     * @param string $organization WorkOS organization ID
     *
     * @return \WorkOS\Resource\Response
     */
    public function deleteOrganization($organization)
    {
        $organizationsPath = "organizations/${organization}";

        $response = Client::request(
            Client::METHOD_DELETE,
            $organizationsPath,
            null,
            null,
            true
        );

        return $response;
    }
}
