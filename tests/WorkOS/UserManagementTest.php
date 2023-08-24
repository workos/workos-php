<?php

namespace WorkOS;

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

    public function testAddUserToOrganization()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $usersOrganizationsPath = "users/{$userId}/organizations";

        $result = $this->addUserToOrganizationResponseFixture();

        $params = [
            "organization_id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $usersOrganizationsPath,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->addUserToOrganization("user_01H7X1M4TZJN5N4HG4XXMA1234", "org_01EHQMYV6MBK39QC5PZXHY59C3");
        $this->assertSame($user, $response->toArray());
    }

    public function testAuthenticateUserWithPassword()
    {
        $usersPath = "users/sessions/token";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->createSessionAndUserResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "email" => "marcelina@foo-corp.com",
            "password" => "i8uv6g34kd490s",
            "ip_address" => null,
            "user_agent" => null,
            "expires_in" => 1440,
            "grant_type" => "password",
            "client_secret" => Workos::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();
        $sessionFixture = $this->sessionFixture();

        $response = $this->userManagement->authenticateUserWithPassword("project_0123456", "marcelina@foo-corp.com", "i8uv6g34kd490s");
        $this->assertSame($sessionFixture, $response->session->toArray());
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testAuthenticateUserWithCode()
    {
        $usersPath = "users/sessions/token";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->createSessionAndUserResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "code" => "01E2RJ4C05B52KKZ8FSRDAP23J",
            "ip_address" => null,
            "user_agent" => null,
            "expires_in" => 1440,
            "grant_type" => "authorization_code",
            "client_secret" => Workos::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();
        $sessionFixture = $this->sessionFixture();

        $response = $this->userManagement->authenticateUserWithCode("project_0123456", "01E2RJ4C05B52KKZ8FSRDAP23J");
        $this->assertSame($sessionFixture, $response->session->toArray());
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testAuthenticateUserWithMagicAuth()
    {
        $usersPath = "users/sessions/token";
        WorkOS::setApiKey("sk_test_12345");
        $result = $this->createSessionAndUserResponseFixture();

        $params = [
            "client_id" => "project_0123456",
            "code" => "123456",
            "user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "ip_address" => null,
            "user_agent" => null,
            "expires_in" => 1440,
            "grant_type" => "urn:workos:oauth:grant-type:magic-auth:code",
            "client_secret" => Workos::getApiKey()
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();
        $sessionFixture = $this->sessionFixture();

        $response = $this->userManagement->authenticateUserWithMagicAuth("project_0123456", "123456", "user_01H7X1M4TZJN5N4HG4XXMA1234");
        $this->assertSame($sessionFixture, $response->session->toArray());
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testCreateUser()
    {
        $usersPath = "users";

        $result = $this->createUserResponseFixture();

        $params = [
            "email" => "test@test.com",
            "password" => "x^T!V23UN1@V",
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified" => true
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->createUser("test@test.com", "x^T!V23UN1@V", "Damien", "Alabaster", true);
        $this->assertSame($user, $response->toArray());
    }

    public function testSendVerificationEmail()
    {
        $id = "user_01E4ZCR3C56J083X43JQXF3JK5";
        $sendVerificationEmailPath = "users/{$id}/send_verification_email";

        $result = $this->sendMagicAuthCodeResponseFixture();


        $this->mockRequest(
            Client::METHOD_POST,
            $sendVerificationEmailPath,
            null,
            null,
            true,
            $result
        );


        $magicAuthChallenge = $this->magicAuthChallengeFixture();

        $response = $this->userManagement->sendVerificationEmail("user_01E4ZCR3C56J083X43JQXF3JK5");
        $this->assertSame($magicAuthChallenge, $response->toArray());
    }

    public function testVerifyEmail()
    {
        $usersPath = "users/verify_email";

        $result = $this->createUserResponseFixture();

        $params = [
            "user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "code" => "01DMEK0J53CVMC32CK5SE0KZ8Q",
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->verifyEmail("user_01H7X1M4TZJN5N4HG4XXMA1234", "01DMEK0J53CVMC32CK5SE0KZ8Q");
        $this->assertSame($user, $response->toArray());
    }

    public function testCreatePasswordResetChallenge()
    {
        $createPasswordResetChallengePath = "users/password_reset_challenge";

        $result = $this->createUserAndTokenResponseFixture();

        $params = [
            "email" => "test@test.com",
            "password_reset_url" => "https://your-app.com/reset-password"
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $createPasswordResetChallengePath,
            null,
            $params,
            true,
            $result
        );


        $userFixture = $this->userFixture();

        $response = $this->userManagement->createPasswordResetChallenge("test@test.com", "https://your-app.com/reset-password");
        $this->assertSame("01DMEK0J53CVMC32CK5SE0KZ8Q", $response->token);
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testCompletePasswordReset()
    {
        $completePasswordResetPath = "users/password_reset";

        $result = $this->createUserResponseFixture();

        $params = [
            "token" => "01DMEK0J53CVMC32CK5SE0KZ8Q",
            "new_password" => "^O9w8hiZu3x!"
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $completePasswordResetPath,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->completePasswordReset("01DMEK0J53CVMC32CK5SE0KZ8Q", "^O9w8hiZu3x!");
        $this->assertSame($user, $response->toArray());
    }



    public function testGetUser()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $usersPath = "users/{$userId}";

        $result = $this->getUserResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $usersPath,
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
        $usersPath = "users";
        $params = [
            "email" => null,
            "organization" => null,
            "limit" => UserManagement::DEFAULT_PAGE_SIZE,
            "before" => null,
            "after" => null,
            "order" => null
        ];

        $result = $this->listUsersResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();
        list($before, $after, $users) = $this->userManagement->listUsers();
        $this->assertSame($user, $users[0]->toArray());
    }

    public function testRemoveUserFromOrganization()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $organizationId = "org_01EHQMYV6MBK39QC5PZXHY59C3";
        $usersOrganizationsDeletionPath = "users/{$userId}/organizations/{$organizationId}";

        $result = $this->removeUserFromOrganizationResponseFixture();

        $this->mockRequest(
            Client::METHOD_DELETE,
            $usersOrganizationsDeletionPath,
            null,
            null,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->removeUserFromOrganization("user_01H7X1M4TZJN5N4HG4XXMA1234", "org_01EHQMYV6MBK39QC5PZXHY59C3");
        $this->assertSame($user, $response->toArray());
    }

    private function testSendMagicAuthCode()
    {
        $sendCodePath = "users/magic_auth/send";

        $result = $this->sendMagicAuthCodeResponseFixture();

        $params = [
            "email" => "test@test.com"
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $sendCodePath,
            null,
            $params,
            true,
            $result
        );

        $magicAuthChallenge = $this->magicAuthChallengeFixture();

        $response = $this->userManagement->sendMagicAuthCode("test@test.com");
        $this->assertSame($magicAuthChallenge, $response->toArray());
    }
    // Fixtures

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
                "created_at" => "2021-06-25T19:07:33.155Z",
                "updated_at" => "2021-06-25T19:07:33.155Z"
            ]
        ]);
    }

    private function createSessionAndUserResponseFixture()
    {
        return json_encode([
            "session" => [
                "object" => "session",
                "id" => "session_01E4ZCR3C56J083X43JQXF3JK5",
                "token" => "session_token_123abc",
                "authorized_organizations" => [
                    "organization" => [
                        "id" => "org_01E4ZCR3C56J083X43JQXF3JK5",
                        "name" => "Foo Corp"
                    ]
                ],
                "unauthorized_organizations" => [],
                "created_at" => "2021-06-25T19:07:33.155Z",
                "expires_at" => "2021-06-25T19:07:33.155Z"
            ],
            "user" => [
                "object" => "user",
                "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
                "email" => "test@test.com",
                "first_name" => "Damien",
                "last_name" => "Alabaster",
                "email_verified" => true,
                "created_at" => "2021-06-25T19:07:33.155Z",
                "updated_at" => "2021-06-25T19:07:33.155Z"
            ]
        ]);
    }

    private function addUserToOrganizationResponseFixture()
    {
        return json_encode([
            "object" => "user",
            "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "test@test.com",
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified" => true,
            "created_at" => "2021-06-25T19:07:33.155Z",
            "updated_at" => "2021-06-25T19:07:33.155Z"
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
            "created_at" => "2021-06-25T19:07:33.155Z",
            "updated_at" => "2021-06-25T19:07:33.155Z"
        ]);
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

    private function removeUserFromOrganizationResponseFixture()
    {
        return json_encode([
            "object" => "user",
            "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "email" => "test@test.com",
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified" => true,
            "created_at" => "2021-06-25T19:07:33.155Z",
            "updated_at" => "2021-06-25T19:07:33.155Z"
        ]);
    }

    private function magicAuthChallengeFixture()
    {
        return [
            "object" => "magic_auth_challenge",
            "id" => "auth_challenge_01E4ZCR3C56J083X43JQXF3JK5"
        ];
    }

    private function sessionFixture()
    {
        return [
            "object" => "session",
            "id" => "session_01E4ZCR3C56J083X43JQXF3JK5",
            "token" => "session_token_123abc",
            "authorizedOrganizations" => [
                "organization" => [
                    "id" => "org_01E4ZCR3C56J083X43JQXF3JK5",
                    "name" => "Foo Corp"
                ]
            ],
            "unauthorizedOrganizations" => [],
            "createdAt" => "2021-06-25T19:07:33.155Z",
            "expiresAt" => "2021-06-25T19:07:33.155Z"
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
            "createdAt" => "2021-06-25T19:07:33.155Z",
            "updatedAt" => "2021-06-25T19:07:33.155Z"
        ];
    }
}
