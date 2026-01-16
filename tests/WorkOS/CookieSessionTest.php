<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class CookieSessionTest extends TestCase
{
    use TestHelper {
        setUp as traitSetUp;
    }

    /**
     * @var UserManagement
     */
    private $userManagement;

    /**
     * @var string
     */
    private $sealedSession;

    /**
     * @var string
     */
    private $cookiePassword = "test-password-for-encryption-with-minimum-length";

    protected function setUp(): void
    {
        $this->traitSetUp();
        $this->withApiKeyAndClientId();
        $this->userManagement = new UserManagement();

        // Create a sealed session for testing using encryptor directly
        // (sealing is authkit-php's responsibility, not SDK's)
        $sessionData = [
            'access_token' => 'test_access_token_12345',
            'refresh_token' => 'test_refresh_token_67890',
            'session_id' => 'session_01H7X1M4TZJN5N4HG4XXMA1234'
        ];
        $encryptor = new Session\HaliteSessionEncryption();
        $this->sealedSession = $encryptor->seal($sessionData, $this->cookiePassword);
    }

    public function testConstructCookieSession()
    {
        $cookieSession = new CookieSession(
            $this->userManagement,
            $this->sealedSession,
            $this->cookiePassword
        );

        $this->assertInstanceOf(CookieSession::class, $cookieSession);
    }

    public function testAuthenticateFailsWithInvalidSession()
    {
        $cookieSession = new CookieSession(
            $this->userManagement,
            "invalid-sealed-session-data",
            $this->cookiePassword
        );

        $result = $cookieSession->authenticate();

        $this->assertInstanceOf(
            Resource\SessionAuthenticationFailureResponse::class,
            $result
        );
        $this->assertFalse($result->authenticated);
    }

    public function testGetLogoutUrlThrowsExceptionForUnauthenticatedSession()
    {
        $cookieSession = new CookieSession(
            $this->userManagement,
            "invalid-sealed-session-data",
            $this->cookiePassword
        );

        $this->expectException(Exception\UnexpectedValueException::class);
        $this->expectExceptionMessage("Cannot get logout URL for unauthenticated session");

        $cookieSession->getLogoutUrl();
    }

    public function testLoadSealedSessionReturnsValidCookieSession()
    {
        $cookieSession = $this->userManagement->loadSealedSession(
            $this->sealedSession,
            $this->cookiePassword
        );

        $this->assertInstanceOf(CookieSession::class, $cookieSession);
    }

    public function testRefreshReturnsRawTokensOnSuccess()
    {
        $organizationId = "org_01H7X1M4TZJN5N4HG4XXMA1234";

        // Create a mock UserManagement to verify method calls
        $userManagementMock = $this->getMockBuilder(UserManagement::class)
            ->onlyMethods(['authenticateWithSessionCookie', 'authenticateWithRefreshToken'])
            ->getMock();

        // Mock authenticateWithSessionCookie to return a successful authentication
        $authResponseData = [
            'authenticated' => true,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'session_id' => 'session_123',
            'user' => [
                'object' => 'user',
                'id' => 'user_123',
                'email' => 'test@test.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email_verified' => true,
                'created_at' => '2021-01-01T00:00:00.000Z',
                'updated_at' => '2021-01-01T00:00:00.000Z'
            ]
        ];
        $authResponse = Resource\SessionAuthenticationSuccessResponse::constructFromResponse($authResponseData);
        $userManagementMock->method('authenticateWithSessionCookie')
            ->willReturn($authResponse);

        // Setup refresh to succeed
        $refreshResponseData = [
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'user' => [
                'object' => 'user',
                'id' => 'user_123',
                'email' => 'test@test.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email_verified' => true,
                'created_at' => '2021-01-01T00:00:00.000Z',
                'updated_at' => '2021-01-01T00:00:00.000Z'
            ]
        ];
        $refreshResponse = Resource\AuthenticationResponse::constructFromResponse($refreshResponseData);

        $userManagementMock->expects($this->once())
            ->method('authenticateWithRefreshToken')
            ->with(
                $this->identicalTo(WorkOS::getClientId()),  // clientId from config
                $this->identicalTo('test_refresh_token'),   // refresh token
                $this->identicalTo(null),                   // ipAddress
                $this->identicalTo(null),                   // userAgent
                $this->identicalTo($organizationId)         // organizationId
            )
            ->willReturn($refreshResponse);

        // Execute refresh with the mocked UserManagement
        $cookieSession = new CookieSession(
            $userManagementMock,
            $this->sealedSession,
            $this->cookiePassword
        );

        [$response, $tokens] = $cookieSession->refresh([
            'organizationId' => $organizationId
        ]);

        // Verify response is successful
        $this->assertInstanceOf(Resource\SessionAuthenticationSuccessResponse::class, $response);
        $this->assertTrue($response->authenticated);

        // Verify tokens are returned as raw array (not sealed)
        $this->assertIsArray($tokens);
        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertArrayHasKey('session_id', $tokens);
        $this->assertEquals('new_access_token', $tokens['access_token']);
        $this->assertEquals('new_refresh_token', $tokens['refresh_token']);
        $this->assertEquals('session_123', $tokens['session_id']);
    }

    public function testRefreshReturnsNullTokensOnAuthFailure()
    {
        $userManagementMock = $this->getMockBuilder(UserManagement::class)
            ->onlyMethods(['authenticateWithSessionCookie'])
            ->getMock();

        $failResponse = new Resource\SessionAuthenticationFailureResponse(
            Resource\SessionAuthenticationFailureResponse::REASON_INVALID_SESSION_COOKIE
        );

        $userManagementMock->method('authenticateWithSessionCookie')
            ->willReturn($failResponse);

        $cookieSession = new CookieSession(
            $userManagementMock,
            'invalid-session',
            $this->cookiePassword
        );

        [$response, $tokens] = $cookieSession->refresh();

        $this->assertFalse($response->authenticated);
        $this->assertNull($tokens);
    }

    public function testRefreshReturnsHttpErrorOnApiFailure()
    {
        $userManagementMock = $this->getMockBuilder(UserManagement::class)
            ->onlyMethods(['authenticateWithSessionCookie', 'authenticateWithRefreshToken'])
            ->getMock();

        // Mock successful initial auth
        $authResponseData = [
            'authenticated' => true,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'session_id' => 'session_123',
            'user' => [
                'object' => 'user',
                'id' => 'user_123',
                'email' => 'test@test.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email_verified' => true,
                'created_at' => '2021-01-01T00:00:00.000Z',
                'updated_at' => '2021-01-01T00:00:00.000Z'
            ]
        ];
        $authResponse = Resource\SessionAuthenticationSuccessResponse::constructFromResponse($authResponseData);
        $userManagementMock->method('authenticateWithSessionCookie')
            ->willReturn($authResponse);

        // Mock refresh to throw exception
        $userManagementMock->method('authenticateWithRefreshToken')
            ->willThrowException(new \Exception('HTTP request failed'));

        $cookieSession = new CookieSession(
            $userManagementMock,
            $this->sealedSession,
            $this->cookiePassword
        );

        [$response, $tokens] = $cookieSession->refresh();

        $this->assertFalse($response->authenticated);
        $this->assertEquals(
            Resource\SessionAuthenticationFailureResponse::REASON_HTTP_ERROR,
            $response->reason
        );
        $this->assertNull($tokens);
    }
}
