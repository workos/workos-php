<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class RBACTest extends TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }

    protected $rbac;

    protected function setUp(): void
    {
        $this->traitSetUp();

        $this->withApiKey();
        $this->rbac = new RBAC();
    }

    public function testCreatePermission()
    {
        $path = "authorization/permissions";

        $result = $this->permissionResponseFixture();

        $params = [
            "slug" => "posts:read",
            "name" => "Read Posts",
            "description" => "Allows reading posts",
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $permission = $this->permissionFixture();

        $response = $this->rbac->createPermission("posts:read", "Read Posts", "Allows reading posts");
        $this->assertSame($permission, $response->toArray());
    }

    public function testListPermissions()
    {
        $path = "authorization/permissions";

        $result = $this->permissionsListResponseFixture();

        $params = [
            "limit" => RBAC::DEFAULT_PAGE_SIZE,
            "before" => null,
            "after" => null,
            "order" => null,
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true,
            $result
        );

        $permission = $this->permissionFixture();

        $response = $this->rbac->listPermissions();
        $this->assertSame($permission, $response->permissions[0]->toArray());
    }

    public function testGetPermission()
    {
        $path = "authorization/permissions/posts:read";

        $result = $this->permissionResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $permission = $this->permissionFixture();

        $response = $this->rbac->getPermission("posts:read");
        $this->assertSame($permission, $response->toArray());
    }

    public function testUpdatePermission()
    {
        $path = "authorization/permissions/posts:read";

        $result = $this->permissionResponseFixture();

        $params = [
            "name" => "Read Posts Updated",
        ];

        $this->mockRequest(
            Client::METHOD_PATCH,
            $path,
            null,
            $params,
            true,
            $result
        );

        $permission = $this->permissionFixture();

        $response = $this->rbac->updatePermission("posts:read", "Read Posts Updated");
        $this->assertSame($permission, $response->toArray());
    }

    public function testDeletePermission()
    {
        $path = "authorization/permissions/posts:read";

        $this->mockRequest(
            Client::METHOD_DELETE,
            $path,
            null,
            null,
            true
        );

        $response = $this->rbac->deletePermission("posts:read");
        $this->assertSame([], $response);
    }

    public function testCreateEnvironmentRole()
    {
        $path = "authorization/roles";

        $result = $this->roleResponseFixture();

        $params = [
            "slug" => "admin",
            "name" => "Admin",
            "description" => "Admin role",
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $role = $this->roleFixture();

        $response = $this->rbac->createEnvironmentRole("admin", "Admin", "Admin role");
        $this->assertSame($role, $response->toArray());
    }

    public function testListEnvironmentRoles()
    {
        $path = "authorization/roles";

        $result = $this->rolesListResponseFixture();

        $params = [
            "limit" => RBAC::DEFAULT_PAGE_SIZE,
            "before" => null,
            "after" => null,
            "order" => null,
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true,
            $result
        );

        $role = $this->roleFixture();

        $response = $this->rbac->listEnvironmentRoles();
        $this->assertSame($role, $response->roles[0]->toArray());
    }

    public function testGetEnvironmentRole()
    {
        $path = "authorization/roles/admin";

        $result = $this->roleResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $role = $this->roleFixture();

        $response = $this->rbac->getEnvironmentRole("admin");
        $this->assertSame($role, $response->toArray());
    }

    public function testUpdateEnvironmentRole()
    {
        $path = "authorization/roles/admin";

        $result = $this->roleResponseFixture();

        $params = [
            "name" => "Admin Updated",
        ];

        $this->mockRequest(
            Client::METHOD_PATCH,
            $path,
            null,
            $params,
            true,
            $result
        );

        $role = $this->roleFixture();

        $response = $this->rbac->updateEnvironmentRole("admin", "Admin Updated");
        $this->assertSame($role, $response->toArray());
    }

    public function testSetEnvironmentRolePermissions()
    {
        $path = "authorization/roles/admin/permissions";

        $result = $this->roleResponseFixture();

        $params = [
            "permissions" => ["posts:read", "posts:write"],
        ];

        $this->mockRequest(
            Client::METHOD_PUT,
            $path,
            null,
            $params,
            true,
            $result
        );

        $role = $this->roleFixture();

        $response = $this->rbac->setEnvironmentRolePermissions("admin", ["posts:read", "posts:write"]);
        $this->assertSame($role, $response->toArray());
    }

    public function testAddEnvironmentRolePermission()
    {
        $path = "authorization/roles/admin/permissions";

        $result = $this->roleResponseFixture();

        $params = [
            "slug" => "posts:read",
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $role = $this->roleFixture();

        $response = $this->rbac->addEnvironmentRolePermission("admin", "posts:read");
        $this->assertSame($role, $response->toArray());
    }

    public function testCreateOrganizationRole()
    {
        $path = "authorization/organizations/org_01EHQMYV6MBK39QC5PZXHY59C3/roles";

        $result = $this->organizationRoleResponseFixture();

        $params = [
            "slug" => "org-admin",
            "name" => "Org Admin",
            "description" => "Organization admin role",
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $role = $this->organizationRoleFixture();

        $response = $this->rbac->createOrganizationRole("org_01EHQMYV6MBK39QC5PZXHY59C3", "org-admin", "Org Admin", "Organization admin role");
        $this->assertSame($role, $response->toArray());
    }

    public function testListOrganizationRoles()
    {
        $path = "authorization/organizations/org_01EHQMYV6MBK39QC5PZXHY59C3/roles";

        $result = $this->organizationRolesListResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $role = $this->organizationRoleFixture();

        $roles = $this->rbac->listOrganizationRoles("org_01EHQMYV6MBK39QC5PZXHY59C3");
        $this->assertSame($role, $roles[0]->toArray());
    }

    public function testGetOrganizationRole()
    {
        $path = "authorization/organizations/org_01EHQMYV6MBK39QC5PZXHY59C3/roles/org-admin";

        $result = $this->organizationRoleResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $role = $this->organizationRoleFixture();

        $response = $this->rbac->getOrganizationRole("org_01EHQMYV6MBK39QC5PZXHY59C3", "org-admin");
        $this->assertSame($role, $response->toArray());
    }

    public function testUpdateOrganizationRole()
    {
        $path = "authorization/organizations/org_01EHQMYV6MBK39QC5PZXHY59C3/roles/org-admin";

        $result = $this->organizationRoleResponseFixture();

        $params = [
            "name" => "Org Admin Updated",
        ];

        $this->mockRequest(
            Client::METHOD_PATCH,
            $path,
            null,
            $params,
            true,
            $result
        );

        $role = $this->organizationRoleFixture();

        $response = $this->rbac->updateOrganizationRole("org_01EHQMYV6MBK39QC5PZXHY59C3", "org-admin", "Org Admin Updated");
        $this->assertSame($role, $response->toArray());
    }

    public function testSetOrganizationRolePermissions()
    {
        $path = "authorization/organizations/org_01EHQMYV6MBK39QC5PZXHY59C3/roles/org-admin/permissions";

        $result = $this->organizationRoleResponseFixture();

        $params = [
            "permissions" => ["posts:read", "posts:write"],
        ];

        $this->mockRequest(
            Client::METHOD_PUT,
            $path,
            null,
            $params,
            true,
            $result
        );

        $role = $this->organizationRoleFixture();

        $response = $this->rbac->setOrganizationRolePermissions("org_01EHQMYV6MBK39QC5PZXHY59C3", "org-admin", ["posts:read", "posts:write"]);
        $this->assertSame($role, $response->toArray());
    }

    public function testAddOrganizationRolePermission()
    {
        $path = "authorization/organizations/org_01EHQMYV6MBK39QC5PZXHY59C3/roles/org-admin/permissions";

        $result = $this->organizationRoleResponseFixture();

        $params = [
            "slug" => "posts:read",
        ];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $role = $this->organizationRoleFixture();

        $response = $this->rbac->addOrganizationRolePermission("org_01EHQMYV6MBK39QC5PZXHY59C3", "org-admin", "posts:read");
        $this->assertSame($role, $response->toArray());
    }

    public function testRemoveOrganizationRolePermission()
    {
        $path = "authorization/organizations/org_01EHQMYV6MBK39QC5PZXHY59C3/roles/org-admin/permissions/posts:read";

        $this->mockRequest(
            Client::METHOD_DELETE,
            $path,
            null,
            null,
            true
        );

        $response = $this->rbac->removeOrganizationRolePermission("org_01EHQMYV6MBK39QC5PZXHY59C3", "org-admin", "posts:read");
        $this->assertSame([], $response);
    }

    // Fixtures

    private function permissionResponseFixture()
    {
        return json_encode([
            "object" => "permission",
            "id" => "perm_01EHQMYV6MBK39QC5PZXHY59C3",
            "slug" => "posts:read",
            "name" => "Read Posts",
            "description" => "Allows reading posts",
            "resource_type_slug" => "organization",
            "system" => false,
            "created_at" => "2024-01-01T00:00:00.000Z",
            "updated_at" => "2024-01-01T00:00:00.000Z"
        ]);
    }

    private function permissionsListResponseFixture()
    {
        return json_encode([
            "object" => "list",
            "data" => [
                [
                    "object" => "permission",
                    "id" => "perm_01EHQMYV6MBK39QC5PZXHY59C3",
                    "slug" => "posts:read",
                    "name" => "Read Posts",
                    "description" => "Allows reading posts",
                    "resource_type_slug" => "organization",
                    "system" => false,
                    "created_at" => "2024-01-01T00:00:00.000Z",
                    "updated_at" => "2024-01-01T00:00:00.000Z"
                ]
            ],
            "list_metadata" => [
                "before" => null,
                "after" => null
            ]
        ]);
    }

    private function permissionFixture()
    {
        return [
            "id" => "perm_01EHQMYV6MBK39QC5PZXHY59C3",
            "slug" => "posts:read",
            "name" => "Read Posts",
            "description" => "Allows reading posts",
            "resource_type_slug" => "organization",
            "system" => false,
            "created_at" => "2024-01-01T00:00:00.000Z",
            "updated_at" => "2024-01-01T00:00:00.000Z"
        ];
    }

    private function roleResponseFixture()
    {
        return json_encode([
            "object" => "role",
            "id" => "role_01EHQMYV6MBK39QC5PZXHY59C2",
            "name" => "Admin",
            "slug" => "admin",
            "description" => "Admin role",
            "permissions" => ["posts:read", "posts:write"],
            "resource_type_slug" => "organization",
            "type" => "EnvironmentRole",
            "created_at" => "2024-01-01T00:00:00.000Z",
            "updated_at" => "2024-01-01T00:00:00.000Z"
        ]);
    }

    private function rolesListResponseFixture()
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
                    "resource_type_slug" => "organization",
                    "type" => "EnvironmentRole",
                    "created_at" => "2024-01-01T00:00:00.000Z",
                    "updated_at" => "2024-01-01T00:00:00.000Z"
                ]
            ],
            "list_metadata" => [
                "before" => null,
                "after" => null
            ]
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
            "resource_type_slug" => "organization",
            "type" => "EnvironmentRole",
            "created_at" => "2024-01-01T00:00:00.000Z",
            "updated_at" => "2024-01-01T00:00:00.000Z"
        ];
    }

    private function organizationRoleResponseFixture()
    {
        return json_encode([
            "object" => "role",
            "id" => "role_01EHQMYV6MBK39QC5PZXHY59C5",
            "name" => "Org Admin",
            "slug" => "org-admin",
            "description" => "Organization admin role",
            "permissions" => ["posts:read", "posts:write"],
            "resource_type_slug" => "organization",
            "type" => "OrganizationRole",
            "created_at" => "2024-01-01T00:00:00.000Z",
            "updated_at" => "2024-01-01T00:00:00.000Z"
        ]);
    }

    private function organizationRolesListResponseFixture()
    {
        return json_encode([
            "object" => "list",
            "data" => [
                [
                    "object" => "role",
                    "id" => "role_01EHQMYV6MBK39QC5PZXHY59C5",
                    "name" => "Org Admin",
                    "slug" => "org-admin",
                    "description" => "Organization admin role",
                    "permissions" => ["posts:read", "posts:write"],
                    "resource_type_slug" => "organization",
                    "type" => "OrganizationRole",
                    "created_at" => "2024-01-01T00:00:00.000Z",
                    "updated_at" => "2024-01-01T00:00:00.000Z"
                ]
            ]
        ]);
    }

    private function organizationRoleFixture()
    {
        return [
            "id" => "role_01EHQMYV6MBK39QC5PZXHY59C5",
            "name" => "Org Admin",
            "slug" => "org-admin",
            "description" => "Organization admin role",
            "permissions" => ["posts:read", "posts:write"],
            "resource_type_slug" => "organization",
            "type" => "OrganizationRole",
            "created_at" => "2024-01-01T00:00:00.000Z",
            "updated_at" => "2024-01-01T00:00:00.000Z"
        ];
    }
}
