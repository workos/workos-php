<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class PortalTest extends TestCase
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
            "return_url" => null,
            "success_url" => null
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
            "return_url" => null,
            "success_url" => null
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
            "return_url" => null,
            "success_url" => null
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

    public function testGenerateLinkLogStreams()
    {
        $generateLinkPath = "portal/generate_link";

        $result = $this->generatePortalLinkFixture();

        $params = [
            "organization" => "org_01EHZNVPK3SFK441A1RGBFSHRT",
            "intent" => "log_streams",
            "return_url" => null,
            "success_url" => null
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

        $response = $this->ap->generateLink("org_01EHZNVPK3SFK441A1RGBFSHRT", "log_streams");
        $this->assertSame($expectation, $response->link);
    }

    public function testGenerateLinkCertificateRenewal()
    {
        $generateLinkPath = "portal/generate_link";

        $result = $this->generatePortalLinkFixture();

        $params = [
            "organization" => "org_01EHZNVPK3SFK441A1RGBFSHRT",
            "intent" => "certificate_renewal",
            "return_url" => null,
            "success_url" => null
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

        $response = $this->ap->generateLink("org_01EHZNVPK3SFK441A1RGBFSHRT", "certificate_renewal");
        $this->assertSame($expectation, $response->link);
    }

    public function testGenerateLinkDomainVerification()
    {
        $generateLinkPath = "portal/generate_link";

        $result = $this->generatePortalLinkFixture();

        $params = [
            "organization" => "org_01EHZNVPK3SFK441A1RGBFSHRT",
            "intent" => "domain_verification",
            "return_url" => null,
            "success_url" => null
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

        $response = $this->ap->generateLink("org_01EHZNVPK3SFK441A1RGBFSHRT", "domain_verification");
        $this->assertSame($expectation, $response->link);
    }

    public function testGenerateLinkWithInvalidIntent()
    {
        $this->expectException(Exception\UnexpectedValueException::class);
        $this->expectExceptionMessage("Invalid intent. Valid values are:");

        $this->ap->generateLink("org_01EHZNVPK3SFK441A1RGBFSHRT", "invalid_intent");
    }

    // Fixtures

    private function generatePortalLinkFixture()
    {
        return json_encode([
            "link" => "https://id.workos.com/portal/launch?secret=secret"
        ]);
    }
}
