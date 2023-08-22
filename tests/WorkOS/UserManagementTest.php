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
            "magic_auth_challenge_id" => "auth_challenge_01E4ZCR3C56J083X43JQXF3JK5",
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

        $response = $this->userManagement->verifyEmail("auth_challenge_01E4ZCR3C56J083X43JQXF3JK5", "01DMEK0J53CVMC32CK5SE0KZ8Q");
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
            "type" => null,
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
                "user_type" => "unmanaged",
                "email" => "test@test.com",
                "first_name" => "Damien",
                "last_name" => "Alabaster",
                "email_verified_at" => "2021-07-25T19:07:33.155Z",
                "sso_profile_id" => "1AO5ZPQDE43",
                "google_oauth_profile_id" => "goog_123ABC",
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
            "user_type" => "unmanaged",
            "email" => "test@test.com",
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified_at" => "2021-07-25T19:07:33.155Z",
            "sso_profile_id" => "1AO5ZPQDE43",
            "google_oauth_profile_id" => "goog_123ABC",
            "created_at" => "2021-06-25T19:07:33.155Z",
            "updated_at" => "2021-06-25T19:07:33.155Z"
        ]);
    }

    private function createUserResponseFixture()
    {
        return json_encode([
            "object" => "user",
            "id" => "user_01H7X1M4TZJN5N4HG4XXMA1234",
            "user_type" => "unmanaged",
            "email" => "test@test.com",
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified_at" => "2021-07-25T19:07:33.155Z",
            "sso_profile_id" => "1AO5ZPQDE43",
            "google_oauth_profile_id" => "goog_123ABC",
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
            "user_type" => "unmanaged",
            "email" => "test@test.com",
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified_at" => "2021-07-25T19:07:33.155Z",
            "sso_profile_id" => "1AO5ZPQDE43",
            "google_oauth_profile_id" => "goog_123ABC",
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
                    "user_type" => "unmanaged",
                    "email" => "test@test.com",
                    "first_name" => "Damien",
                    "last_name" => "Alabaster",
                    "email_verified_at" => "2021-07-25T19:07:33.155Z",
                    "sso_profile_id" => "1AO5ZPQDE43",
                    "google_oauth_profile_id" => "goog_123ABC",
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
            "user_type" => "unmanaged",
            "email" => "test@test.com",
            "first_name" => "Damien",
            "last_name" => "Alabaster",
            "email_verified_at" => "2021-07-25T19:07:33.155Z",
            "sso_profile_id" => "1AO5ZPQDE43",
            "google_oauth_profile_id" => "goog_123ABC",
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
            "userType" => "unmanaged",
            "email" => "test@test.com",
            "firstName" => "Damien",
            "lastName" => "Alabaster",
            "emailVerifiedAt" => "2021-07-25T19:07:33.155Z",
            "googleOauthProfileId" => "goog_123ABC",
            "ssoProfileId" => "1AO5ZPQDE43",
            "createdAt" => "2021-06-25T19:07:33.155Z",
            "updatedAt" => "2021-06-25T19:07:33.155Z"
        ];
    }
}
