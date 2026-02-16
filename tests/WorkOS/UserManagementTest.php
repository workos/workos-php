<?php

namespace WorkOS;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkOS\Resource\RoleResponse;

class UserManagementTest extends TestCase
{
    use TestHelper {
        setUp as traitSetUp;
    }

    /**
     * @var UserManagement
     */
    protected $userManagement;

    protected function setUp(): void
    {
        $this->traitSetUp();

        $this->withApiKeyAndClientId();
        $this->userManagement = new UserManagement();
    }

    public function testDeleteUser()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $path = "user_management/users/{$userId}";
        $responseCode = 200;

        $this->mockRequest(
            Client::METHOD_DELETE,
            $path,
            null,
            null,
            true,
            null,
            null,
            $responseCode
        );

        $response = $this->userManagement->deleteUser($userId);
        $this->assertSame(200, $responseCode);
        $this->assertSame($response, []);
    }

    public function testUpdateUser()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $path = "user_management/users/{$userId}";

        $result = $this->createUserResponseFixture();

        $params = [
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified" => true,
            "password" => null,
            "password_hash" => null,
            "password_hash_type" => null,
            "external_id" => null,
            "metadata" => null,
            "email" => null
        ];

        $this->mockRequest(
            Client::METHOD_PUT,
            $path,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->updateUser("user_01H7X1M4TZJN5N4HG4XXMA1234", "Damien", "Alabaster", true);
        $this->assertSame($user, $response->toArray());
    }

    public function testUpdateUserWithNullOptionalParams()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $path = "user_management/users/{$userId}";

        $result = $this->createUserResponseFixture();

        $params = [
            "first_name" => null,
            "last_name" => null,
            "email_verified" => null,
            "password" => null,
            "password_hash" => null,
            "password_hash_type" => null,
            "external_id" => null,
            "metadata" => null,
            "email" => null
        ];

        $this->mockRequest(
            Client::METHOD_PUT,
            $path,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->updateUser("user_01H7X1M4TZJN5N4HG4XXMA1234", null, null, null, null, null, null, null, null, null);
        $this->assertSame($user, $response->toArray());
    }

    public function testAuthorizationURLInvalidInputs()
    {
        $this->expectException(Exception\UnexpectedValueException::class);
        $authorizationUrl = $this->userManagement->getAuthorizationUrl(
            "https://apage.com",
            null,
            "randomProvider",
            null,
            null,
            null,
            null,
            'sign-up'
        );

        $this->expectException(Exception\UnexpectedValueException::class);
        $authorizationUrl = $this->userManagement->getAuthorizationUrl(
            "https://apage.com",
            null,
            "randomProvider",
        );

        $this->expectException(Exception\UnexpectedValueException::class);
        $authorizationUrl = $this->userManagement->getAuthorizationUrl(
            "https://apage.com",
            null,
            null,
            null,
        );
    }

    public static function authorizationUrlTestDataProvider()
    {
        return [
            [null, null, Resource\ConnectionType::AppleOAuth, null],
            [null, null, Resource\ConnectionType::GitHubOAuth, null],
            [null, null, Resource\ConnectionType::GoogleOAuth, null],
            [null, null, Resource\ConnectionType::MicrosoftOAuth, null],
            [null, null, null, "connection_123"],
            [null, null, null, null, "org_01FG7HGMY2CZZR2FWHTEE94VF0"],
            ["https://papagenos.com/auth/callback", null, null, "connection_123", null, "foo.com", null],
            ["https://papagenos.com/auth/callback", null, null, "connection_123", null, null, "foo@workos.com"],
            ["https://papagenos.com/auth/callback", null, null, "connection_123"],
            [null, null, null, "connection_123"],
            ["https://papagenos.com/auth/callback", ["toppings" => "ham"], null, "connection_123"],
            ["https://papagenos.com/auth/callback", null, null, "connection_123", null, null, null, null, ["read", "write"]],
            [null, null, Resource\ConnectionType::GoogleOAuth, null, null, null, null, null, ["email", "profile"]]
        ];
    }

    /**
     * @dataProvider authorizationUrlTestDataProvider
     */
    public function testAuthorizationURLExpectedParams(
        $redirectUri,
        $state,
        $provider,
        $connectionId,
        $organizationId = null,
        $domainHint = null,
        $loginHint = null,
        $screenHint = null,
        $providerScopes = null
    ) {
        $expectedParams = [
            "client_id" => WorkOS::getClientId(),
            "response_type" => "code"
        ];

        if ($redirectUri) {
            $expectedParams["redirect_uri"] = $redirectUri;
        }

        if (null !== $state && !empty($state)) {
            $expectedParams["state"] = \json_encode($state);
        }

        if ($provider) {
            $expectedParams["provider"] = $provider;
        }

        if ($connectionId) {
            $expectedParams["connection_id"] = $connectionId;
        }

        if ($organizationId) {
            $expectedParams["organization_id"] = $organizationId;
        }

        if ($domainHint) {
            $expectedParams["domain_hint"] = $domainHint;
        }

        if ($loginHint) {
            $expectedParams["login_hint"] = $loginHint;
        }

        if ($providerScopes && is_array($providerScopes)) {
            $expectedParams["provider_scopes"] = implode(",", $providerScopes);
        }

        $authorizationUrl = $this->userManagement->getAuthorizationUrl(
            $redirectUri,
            $state,
            $provider,
            $connectionId,
            $organizationId,
            $domainHint,
            $loginHint,
            $screenHint,
            $providerScopes
        );
        $paramsString = \parse_url($authorizationUrl, \PHP_URL_QUERY);
        \parse_str($paramsString, $paramsArray);
        $this->assertSame($expectedParams, $paramsArray);
    }

    public function testAuthenticateWithPassword()
    {
        $path = "user_management/authenticate";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->UserResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "email" => "marcelina@foo-corp.com",
            "password" => "i8uv6g34kd490s",
            "ip_address" => null,
            "user_agent" => null,
            "grant_type" => "password",
            "client_secret" => WorkOS::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateWithPassword("project_0123456", "marcelina@foo-corp.com", "i8uv6g34kd490s");
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testAuthenticateWithSelectedOrganization()
    {
        $path = "user_management/authenticate";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->userAndOrgResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "pending_authentication_token" => "token_super_safe",
            "organization_id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
            "ip_address" => null,
            "user_agent" => null,
            "grant_type" => "urn:workos:oauth:grant-type:organization-selection",
            "client_secret" => WorkOS::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateWithSelectedOrganization(
            "project_0123456",
            "token_super_safe",
            "org_01EHQMYV6MBK39QC5PZXHY59C3"
        );
        $this->assertSame($userFixture, $response->user->toArray());
        $this->assertSame("org_01EHQMYV6MBK39QC5PZXHY59C3", $response->organizationId);
    }

    public function testAuthenticateWithEmailVerification()
    {
        $path = "user_management/authenticate";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->userAndOrgResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "code" => "code_super_safe",
            "pending_authentication_token" => "token_super_safe",
            "ip_address" => null,
            "user_agent" => null,
            "grant_type" => "urn:workos:oauth:grant-type:email-verification:code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateWithEmailVerification(
            "project_0123456",
            "code_super_safe",
            "token_super_safe",
        );
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testAuthenticateWithCode()
    {
        $path = "user_management/authenticate";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->UserResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "code" => "01E2RJ4C05B52KKZ8FSRDAP23J",
            "ip_address" => null,
            "user_agent" => null,
            "grant_type" => "authorization_code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateWithCode("project_0123456", "01E2RJ4C05B52KKZ8FSRDAP23J");
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testAuthenticateImpersonatorWithCode()
    {
        $path = "user_management/authenticate";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->userAndImpersonatorResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "code" => "01E2RJ4C05B52KKZ8FSRDAP23J",
            "ip_address" => null,
            "user_agent" => null,
            "grant_type" => "authorization_code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateWithCode("project_0123456", "01E2RJ4C05B52KKZ8FSRDAP23J");
        $this->assertSame($userFixture, $response->user->toArray());
        $this->assertSame([
            "email" => "admin@foocorp.com",
            "reason" => "Helping debug an account issue."
        ], $response->impersonator->toArray());
    }

    public function testAuthenticateWithOAuthTokensReturned()
    {
        $path = "user_management/authenticate";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->userAndOAuthTokensResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "code" => "01E2RJ4C05B52KKZ8FSRDAP23J",
            "ip_address" => null,
            "user_agent" => null,
            "grant_type" => "authorization_code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateWithCode("project_0123456", "01E2RJ4C05B52KKZ8FSRDAP23J");
        $this->assertSame($userFixture, $response->user->toArray());

        // Test OAuth tokens
        $this->assertNotNull($response->oauthTokens);
        $this->assertSame("oauth_access_token_123", $response->oauthTokens->accessToken);
        $this->assertSame("oauth_refresh_token_456", $response->oauthTokens->refreshToken);
        $this->assertSame(1640995200, $response->oauthTokens->expiresAt);
        $this->assertSame(["read", "write"], $response->oauthTokens->scopes);
    }

    public function testEnrollAuthFactor()
    {
        $userId = "user_123456";
        $path = "user_management/users/{$userId}/auth_factors";
        $params = [
            "type" => "totp",
            "totp_user" => "totpUser",
            "totp_issuer" => "totpIssuer"
        ];

        $result = $this->enrollAuthFactorResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $enrollFactorTotp = $this->userManagement->enrollAuthFactor($userId, "totp", "totpIssuer", "totpUser");
        $enrollUserAuthFactorFixture = $this->enrollAuthFactorFixture();
        $enrollUserAuthChallengeFixture = $this->enrollAuthChallengeFixture();

        $this->assertSame($enrollUserAuthFactorFixture, $enrollFactorTotp->authenticationFactor->toArray());
        $this->assertSame($enrollUserAuthChallengeFixture, $enrollFactorTotp->authenticationChallenge->toArray());
    }

    public function testEnrollAuthFactorWithNullOptionalParams()
    {
        $userId = "user_123456";
        $path = "user_management/users/{$userId}/auth_factors";
        $params = [
            "type" => "totp",
            "totp_user" => null,
            "totp_issuer" => null
        ];

        $result = $this->enrollAuthFactorResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $enrollFactorTotp = $this->userManagement->enrollAuthFactor($userId, "totp", null, null);
        $enrollUserAuthFactorFixture = $this->enrollAuthFactorFixture();
        $enrollUserAuthChallengeFixture = $this->enrollAuthChallengeFixture();

        $this->assertSame($enrollUserAuthFactorFixture, $enrollFactorTotp->authenticationFactor->toArray());
        $this->assertSame($enrollUserAuthChallengeFixture, $enrollFactorTotp->authenticationChallenge->toArray());
    }

    public function testAuthenticateWithRefreshToken()
    {
        $path = "user_management/authenticate";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->UserResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "refresh_token" => "Xw0NsCVXMBf7svAoIoKBmkpEK",
            "organization_id" => null,
            "ip_address" => null,
            "user_agent" => null,
            "grant_type" => "refresh_token",
            "client_secret" => WorkOS::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateWithRefreshToken("project_0123456", "Xw0NsCVXMBf7svAoIoKBmkpEK");
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testAuthenticateWithTotp()
    {
        $path = "user_management/authenticate";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->UserResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "pending_authentication_token" => "cTDQJTTkTkkVYxQUlKBIxEsFs",
            "authentication_challenge_id" => "auth_challenge_01H96FETXGTW1QMBSBT2T36PW0",
            "code" => "123456",
            "ip_address" => null,
            "user_agent" => null,
            "grant_type" => "urn:workos:oauth:grant-type:mfa-totp",
            "client_secret" => WorkOS::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateWithTotp("project_0123456", "cTDQJTTkTkkVYxQUlKBIxEsFs", "auth_challenge_01H96FETXGTW1QMBSBT2T36PW0", "123456");
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testAuthenticateWithMagicAuth()
    {
        $path = "user_management/authenticate";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->UserResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "code" => "123456",
            "user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "ip_address" => null,
            "user_agent" => null,
            "grant_type" => "urn:workos:oauth:grant-type:magic-auth:code",
            "client_secret" => WorkOS::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateWithMagicAuth("project_0123456", "123456", "user_01H7X1M4TZJN5N4HG4XXMA1234");
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testCreateUser()
    {
        $path = "user_management/users";

        $result = $this->createUserResponseFixture();

        $params = [
            "email" => "test@test.com",
            "password" => "x^T!V23UN1@V",
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified" => true,
            "password_hash" => null,
            "password_hash_type" => null,
            "external_id" => null,
            "metadata" => null
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->createUser("test@test.com", "x^T!V23UN1@V", "Damien", "Alabaster", true);
        $this->assertSame($user, $response->toArray());
    }

    public function testCreateUserWithNullOptionalParams()
    {
        $path = "user_management/users";

        $result = $this->createUserResponseFixture();

        $params = [
            "email" => "test@test.com",
            "password" => null,
            "first_name" => null,
            "last_name" => null,
            "email_verified" => null,
            "password_hash" => null,
            "password_hash_type" => null,
            "external_id" => null,
            "metadata" => null
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->createUser("test@test.com", null, null, null, null, null, null, null, null);
        $this->assertSame($user, $response->toArray());
    }

    public function testGetEmailVerification()
    {
        $emailVerificationId = "email_verification_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/email_verification/{$emailVerificationId}";

        $result = $this->emailVerificationResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $response = $this->userManagement->getEmailVerification($emailVerificationId);

        $expected = $this->emailVerificationFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    public function testSendVerificationEmail()
    {
        $userId = "user_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/users/{$userId}/email_verification/send";

        $result = $this->userResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            null,
            true,
            $result
        );


        $user = $this->userFixture();

        $response = $this->userManagement->sendVerificationEmail("user_01E4ZCR3C56J083X43JQXF3JK5");
        $this->assertSame($user, $response->user->toArray());
    }

    public function testVerifyEmail()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $path = "user_management/users/{$userId}/email_verification/confirm";

        $result = $this->UserResponseFixture();

        $params = [
            "code" => "01DMEK0J53CVMC32CK5SE0KZ8Q",
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->verifyEmail("user_01H7X1M4TZJN5N4HG4XXMA1234", "01DMEK0J53CVMC32CK5SE0KZ8Q");
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testGetPasswordReset()
    {
        $passwordResetId = "password_reset_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/password_reset/{$passwordResetId}";

        $result = $this->passwordResetResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $response = $this->userManagement->getPasswordReset($passwordResetId);

        $expected = $this->passwordResetFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    public function testCreatePasswordReset()
    {
        $path = "user_management/password_reset";

        $response = $this->passwordResetResponseFixture();

        $params = [
            "email" => "someemail@test.com"
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $response
        );

        $response = $this->userManagement->createPasswordReset(
            "someemail@test.com",
        );

        $expected = $this->passwordResetFixture();
        $this->assertSame($expected, $response->toArray());
    }

    public function testSendPasswordResetEmail()
    {
        $path = "user_management/password_reset/send";

        // Mock the API request
        $responseCode = 200;
        $params = [
            "email" => "test@test.com",
            "password_reset_url" => "https://your-app.com/reset-password"
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            null,
            null,
            $responseCode
        );

        $response = $this->userManagement->sendPasswordResetEmail("test@test.com", "https://your-app.com/reset-password");
        // Test the functionality
        $this->assertSame(200, $responseCode);
        $this->assertSame($response, []);
    }

    public function testResetPassword()
    {
        $path = "user_management/password_reset/confirm";

        $result = $this->userResponseFixture();

        $params = [
            "token" => "01DMEK0J53CVMC32CK5SE0KZ8Q",
            "new_password" => "^O9w8hiZu3x!"
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->resetPassword("01DMEK0J53CVMC32CK5SE0KZ8Q", "^O9w8hiZu3x!");
        $this->assertSame($userFixture, $response->user->toArray());
    }



    public function testGetUser()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $path = "user_management/users/{$userId}";

        $result = $this->getUserResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->getUser($userId);

        $this->assertSame($user, $response->toArray());
    }

    public function testListUsers()
    {
        $path = "user_management/users";
        $params = [
            "email" => null,
            "organization_id" => null,
            "limit" => UserManagement::DEFAULT_PAGE_SIZE,
            "before" => null,
            "after" => null,
            "order" => null
        ];

        $result = $this->listUsersResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();
        list($before, $after, $users) = $this->userManagement->listUsers();
        $this->assertSame($user, $users[0]->toArray());
    }

    public function testGetMagicAuth()
    {
        $magicAuthId = "magic_auth_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/magic_auth/{$magicAuthId}";

        $result = $this->magicAuthResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $response = $this->userManagement->getMagicAuth($magicAuthId);

        $expected = $this->magicAuthFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    public function testCreateMagicAuth()
    {
        $path = "user_management/magic_auth";

        $result = $this->magicAuthResponseFixture();

        $params = [
            "email" => "someemail@test.com",
            "invitation_token" => null
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $response = $this->userManagement->createMagicAuth(
            "someemail@test.com",
        );

        $expected = $this->magicAuthFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    private function testSendMagicAuthCode()
    {
        $path = "user_management/magic_auth/send";

        $params = [
            "email" => "test@test.com"
        ];

        $responseCode = 200;

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            null,
            null,
            $responseCode
        );

        $user = $this->userFixture();

        $response = $this->userManagement->sendMagicAuthCode("test@test.com");

        $this->assertSame(200, $responseCode);
        $this->assertSame($response, []);
    }

    public function testListAuthFactors()
    {
        $userId = "user_01H96FETWYSJMJEGF0Q3ZB272F";
        $path = "user_management/users/{$userId}/auth_factors";

        $result = $this->listAuthFactorResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $authFactors = $this->listAuthFactorFixture();
        $response = $this->userManagement->listAuthFactors("user_01H96FETWYSJMJEGF0Q3ZB272F");
        $this->assertSame($authFactors, $response[0]->toArray());
    }

    public function testCreateOrganizationMembership()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $orgId = "org_01EHQMYV6MBK39QC5PZXHY59C3";
        $roleSlug = "admin";
        $path = "user_management/organization_memberships";

        $result = $this->organizationMembershipResponseFixture();

        $params = [
            "organization_id" => $orgId,
            "user_id" => $userId,
            "role_slug" => $roleSlug,
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $organizationMembership = $this->organizationMembershipFixture();

        $response = $this->userManagement->createOrganizationMembership($userId, $orgId, $roleSlug);

        $this->assertEquals($organizationMembership, $response->toArray());
    }

    public function testCreateOrganizationMembershipWithRoleSlugs()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $orgId = "org_01EHQMYV6MBK39QC5PZXHY59C3";
        $roleSlugs = ["admin"];
        $path = "user_management/organization_memberships";

        $result = $this->organizationMembershipResponseFixture();

        $params = [
            "organization_id" => $orgId,
            "user_id" => $userId,
            "role_slugs" => $roleSlugs,
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $organizationMembership = $this->organizationMembershipFixture();

        $response = $this->userManagement->createOrganizationMembership($userId, $orgId, null, $roleSlugs);

        $this->assertEquals($organizationMembership, $response->toArray());
    }

    public function testCreateOrganizationMembershipWithNullRoleParams()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $orgId = "org_01EHQMYV6MBK39QC5PZXHY59C3";
        $path = "user_management/organization_memberships";

        $result = $this->organizationMembershipResponseFixture();

        // When both roleSlug and roleSlugs are null, neither should be in params
        $params = [
            "organization_id" => $orgId,
            "user_id" => $userId,
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $organizationMembership = $this->organizationMembershipFixture();

        $response = $this->userManagement->createOrganizationMembership($userId, $orgId, null, null);
        $this->assertEquals($organizationMembership, $response->toArray());
    }

    public function testGetOrganizationMembership()
    {
        $organizationMembershipId = "om_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/organization_memberships/{$organizationMembershipId}";

        $result = $this->organizationMembershipResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $organizationMembership = $this->organizationMembershipFixture();

        $response = $this->userManagement->getOrganizationMembership($organizationMembershipId);

        $this->assertEquals($organizationMembership, $response->toArray());
    }

    public function testListOrganizationMemberships()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $orgId = "org_01EHQMYV6MBK39QC5PZXHY59C3";
        $path = "user_management/organization_memberships";

        $result = $this->organizationMembershipListResponseFixture();

        $params = [
            "organization_id" => $orgId,
            "user_id" => $userId,
            "statuses" => null,
            "limit" => 10,
            "before" => null,
            "after" => null,
            "order" => null,
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true,
            $result
        );

        $organizationMembership = $this->organizationMembershipFixture();

        list($before, $after, $organizationMemberships) = $this->userManagement->listOrganizationMemberships($userId, $orgId);

        $this->assertEquals($organizationMembership, $organizationMemberships[0]->toArray());
    }

    public function testListOrganizationMembershipsWithStatuses()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $orgId = "org_01EHQMYV6MBK39QC5PZXHY59C3";
        $statuses = array("active", "inactive");
        $path = "user_management/organization_memberships";

        $result = $this->organizationMembershipListResponseFixture();

        $params = [
            "organization_id" => $orgId,
            "user_id" => $userId,
            "statuses" => "active,inactive",
            "limit" => 10,
            "before" => null,
            "after" => null,
            "order" => null,
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true,
            $result
        );

        $organizationMembership = $this->organizationMembershipFixture();

        list($before, $after, $organizationMemberships) = $this->userManagement->listOrganizationMemberships($userId, $orgId, $statuses);

        $this->assertEquals($organizationMembership, $organizationMemberships[0]->toArray());
    }

    public function testListOrganizationMembershipsWithStatus()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $orgId = "org_01EHQMYV6MBK39QC5PZXHY59C3";
        $statuses = array("inactive");
        $path = "user_management/organization_memberships";

        $result = $this->organizationMembershipListResponseFixture();

        $params = [
            "organization_id" => $orgId,
            "user_id" => $userId,
            "statuses" => "inactive",
            "limit" => 10,
            "before" => null,
            "after" => null,
            "order" => null,
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true,
            $result
        );

        $organizationMembership = $this->organizationMembershipFixture();

        list($before, $after, $organizationMemberships) = $this->userManagement->listOrganizationMemberships($userId, $orgId, $statuses);

        $this->assertEquals($organizationMembership, $organizationMemberships[0]->toArray());
    }

    public function testDeleteOrganizationMembership()
    {
        $organizationMembershipId = "om_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/organization_memberships/{$organizationMembershipId}";

        $result = $this->organizationMembershipResponseFixture();

        $this->mockRequest(
            Client::METHOD_DELETE,
            $path,
            null,
            null,
            true,
        );

        $response = $this->userManagement->deleteOrganizationMembership($organizationMembershipId);

        $this->assertSame($response, []);
    }

    public function testUpdateOrganizationMembership()
    {
        $organizationMembershipId = "om_01E4ZCR3C56J083X43JQXF3JK5";
        $roleSlug = "staff";
        $path = "user_management/organization_memberships/{$organizationMembershipId}";

        $result = $this->organizationMembershipResponseFixture();

        $this->mockRequest(
            Client::METHOD_PUT,
            $path,
            null,
            ["role_slug" => $roleSlug],
            true,
            $result
        );

        $response = $this->userManagement->updateOrganizationMembership($organizationMembershipId, $roleSlug);
        $this->assertEquals($this->organizationMembershipFixture(), $response->toArray());
    }

    public function testUpdateOrganizationMembershipWithRoleSlugs()
    {
        $organizationMembershipId = "om_01E4ZCR3C56J083X43JQXF3JK5";
        $roleSlugs = ["admin"];
        $path = "user_management/organization_memberships/{$organizationMembershipId}";

        $result = $this->organizationMembershipResponseFixture();

        $this->mockRequest(
            Client::METHOD_PUT,
            $path,
            null,
            ["role_slugs" => $roleSlugs],
            true,
            $result
        );

        $response = $this->userManagement->updateOrganizationMembership($organizationMembershipId, null, $roleSlugs);
        $this->assertEquals($this->organizationMembershipFixture(), $response->toArray());
    }

    public function testUpdateOrganizationMembershipWithNullRoleParams()
    {
        $organizationMembershipId = "om_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/organization_memberships/{$organizationMembershipId}";

        $result = $this->organizationMembershipResponseFixture();

        // When both roleSlug and roleSlugs are null, params should be empty array
        $this->mockRequest(
            Client::METHOD_PUT,
            $path,
            null,
            [],
            true,
            $result
        );

        $response = $this->userManagement->updateOrganizationMembership($organizationMembershipId, null, null);
        $this->assertEquals($this->organizationMembershipFixture(), $response->toArray());
    }

    public function testDeactivateOrganizationMembership()
    {
        $organizationMembershipId = "om_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/organization_memberships/{$organizationMembershipId}/deactivate";

        $result = $this->organizationMembershipResponseFixture("inactive");

        $this->mockRequest(
            Client::METHOD_PUT,
            $path,
            null,
            null,
            true,
            $result
        );

        $organizationMembership = $this->organizationMembershipFixture();

        $response = $this->userManagement->deactivateOrganizationMembership($organizationMembershipId);

        $this->assertEquals(array_merge($organizationMembership, array("status" => "inactive")), $response->toArray());
    }

    public function testReactivateOrganizationMembership()
    {
        $organizationMembershipId = "om_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/organization_memberships/{$organizationMembershipId}/reactivate";

        $result = $this->organizationMembershipResponseFixture();

        $this->mockRequest(
            Client::METHOD_PUT,
            $path,
            null,
            null,
            true,
            $result
        );

        $organizationMembership = $this->organizationMembershipFixture();

        $response = $this->userManagement->reactivateOrganizationMembership($organizationMembershipId);

        $this->assertEquals($organizationMembership, $response->toArray());
    }

    public function testSendInvitation()
    {
        $path = "user_management/invitations";

        $result = $this->invitationResponseFixture();

        $params = [
            "email" => "someemail@test.com",
            "organization_id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
            "expires_in_days" => 10,
            "inviter_user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "role_slug" => "staff"
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $response = $this->userManagement->sendInvitation(
            "someemail@test.com",
            "org_01EHQMYV6MBK39QC5PZXHY59C3",
            10,
            "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "staff"
        );

        $expected = $this->invitationFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    public function testSendInvitationWithNullOptionalParams()
    {
        $path = "user_management/invitations";

        $result = $this->invitationResponseFixture();

        // The implementation includes null values in params
        $params = [
            "email" => "someemail@test.com",
            "organization_id" => null,
            "expires_in_days" => null,
            "inviter_user_id" => null,
            "role_slug" => null
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $response = $this->userManagement->sendInvitation(
            "someemail@test.com",
            null,
            null,
            null,
            null
        );

        $expected = $this->invitationFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    public function testGetInvitation()
    {
        $invitationId = "invitation_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/invitations/{$invitationId}";

        $result = $this->invitationResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $response = $this->userManagement->getInvitation($invitationId);

        $expected = $this->invitationFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    public function testFindInvitationByToken()
    {
        $invitationToken = "Z1uX3RbwcIl5fIGJJJCXXisdI";
        $path = "user_management/invitations/by_token/{$invitationToken}";

        $result = $this->invitationResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $response = $this->userManagement->findInvitationByToken($invitationToken);

        $expected = $this->invitationFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    public function testListInvitations()
    {
        $path = "user_management/invitations";

        $result = $this->invitationListResponseFixture();

        $params = [
            "email" => "someemail@test.com",
            "organization_id" => null,
            "limit" => 10,
            "before" => null,
            "after" => null,
            "order" => null
        ];
        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true,
            $result
        );

        list($before, $after, $invitations) = $this->userManagement->listInvitations("someemail@test.com");

        $expected = $this->invitationFixture();

        $this->assertSame($expected, $invitations[0]->toArray());
    }

    public function testRevokeInvitation()
    {
        $invitationId = "invitation_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/invitations/{$invitationId}/revoke";

        $result = $this->invitationResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            null,
            true,
            $result
        );

        $response = $this->userManagement->revokeInvitation($invitationId);

        $expected = $this->invitationFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    public function testResendInvitation()
    {
        $invitationId = "invitation_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/invitations/{$invitationId}/resend";

        $result = $this->invitationResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            null,
            true,
            $result
        );

        $response = $this->userManagement->resendInvitation($invitationId);

        $expected = $this->invitationFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    public function testResendInvitation404()
    {
        $invitationId = "invitation_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/invitations/{$invitationId}/resend";

        $this->expectException(Exception\NotFoundException::class);

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            null,
            true,
            null,
            null,
            404
        );

        $this->userManagement->resendInvitation($invitationId);
    }

    public function testResendInvitationExpired()
    {
        $invitationId = "invitation_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/invitations/{$invitationId}/resend";

        $this->expectException(Exception\BadRequestException::class);

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            null,
            true,
            null,
            null,
            400
        );

        $this->userManagement->resendInvitation($invitationId);
    }

    public function testResendInvitationRevoked()
    {
        $invitationId = "invitation_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/invitations/{$invitationId}/resend";

        $this->expectException(Exception\BadRequestException::class);

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            null,
            true,
            null,
            null,
            400
        );

        $this->userManagement->resendInvitation($invitationId);
    }

    public function testResendInvitationAccepted()
    {
        $invitationId = "invitation_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "user_management/invitations/{$invitationId}/resend";

        $this->expectException(Exception\BadRequestException::class);

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            null,
            true,
            null,
            null,
            400
        );

        $this->userManagement->resendInvitation($invitationId);
    }

    public function testGetJwksUrl()
    {
        $clientId = "12345";

        $result = $this->userManagement->getJwksUrl($clientId);

        $baseUrl = WorkOS::getApiBaseUrl();
        $expected = "{$baseUrl}sso/jwks/{$clientId}";

        $this->assertSame($result, $expected);
    }

    public function testGetJwksUrlException()
    {
        $result = "clientId must not be empty";

        try {
            $this->userManagement->getJwksUrl('');
        } catch (Exception\UnexpectedValueException $e) {
            $this->assertEquals($e->getMessage(), $result);
        }
    }

    public function testGetLogoutUrl()
    {
        $sessionId = "session_123";

        $result = $this->userManagement->getLogoutUrl($sessionId);

        $baseUrl = WorkOS::getApiBaseUrl();
        $expected = "{$baseUrl}user_management/sessions/logout?session_id={$sessionId}";

        $this->assertSame($result, $expected);
    }

    public function testGetLogoutUrlWithReturnTo()
    {
        $result = $this->userManagement->getLogoutUrl("session_123", "https://your-app.com");

        $this->assertSame(
            $result,
            "https://api.workos.com/user_management/sessions/logout?session_id=session_123&return_to=https%3A%2F%2Fyour-app.com"
        );
    }

    public function testGetLogoutUrlException()
    {
        $result = "sessionId must not be empty";

        try {
            $this->userManagement->getLogoutUrl('');
        } catch (Exception\UnexpectedValueException $e) {
            $this->assertEquals($e->getMessage(), $result);
        }
    }

    //Fixtures

    private function invitationResponseFixture()
    {
        return json_encode([
            "object" => "invitation",
            "id" => "invitation_01E4ZCR3C56J083X43JQXF3JK5",
            "email" => "someemail@test.com",
            "state" => "pending",
            "accepted_at" => "2021-07-01T19:07:33.155Z",
            "revoked_at" => "2021-07-01T19:07:33.155Z",
            "expires_at" => "2021-07-01T19:07:33.155Z",
            "token" => "Z1uX3RbwcIl5fIGJJJCXXisdI",
            "accept_invitation_url" => "https://your-app.com/invite?invitation_token=Z1uX3RbwcIl5fIGJJJCXXisdI",
            "organization_id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
            "inviter_user_id" => "user_01HYKE1DMN34HMHC180HJMF4AQ",
            "created_at" => "2021-07-01T19:07:33.155Z",
            "updated_at" => "2021-07-01T19:07:33.155Z",
        ]);
    }

    private function invitationFixture()
    {
        return [
            "object" => "invitation",
            "id" => "invitation_01E4ZCR3C56J083X43JQXF3JK5",
            "email" => "someemail@test.com",
            "state" => "pending",
            "acceptedAt" => "2021-07-01T19:07:33.155Z",
            "revokedAt" => "2021-07-01T19:07:33.155Z",
            "expiresAt" => "2021-07-01T19:07:33.155Z",
            "token" => "Z1uX3RbwcIl5fIGJJJCXXisdI",
            "acceptInvitationUrl" => "https://your-app.com/invite?invitation_token=Z1uX3RbwcIl5fIGJJJCXXisdI",
            "organizationId" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
            "inviterUserId" => "user_01HYKE1DMN34HMHC180HJMF4AQ",
            "createdAt" => "2021-07-01T19:07:33.155Z",
            "updatedAt" => "2021-07-01T19:07:33.155Z",
        ];
    }

    private function invitationListResponseFixture()
    {
        return json_encode(
            [
                "data" => [
                    [
                        "object" => "invitation",
                        "id" => "invitation_01E4ZCR3C56J083X43JQXF3JK5",
                        "email" => "someemail@test.com",
                        "state" => "pending",
                        "accepted_at" => "2021-07-01T19:07:33.155Z",
                        "revoked_at" => "2021-07-01T19:07:33.155Z",
                        "expires_at" => "2021-07-01T19:07:33.155Z",
                        "token" => "Z1uX3RbwcIl5fIGJJJCXXisdI",
                        "accept_invitation_url" => "https://your-app.com/invite?invitation_token=Z1uX3RbwcIl5fIGJJJCXXisdI",
                        "organization_id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
                        "inviter_user_id" => "user_01HYKE1DMN34HMHC180HJMF4AQ",
                        "created_at" => "2021-07-01T19:07:33.155Z",
                        "updated_at" => "2021-07-01T19:07:33.155Z",
                    ]
                ],
                "list_metadata" => [
                    "before" => null,
                    "after" => null
                ],
            ]
        );
    }

    private function organizationMembershipResponseFixture($status = "active")
    {
        return json_encode([
            "object" => "organization_membership",
            "id" => "om_01E4ZCR3C56J083X43JQXF3JK5",
            "user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "organization_id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
            "role" => [
                "slug" => "admin",
            ],
            "roles" => [
                [
                    "slug" => "admin",
                ],
            ],
            "status" => $status,
            "custom_attributes" => [],
            "created_at" => "2021-06-25T19:07:33.155Z",
            "updated_at" => "2021-06-25T19:07:33.155Z",
        ]);
    }

    private function organizationMembershipListResponseFixture()
    {
        return json_encode(
            [
                "data" => [
                    [
                        "object" => "organization_membership",
                        "id" => "om_01E4ZCR3C56J083X43JQXF3JK5",
                        "user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
                        "organization_id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
                        "role" => [
                            "slug" => "admin",
                        ],
                        "roles" => [
                            [
                                "slug" => "admin",
                            ]
                        ],
                        "status" => "active",
                        "custom_attributes" => [],
                        "created_at" => "2021-06-25T19:07:33.155Z",
                        "updated_at" => "2021-06-25T19:07:33.155Z",
                    ]
                ],
                "list_metadata" => [
                    "before" => null,
                    "after" => null
                ],
            ]
        );
    }

    private function organizationMembershipFixture()
    {
        return [
            "object" => "organization_membership",
            "id" => "om_01E4ZCR3C56J083X43JQXF3JK5",
            "userId" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "organizationId" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
            "role" => new RoleResponse("admin"),
            "roles" => [
                new RoleResponse("admin"),
            ],
            "status" => "active",
            "customAttributes" => [],
            "createdAt" => "2021-06-25T19:07:33.155Z",
            "updatedAt" => "2021-06-25T19:07:33.155Z",
        ];
    }

    private function listAuthFactorResponseFixture()
    {
        return json_encode(
            [
                "data" => [
                    [
                        "object" => "authentication_factor",
                        "id" => "auth_factor_01FXNWW32G7F3MG8MYK5D1HJJM",
                        "user_id" => "user_01H96FETWYSJMJEGF0Q3ZB272F",
                        "created_at" => "2022-03-08T23:12:20.157Z",
                        "updated_at" => "2022-03-08T23:12:20.157Z",
                        "type" => "totp",
                        "totp" => [
                            "issuer" => "Foo Corp",
                            "user" => "user@foo-corp.com",
                        ]
                    ],
                    [
                        "object" => "authentication_factor",
                        "id" => "auth_factor_01FXNWW32G7F3MG8MYK5D1HJJN",
                        "user_id" => "user_01H96FETWYSJMJEGF0Q3ZB272F",
                        "created_at" => "2022-03-08T23:12:20.157Z",
                        "updated_at" => "2022-03-08T23:12:20.157Z",
                        "type" => "totp",
                        "totp" => [
                            "issuer" => "Bar Corp",
                            "user" => "user@bar-corp.com"
                        ]
                    ]
                ]
            ]
        );
    }

    private function listAuthFactorFixture()
    {
        return [
            "object" => "authentication_factor",
            "id" => "auth_factor_01FXNWW32G7F3MG8MYK5D1HJJM",
            "userId" => "user_01H96FETWYSJMJEGF0Q3ZB272F",
            "createdAt" => "2022-03-08T23:12:20.157Z",
            "updatedAt" => "2022-03-08T23:12:20.157Z",
            "type" => "totp",
            "totp" => [
                "issuer" => "Foo Corp",
                "user" => "user@foo-corp.com",
            ]
        ];
    }

    private function userResponseFixture()
    {
        return json_encode([
            "user" => [
                "object" => "user",
                "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
                "email" => "test@test.com",
                "first_name" => "Damien",
                "last_name" => "Alabaster",
                "email_verified" => true,
                "profile_picture_url" => "https://example.com/photo.jpg",
                "last_sign_in_at" => "2021-06-25T19:07:33.155Z",
                "created_at" => "2021-06-25T19:07:33.155Z",
                "updated_at" => "2021-06-25T19:07:33.155Z",
                "external_id" => null,
                "metadata" => []
            ]
        ]);
    }

    private function userAndImpersonatorResponseFixture()
    {
        return json_encode([
            "user" => [
                "object" => "user",
                "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
                "email" => "test@test.com",
                "first_name" => "Damien",
                "last_name" => "Alabaster",
                "email_verified" => true,
                "profile_picture_url" => "https://example.com/photo.jpg",
                "last_sign_in_at" => "2021-06-25T19:07:33.155Z",
                "created_at" => "2021-06-25T19:07:33.155Z",
                "updated_at" => "2021-06-25T19:07:33.155Z",
                "external_id" => null,
                "metadata" => []
            ],
            "impersonator" => [
                "email" => "admin@foocorp.com",
                "reason" => "Helping debug an account issue.",
            ]
        ]);
    }

    private function userAndOAuthTokensResponseFixture()
    {
        return json_encode([
            "user" => [
                "object" => "user",
                "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
                "email" => "test@test.com",
                "first_name" => "Damien",
                "last_name" => "Alabaster",
                "email_verified" => true,
                "profile_picture_url" => "https://example.com/photo.jpg",
                "last_sign_in_at" => "2021-06-25T19:07:33.155Z",
                "created_at" => "2021-06-25T19:07:33.155Z",
                "updated_at" => "2021-06-25T19:07:33.155Z",
                "external_id" => null,
                "metadata" => []
            ],
            "access_token" => "01DMEK0J53CVMC32CK5SE0KZ8Q",
            "refresh_token" => "refresh_token_123",
            "oauth_tokens" => [
                "access_token" => "oauth_access_token_123",
                "refresh_token" => "oauth_refresh_token_456",
                "expires_at" => 1640995200,
                "scopes" => ["read", "write"]
            ]
        ]);
    }

    private function createUserAndTokenResponseFixture()
    {
        return json_encode([
            "token" => "01DMEK0J53CVMC32CK5SE0KZ8Q",
            "user" => [
                "object" => "user",
                "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
                "email" => "test@test.com",
                "first_name" => "Damien",
                "last_name" => "Alabaster",
                "email_verified" => true,
                'profile_picture_url' => 'https://example.com/photo.jpg',
                "last_sign_in_at" => "2021-06-25T19:07:33.155Z",
                "created_at" => "2021-06-25T19:07:33.155Z",
                "updated_at" => "2021-06-25T19:07:33.155Z"
            ]
        ]);
    }

    private function createUserResponseFixture()
    {
        return json_encode([
            "object" => "user",
            "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "test@test.com",
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified" => true,
            'profile_picture_url' => 'https://example.com/photo.jpg',
            "last_sign_in_at" => "2021-06-25T19:07:33.155Z",
            "created_at" => "2021-06-25T19:07:33.155Z",
            "updated_at" => "2021-06-25T19:07:33.155Z",
            "external_id" => null,
            "metadata" => []
        ]);
    }

    private function magicAuthResponseFixture()
    {
        return json_encode([
            "object" => "magic_auth",
            "id" => "magic_auth_01E4ZCR3C56J083X43JQXF3JK5",
            "user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "someemail@test.com",
            "expires_at" => "2021-07-01T19:07:33.155Z",
            "code" => "123456",
            "created_at" => "2021-07-01T19:07:33.155Z",
            "updated_at" => "2021-07-01T19:07:33.155Z",
        ]);
    }

    private function magicAuthFixture()
    {
        return [
            "object" => "magic_auth",
            "id" => "magic_auth_01E4ZCR3C56J083X43JQXF3JK5",
            "userId" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "someemail@test.com",
            "expiresAt" => "2021-07-01T19:07:33.155Z",
            "code" => "123456",
            "createdAt" => "2021-07-01T19:07:33.155Z",
            "updatedAt" => "2021-07-01T19:07:33.155Z",
        ];
    }

    private function emailVerificationResponseFixture()
    {
        return json_encode([
            "object" => "email_verification",
            "id" => "email_verification_01E4ZCR3C56J083X43JQXF3JK5",
            "user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "someemail@test.com",
            "expires_at" => "2021-07-01T19:07:33.155Z",
            "code" => "123456",
            "created_at" => "2021-07-01T19:07:33.155Z",
            "updated_at" => "2021-07-01T19:07:33.155Z",
        ]);
    }

    private function emailVerificationFixture()
    {
        return [
            "object" => "email_verification",
            "id" => "email_verification_01E4ZCR3C56J083X43JQXF3JK5",
            "userId" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "someemail@test.com",
            "expiresAt" => "2021-07-01T19:07:33.155Z",
            "code" => "123456",
            "createdAt" => "2021-07-01T19:07:33.155Z",
            "updatedAt" => "2021-07-01T19:07:33.155Z",
        ];
    }

    private function passwordResetResponseFixture()
    {
        return json_encode([
            "object" => "password_reset",
            "id" => "password_reset_01E4ZCR3C56J083X43JQXF3JK5",
            "user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "someemail@test.com",
            "password_reset_token" => "Z1uX3RbwcIl5fIGJJJCXXisdI",
            "password_reset_url" => "https://your-app.com/reset-password?token=Z1uX3RbwcIl5fIGJJJCXXisdI",
            "expires_at" => "2021-07-01T19:07:33.155Z",
            "created_at" => "2021-07-01T19:07:33.155Z",
        ]);
    }

    private function passwordResetFixture()
    {
        return [
            "object" => "password_reset",
            "id" => "password_reset_01E4ZCR3C56J083X43JQXF3JK5",
            "userId" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "someemail@test.com",
            "passwordResetToken" => "Z1uX3RbwcIl5fIGJJJCXXisdI",
            "passwordResetUrl" => "https://your-app.com/reset-password?token=Z1uX3RbwcIl5fIGJJJCXXisdI",
            "expiresAt" => "2021-07-01T19:07:33.155Z",
            "createdAt" => "2021-07-01T19:07:33.155Z",
        ];
    }

    private function sendMagicAuthCodeResponseFixture()
    {
        return json_encode([
            "object" => "magic_auth_challenge",
            "id" => "auth_challenge_01E4ZCR3C56J083X43JQXF3JK5"
        ]);
    }

    private function getUserResponseFixture()
    {
        return json_encode([
            "object" => "user",
            "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "test@test.com",
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified" => true,
            "profile_picture_url" => "https://example.com/photo.jpg",
            "last_sign_in_at" => "2021-06-25T19:07:33.155Z",
            "created_at" => "2021-06-25T19:07:33.155Z",
            "updated_at" => "2021-06-25T19:07:33.155Z",
            "external_id" => null,
            "metadata" => []
        ]);
    }

    private function listUsersResponseFixture()
    {
        return json_encode([
            "data" => [
                [
                    "object" => "user",
                    "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
                    "email" => "test@test.com",
                    "first_name" => "Damien",
                    "last_name" => "Alabaster",
                    "email_verified" => true,
                    "profile_picture_url" => "https://example.com/photo.jpg",
                    "last_sign_in_at" => "2021-06-25T19:07:33.155Z",
                    "created_at" => "2021-06-25T19:07:33.155Z",
                    "updated_at" => "2021-06-25T19:07:33.155Z",
                    "external_id" => null,
                    "metadata" => []
                ]
            ],
            "list_metadata" => [
                "before" => null,
                "after" => null
            ],
        ]);
    }

    private function magicAuthChallengeFixture()
    {
        return [
            "object" => "magic_auth_challenge",
            "id" => "auth_challenge_01E4ZCR3C56J083X43JQXF3JK5"
        ];
    }

    private function userFixture()
    {
        return [
            "object" => "user",
            "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "test@test.com",
            "firstName" => "Damien",
            "lastName" => "Alabaster",
            "emailVerified" => true,
            "profilePictureUrl" => "https://example.com/photo.jpg",
            "lastSignInAt" => "2021-06-25T19:07:33.155Z",
            "createdAt" => "2021-06-25T19:07:33.155Z",
            "updatedAt" => "2021-06-25T19:07:33.155Z",
            "externalId" => null,
            "metadata" => []
        ];
    }

    private function userAndOrgResponseFixture()
    {
        return json_encode([
            "user" => [
                "object" => "user",
                "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
                "email" => "test@test.com",
                "first_name" => "Damien",
                "last_name" => "Alabaster",
                "email_verified" => true,
                "profile_picture_url" => "https://example.com/photo.jpg",
                "last_sign_in_at" => "2021-06-25T19:07:33.155Z",
                "created_at" => "2021-06-25T19:07:33.155Z",
                "updated_at" => "2021-06-25T19:07:33.155Z",
                "external_id" => null,
                "metadata" => []
            ],
            "organization_id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
        ]);
    }


    private function enrollAuthFactorResponseFixture()
    {
        return json_encode([
            "authentication_factor" => [
                "object" => "authentication_factor",
                "id" => "auth_factor_01FXNWW32G7F3MG8MYK5D1HJJM",
                "created_at" => "2022-03-08T23:12:20.157Z",
                "updated_at" => "2022-03-08T23:12:20.157Z",
                "type" => "totp",
                "totp" => [
                    "qr_code" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAApAAAAKQCAYAAAAotUpQAAAAAklEQVR4AewaftIAABPuSURBVO3B0Y0s2w0EsCrh5Z+y7BR0z0djsCSbZMPP2t18qW2+tLt50TYvdjdfaptftrt50TYvdjcv2obv7G5etM2L3c2X2ubF7uZLbcPvmgAAwMEEAAAOJgAAcDABAICDCQAAHEwAAOBgAgAABxMAADiYAADAwQQAAA4mAABwMAEAgIMJAAAcTAAA4GACAAAH/+XR7oZ/1zZ8Z3fzom1e7G5e7G5etA3f2d38srb5ZbubL7XNi93Ni7Z5sbv50u6Gf9c2LyYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcDABAICDCQAAHEwAAOBgAgAABxMAADiYAADAwX/5WNv8st3NL2ubL+1uXrTNl3Y3X2qbX9Y2f1nb/GW7mxdt86XdDb+rbX7Z7uZLEwAAOJgAAMDBBAAADiYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcDABAICDCQAAHEwAAODgv8CD3c2Xdjcv2uYv2938ZW3zpd3NX9Y2L3Y3/LvdDfyrCQAAHEwAAOBgAgAABxMAADiYAADAwQQAAA4mAABwMAEAgIMJAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHDwX+BB27zY3Xxpd/PLdjcv2ubF7uZF2/xlbfPLdjcvdjdfaptf1jZf2t3wd00AAOBgAgAABxMAADiYAADAwQQAAA4mAABwMAEAgIMJAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHAwAQCAg//ysd0Nv2t388va5sXu5kXbvNjdvNjdfGl386W2+WW7mxdt88va5pftbvjO7oZ/NwEAgIMJAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcDABAICDCQAAHEwAAOBgAgAABxMAADiYAADAwQQAAA4mAABwMAEAgIPu/+VDbfOl3c2LtuE7u5svtc1ftrt50TYvdjcv2uaX7W5+Wdt8aXfDv2sb/q4JAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcPBfftzu5pftbl60zYvdzV/WNl/a3bxom7+sbV7sbl60zZfa5ku7m1/WNi92N19qmxe7m1+2u3nRNl/a3bxomy9NAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcDABAICDCQAAHEwAAOBgAgAABxMAADiYAADAwQQAAA4mAABwMAEAgIMm2fyw3c2LtuF37W6+1DZf2t28aJsXu5sXbfNid/OibV7sbn5Z2/Cd3c2Ltnmxu3nRNi92Ny/a5sXu5kXb/LLdzYsJAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcDABAICDCQAAHDTJhp+1u3nRNl/a3bxomy/tbn5Z2/yy3c0va5sXu5sXbfOX7W5etM2L3c2Ltnmxu/nL2uaX7W6+NAEAgIMJAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAO/gfiF4JV0SXN7wAAAABJRU5ErkJggg==",
                    "secret" => "JJWBYBLLH5TRKYZEGMREU6DRKFRVMTCV",
                    "uri" => "otpauth://totp/test:example?secret=JJWBYBLLH5TRKYZEGMREU6DRKFRVMTCV&issuer=test"
                ]
            ],
            "authentication_challenge" => [
                "object" => "authentication_challenge",
                "id" => "auth_challenge_01FXNX3BTZPPJVKF65NNWGRHZJ",
                "created_at" => "2022-03-08T23:16:18.532Z",
                "updated_at" => "2022-03-08T23:16:18.532Z",
                "expires_at" => "2022-03-08T23:16:18.532Z",
                "authentication_factor_id" => "auth_factor_01FXNWW32G7F3MG8MYK5D1HJJM"
            ],
        ]);
    }

    private function enrollAuthFactorFixture()
    {
        return [
            "object" => "authentication_factor",
            "id" => "auth_factor_01FXNWW32G7F3MG8MYK5D1HJJM",
            "createdAt" => "2022-03-08T23:12:20.157Z",
            "updatedAt" => "2022-03-08T23:12:20.157Z",
            "type" => "totp",
            "totp" => [
                "qr_code" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAApAAAAKQCAYAAAAotUpQAAAAAklEQVR4AewaftIAABPuSURBVO3B0Y0s2w0EsCrh5Z+y7BR0z0djsCSbZMPP2t18qW2+tLt50TYvdjdfaptftrt50TYvdjcv2obv7G5etM2L3c2X2ubF7uZLbcPvmgAAwMEEAAAOJgAAcDABAICDCQAAHEwAAOBgAgAABxMAADiYAADAwQQAAA4mAABwMAEAgIMJAAAcTAAA4GACAAAH/+XR7oZ/1zZ8Z3fzom1e7G5e7G5etA3f2d38srb5ZbubL7XNi93Ni7Z5sbv50u6Gf9c2LyYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcDABAICDCQAAHEwAAOBgAgAABxMAADiYAADAwX/5WNv8st3NL2ubL+1uXrTNl3Y3X2qbX9Y2f1nb/GW7mxdt86XdDb+rbX7Z7uZLEwAAOJgAAMDBBAAADiYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcDABAICDCQAAHEwAAODgv8CD3c2Xdjcv2uYv2938ZW3zpd3NX9Y2L3Y3/LvdDfyrCQAAHEwAAOBgAgAABxMAADiYAADAwQQAAA4mAABwMAEAgIMJAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHDwX+BB27zY3Xxpd/PLdjcv2ubF7uZF2/xlbfPLdjcvdjdfaptf1jZf2t3wd00AAOBgAgAABxMAADiYAADAwQQAAA4mAABwMAEAgIMJAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHAwAQCAg//ysd0Nv2t388va5sXu5kXbvNjdvNjdfGl386W2+WW7mxdt88va5pftbvjO7oZ/NwEAgIMJAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcDABAICDCQAAHEwAAOBgAgAABxMAADiYAADAwQQAAA4mAABwMAEAgIPu/+VDbfOl3c2LtuE7u5svtc1ftrt50TYvdjcv2uaX7W5+Wdt8aXfDv2sb/q4JAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcPBfftzu5pftbl60zYvdzV/WNl/a3bxom7+sbV7sbl60zZfa5ku7m1/WNi92N19qmxe7m1+2u3nRNl/a3bxomy9NAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcDABAICDCQAAHEwAAOBgAgAABxMAADiYAADAwQQAAA4mAABwMAEAgIMm2fyw3c2LtuF37W6+1DZf2t28aJsXu5sXbfNid/OibV7sbn5Z2/Cd3c2Ltnmxu3nRNi92Ny/a5sXu5kXb/LLdzYsJAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAOJgAAcDABAICDCQAAHDTJhp+1u3nRNl/a3bxomy/tbn5Z2/yy3c0va5sXu5sXbfOX7W5etM2L3c2Ltnmxu/nL2uaX7W6+NAEAgIMJAAAcTAAA4GACAAAHEwAAOJgAAMDBBAAADiYAAHAwAQCAgwkAABxMAADgYAIAAAcTAAA4mAAAwMEEAAAO/gfiF4JV0SXN7wAAAABJRU5ErkJggg==",
                "secret" => "JJWBYBLLH5TRKYZEGMREU6DRKFRVMTCV",
                "uri" => "otpauth://totp/test:example?secret=JJWBYBLLH5TRKYZEGMREU6DRKFRVMTCV&issuer=test"
            ]
        ];
    }

    private function enrollAuthChallengeFixture()
    {
        return [
            "object" => "authentication_challenge",
            "id" => "auth_challenge_01FXNX3BTZPPJVKF65NNWGRHZJ",
            "createdAt" => "2022-03-08T23:16:18.532Z",
            "updatedAt" => "2022-03-08T23:16:18.532Z",
            "expiresAt" => "2022-03-08T23:16:18.532Z",
            "authenticationFactorId" => "auth_factor_01FXNWW32G7F3MG8MYK5D1HJJM"
        ];
    }

    // Session Management Tests

    public function testListSessions()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $path = "user_management/users/{$userId}/sessions";

        $result = json_encode([
            "data" => [
                [
                    "id" => "session_01H7X1M4TZJN5N4HG4XXMA1234",
                    "user_id" => $userId,
                    "ip_address" => "192.168.1.1",
                    "user_agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
                    "organization_id" => "org_01H7X1M4TZJN5N4HG4XXMA9876",
                    "authentication_method" => "SSO",
                    "status" => "active",
                    "expires_at" => "2026-02-01T00:00:00.000Z",
                    "ended_at" => null,
                    "created_at" => "2026-01-01T00:00:00.000Z",
                    "updated_at" => "2026-01-01T00:00:00.000Z",
                    "object" => "session"
                ],
                [
                    "id" => "session_01H7X1M4TZJN5N4HG4XXMA5678",
                    "user_id" => $userId,
                    "ip_address" => "192.168.1.2",
                    "user_agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)",
                    "organization_id" => null,
                    "authentication_method" => "Password",
                    "status" => "active",
                    "expires_at" => "2026-02-01T00:00:00.000Z",
                    "ended_at" => null,
                    "created_at" => "2026-01-01T00:00:00.000Z",
                    "updated_at" => "2026-01-01T00:00:00.000Z",
                    "object" => "session"
                ]
            ],
            "list_metadata" => ["before" => null, "after" => null]
        ]);

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            ["limit" => 10, "before" => null, "after" => null, "order" => null],
            true,
            $result
        );

        list($before, $after, $sessions) = $this->userManagement->listSessions($userId);

        $this->assertCount(2, $sessions);
        $this->assertInstanceOf(Resource\Session::class, $sessions[0]);
        $this->assertEquals("session_01H7X1M4TZJN5N4HG4XXMA1234", $sessions[0]->id);
        $this->assertEquals("active", $sessions[0]->status);
        $this->assertEquals("192.168.1.1", $sessions[0]->ipAddress);
        $this->assertEquals("SSO", $sessions[0]->authenticationMethod);
    }

    public function testRevokeSession()
    {
        $sessionId = "session_01H7X1M4TZJN5N4HG4XXMA1234";
        $path = "user_management/sessions/revoke";

        $result = json_encode([
            "id" => $sessionId,
            "user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "ip_address" => "192.168.1.1",
            "user_agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
            "organization_id" => null,
            "authentication_method" => "Password",
            "status" => "inactive",
            "expires_at" => "2026-02-01T00:00:00.000Z",
            "ended_at" => "2026-01-05T12:00:00.000Z",
            "created_at" => "2026-01-01T00:00:00.000Z",
            "updated_at" => "2026-01-05T12:00:00.000Z",
            "object" => "session"
        ]);

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            [ "session_id" => $sessionId ],
            true,
            $result
        );

        $session = $this->userManagement->revokeSession($sessionId);

        $this->assertInstanceOf(Resource\Session::class, $session);
        $this->assertEquals($sessionId, $session->id);
        $this->assertEquals("inactive", $session->status);
        $this->assertNotNull($session->endedAt);
        $this->assertEquals("2026-01-05T12:00:00.000Z", $session->endedAt);
    }

    public function testAuthenticateWithSessionCookieNoSessionProvided()
    {
        $result = $this->userManagement->authenticateWithSessionCookie("", "password");

        $this->assertInstanceOf(
            Resource\SessionAuthenticationFailureResponse::class,
            $result
        );
        $this->assertFalse($result->authenticated);
        $this->assertEquals(
            Resource\SessionAuthenticationFailureResponse::REASON_NO_SESSION_COOKIE_PROVIDED,
            $result->reason
        );
    }

    public function testLoadSealedSession()
    {
        $sessionData = [
            'access_token' => 'test_access_token_12345',
            'refresh_token' => 'test_refresh_token_67890',
            'session_id' => 'session_01H7X1M4TZJN5N4HG4XXMA1234'
        ];
        $cookiePassword = 'test-password-for-encryption-with-minimum-length';

        // Use encryptor directly (sealing is authkit-php's responsibility)
        $encryptor = new Session\HaliteSessionEncryption();
        $sealed = $encryptor->seal($sessionData, $cookiePassword);
        $cookieSession = $this->userManagement->loadSealedSession($sealed, $cookiePassword);

        $this->assertInstanceOf(CookieSession::class, $cookieSession);
    }

    public function testGetSessionFromCookieWithNoCookie()
    {
        $cookiePassword = 'test-password-for-encryption-with-minimum-length';

        // Ensure no cookie is set
        if (isset($_COOKIE['wos-session'])) {
            unset($_COOKIE['wos-session']);
        }

        $result = $this->userManagement->getSessionFromCookie($cookiePassword);

        $this->assertNull($result);
    }

    public function testGetSessionFromCookieWithCookie()
    {
        $sessionData = [
            'access_token' => 'test_access_token_12345',
            'refresh_token' => 'test_refresh_token_67890',
            'session_id' => 'session_01H7X1M4TZJN5N4HG4XXMA1234'
        ];
        $cookiePassword = 'test-password-for-encryption-with-minimum-length';

        // Use encryptor directly (sealing is authkit-php's responsibility)
        $encryptor = new Session\HaliteSessionEncryption();
        $sealed = $encryptor->seal($sessionData, $cookiePassword);

        // Simulate cookie being set
        $_COOKIE['wos-session'] = $sealed;

        $cookieSession = $this->userManagement->getSessionFromCookie($cookiePassword);

        $this->assertInstanceOf(CookieSession::class, $cookieSession);

        // Cleanup
        unset($_COOKIE['wos-session']);
    }

    public function testConstructorWithCustomEncryptor()
    {
        $mockEncryptor = $this->createMock(Session\SessionEncryptionInterface::class);
        $mockEncryptor->method('unseal')
            ->willReturn(['access_token' => 'test', 'refresh_token' => 'test']);

        // Create fresh HTTP client mock to throw exception
        $httpMock = $this->createMock(\WorkOS\RequestClient\RequestClientInterface::class);
        $response = new Resource\Response('{"error": "server_error"}', [], 500);
        $httpMock->method('request')
            ->willThrowException(new Exception\ServerException($response));
        Client::setRequestClient($httpMock);

        $userManagement = new UserManagement($mockEncryptor);

        // The custom encryptor should be used for authentication
        // Mock will succeed on unseal, but API call will fail - we just verify no encryption error
        $result = $userManagement->authenticateWithSessionCookie('any_sealed_data', 'password');

        // Should get past encryption (HTTP error expected, not encryption error)
        $this->assertInstanceOf(
            Resource\SessionAuthenticationFailureResponse::class,
            $result
        );
        $this->assertEquals(
            Resource\SessionAuthenticationFailureResponse::REASON_HTTP_ERROR,
            $result->reason
        );
    }

    public function testSetSessionEncryptor()
    {
        $mockEncryptor = $this->createMock(Session\SessionEncryptionInterface::class);
        $mockEncryptor->method('unseal')
            ->willReturn(['access_token' => 'test', 'refresh_token' => 'test']);

        // Create fresh HTTP client mock to throw exception
        $httpMock = $this->createMock(\WorkOS\RequestClient\RequestClientInterface::class);
        $response = new Resource\Response('{"error": "server_error"}', [], 500);
        $httpMock->method('request')
            ->willThrowException(new Exception\ServerException($response));
        Client::setRequestClient($httpMock);

        $userManagement = new UserManagement();
        $userManagement->setSessionEncryptor($mockEncryptor);

        // The custom encryptor should be used for authentication
        $result = $userManagement->authenticateWithSessionCookie('any_sealed_data', 'password');

        // Should get past encryption (HTTP error expected, not encryption error)
        $this->assertInstanceOf(
            Resource\SessionAuthenticationFailureResponse::class,
            $result
        );
        $this->assertEquals(
            Resource\SessionAuthenticationFailureResponse::REASON_HTTP_ERROR,
            $result->reason
        );
    }

    public function testAuthenticateWithSessionCookieEncryptionError()
    {
        $mockEncryptor = $this->createMock(Session\SessionEncryptionInterface::class);
        $mockEncryptor->method('unseal')
            ->willThrowException(new \Exception('Decryption failed'));

        $userManagement = new UserManagement($mockEncryptor);
        $result = $userManagement->authenticateWithSessionCookie('invalid_sealed_data', 'password');

        $this->assertInstanceOf(
            Resource\SessionAuthenticationFailureResponse::class,
            $result
        );
        $this->assertFalse($result->authenticated);
        $this->assertEquals(
            Resource\SessionAuthenticationFailureResponse::REASON_ENCRYPTION_ERROR,
            $result->reason
        );
    }

    public function testAuthenticateWithSessionCookieHttpError()
    {
        $sessionData = [
            'access_token' => 'test_access_token_12345',
            'refresh_token' => 'test_refresh_token_67890'
        ];
        $cookiePassword = 'test-password-for-encryption-with-minimum-length';

        // Use encryptor directly (sealing is authkit-php's responsibility)
        $encryptor = new Session\HaliteSessionEncryption();
        $sealed = $encryptor->seal($sessionData, $cookiePassword);

        // Set up mock to throw HTTP exception on API call
        $response = new Resource\Response('{"error": "server_error"}', [], 500);
        Client::setRequestClient($this->requestClientMock);
        $this->requestClientMock
            ->expects($this->atLeastOnce())
            ->method('request')
            ->willThrowException(new Exception\ServerException($response));

        $result = $this->userManagement->authenticateWithSessionCookie($sealed, $cookiePassword);

        $this->assertInstanceOf(
            Resource\SessionAuthenticationFailureResponse::class,
            $result
        );
        $this->assertFalse($result->authenticated);
        $this->assertEquals(
            Resource\SessionAuthenticationFailureResponse::REASON_HTTP_ERROR,
            $result->reason
        );
    }
}
