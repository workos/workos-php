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
     * @param \WorkOS\Resource\Order $order The Order in which to paginate records
     *
     * @throws Exception\WorkOSException
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
        $after = null,
        $order = null
    ) {
        $organizationsPath = "organizations";
        $params = [
          "limit" => $limit,
          "before" => $before,
          "after" => $after,
          "domains" => $domains,
          "order" => $order
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
     * @param null|array $domains [Deprecated] The domains of the Organization. Use domain_data instead.
     * @param null|array $domain_data The domains of the Organization.
     * @param null|boolean $allowProfilesOutsideOrganization [Deprecated] If you need to allow sign-ins from
     *      any email domain, contact support@workos.com.
     * @param null|string $idempotencyKey is a unique string that identifies a distinct organization
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Organization
     */
    public function createOrganization($name, $domains = null, $allowProfilesOutsideOrganization = null, $idempotencyKey = null, $domain_data = null)
    {
        $idempotencyKey ? $headers = array("Idempotency-Key: $idempotencyKey") : $headers = null;
        $organizationsPath = "organizations";

        $params = [ "name" => $name ];

        if (isset($domains)) {
            $params["domains"] = $domains;
        }
        if (isset($domain_data)) {
            $params["domain_data"] = $domain_data;
        }
        if (isset($allowProfilesOutsideOrganization)) {
            $params["allow_profiles_outside_organization"] = $allowProfilesOutsideOrganization;
        }

        $response = Client::request(Client::METHOD_POST, $organizationsPath, $headers, $params, true);

        return Resource\Organization::constructFromResponse($response);
    }

    /**
     * Update Organization.
     *
     * @param string $organization An Organization identifier.
     * @param null|array $domains [Deprecated] The domains of the Organization. Use domain_data instead.
     * @param null|array $domain_data The domains of the Organization.
     * @param null|string $name The name of the Organization.
     * @param null|boolean $allowProfilesOutsideOrganization [Deprecated] If you need to allow sign-ins from
     *      any email domain, contact support@workos.com.
     *
     * @throws Exception\WorkOSException
     */
    public function updateOrganization($organization, $domains = null, $name = null, $allowProfilesOutsideOrganization = null, $domain_data = null)
    {
        $organizationsPath = "organizations/{$organization}";

        $params = [ "name" => $name ];

        if (isset($domains)) {
            $params["domains"] = $domains;
        }
        if (isset($domain_data)) {
            $params["domain_data"] = $domain_data;
        }
        if (isset($allowProfilesOutsideOrganization)) {
            $params["allow_profiles_outside_organization"] = $allowProfilesOutsideOrganization;
        }

        $response = Client::request(Client::METHOD_PUT, $organizationsPath, null, $params, true);

        return Resource\Organization::constructFromResponse($response);
    }

    /**
     * Get a Directory Group.
     *
     * @param string $organization WorkOS organization ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Organization
     */
    public function getOrganization($organization)
    {
        $organizationsPath = "organizations/{$organization}";

        $response = Client::request(Client::METHOD_GET, $organizationsPath, null, null, true);

        return Resource\Organization::constructFromResponse($response);
    }

    /**
     * Delete an Organization.
     *
     * @param string $organization WorkOS organization ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Response
     */
    public function deleteOrganization($organization)
    {
        $organizationsPath = "organizations/{$organization}";

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
