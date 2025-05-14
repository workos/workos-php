<?php

namespace Work;O;S;

/**
 * Class UserManagement
 */
class UserManagement
{
    public const DEFAULT_PAGE_SIZE = 10;
    public const DEFAULT_TOKEN_EXPIRATION = 1440;

    public const AUTHORIZATION_PROVIDER_AUTHKIT = "authkit";
    public const AUTHORIZATION_PROVIDER_APPLE_OAUTH = "AppleOAuth";
    public const AUTHORIZATION_PROVIDER_GITHUB_OAUTH = "GitHubOAuth";
    public const AUTHORIZATION_PROVIDER_GOOGLE_OAUTH = "GoogleOAuth";
    public const AUTHORIZATION_PROVIDER_MICROSOFT_OAUTH = "MicrosoftOAuth";

    /**
     * Create User.
     *
     * @param string $email The email address of the user.
     * @param string|null $password The password of the user.
     * @param string|null $firstName The first name of the user.
     * @param string|null $lastName The last name of the user.
     * @param boolean|null $emailVerified A boolean declaring if the user's email has been verified.
     * @param string|null $passwordHash The hashed password to set for the user.
     * @param string|null $passwordHashType The algorithm originally used to hash the password. Valid values are `bcrypt`, `ssha`, and `firebase-scrypt`.
     * @param string|null $externalId The user's external ID.
     * @param array<string, string> $metadata The user's metadata.
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\User
     */

    public function createUser(
        $email,
        $password = null,
        $firstName = null,
        $lastName = null,
        $emailVerified = null,
        $passwordHash = null,
        $passwordHashType = null,
        $externalId = null,
        $metadata = null
    ) {
        $path = "user_management/users";
        $params = [
            "email" => $email,
            "email_verified" => $emailVerified,
            "first_name" => $firstName,
            "last_name" => $lastName,
        ];
    }
}