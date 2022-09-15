<?php

namespace WorkOS;

class PortalTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }

    protected function setUp(): void
    {
        $this->traitSetUp();

        $this->withApiKey();
        $this->ap = new Portal();
    }

    public function testGenerateLinkSSO()
    {
        $generateLinkPath = "portal/generate_link";

        $result = $this->generatePortalLinkFixture();

        $params = [
            "organization" => "org_01EHZNVPK3SFK441A1RGBFSHRT",
            "intent" => "sso",
            "return_url" => null
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $generateLinkPath,
            null,
            $params,
            true,
            $result
        );

        $expectation = "https://id.workos.com/portal/launch?secret=secret";

        $response = $this->ap->generateLink("org_01EHZNVPK3SFK441A1RGBFSHRT", "sso");
        $this->assertSame($expectation, $response->link);
    }

    public function testGenerateLinkDSync()
    {
        $generateLinkPath = "portal/generate_link";

        $result = $this->generatePortalLinkFixture();

        $params = [
            "organization" => "org_01EHZNVPK3SFK441A1RGBFSHRT",
            "intent" => "dsync",
            "return_url" => null
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $generateLinkPath,
            null,
            $params,
            true,
            $result
        );

        $expectation = "https://id.workos.com/portal/launch?secret=secret";

        $response = $this->ap->generateLink("org_01EHZNVPK3SFK441A1RGBFSHRT", "dsync");
        $this->assertSame($expectation, $response->link);
    }

    public function testGenerateLinkAuditLogs()
    {
        $generateLinkPath = "portal/generate_link";

        $result = $this->generatePortalLinkFixture();

        $params = [
            "organization" => "org_01EHZNVPK3SFK441A1RGBFSHRT",
            "intent" => "audit_logs",
            "return_url" => null
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $generateLinkPath,
            null,
            $params,
            true,
            $result
        );

        $expectation = "https://id.workos.com/portal/launch?secret=secret";

        $response = $this->ap->generateLink("org_01EHZNVPK3SFK441A1RGBFSHRT", "audit_logs");
        $this->assertSame($expectation, $response->link);
    }

    // Fixtures

    private function generatePortalLinkFixture()
    {
        return json_encode([
            "link" => "https://id.workos.com/portal/launch?secret=secret"
        ]);
    }
}
