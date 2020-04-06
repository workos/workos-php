<?php

namespace WorkOS;

class SSOTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper;

    public function testConfiguratiponExceptionWithApiKey()
    {
        $this->expectException(Exception\ConfigurationException::class);

        $this->withApiKey();
        new SSO();
    }

    public function testConfigurationExceptionWithProjectId()
    {
        $this->expectException(Exception\ConfigurationException::class);

        $this->withProjectId();
        new SSO();
    }

    public function testNoExceptionWithApiKeyAndProjectId()
    {
        $this->withApiKeyAndProjectId();

        new SSO();

        $this->assertTrue(true);
    }

    /**
     * @dataProvider authorizationUrlTestProvider
     */
    public function testAuthorizationURLExpectedParams($domain, $redirectUri, $state, $provider)
    {
        $this->withApiKeyAndProjectId();

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

        $authorizationUrl = (new SSO())->getAuthorizationUrl($domain, $redirectUri, $state, $provider);
        $paramsString = \parse_url($authorizationUrl, \PHP_URL_QUERY);
        \parse_str($paramsString, $paramsArray);

        $this->assertSame($expectedParams, $paramsArray);
    }

    public function testGetProfile()
    {
        $body = "{\"profile\":{\"id\":\"prof_hen\",\"email\":\"hen@papagenos.com\",\"first_name\":\"hen\",\"last_name\":\"cha\",\"connection_type\":\"GoogleOAuth\",\"idp_id\":\"randomalphanum\"}}";
        $headers = [];
        $statusCode = 200;

        $this->withApiKeyAndProjectId();
        $this->mockResponse($body, $headers, $statusCode);

        $profile = (new SSO())->getProfile('code');

        $expected = [
            "id" => "prof_hen",
            "email" => "hen@papagenos.com",
            "firstName" => "hen",
            "lastName" => "cha",
            "connectionType" => "GoogleOAuth",
            "idpId" => "randomalphanum"
        ];
        $this->assertSame($expected, $profile->toArray());
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
}
