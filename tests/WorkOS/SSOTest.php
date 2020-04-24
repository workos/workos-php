<?php

namespace WorkOS;

class SSOTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as traitSetUp;
    }
    
    protected function setUp()
    {
        $this->traitSetUp();

        $this->withApiKeyAndProjectId();
        $this->sso = new SSO();
    }

    /**
     * @dataProvider authorizationUrlTestProvider
     */
    public function testAuthorizationURLExpectedParams($domain, $redirectUri, $state, $provider)
    {
        $expectedParams = [
            "client_id" => WorkOS::getProjectId(),
            "response_type" => "code"
        ];

        if ($domain) {
            $expectedParams["domain"] = $domain;
        }

        if ($redirectUri) {
            $expectedParams["redirect_uri"] = $redirectUri;
        }

        if (null !== $state && !empty($state)) {
            $expectedParams["state"] = \json_encode($state);
        }

        if ($provider) {
            $expectedParams["provider"] = $provider;
        }

        $authorizationUrl = $this->sso->getAuthorizationUrl($domain, $redirectUri, $state, $provider);
        $paramsString = \parse_url($authorizationUrl, \PHP_URL_QUERY);
        \parse_str($paramsString, $paramsArray);

        $this->assertSame($expectedParams, $paramsArray);
    }

    public function testGetProfileReturnsProfileWithExpectedValues()
    {
        $code = 'code';
        $path = "sso/token";
        $params = [
            "client_id" => WorkOS::getProjectId(),
            "client_secret" => WorkOS::getApikey(),
            "code" => $code,
            "grant_type" => "authorization_code"
        ];

        $result = $this->profileResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            false,
            $result
        );

        $profile = $this->sso->getProfile('code');
        $profileFixture = $this->profileFixture();

        $this->assertSame($profileFixture, $profile->toArray());
    }

    public function testPromoteDraftConnectionExpectedReturnWhenSuccessful()
    {
        $token = 'token';
        $path = "draft_connections/${token}/activate";

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            null,
            true
        );

        $this->assertTrue($this->sso->promoteDraftConnection($token));
    }

    // Providers

    public function authorizationUrlTestProvider()
    {
        return [
            ["papagenos.com", null, null, null],
            [null, null, null, Resource\ConnectionType::GoogleOAuth],
            ["papagenos.com", "https://papagenos.com/auth/callback", null, null],
            ["papagenos.com", "https://papagenos.com/auth/callback", ["toppings" => "ham"], null],
        ];
    }

    // Fixtures

    private function profileResponseFixture()
    {
        return json_encode([
            "profile" => [
                "id" => "prof_hen",
                "email" => "hen@papagenos.com",
                "first_name" => "hen",
                "last_name" => "cha",
                "connection_type" => "GoogleOAuth",
                "idp_id" => "randomalphanum"
            ]
        ]);
    }

    private function profileFixture()
    {
        return [
            "id" => "prof_hen",
            "email" => "hen@papagenos.com",
            "firstName" => "hen",
            "lastName" => "cha",
            "connectionType" => "GoogleOAuth",
            "idpId" => "randomalphanum"
        ];
    }
}
