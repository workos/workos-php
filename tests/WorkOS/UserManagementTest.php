<?php

namespace WorkOS;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserManagementTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as traitSetUp;
    }

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
            [null, null, Resource\ConnectionType::GoogleOAuth, null],
            [null, null, null, "connection_123"],
            [null, null, null, null, "org_01FG7HGMY2CZZR2FWHTEE94VF0"],
            ["https://papagenos.com/auth/callback", null, null, "connection_123", null, "foo.com", null],
            ["https://papagenos.com/auth/callback", null, null, "connection_123", null, null, "foo@workos.com"],
            ["https://papagenos.com/auth/callback", null, null, "connection_123"],
            [null, null, null, "connection_123"],
            ["https://papagenos.com/auth/callback", ["toppings" => "ham"], null, "connection_123"]
        ];
    }

    #[DataProvider('authorizationUrlTestDataProvider')]
    public function testAuthorizationURLExpectedParams(
        $redirectUri,
        $state,
        $provider,
        $connectionId,
        $organizationId = null,
        $domainHint = null,
        $loginHint = null
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

        $authorizationUrl = $this->userManagement->getAuthorizationUrl(
            $redirectUri,
            $state,
            $provider,
            $connectionId,
            $organizationId,
            $domainHint,
            $loginHint
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

    public function testAuthenticateWithRefreshToken()
    {
        $path = "user_management/authenticate";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->UserResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "refresh_token" => "Xw0NsCVXMBf7svAoIoKBmkpEK",
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

    public function testGetEmailVerification()
    {
        $emailVerificationId = "email_verification_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "/user_management/email_verification/{$emailVerificationId}";

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
        $path = "/user_management/password_reset/{$passwordResetId}";

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
        $path = "/user_management/password_reset";

        $result = $this->passwordResetResponseFixture();

        $params = [
            "email" => "someemail@test.com",
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $response = $this->userManagement->createPasswordReset(
            "someemail@test.com",
        );

        $expected = $this->passwordResetFixture();

        $this->assertSame($response->toArray(), $expected);
    }

    public function testSendPasswordResetEmail()
    {
        $path = "user_management/password_reset/send";

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
        $path = "/user_management/magic_auth/{$magicAuthId}";

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
        $path = "/user_management/magic_auth";

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
        $path = "/user_management/magic_auth/send";

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
        $path = "user_management/organization_memberships";

        $result = $this->organizationMembershipResponseFixture();

        $params = [
            "organization_id" => $orgId,
            "user_id" => $userId
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

        $response = $this->userManagement->createOrganizationMembership($userId, $orgId);
        $this->assertSame($organizationMembership, $response->toArray());
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

        $this->assertSame($organizationMembership, $response->toArray());
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

        $this->assertSame($organizationMembership, $organizationMemberships[0]->toArray());
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

        $this->assertSame($organizationMembership, $organizationMemberships[0]->toArray());
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

        $this->assertSame($organizationMembership, $organizationMemberships[0]->toArray());
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

        $this->assertSame(array_merge($organizationMembership, array("status" => "inactive")), $response->toArray());
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

        $this->assertSame($organizationMembership, $response->toArray());
    }

    public function testSendInvitation()
    {
        $path = "/user_management/invitations";

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

    public function testGetInvitation()
    {
        $invitationId = "invitation_01E4ZCR3C56J083X43JQXF3JK5";
        $path = "/user_management/invitations/{$invitationId}";

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
        $path = "/user_management/invitations/by_token/{$invitationToken}";

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
        $path = "/user_management/invitations";

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
        $path = "/user_management/invitations/{$invitationId}/revoke";

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
            "status" => $status,
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
                        "status" => "active",
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
            "status" => "active",
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
                "created_at" => "2021-06-25T19:07:33.155Z",
                "updated_at" => "2021-06-25T19:07:33.155Z"
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
                "created_at" => "2021-06-25T19:07:33.155Z",
                "updated_at" => "2021-06-25T19:07:33.155Z"
            ],
            "impersonator" => [
                "email" => "admin@foocorp.com",
                "reason" => "Helping debug an account issue.",
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
            "created_at" => "2021-06-25T19:07:33.155Z",
            "updated_at" => "2021-06-25T19:07:33.155Z"
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
            "created_at" => "2021-06-25T19:07:33.155Z",
            "updated_at" => "2021-06-25T19:07:33.155Z"
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
                    "created_at" => "2021-06-25T19:07:33.155Z",
                    "updated_at" => "2021-06-25T19:07:33.155Z"
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
            "createdAt" => "2021-06-25T19:07:33.155Z",
            "updatedAt" => "2021-06-25T19:07:33.155Z"
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
                "created_at" => "2021-06-25T19:07:33.155Z",
                "updated_at" => "2021-06-25T19:07:33.155Z"
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
                    "qr_code" => "data:image/png;base64,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",
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
                "qr_code" => "data:image/png;base64,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",
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
}
