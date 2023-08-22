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
     * Authenticate a User with Password
     *
     * @param string $clientId This value can be obtained from the Configuration page in the WorkOS dashboard.
     * @param string $email The email address of the user.
     * @param string $password The password of the user.
     * @param string|null $ipAddress The IP address of the request from the user who is attempting to authenticate.
     * @param string|null $userAgent The user agent of the request from the user who is attempting to authenticate.
     * @param int|null $expiresIn The length of the session in minutes. Defaults to 1 day, 1440.
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\SessionAndUser
     */
    public function authenticateUserWithPassword($clientId, $email, $password, $ipAddress = null, $userAgent = null, $expiresIn = null)
    {
        if (!$expiresIn) {
            $expiresIn = self::DEFAULT_TOKEN_EXPIRATION;
        }

        $authenticateUserWithPasswordPath = "users/sessions/token";
        $params = [
            "client_id" => $clientId,
            "email" => $email,
            "password" => $password,
            "ip_address" => $ipAddress,
            "user_agent" => $userAgent,
            "expires_in" => $expiresIn,
            "grant_type" => "password",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $authenticateUserWithPasswordPath, null, $params, true);

        return Resource\SessionAndUser::constructFromResponse($response);
    }

    /**
     * Authenticate an OAuth or SSO User with a Code
     *
     * @param string $clientId This value can be obtained from the Configuration page in the WorkOS dashboard.
     * @param string $code The authorization value which was passed back as a query parameter in the callback to the Redirect URI.
     * @param string|null $ipAddress The IP address of the request from the user who is attempting to authenticate.
     * @param string|null $userAgent The user agent of the request from the user who is attempting to authenticate.
     * @param int|null $expiresIn The length of the session in minutes. Defaults to 1 day, 1440.
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\SessionAndUser
     */
    public function authenticateUserWithCode($clientId, $code, $ipAddress = null, $userAgent = null, $expiresIn = null)
    {
        if (!$expiresIn) {
            $expiresIn = self::DEFAULT_TOKEN_EXPIRATION;
        }

        $authenticateUserWithCodePath = "users/sessions/token";
        $params = [
            "client_id" => $clientId,
            "code" => $code,
            "ip_address" => $ipAddress,
            "user_agent" => $userAgent,
            "expires_in" => $expiresIn,
            "grant_type" => "authorization_code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $authenticateUserWithCodePath, null, $params, true);

        return Resource\SessionAndUser::constructFromResponse($response);
    }

    /**
     * Authenticate with Magic Auth
     *
     * @param string $clientId This value can be obtained from the Configuration page in the WorkOS dashboard.
     * @param string $code The authorization value which was passed back as a query parameter in the callback to the Redirect URI.
     * @param string $magicAuthChallengeId The challenge ID returned from when the one-time code was sent to the user.
     * @param string|null $ipAddress The IP address of the request from the user who is attempting to authenticate.
     * @param string|null $userAgent The user agent of the request from the user who is attempting to authenticate.
     * @param int|null $expiresIn The length of the session in minutes. Defaults to 1 day, 1440.
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\SessionAndUser
     */
    public function authenticateUserWithMagicAuth($clientId, $code, $magicAuthChallengeId, $ipAddress = null, $userAgent = null, $expiresIn = null)
    {
        if (!$expiresIn) {
            $expiresIn = self::DEFAULT_TOKEN_EXPIRATION;
        }

        $authenticateUserWithMagicAuthPath = "users/sessions/token";
        $params = [
            "client_id" => $clientId,
            "code" => $code,
            "magic_auth_challenge_id" => $magicAuthChallengeId,
            "ip_address" => $ipAddress,
            "user_agent" => $userAgent,
            "expires_in" => $expiresIn,
            "grant_type" => "urn:workos:oauth:grant-type:magic-auth:code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $response = Client::request(Client::METHOD_POST, $authenticateUserWithMagicAuthPath, null, $params, true);

        return Resource\SessionAndUser::constructFromResponse($response);
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
     * Create Email Verification Challenge.
     *
     * @param string $id The unique ID of the User whose email address will be verified.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\MagicAuthchallenge
     */
    public function sendVerificationEmail($id)
    {
        $sendVerificationEmailPath = "users/{$id}/send_verification_email";

        $response = Client::request(Client::METHOD_POST, $sendVerificationEmailPath, null, null, true);

        return Resource\MagicAuthChallenge::constructFromResponse($response);
    }

    /**
     * Complete Email Verification.
     *
     * @param string $magicAuthChallengeId The challenge ID returned from the send verification email endpoint.
     * @param string $code The one-time code emailed to the user.
     *
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\User
     */
    public function verifyEmail($magicAuthChallengeId, $code)
    {
        $verifyEmailPath = "users/verify_email";

        $params = [
            "magic_auth_challenge_id" => $magicAuthChallengeId,
            "code" => $code
        ];

        $response = Client::request(Client::METHOD_POST, $verifyEmailPath, null, $params, true);

        return Resource\User::constructFromResponse($response);
    }

    /**
     * Create Password Reset Challenge.
     *
     * @param string $email The email of the user that wishes to reset their password.
     * @param string $passwordResetUrl The URL that will be linked to in the email.
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\UserAndToken
     */
    public function createPasswordResetChallenge($email, $passwordResetUrl)
    {
        $createPasswordResetChallengePath = "users/password_reset_challenge";

        $params = [
            "email" => $email,
            "password_reset_url" => $passwordResetUrl
        ];

        $response = Client::request(Client::METHOD_POST, $createPasswordResetChallengePath, null, $params, true);

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
     * @return \WorkOS\Resource\User
     */
    public function completePasswordReset($token, $newPassword)
    {
        $completePasswordResetPath = "users/password_reset";

        $params = [
            "token" => $token,
            "new_password" => $newPassword
        ];

        $response = Client::request(Client::METHOD_POST, $completePasswordResetPath, null, $params, true);

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
