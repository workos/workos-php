<?php

namespace WorkOS;

class PasswordlessTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as traitSetUp;
    }

    protected function setUp()
    {
        $this->traitSetUp();

        $this->withApiKeyAndClientId();
        $this->passwordless = new Passwordless();
    }

    public function testCreateSession()
    {
        $path = "passwordless/sessions";
        $params = [
            "email" => "demo@foo-corp.com",
            "type" => Resource\ConnectionType::MagicLink
        ];

        $result = $this->sessionResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $session = $this->passwordless->createSession("demo@foo-corp.com", null, null, Resource\ConnectionType::MagicLink, null);
        $sessionFixture = $this->sessionFixture();

        $this->assertSame($sessionFixture, $session->toArray());
    }

    public function testSendSession()
    {
        $sessionResponse = \json_decode($this->sessionResponseFixture(), true);
        $session = Resource\PasswordlessSession::constructFromResponse($sessionResponse);
        $path = "passwordless/sessions/$session->id/send";

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            null,
            true
        );

        $this->assertTrue($this->passwordless->sendSession($session));
    }

    // Fixtures

    private function sessionResponseFixture()
    {
        return json_encode([
            "id" => "passwordless_session_abc123",
            "email" => "demo@foo-corp.com",
            "expires_at" => "2021-01-01T01:00:00.000Z",
            "link" => "https://auth.workos.com/passwordless/alphanum123/confirm",
            "object" => "passwordless_session"
        ]);
    }

    private function sessionFixture()
    {
        return [
            "id" => "passwordless_session_abc123",
            "email" => "demo@foo-corp.com",
            "expiresAt" => "2021-01-01T01:00:00.000Z",
            "link" => "https://auth.workos.com/passwordless/alphanum123/confirm",
            "object" => "passwordless_session"
        ];
    }
}
