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

    public function testDeleteUser()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $usersPath = "users/{$userId}";
        $responseCode = 204;

        $this->mockRequest(
            Client::METHOD_DELETE,
            $usersPath,
            null,
            null,
            true,
            null,
            null,
            $responseCode
        );

        $response = $this->userManagement->deleteUser($userId);
        $this->assertSame(204, $responseCode);
        $this->assertSame($response, []);
    }

    public function testUpdateUser()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $usersPath = "users/{$userId}";

        $result = $this->createUserResponseFixture();

        $params = [
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified" => true
        ];

        $this->mockRequest(
            Client::METHOD_PUT,
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->updateUser("user_01H7X1M4TZJN5N4HG4XXMA1234", "Damien", "Alabaster", true);
        $this->assertSame($user, $response->toArray());
    }

    public function testUpdateUserPassword()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $usersPath = "users/{$userId}/password";

        $result = $this->createUserResponseFixture();

        $params = [
            "password" => "x^T!V23UN1@V"
        ];

        $this->mockRequest(
            Client::METHOD_PUT,
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        $response = $this->userManagement->updateUserPassword("user_01H7X1M4TZJN5N4HG4XXMA1234", "x^T!V23UN1@V");
        $this->assertSame($user, $response->toArray());
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
        $usersPath = "users/authenticate";
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
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateUserWithPassword("project_0123456", "marcelina@foo-corp.com", "i8uv6g34kd490s");
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testAuthenticateUserWithCode()
    {
        $usersPath = "users/authenticate";
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
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateUserWithCode("project_0123456", "01E2RJ4C05B52KKZ8FSRDAP23J");
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testAuthenticateUserWithMagicAuth()
    {
        $usersPath = "users/authenticate";
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
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->authenticateUserWithMagicAuth("project_0123456", "123456", "user_01H7X1M4TZJN5N4HG4XXMA1234");
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
        $userId = "user_01E4ZCR3C56J083X43JQXF3JK5";
        $sendVerificationEmailPath = "users/{$userId}/send_verification_email";

        $result = $this->createUserResponseFixture();


        $this->mockRequest(
            Client::METHOD_POST,
            $sendVerificationEmailPath,
            null,
            null,
            true,
            $result
        );


        $user = $this->userFixture();

        $response = $this->userManagement->sendVerificationEmail("user_01E4ZCR3C56J083X43JQXF3JK5");
        $this->assertSame($user, $response->toArray());
    }

    public function testVerifyEmailCode()
    {
        $userId = "user_01H7X1M4TZJN5N4HG4XXMA1234";
        $verifyEmailCodePath = "users/{$userId}/verify_email_code";

        $result = $this->UserResponseFixture();

        $params = [
            "user_id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "code" => "01DMEK0J53CVMC32CK5SE0KZ8Q",
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $verifyEmailCodePath,
            null,
            $params,
            true,
            $result
        );

        $userFixture = $this->userFixture();

        $response = $this->userManagement->verifyEmailCode("user_01H7X1M4TZJN5N4HG4XXMA1234", "01DMEK0J53CVMC32CK5SE0KZ8Q");
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testSendPasswordResetEmail()
    {
        $sendPasswordResetEmailPath = "users/send_password_reset_email";

        $result = $this->createUserAndTokenResponseFixture();

        $params = [
            "email" => "test@test.com",
            "password_reset_url" => "https://your-app.com/reset-password"
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $sendPasswordResetEmailPath,
            null,
            $params,
            true,
            $result
        );


        $userFixture = $this->userFixture();

        $response = $this->userManagement->sendPasswordResetEmail("test@test.com", "https://your-app.com/reset-password");
        $this->assertSame("01DMEK0J53CVMC32CK5SE0KZ8Q", $response->token);
        $this->assertSame($userFixture, $response->user->toArray());
    }

    public function testResetPassword()
    {
        $resetPasswordPath = "users/password_reset";

        $result = $this->userResponseFixture();

        $params = [
            "token" => "01DMEK0J53CVMC32CK5SE0KZ8Q",
            "new_password" => "^O9w8hiZu3x!"
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $resetPasswordPath,
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

        $result = $this->createUserResponseFixture();

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

        $user = $this->userFixture();

        $response = $this->userManagement->sendMagicAuthCode("test@test.com");
        $this->assertSame($user, $response->toArray());
    }
    // Fixtures

    private function UserResponseFixture()
    {
        return json_encode([
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
