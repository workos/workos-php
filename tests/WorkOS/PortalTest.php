<?php

namespace WorkOS;

class PortalTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }

    protected function setUp()
    {
        $this->traitSetUp();

        $this->withApiKeyAndProjectId();
        $this->ap = new Portal();
    }

    public function testListOrganizations()
    {
        $organizationsPath = "organizations";
        $params = [
            "limit" => Portal::DEFAULT_PAGE_SIZE,
            "before" => null,
            "after" => null,
            "domains" => null
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

        list($before, $after, $organizations) = $this->ap->listOrganizations();
        $this->assertSame($organization, $organizations[0]->toArray());
    }

    // Fixtures

    private function organizationsResponseFixture()
    {
        return json_encode([
            "object" => "list",
            "data" => [
                [
                "object" => "organization",
                "id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
                "name" => "example.com",
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
                "domains" => [
                    [
                    "object" => "organization_domain",
                    "id" => "org_domain_01EGP9Z6S6HVQ5CPD152GJBEA5",
                    "domain" => "example5.com"
                    ]
                ]
                ]
            ],
            "listMetadata" => [
                "before" => "before-id",
                "after" => null
            ]
        ]);
    }

    private function organizationFixture()
    {
        return [
            "id" => "org_01EHQMYV6MBK39QC5PZXHY59C3",
            "name" => "example.com",
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
