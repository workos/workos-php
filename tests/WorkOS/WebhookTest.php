<?php

namespace WorkOS;

use WorkOS\Webhook;
use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }
    /**
     * @var Webhook
     */
    protected $webhook;

    /**
     * @var string
     */
    protected $payload;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var int
     */
    protected $tolerance;

    /**
     * @var int
     */
    protected $time;

    /**
     * @var string
     */
    protected $expectedSignature;

    /**
     * @var string
     */
    protected $sigHeader;

    protected function setUp(): void
    {
        $this->traitSetUp();

        $this->withApiKey();
        $this->webhook = new Webhook();

        $this->payload = '{"id":"wh_01FGCG6SDYCT5XWZT9CDW0XEB8","data":{"id":"conn_01EHWNC0FCBHZ3BJ7EGKYXK0E6","name":"Foo Corp\'s Connection","state":"active","object":"connection","domains":[{"id":"conn_domain_01EHWNFTAFCF3CQAE5A9Q0P1YB","domain":"foo-corp.com","object":"connection_domain"}],"connection_type":"OktaSAML","organization_id":"org_01EHWNCE74X7JSDV0X3SZ3KJNY"},"event":"connection.activated"}';
        $this->secret = 'secret';
        $this->tolerance = 180;
        $this->time = time();
        $decodedBody = mb_convert_encoding($this->payload, 'ISO-8859-1', 'UTF-8');
        $signedPayload = $this->time . "." . $decodedBody;
        $this->expectedSignature = hash_hmac("sha256", $signedPayload, $this->secret, false);
        $this->sigHeader = 't=' . $this->time . ', v1=' . $this->expectedSignature;
    }

    public function testConstructEventWebhook()
    {
        $this->generateConnectionFixture();

        $expectation = $this->payload;

        $response = $this->webhook->constructEvent($this->sigHeader, $this->payload, $this->secret, $this->tolerance);
        $this->assertSame($expectation, json_encode($response));
    }

    public function testVerifyHeaderWebhook()
    {
        $expectation = 'pass';

        $response = $this->webhook->verifyHeader($this->sigHeader, $this->payload, $this->secret, $this->tolerance);
        $this->assertSame($expectation, $response);
    }

    public function testGetTimeStamp()
    {
        $expectation = strval($this->time);

        $response = $this->webhook->getTimeStamp($this->sigHeader);
        $this->assertSame($expectation, $response);
    }

    public function testGetSignature()
    {
        $expectation = $this->expectedSignature;

        $response = $this->webhook->getSignature($this->sigHeader);
        $this->assertSame($expectation, $response);
    }

    // Fixtures

    private function generateConnectionFixture()
    {
        return json_encode([
            "id" => "conn_01E0CG2C820RP4VS50PRJF8YPX",
            "domains" => [
                [
                    "id" => "conn_dom_01E2GCC7Q3KCNEFA2BW9MXR4T5",
                    "domain" => "workos.com"
                ]
            ],
            "state" => "active",
            "status" => "linked",
            "name" => "Google OAuth 2.0",
            "connectionType" => "GoogleOAuth",
            "organizationId" => "org_1234",
        ]);
    }
}
