<?php

namespace WorkOS;

class WebhookTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }

    protected function setUp(): void
    {
        $this->traitSetUp();

        $this->withApiKey();
        $this->ap = new Webhook();
    }

    public function testConstructEventWebhook()
    {
        $time = time();
        $payload = '{"id":"wh_01FGCG6SDYCT5XWZT9CDW0XEB8","data":{"id":"conn_01EHWNC0FCBHZ3BJ7EGKYXK0E6","name":"Foo Corp\'s Connection","state":"active","object":"connection","domains":[{"id":"conn_domain_01EHWNFTAFCF3CQAE5A9Q0P1YB","domain":"foo-corp.com","object":"connection_domain"}],"connection_type":"OktaSAML","organization_id":"org_01EHWNCE74X7JSDV0X3SZ3KJNY"},"event":"connection.activated"}';
        $secret = 'secret';
        $tolerance = 180;
        $decodedBody = utf8_decode($payload);
        $signedPayload = $time . "." . $decodedBody;
        $expectedSignature = hash_hmac("sha256", $signedPayload, $secret, false);
        $headers = 't=' . $time . ', v1=' . $expectedSignature;
        $result = $this->generateConnectionFixture();

        $expectation = '{"id":"wh_01FGCG6SDYCT5XWZT9CDW0XEB8","data":{"id":"conn_01EHWNC0FCBHZ3BJ7EGKYXK0E6","name":"Foo Corp\'s Connection","state":"active","object":"connection","domains":[{"id":"conn_domain_01EHWNFTAFCF3CQAE5A9Q0P1YB","domain":"foo-corp.com","object":"connection_domain"}],"connection_type":"OktaSAML","organization_id":"org_01EHWNCE74X7JSDV0X3SZ3KJNY"},"event":"connection.activated"}';

        $response = $this->ap->constructEvent($headers, $payload, $secret, $tolerance);
        $this->assertSame($expectation, json_encode($response));
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
