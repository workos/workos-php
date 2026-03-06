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

        list($before, $after, $permissions) = $this->rbac->listPermissions();
        $this->assertSame($permission, $permissions[0]->toArray());
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

    // Fixtures

    private function permissionResponseFixture()
    {
        return json_encode([
            "object" => "permission",
            "id" => "perm_01EHQMYV6MBK39QC5PZXHY59C3",
            "slug" => "posts:read",
            "name" => "Read Posts",
            "description" => "Allows reading posts",
            "resource_type_slug" => null,
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
                    "resource_type_slug" => null,
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
            "resource_type_slug" => null,
            "system" => false,
            "created_at" => "2024-01-01T00:00:00.000Z",
            "updated_at" => "2024-01-01T00:00:00.000Z"
        ];
    }
}
