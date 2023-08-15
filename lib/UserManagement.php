<?php

namespace WorkOS;

/**
 * Class UserManagement
 */
class UserManagement
{
    /**
     * Add a user to an organization.
     *
     * @param string $userId User ID
     * @param string $organizationId Organization ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Response
     */
    public function addUserToOrganization($userId, $organizationId)
    {
        $userOrganizationPath = "users/{$userId}/organizations";

        $params = [
            "organization_id" => $organizationId,
        ];

        $response = Client::request(
            Client::METHOD_POST,
            $userOrganizationPath,
            null,
            $params,
            true
        );

        return Resource\User::constructFromResponse($response);
    }

    /**
     * Remove a user from an organization.
     *
     * @param string $userId User ID
     * @param string $organizationId Organization ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Response
     */
    public function removeUserFromOrganization($userId, $organizationId)
    {
        $userOrganizationPath = "users/{$userId}/organizations/{$organizationId}";

        $response = Client::request(
            Client::METHOD_DELETE,
            $userOrganizationPath,
            null,
            null,
            true
        );

        return Resource\User::constructFromResponse($response);
    }

    /**
     * Get a User.
     *
     * @param string $userId user ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\User
     */
    public function getUser($userId)
    {
        $usersPath = "users/{$userId}";

        $response = Client::request(Client::METHOD_GET, $usersPath, null, null, true);

        return Resource\User::constructFromResponse($response);
    }
}
