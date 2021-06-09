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

        $this->withApiKeyAndClientId();
        $this->sso = new SSO();
    }

    /**
     * @dataProvider authorizationUrlTestProvider
     */
    public function testAuthorizationURLExpectedParams($domain, $redirectUri, $state, $provider, $connection)
    {
        $expectedParams = [
            "client_id" => WorkOS::getClientId(),
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

        if ($connection) {
            $expectedParams["connection"] = $connection;
        }

        $authorizationUrl = $this->sso->getAuthorizationUrl($domain, $redirectUri, $state, $provider, $connection);
        $paramsString = \parse_url($authorizationUrl, \PHP_URL_QUERY);
        \parse_str($paramsString, $paramsArray);

        $this->assertSame($expectedParams, $paramsArray);
    }

    public function testGetProfileAndTokenReturnsProfileWithExpectedValues()
    {
        $getProfilePath = "sso/profile";
        $params = [
            "access_token" => $accessToken
        ];

        $result = $this->profileFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $getProfilePath,
            null,
            $params,
            false,
            $result
        );

        $profile = $this->sso->getProfile('access_token');

        $this->assertSame($profileFixture, $profile->toArray());
    }

    public function testGetProfile()
    {
        $code = 'code';
        $path = "sso/token";
        $params = [
            "client_id" => WorkOS::getClientId(),
            "client_secret" => WorkOS::getApikey(),
            "code" => $code,
            "grant_type" => "authorization_code"
        ];

        $result = $this->profileAndTokenResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            false,
            $result
        );

        $profileAndToken = $this->sso->getProfileAndToken('code');
        $profileFixture = $this->profileFixture();

        $this->assertSame("01DMEK0J53CVMC32CK5SE0KZ8Q", $profileAndToken->accessToken);
        $this->assertSame($profileFixture, $profileAndToken->profile->toArray());
    }

    public function testGetConnection()
    {
        $connection = "connection_id";
        $connectionPath = "connections/${connection}";

        $result = $this->connectionResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $connectionPath,
            null,
            null,
            true,
            $result
        );

        $connection = $this->sso->getConnection($connection);
        $connectionFixture = $this->connectionFixture();

        $this->assertSame($connectionFixture, $connection->toArray());
    }

    public function testListConnections()
    {
        $connectionsPath = "connections";
        $params = [
            "limit" => SSO::DEFAULT_PAGE_SIZE,
            "before" => null,
            "after" => null,
            "domain" => null,
            "connection_type" => null
        ];

        $result = $this->connectionsResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $connectionsPath,
            null,
            $params,
            true,
            $result
        );

        $connection = $this->connectionFixture();

        list($before, $after, $connections) = $this->sso->listConnections();
        $this->assertSame($connection, $connections[0]->toArray());
    }

    public function testDeleteConnection()
    {
        $connection = "connection_id";
        $connectionPath = "connections/${connection}";
        $responseCode = 204;

        $this->mockRequest(
            Client::METHOD_DELETE,
            $connectionPath,
            null,
            null,
            true,
            null,
            null,
            $responseCode
        );

        $response = $this->sso->deleteConnection($connection);
        $this->assertSame(204, $responseCode);
    }

    // Providers

    public function authorizationUrlTestProvider()
    {
        return [
            ["papagenos.com", null, null, null, null],
            [null, null, null, Resource\ConnectionType::GoogleOAuth, null],
            ["papagenos.com", "https://papagenos.com/auth/callback", null, null, null],
            ["papagenos.com", "https://papagenos.com/auth/callback", ["toppings" => "ham"], null, null],
            [null, null, null, null, "connection_123"],
        ];
    }

    // Fixtures

    private function profileAndTokenResponseFixture()
    {
        return json_encode([
            "access_token" => "01DMEK0J53CVMC32CK5SE0KZ8Q",
            "profile" => [
                "id" => "prof_hen",
                "email" => "hen@papagenos.com",
                "first_name" => "hen",
                "last_name" => "cha",
                "connection_id" => "conn_01EMH8WAK20T42N2NBMNBCYHAG",
                "connection_type" => "GoogleOAuth",
                "idp_id" => "randomalphanum",
                "raw_attributes" => array(
                    "email" => "hen@papagenos.com",
                    "first_name" => "hen",
                    "last_name" => "cha",
                    "ipd_id" => "randomalphanum"
                )
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
            "connectionId" => "conn_01EMH8WAK20T42N2NBMNBCYHAG",
            "connectionType" => "GoogleOAuth",
            "idpId" => "randomalphanum",
            "rawAttributes" => array(
                "email" => "hen@papagenos.com",
                "first_name" => "hen",
                "last_name" => "cha",
                "ipd_id" => "randomalphanum"
            )
        ];
    }

    private function connectionFixture()
    {
        return [
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
        ];
    }

    private function connectionResponseFixture()
    {
        return json_encode([
            "id" => "conn_01E0CG2C820RP4VS50PRJF8YPX",
            "state" => "active",
            "status" => "linked",
            "name" => "Google OAuth 2.0",
            "connection_type" => "GoogleOAuth",
            "organization_id" => "org_1234",
            "domains" => [
                [
                    "object" => "connection_domain",
                    "id" => "conn_dom_01E2GCC7Q3KCNEFA2BW9MXR4T5",
                    "domain" => "workos.com"
                ]
            ]
        ]);
    }

    private function connectionsResponseFixture()
    {
        return json_encode([
            "data" => [
                [
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
                    "connection_type" => "GoogleOAuth",
                    "oidc_client_id" => null,
                    "oidc_client_secret" => null,
                    "oidc_discovery_endpoint" => null,
                    "oidc_redirect_uri" => null,
                    "saml_entity_id" => null,
                    "saml_idp_url" => null,
                    "saml_relying_party_private_key" => null,
                    "saml_relying_party_public_key" => null,
                    "saml_x509_certs" => null,
                    "organization_id" => "org_1234",
                    "oauth_uid" => "oauthuid",
                    "oauth_secret" => "oauthsecret",
                    "oauth_redirect_uri" => "http://localhost:7000/sso/oauth/google/GbQX1B6LWUYcsGiq6k20iCUMA/callback",
                ]
            ],
            "listMetadata" => [
                "before" => null,
                "after" => null
            ],
        ]);
    }
}
