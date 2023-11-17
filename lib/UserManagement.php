<?php

namespace WorkOS;

/**
 * Class UserManagement
 */
class UserManagement
{
    public const DEFAULT_PAGE_SIZE = 10;
    public const DEFAULT_TOKEN_EXPIRATION = 1440;

    /**
     * Create User.
     *
     * @param string $email The email address of the user.
     * @param string|null $password The password of the user.
     * @param string|null $firstName The first name of the user.
     * @param string|null $lastName The last name of the user.
     * @param boolean|null $emailVerified A boolean declaring if the user's email has been verified.
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\User
     */
    public function createUser($email, $password, $firstName, $lastName, $emailVerified)
    {
        $usersPath = "users";
        $params = [
            "email" => $email,
            "password" => $password,
            "first_name" => $firstName,
            "last_name" => $lastName,
            "email_verified" => $emailVerified
        ];

        $response = Client::request(Client::METHOD_POST, $usersPath, null, $params, true);

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
     * Update a User
     *
     * @param string $userId The unique ID of the user.
     * @param string|null $firstName The first name of the user.
     * @param string|null $lastName The last name of the user.
     * @param boolean|null $emailVerified A boolean declaring if the user's email has been verified.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\User
     */
    public function updateUser($userId, $firstName = null, $lastName = null, $emailVerified = null)
    {
        $usersPath = "users/{$userId}";

        $params = [
            "first_name" => $firstName,
            "last_name" => $lastName,
            "email_verified" => $emailVerified
        ];

        $response = Client::request(Client::METHOD_PUT, $usersPath, null, $params, true);

        return Resource\User::constructFromResponse($response);
    }

    /**
     * List Users.
     *
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
        $email = null,
        $organization = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null,
        $order = null
    ) {
        $usersPath = "user_management/users";

        $params = [
            "email" => $email,
            "organization_id" => $organization,
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
     * Delete a user.
     *
     * @param string $userId Unique ID of a user
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Response
     */
    public function deleteUser($userId)
    {
        $usersPath = "user_management/users/{$userId}";

        $response = Client::request(Client::METHOD_DELETE, $usersPath, null, null, true);

        return $response;
    }

    /**
     * Add a User to an Organization.
     *
     * @param string $userId User ID
     * @param string $organizationId Organization ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Response
     */
    public function createOrganizationMembership($userId, $organizationId)
    {
        $path = "user_management/organization_memberships";

        $params = [
            "organization_id" => $organizationId,
            "user_id" => $userId
        ];

        $response = Client::request(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true
        );

        return Resource\OrganizationMembership::constructFromResponse($response);
    }

    /**
     * Get an Organization Membership.
     *
     * @param string $organizationMembershipId Organization Membership ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Response
     */
    public function getOrganizationMembership($organizationMembershipId)
    {
        $path = "user_management/organization_memberships/{$organizationMembershipId}";

        $response = Client::request(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true
        );

        return Resource\OrganizationMembership::constructFromResponse($response);
    }

    /**
     * Remove a user from an organization.
     *
     * @param string $organizationMembershipId Organization Membership ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Response
     */
    public function deleteOrganizationMembership($organizationMembershipId)
    {
        $path = "user_management/organization_memberships/{$organizationMembershipId}";

        $response = Client::request(
            Client::METHOD_DELETE,
            $path,
            null,
            null,
            true
        );

        return $response;
    }

    /**
     * List organization memberships.
     *
     * @param string $userId User ID
     * @param string $organizationId Organization ID
     * @param int $limit Maximum number of records to return
     * @param null|string $before Organization Membership ID to look before
     * @param null|string $after Organization Membership ID to look after
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Response
     */
    public function listOrganizationMemberships(
        $userId,
        $organizationId,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null
    ) {
        $path = "user_management/organization_memberships";

        $params = [
            "organization_id" => $organizationId,
            "user_id" => $userId,
            "limit" => $limit,
            "before" => $before,
            "after" => $after,
        ];

        $response = Client::request(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true
        );

        $organizationMemberships = [];

        foreach ($response["data"] as $responseData) {
            \array_push($organizationMemberships, Resource\OrganizationMembership::constructFromResponse($responseData));
        }

        list($before, $after) = Util\Request::parsePaginationArgs($response);

        return [$before, $after, $organizationMemberships];
    }

    /**
     * Sends an Invitation
     *
     * @param string $email The email address of the invitee
     * @param string $organizationId Organization ID
     * @param int $expiresInDays expiration delay in days
     * @param string $inviterUserId User ID of the inviter
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Invitation
     */
    public function sendInvitation($email, $organizationId, $expiresInDays, $inviterUserId)
    {
        $path = "/user_management/invitations";

        $params = [
            "email" => $email,
            "organization_id" => $organizationId,
            "expires_in_days" => $expiresInDays,
            "inviter_user_id" => $inviterUserId
        ];

        $response = Client::request(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true
        );

        return Resource\Invitation::constructFromResponse($response);
    }

    /**
     * Get an Invitation
     *
     * @param string $invitationId ID of the Invitation
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Invitation
     */
    public function getInvitation($invitationId)
    {
        $path = "/user_management/invitations/{$invitationId}";

        $response = Client::request(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true
        );

        return Resource\Invitation::constructFromResponse($response);
    }

    /**
     * List Invitations
     *
     * @param string $invitationId ID of the Invitation
     * @throws Exception\WorkOSException
     *
     * @return array An array containing the following:
     *      null|string Invitation ID to use as before cursor
     *      null|string Invitation ID to use as after cursor
     *      array \WorkOS\Resource\Invitation instances
     */
    public function listInvitations(
        $email = null,
        $organizationId = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null,
        $order = null
    ) {
        $path = "/user_management/invitations";

        $params = [
            "email" => $email,
            "organization_id" => $organizationId,
            "limit" => $limit,
            "before" => $before,
            "after" => $after,
            "order" => $order
        ];

        $response = Client::request(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true
        );

        $invitations = [];

        foreach ($response["data"] as $responseData) {
            \array_push($invitations, Resource\Invitation::constructFromResponse($responseData));
        }

        list($before, $after) = Util\Request::parsePaginationArgs($response);

        return [$before, $after, $invitations];
    }

    /**
     * Revoke an Invitation
     *
     * @param string $invitationId ID of the Invitation
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Invitation
     */
    public function revokeInvitation($invitationId)
    {
        $path = "/user_management/invitations/{$invitationId}/revoke";

        $response = Client::request(
            Client::METHOD_POST,
            $path,
            null,
            null,
            true
        );

        return Resource\Invitation::constructFromResponse($response);
    }

    /**
     * Authenticate a User with Password
     *
     * @param string $clientId This value can be obtained from the Configuration page in the WorkOS dashboard.
     * @param string $email The email address of the user.
     * @param string $password The password of the user.
     * @param string|null $ipAddress The IP address of the request from the user who is attempting to authenticate.
     * @param string|null $userAgent The user agent of the request from the user who is attempting to authenticate.
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function authenticateWithPassword($clientId, $email, $password, $ipAddress = null, $userAgent = null)
    {
        $authenticateWithPasswordPath = "users/authenticate";
        $params = [
            "client_id" => $clientId,
            "email" => $email,
            "password" => $password,
            "ip_address" => $ipAddress,
            "user_agent" => $userAgent,
            "grant_type" => "password",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $authenticateWithPasswordPath, null, $params, true);

        return Resource\UserResponse::constructFromResponse($response);
    }

    /**
     * Authenticate an OAuth or SSO User with a Code
     *
     * @param string $clientId This value can be obtained from the Configuration page in the WorkOS dashboard.
     * @param string $code The authorization value which was passed back as a query parameter in the callback to the Redirect URI.
     * @param string|null $ipAddress The IP address of the request from the user who is attempting to authenticate.
     * @param string|null $userAgent The user agent of the request from the user who is attempting to authenticate.
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function authenticateWithCode($clientId, $code, $ipAddress = null, $userAgent = null)
    {
        $authenticateWithCodePath = "users/authenticate";
        $params = [
            "client_id" => $clientId,
            "code" => $code,
            "ip_address" => $ipAddress,
            "user_agent" => $userAgent,
            "grant_type" => "authorization_code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $authenticateWithCodePath, null, $params, true);

        return Resource\UserResponse::constructFromResponse($response);
    }

    /**
     * Authenticate with Magic Auth
     *
     * @param string $clientId This value can be obtained from the Configuration page in the WorkOS dashboard.
     * @param string $code The authorization value which was passed back as a query parameter in the callback to the Redirect URI.
     * @param string $userId The unique ID of the user.
     * @param string|null $ipAddress The IP address of the request from the user who is attempting to authenticate.
     * @param string|null $userAgent The user agent of the request from the user who is attempting to authenticate.
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */

    public function authenticateWithMagicAuth($clientId, $code, $userId, $ipAddress = null, $userAgent = null)
    {
        $authenticateWithMagicAuthPath = "users/authenticate";
        $params = [
            "client_id" => $clientId,
            "code" => $code,
            "user_id" => $userId,
            "ip_address" => $ipAddress,
            "user_agent" => $userAgent,
            "grant_type" => "urn:workos:oauth:grant-type:magic-auth:code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $authenticateWithMagicAuthPath, null, $params, true);

        return Resource\UserResponse::constructFromResponse($response);
    }

    /**
     * Authenticate with TOTP
     *
     * @param string $clientId This value can be obtained from the Configuration page in the WorkOS dashboard.
     * @param string $pendingAuthenticationToken
     * @param string $authenticationChallengeId
     * @param string $code
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function authenticateWithTotp($clientId, $pendingAuthenticationToken, $authenticationChallengeId, $code)
    {
        $authenticatePath = "users/authenticate";
        $params = [
            "client_id" => $clientId,
            "pending_authentication_token" => $pendingAuthenticationToken,
            "authentication_challenge_id" => $authenticationChallengeId,
            "code" => $code,
            "grant_type" => "urn:workos:oauth:grant-type:mfa-totp",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $authenticatePath, null, $params, true);

        return Resource\UserResponse::constructFromResponse($response);
    }

    /**
     * Enroll An Authentication Factor.
     *
     * @param string $userId The unique ID of the user.
     * @param string $type The type of MFA factor used to authenticate.
     *
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function enrollAuthFactor($userId, $type)
    {
        $enrollAuthFactorPath = "users/{$userId}/auth/factors";

        $params = [
            "type" => $type
        ];

        $response = Client::request(Client::METHOD_POST, $enrollAuthFactorPath, null, $params, true);

        return Resource\AuthenticationFactorAndChallengeTotp::constructFromResponse($response);
    }

    /**
     * List a User's Authentication Factors.
     *
     * @param string $userId The unique ID of the user.
     *
     * @throws Exception\WorkOSException
     *
     * @return array $authFactors An array containing the user's authentication factors as \WorkOS\Resource\UserAuthenticationFactorTotp instances
     */
    public function listAuthFactors($userId)
    {
        $usersPath = "users/{$userId}/auth/factors";

        $response = Client::request(Client::METHOD_GET, $usersPath, null, null, true);

        $authFactors = [];

        foreach ($response["data"] as $responseData) {
            \array_push($authFactors, Resource\UserAuthenticationFactorTotp::constructFromResponse($responseData));
        }

        return $authFactors;
    }

    /**
     * Create Email Verification Challenge.
     *
     * @param string $userId The unique ID of the User whose email address will be verified.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\User
     */
    public function sendVerificationEmail($userId)
    {
        $sendVerificationEmailPath = "user_management/users/{$userId}/email_verification/send";

        $response = Client::request(Client::METHOD_POST, $sendVerificationEmailPath, null, null, true);

        return Resource\User::constructFromResponse($response);
    }

    /**
     * Complete Email Verification.
     *
     * @param string $userId The unique ID of the user.
     * @param string $code The one-time code emailed to the user.
     *
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function verifyEmailCode($userId, $code)
    {
        $verifyEmailCodePath = "user_management/users/{$userId}/email_verification/confirm";

        $params = [
            "code" => $code
        ];

        $response = Client::request(Client::METHOD_POST, $verifyEmailCodePath, null, $params, true);

        return Resource\UserResponse::constructFromResponse($response);
    }

    /**
     * Create Password Reset Email.
     *
     * @param string $email The email of the user that wishes to reset their password.
     * @param string $passwordResetUrl The URL that will be linked to in the email.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserAndToken
     */
    public function sendPasswordResetEmail($email, $passwordResetUrl)
    {
        $sendPasswordResetEmailPath = "user_management/password_reset/send";

        $params = [
            "email" => $email,
            "password_reset_url" => $passwordResetUrl
        ];

        $response = Client::request(Client::METHOD_POST, $sendPasswordResetEmailPath, null, $params, true);

        return Resource\UserAndToken::constructFromResponse($response);
    }

    /**
     * Complete Password Reset.
     *
     * @param string $token The reset token emailed to the user.
     * @param string $newPassword The new password to be set for the user.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function resetPassword($token, $newPassword)
    {
        $resetPasswordPath = "user_management/password_reset/confirm";

        $params = [
            "token" => $token,
            "new_password" => $newPassword
        ];

        $response = Client::request(Client::METHOD_POST, $resetPasswordPath, null, $params, true);

        return Resource\UserResponse::constructFromResponse($response);
    }

    /**
     * Update a User's password.
     *
     * @param string $userId The unique ID of the user.
     * @param string $password The password of the user.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\User
     */
    public function updateUserPassword($userId, $password)
    {
        $usersPath = "users/{$userId}/password";

        $params = [
            "password" => $password
        ];

        $response = Client::request(Client::METHOD_PUT, $usersPath, null, $params, true);

        return Resource\User::constructFromResponse($response);
    }

    /**
     * Creates a one-time Magic Auth code and emails it to the user.
     *
     * @param string $email The email address the one-time code will be sent to.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\User
     */
    public function sendMagicAuthCode($email)
    {
        $sendCodePath = "/user_management/magic_auth/send";

        $params = [
            "email" => $email,
        ];

        $response = Client::request(
            Client::METHOD_POST,
            $sendCodePath,
            null,
            $params,
            true
        );

        return Resource\User::constructFromResponse($response);
    }
}
