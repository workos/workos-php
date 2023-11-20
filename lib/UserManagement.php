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
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\User
     */
    public function createUser($email, $password, $firstName, $lastName, $emailVerified)
    {
        $path = "users";
        $params = [
            "email" => $email,
            "password" => $password,
            "first_name" => $firstName,
            "last_name" => $lastName,
            "email_verified" => $emailVerified
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

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
        $path = "users/{$userId}";

        $response = Client::request(Client::METHOD_GET, $path, null, null, true);

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
        $path = "users/{$userId}";

        $params = [
            "first_name" => $firstName,
            "last_name" => $lastName,
            "email_verified" => $emailVerified
        ];

        $response = Client::request(Client::METHOD_PUT, $path, null, $params, true);

        return Resource\User::constructFromResponse($response);
    }

    /**
     * List Users.
     *
     * @param null|string $email
     * @param null|string $organizationId Organization users are a member of
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
        $organizationId = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null,
        $order = null
    ) {
        $path = "user_management/users";

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
        $path = "user_management/users/{$userId}";

        $response = Client::request(Client::METHOD_DELETE, $path, null, null, true);

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
     * @param string|null $userId User ID
     * @param string|null $organizationId Organization ID
     * @param int $limit Maximum number of records to return
     * @param string|null $before Organization Membership ID to look before
     * @param string|null $after Organization Membership ID to look after
     *
     * @throws Exception\WorkOSException
     *
     * @return array An array containing the following:
     *      string|null Organization Membership ID to use as before cursor
     *      string|null Organization Membership ID to use as after cursor
     *      array \WorkOS\Resource\OrganizationMembership instances
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
     * @param string|null $organizationId Organization ID
     * @param int|null $expiresInDays expiration delay in days
     * @param string|null $inviterUserId User ID of the inviter
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Invitation
     */
    public function sendInvitation(
        $email,
        $organizationId = null,
        $expiresInDays = null,
        $inviterUserId = null
    ) {
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
     *
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
     * @param string|null $email Email of the invitee
     * @param string|null $organizationId Organization ID
     * @param int $limit Maximum number of records to return
     * @param string|null $before Organization Membership ID to look before
     * @param string|null $after Organization Membership ID to look after
     * @param string|null $after Sort order
     *
     * @throws Exception\WorkOSException
     *
     * @return array An array containing the following:
     *      string|null Invitation ID to use as before cursor
     *      string|null Invitation ID to use as after cursor
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
     *
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
     * Generates an OAuth 2.0 authorization URL used to initiate the SSO flow with WorkOS.
     *
     * @param null|string $redirectUri URI to direct the user to upon successful completion of SSO
     * @param null|array $state Associative array containing state that will be returned from WorkOS as a json encoded string
     * @param null|string $provider Service provider that handles the identity of the user
     * @param null|string $connectionId Unique identifier for a WorkOS Connection
     * @param null|string $organizationId Unique identifier for a WorkOS Organization
     * @param null|string $domainHint DDomain hint that will be passed as a parameter to the IdP login page
     * @param null|string $loginHint Username/email hint that will be passed as a parameter to the to IdP login page
     *
     * @throws Exception\UnexpectedValueException
     * @throws Exception\ConfigurationException
     *
     * @return string
     */
    public function getAuthorizationUrl(
        $redirectUri,
        $state,
        $provider = null,
        $connectionId = null,
        $organizationId = null,
        $domainHint = null,
        $loginHint = null
    ) {
        $path = "user_management/authorize";

        if (!isset($provider) && !isset($connectionId) && !isset($organizationId)) {
            $msg = "Either \$provider, \$connectionId, or \$organizationId is required";
            throw new Exception\UnexpectedValueException($msg);
        }

        $params = [
            "client_id" => WorkOS::getClientId(),
            "response_type" => "code"
        ];

        if ($redirectUri) {
            $params["redirect_uri"] = $redirectUri;
        }

        if (null !== $state && !empty($state)) {
            $params["state"] = \json_encode($state);
        }

        if ($provider) {
            $params["provider"] = $provider;
        }

        if ($connectionId) {
            $params["connection_id"] = $connectionId;
        }

        if ($organizationId) {
            $params["organization_id"] = $organizationId;
        }

        if ($domainHint) {
            $params["domain_hint"] = $domainHint;
        }

        if ($loginHint) {
            $params["login_hint"] = $loginHint;
        }

        return Client::generateUrl($path, $params);
    }

    /**
     * Authenticate a User with Password
     *
     * @param string $clientId This value can be obtained from the Configuration page in the WorkOS dashboard.
     * @param string $email The email address of the user.
     * @param string $password The password of the user.
     * @param string|null $ipAddress The IP address of the request from the user who is attempting to authenticate.
     * @param string|null $userAgent The user agent of the request from the user who is attempting to authenticate.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function authenticateWithPassword($clientId, $email, $password, $ipAddress = null, $userAgent = null)
    {
        $path = "users/authenticate";
        $params = [
            "client_id" => $clientId,
            "email" => $email,
            "password" => $password,
            "ip_address" => $ipAddress,
            "user_agent" => $userAgent,
            "grant_type" => "password",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

        return Resource\UserResponse::constructFromResponse($response);
    }

    /**
     * Authenticate an OAuth or SSO User with a Code
     *
     * @param string $clientId This value can be obtained from the Configuration page in the WorkOS dashboard.
     * @param string $code The authorization value which was passed back as a query parameter in the callback to the Redirect URI.
     * @param string|null $ipAddress The IP address of the request from the user who is attempting to authenticate.
     * @param string|null $userAgent The user agent of the request from the user who is attempting to authenticate.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function authenticateWithCode($clientId, $code, $ipAddress = null, $userAgent = null)
    {
        $path = "users/authenticate";
        $params = [
            "client_id" => $clientId,
            "code" => $code,
            "ip_address" => $ipAddress,
            "user_agent" => $userAgent,
            "grant_type" => "authorization_code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

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
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */

    public function authenticateWithMagicAuth($clientId, $code, $userId, $ipAddress = null, $userAgent = null)
    {
        $path = "users/authenticate";
        $params = [
            "client_id" => $clientId,
            "code" => $code,
            "user_id" => $userId,
            "ip_address" => $ipAddress,
            "user_agent" => $userAgent,
            "grant_type" => "urn:workos:oauth:grant-type:magic-auth:code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

        return Resource\UserResponse::constructFromResponse($response);
    }

    /**
     * Authenticate with TOTP
     *
     * @param string $clientId This value can be obtained from the Configuration page in the WorkOS dashboard.
     * @param string $pendingAuthenticationToken
     * @param string $authenticationChallengeId
     * @param string $code
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function authenticateWithTotp($clientId, $pendingAuthenticationToken, $authenticationChallengeId, $code)
    {
        $path = "users/authenticate";
        $params = [
            "client_id" => $clientId,
            "pending_authentication_token" => $pendingAuthenticationToken,
            "authentication_challenge_id" => $authenticationChallengeId,
            "code" => $code,
            "grant_type" => "urn:workos:oauth:grant-type:mfa-totp",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

        return Resource\UserResponse::constructFromResponse($response);
    }

    /**
     * Enroll An Authentication Factor.
     *
     * @param string $userId The unique ID of the user.
     * @param string $type The type of MFA factor used to authenticate.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function enrollAuthFactor($userId, $type)
    {
        $path = "users/{$userId}/auth/factors";

        $params = [
            "type" => $type
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

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
        $path = "users/{$userId}/auth/factors";

        $response = Client::request(Client::METHOD_GET, $path, null, null, true);

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
        $path = "user_management/users/{$userId}/email_verification/send";

        $response = Client::request(Client::METHOD_POST, $path, null, null, true);

        return Resource\User::constructFromResponse($response);
    }

    /**
     * Complete Email Verification.
     *
     * @param string $userId The unique ID of the user.
     * @param string $code The one-time code emailed to the user.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserResponse
     */
    public function verifyEmail($userId, $code)
    {
        $path = "user_management/users/{$userId}/email_verification/confirm";

        $params = [
            "code" => $code
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

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
        $path = "user_management/password_reset/send";

        $params = [
            "email" => $email,
            "password_reset_url" => $passwordResetUrl
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

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
        $path = "user_management/password_reset/confirm";

        $params = [
            "token" => $token,
            "new_password" => $newPassword
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

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
        $path = "users/{$userId}/password";

        $params = [
            "password" => $password
        ];

        $response = Client::request(Client::METHOD_PUT, $path, null, $params, true);

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
        $path = "/user_management/magic_auth/send";

        $params = [
            "email" => $email,
        ];

        $response = Client::request(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true
        );

        return Resource\User::constructFromResponse($response);
    }
}
