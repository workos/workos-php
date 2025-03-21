<?php

namespace WorkOS\Resource;

use PHPUnit\Framework\TestCase;
use WorkOS\TestHelper;

class WebhookResponseTest extends TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var int
     */
    protected $timestamp;

    protected function setUp(): void
    {
        $this->traitSetUp();
        $this->withApiKey();

        $this->secret = 'test_secret';
        $this->timestamp = time() * 1000; // milliseconds
    }

    public function testCreateAllowResponse()
    {
        $response = WebhookResponse::create(
            WebhookResponse::USER_REGISTRATION_ACTION,
            $this->secret,
            WebhookResponse::VERDICT_ALLOW
        );

        $array = $response->toArray();

        $this->assertEquals(WebhookResponse::USER_REGISTRATION_ACTION, $array['object']);
        $this->assertArrayHasKey('payload', $array);
        $this->assertArrayHasKey('signature', $array);
        $this->assertEquals(WebhookResponse::VERDICT_ALLOW, $array['payload']['verdict']);
        $this->assertArrayHasKey('timestamp', $array['payload']);
        $this->assertArrayNotHasKey('error_message', $array['payload']);
    }

    public function testCreateDenyResponse()
    {
        $errorMessage = 'Registration denied due to risk assessment';
        $response = WebhookResponse::create(
            WebhookResponse::USER_REGISTRATION_ACTION,
            $this->secret,
            WebhookResponse::VERDICT_DENY,
            $errorMessage
        );

        $array = $response->toArray();

        $this->assertEquals(WebhookResponse::USER_REGISTRATION_ACTION, $array['object']);
        $this->assertArrayHasKey('payload', $array);
        $this->assertArrayHasKey('signature', $array);
        $this->assertEquals(WebhookResponse::VERDICT_DENY, $array['payload']['verdict']);
        $this->assertEquals($errorMessage, $array['payload']['error_message']);
        $this->assertArrayHasKey('timestamp', $array['payload']);
    }

    public function testCreateAuthenticationResponse()
    {
        $response = WebhookResponse::create(
            WebhookResponse::AUTHENTICATION_ACTION,
            $this->secret,
            WebhookResponse::VERDICT_ALLOW
        );

        $array = $response->toArray();

        $this->assertEquals(WebhookResponse::AUTHENTICATION_ACTION, $array['object']);
        $this->assertArrayHasKey('payload', $array);
        $this->assertArrayHasKey('signature', $array);
    }

    public function testCreateWithoutSecret()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Secret is required');

        WebhookResponse::create(
            WebhookResponse::USER_REGISTRATION_ACTION,
            '',
            WebhookResponse::VERDICT_ALLOW
        );
    }

    public function testInvalidResponseType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid response type');

        WebhookResponse::create(
            'invalid_type',
            $this->secret,
            WebhookResponse::VERDICT_ALLOW
        );
    }

    public function testInvalidVerdict()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid verdict');

        WebhookResponse::create(
            WebhookResponse::USER_REGISTRATION_ACTION,
            $this->secret,
            'invalid_verdict'
        );
    }

    public function testDenyWithoutErrorMessage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Error message is required when verdict is Deny');

        WebhookResponse::create(
            WebhookResponse::USER_REGISTRATION_ACTION,
            $this->secret,
            WebhookResponse::VERDICT_DENY
        );
    }

    public function testToJson()
    {
        $response = WebhookResponse::create(
            WebhookResponse::USER_REGISTRATION_ACTION,
            $this->secret,
            WebhookResponse::VERDICT_ALLOW
        );

        $json = $response->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals(WebhookResponse::USER_REGISTRATION_ACTION, $decoded['object']);
    }
}
