<?php

namespace WorkOS;

/**
 * Class UserManagement
 */
class UserManagement
{
    public const DEFAULT_PAGE_SIZE = 10;

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

    /**
     * List Users.
     *
     * @param null|string $type User type "unmanaged" or "managed"
     * @param null|string $email
     * @param null|string $organization Organization users are a member of
     * @param int $limit Maximum number of records to return
     * @param null|string $before User ID to look before
     * @param null|string $after User ID to look after
     * @param \WorkOS\Resource\Order $order The Order in which to paginate records
     *
     * @throws Exception\WorkOSException
     *
     * @return array An array containing the following:
     *      null|string User ID to use as before cursor
     *      null|string User ID to use as after cursor
     *      array \WorkOS\Resource\User instances
     */
    public function listUsers(
        $type = null,
        $email = null,
        $organization = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null,
        $order = null
    ) {
        $usersPath = "users";
        $params = [
            "type" => $type,
            "email" => $email,
            "organization" => $organization,
            "limit" => $limit,
            "before" => $before,
            "after" => $after,
            "order" => $order
        ];

        $response = Client::request(
            Client::METHOD_GET,
            $usersPath,
            null,
            $params,
            true
        );

        $users = [];
        list($before, $after) = Util\Request::parsePaginationArgs($response);
        foreach ($response["data"] as $responseData) {
            \array_push($users, Resource\User::constructFromResponse($responseData));
        }

        return [$before, $after, $users];
    }

    /**
     * Create User.
     *
     * @param string $email The name of the User.
     * @param string|null $password The name of the User.
     * @param string|null $firstName The name of the User.
     * @param string|null $lastName The name of the User.
     * @param boolean|null $emailVerified The name of the User.
     * @param string|null $idempotencyKey is a unique string that identifies a distinct user
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\User
     */
    public function createUser($email, $password, $firstName, $lastName, $emailVerified, $idempotencyKey = null)
    {
        $idempotencyKey ? $headers = array("Idempotency-Key: $idempotencyKey") : $headers = null;
        $usersPath = "users";
        $params = [
            "email" => $email,
            "password" => $password,
            "first_name" => $firstName,
            "last_name" => $lastName,
            "email_verified" => $emailVerified
        ];

        $response = Client::request(Client::METHOD_POST, $usersPath, $headers, $params, true);

        return Resource\User::constructFromResponse($response);
    }

    /**
     * Creates a one-time Magic Auth code and emails it to the user.
     *
     * @param string $emailAddress The email address the one-time code will be sent to.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\MagicAuthChallenge
     */
    public function sendMagicAuthCode($emailAddress)
    {
        $sendCodePath = "users/magic_auth/send";

        $params = [
            "email_address" => $emailAddress,
        ];

        $response = Client::request(
            Client::METHOD_POST,
            $sendCodePath,
            null,
            $params,
            true
        );

        return Resource\MagicAuthChallenge::constructFromResponse($response);
    }
}
