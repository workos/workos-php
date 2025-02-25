<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class WidgetsTest extends TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }

    protected function setUp(): void
    {
        $this->traitSetUp();

        $this->withApiKey();
        $this->ap = new Widgets();
    }

    public function testGenerateLinkSSO()
    {
        $getTokenPath = "widgets/token";

        $result = $this->generateWidgetTokenFixture();

        $params = [
            "organization_id" => "org_01EHZNVPK3SFK441A1RGBFSHRT",
            "user_id" => "user_01EHZNVPK3SFK441A1RGBFSHRT",
            "scopes" => ["widgets:users-table:manage"]
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $getTokenPath,
            null,
            $params,
            true,
            $result
        );

        $expectation = "abc123456";

        $response = $this->ap->getToken("org_01EHZNVPK3SFK441A1RGBFSHRT", "user_01EHZNVPK3SFK441A1RGBFSHRT", ["widgets:users-table:manage"]);
        $this->assertSame($expectation, $response->token);
    }

    // Fixtures

    private function generateWidgetTokenFixture()
    {
        return json_encode([
            "token" => "abc123456"
        ]);
    }
}
