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

    public function testGetProfileReturnsProfileWithExpectedValues()
    {
        $this->withApiKeyAndProjectId();

        $code = 'code';
        $path = "sso/token";
        $params = [
            "client_id" => WorkOS::getProjectId(),
            "client_secret" => WorkOS::getApikey(),
            "code" => $code,
            "grant_type" => "authorization_code"
        ];

        $result = "{\"profile\":{\"id\":\"prof_hen\",\"email\":\"hen@papagenos.com\",\"first_name\":\"hen\",\"last_name\":\"cha\",\"connection_type\":\"GoogleOAuth\",\"idp_id\":\"randomalphanum\"}}";

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            $result
        );

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

    public function testPromoteDraftConnectionExpectedReturnWhenSuccessful()
    {
        $this->withApiKeyAndProjectId();

        $token = 'token';
        $path = "draft_connections/${token}/activate";
        $headers = Client::generateBaseHeaders();
        \array_push($headers, "Authorization: Bearer " . WorkOS::getApiKey());

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            $headers
        );

        $this->assertTrue((new SSO())->promoteDraftConnection($token));
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
