<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class OrganizationsTest extends TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }

    /**
     * @var Organizations
     */
    protected $organizations;

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
            null,
            null,
            null,
            array(["domain" => "example.com", "state" => "verified"]),
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
            null,
            null,
            null,
            array(["domain" => "example.com", "state" => "verified"]),
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

    public function testListOrganizationsPaginatedResourceAccessPatterns()
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

        // Test 1: Bare destructuring (indexed)
        [$before1, $after1, $organizations1] = $this->organizations->listOrganizations();
        $this->assertSame("before-id", $before1);
        $this->assertNull($after1);
        $this->assertIsArray($organizations1);
        $this->assertCount(3, $organizations1);

        // Mock the request again for the next test
        $this->mockRequest(
            Client::METHOD_GET,
            $organizationsPath,
            null,
            $params,
            true,
            $result
        );

        // Test 2: Named destructuring
        ["before" => $before2, "after" => $after2, "organizations" => $organizations2] = $this->organizations->listOrganizations();
        $this->assertSame("before-id", $before2);
        $this->assertNull($after2);
        $this->assertIsArray($organizations2);
        $this->assertCount(3, $organizations2);

        // Mock the request again for the next test
        $this->mockRequest(
            Client::METHOD_GET,
            $organizationsPath,
            null,
            $params,
            true,
            $result
        );

        // Test 3: Fluent access
        $response = $this->organizations->listOrganizations();
        $this->assertSame("before-id", $response->before);
        $this->assertNull($response->after);
        $this->assertIsArray($response->organizations);
        $this->assertCount(3, $response->organizations);

        // Test 4: Generic data accessor
        $this->assertIsArray($response->data);
        $this->assertSame($response->organizations, $response->data);
    }

    public function testListOrganizationRoles()
    {
        $organizationRolesPath = "organizations/org_01EHQMYV6MBK39QC5PZXHY59C3/roles";

        $result = $this->organizationRolesResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $organizationRolesPath,
            null,
            null,
            true,
            $result
        );

        $role = $this->roleFixture();

        list($roles) = $this->organizations->listOrganizationRoles("org_01EHQMYV6MBK39QC5PZXHY59C3");
        $this->assertSame($role, $roles[0]->toArray());
    }

    public function testListOrganizationFeatureFlags()
    {
        $featureFlagsPath = "organizations/org_01EHQMYV6MBK39QC5PZXHY59C3/feature-flags";

        $result = $this->featureFlagsResponseFixture();

        $params = [
            "limit" => 10,
            "before" => null,
            "after" => null,
            "order" => null
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $featureFlagsPath,
            null,
            $params,
            true,
            $result
        );

        $featureFlag = $this->featureFlagFixture();

        list($before, $after, $featureFlags) = $this->organizations->listOrganizationFeatureFlags("org_01EHQMYV6MBK39QC5PZXHY59C3");
        $this->assertSame($featureFlag, $featureFlags[0]->toArray());
    }

    public function testListOrganizationFeatureFlagsPaginatedResourceAccessPatterns()
    {
        $featureFlagsPath = "organizations/org_01EHQMYV6MBK39QC5PZXHY59C3/feature-flags";
        $result = $this->featureFlagsResponseFixture();
        $params = [
            "limit" => 10,
            "before" => null,
            "after" => null,
            "order" => null
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $featureFlagsPath,
            null,
            $params,
            true,
            $result
        );

        // Test 1: Bare destructuring (indexed)
        [$before1, $after1, $flags1] = $this->organizations->listOrganizationFeatureFlags("org_01EHQMYV6MBK39QC5PZXHY59C3");
        $this->assertSame("", $before1);
        $this->assertSame("", $after1);
        $this->assertIsArray($flags1);
        $this->assertCount(3, $flags1);

        // Mock the request again for the next test
        $this->mockRequest(
            Client::METHOD_GET,
            $featureFlagsPath,
            null,
            $params,
            true,
            $result
        );

        // Test 2: Named destructuring
        ["before" => $before2, "after" => $after2, "feature_flags" => $flags2] = $this->organizations->listOrganizationFeatureFlags("org_01EHQMYV6MBK39QC5PZXHY59C3");
        $this->assertSame("", $before2);
        $this->assertSame("", $after2);
        $this->assertIsArray($flags2);
        $this->assertCount(3, $flags2);

        // Mock the request again for the next test
        $this->mockRequest(
            Client::METHOD_GET,
            $featureFlagsPath,
            null,
            $params,
            true,
            $result
        );

        // Test 3: Fluent access
        $response = $this->organizations->listOrganizationFeatureFlags("org_01EHQMYV6MBK39QC5PZXHY59C3");
        $this->assertSame("", $response->before);
        $this->assertSame("", $response->after);
        $this->assertIsArray($response->feature_flags);
        $this->assertCount(3, $response->feature_flags);

        // Test 4: Generic data accessor
        $this->assertIsArray($response->data);
        $this->assertSame($response->feature_flags, $response->data);
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
            ],
            "external_id" => null,
            "metadata" => []
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
                ],
                "external_id" => null,
                "metadata" => []
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
                ],
                "external_id" => null,
                "metadata" => []
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
                ],
                "external_id" => null,
                "metadata" => []
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
            ],
            "externalId" => null,
            "metadata" => []
        ];
    }

    private function organizationRolesResponseFixture()
    {
        return json_encode([
            "object" => "list",
            "data" => [
                [
                    "object" => "role",
                    "id" => "role_01EHQMYV6MBK39QC5PZXHY59C2",
                    "name" => "Admin",
                    "slug" => "admin",
                    "description" => "Admin role",
                    "permissions" => ["posts:read", "posts:write"],
                    "type" => "EnvironmentRole",
                    "created_at" => "2024-01-01T00:00:00.000Z",
                    "updated_at" => "2024-01-01T00:00:00.000Z"
                ],
                [
                    "object" => "role",
                    "id" => "role_01EHQMYV6MBK39QC5PZXHY59C3",
                    "name" => "Member",
                    "slug" => "member",
                    "description" => "Member role",
                    "permissions" => [],
                    "type" => "EnvironmentRole",
                    "created_at" => "2024-01-01T00:00:00.000Z",
                    "updated_at" => "2024-01-01T00:00:00.000Z"
                ],
                [
                    "object" => "role",
                    "id" => "role_01EHQMYV6MBK39QC5PZXHY59C4",
                    "name" => "Org. Member",
                    "slug" => "org-member",
                    "description" => "Organization member role",
                    "permissions" => ["posts:read"],
                    "type" => "OrganizationRole",
                    "created_at" => "2024-01-01T00:00:00.000Z",
                    "updated_at" => "2024-01-01T00:00:00.000Z"
                ],
            ],
        ]);
    }

    private function roleFixture()
    {
        return [
            "id" => "role_01EHQMYV6MBK39QC5PZXHY59C2",
            "name" => "Admin",
            "slug" => "admin",
            "description" => "Admin role",
            "permissions" => ["posts:read", "posts:write"],
            "type" => "EnvironmentRole",
            "created_at" => "2024-01-01T00:00:00.000Z",
            "updated_at" => "2024-01-01T00:00:00.000Z"
        ];
    }

    private function featureFlagFixture()
    {
        return [
            "id" => "flag_01K2QR5YSWRB8J7GGAG05Y24HQ",
            "slug" => "flag3",
            "name" => "Flag3",
            "description" => "",
            "createdAt" => "2025-08-15T20:54:13.561Z",
            "updatedAt" => "2025-08-15T20:54:13.561Z"
        ];
    }

    private function featureFlagsResponseFixture()
    {
        return json_encode([
            "object" => "list",
            "data" => [
                [
                    "object" => "feature_flag",
                    "id" => "flag_01K2QR5YSWRB8J7GGAG05Y24HQ",
                    "slug" => "flag3",
                    "name" => "Flag3",
                    "description" => "",
                    "created_at" => "2025-08-15T20:54:13.561Z",
                    "updated_at" => "2025-08-15T20:54:13.561Z"
                ],
                [
                    "object" => "feature_flag",
                    "id" => "flag_01K2QR5HGK2HQVFDZ4T60GWGVD",
                    "slug" => "flag2",
                    "name" => "Flag2",
                    "description" => "",
                    "created_at" => "2025-08-15T20:53:59.952Z",
                    "updated_at" => "2025-08-15T20:53:59.952Z"
                ],
                [
                    "object" => "feature_flag",
                    "id" => "flag_01K2QKSH38RF4P9FV917PE24R3",
                    "slug" => "flag1",
                    "name" => "Flag1",
                    "description" => "",
                    "created_at" => "2025-08-15T19:37:32.005Z",
                    "updated_at" => "2025-08-15T19:37:32.005Z"
                ],
            ],
            "list_metadata" => [
                "before" => "",
                "after" => ""
            ]
        ]);
    }
}
