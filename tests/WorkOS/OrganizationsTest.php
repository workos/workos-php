<?php

namespace WorkOS;

class OrganizationsTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }

    protected function setUp(): void
    {
        $this->traitSetUp();

        $this->withApiKey();
        $this->organizations = new Organizations();
    }

    public function testCreateOrganizationWithDomains()
    {
        $organizationsPath = "organizations";

        $result = $this->createOrganizationResponseFixture();

        $params = [
            "name" => "Organization Name",
            "domains" => array("example.com"),
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $organizationsPath,
            null,
            $params,
            true,
            $result
        );

        $organization = $this->organizationFixture();

        $response = $this->organizations->createOrganization("Organization Name", array("example.com"));
        $this->assertSame($organization, $response->toArray());
    }

    public function testCreateOrganizationWithDomainData()
    {
        $organizationsPath = "organizations";

        $result = $this->createOrganizationResponseFixture();

        $params = [
            "name" => "Organization Name",
            "domain_data" => array([
                "domain" => "example.com",
                "state" => "verified",
            ]),
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $organizationsPath,
            null,
            $params,
            true,
            $result
        );

        $organization = $this->organizationFixture();

        $response = $this->organizations->createOrganization(
            "Organization Name",
            domain_data: array(["domain" => "example.com", "state" => "verified"]),
        );
        $this->assertSame($organization, $response->toArray());
    }

    public function testUpdateOrganizationWithDomainData()
    {
        $organizationsPath = "organizations/org_01EHQMYV6MBK39QC5PZXHY59C3";

        $result = $this->createOrganizationResponseFixture();

        $params = [
            "name" => null,
            "domain_data" => array([
                "domain" => "example.com",
                "state" => "verified",
            ]),
        ];

        $this->mockRequest(
            Client::METHOD_PUT,
            $organizationsPath,
            null,
            $params,
            true,
            $result
        );

        $organization = $this->organizationFixture();

        $response = $this->organizations->updateOrganization(
            "org_01EHQMYV6MBK39QC5PZXHY59C3",
            domain_data: array(["domain" => "example.com", "state" => "verified"]),
        );
        $this->assertSame($organization, $response->toArray());
    }

    public function testCreateOrganizationSendsIdempotencyKey()
    {
        $organizationsPath = "organizations";
        $idempotencyKey = "idempotencyKey123";
        $result = $this->createOrganizationResponseFixture();

        $params = [
            "name" => "Organization Name",
            "domains" => array("example.com"),
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $organizationsPath,
            array("Authorization: Bearer pk_secretsauce", 'Idempotency-Key: idempotencyKey123'),
            $params,
            false,
            $result
        );

        $response = $this->organizations->createOrganization("Organization Name", array("example.com"), null, $idempotencyKey);
        $response2 = $this->organizations->createOrganization("Organization Name", array("example.com"), null, $idempotencyKey);

        $this->assertSame($response2->toArray()["id"], $response->toArray()["id"]);
    }


    public function testListOrganizations()
    {
        $organizationsPath = "organizations";
        $params = [
            "limit" => Organizations::DEFAULT_PAGE_SIZE,
            "before" => null,
            "after" => null,
            "domains" => null,
            "order" => null
        ];

        $result = $this->organizationsResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $organizationsPath,
            null,
            $params,
            true,
            $result
        );

        $organization = $this->organizationFixture();

        list($before, $after, $organizations) = $this->organizations->listOrganizations();
        $this->assertSame($organization, $organizations[0]->toArray());
    }

    // Fixtures

    private function createOrganizationResponseFixture()
    {
        return json_encode([
            "name" => "Organization Name",
            "object" => "organization",
            "id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
            "allow_profiles_outside_organization" => false,
            "domains" => [
                [
                    "object" => "organization_domain",
                    "id" => "org_domain_01EHQMYV71XT8H31WE5HF8YK4A",
                    "domain" => "example.com"
                ]
            ]
        ]);
    }

    private function organizationsResponseFixture()
    {
        return json_encode([
            "object" => "list",
            "data" => [
                [
                "object" => "organization",
                "id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
                "name" => "Organization Name",
                "allow_profiles_outside_organization" => false,
                "domains" => [
                    [
                        "object" => "organization_domain",
                        "id" => "org_domain_01EHQMYV71XT8H31WE5HF8YK4A",
                        "domain" => "example.com"
                    ]
                ]
                ],
                [
                "object" => "organization",
                "id" => "org_01EHQMVDTC2GRAHFCCRNTSKH46",
                "name" => "example2.com",
                "allow_profiles_outside_organization" => false,
                "domains" => [
                    [
                        "object" => "organization_domain",
                        "id" => "org_domain_01EHQMVDTZVA27PK614ME4YK7V",
                        "domain" => "example2.com"
                    ]
                ]
                ],
                [
                "object" => "organization",
                "id" => "org_01EGP9Z6RY2J6YE0ZV57CGEXV2",
                "name" => "example5.com",
                "allow_profiles_outside_organization" => false,
                "domains" => [
                    [
                        "object" => "organization_domain",
                        "id" => "org_domain_01EGP9Z6S6HVQ5CPD152GJBEA5",
                        "domain" => "example5.com"
                    ]
                ]
                ]
            ],
            "list_metadata" => [
                "before" => "before-id",
                "after" => null
            ]
        ]);
    }

    private function organizationFixture()
    {
        return [
            "id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
            "name" => "Organization Name",
            "allowProfilesOutsideOrganization" => false,
            "domains" => [
                [
                    "object" => "organization_domain",
                    "id" => "org_domain_01EHQMYV71XT8H31WE5HF8YK4A",
                    "domain" => "example.com"
                ]
            ]
        ];
    }
}
