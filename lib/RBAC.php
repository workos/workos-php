<?php

namespace WorkOS;

class RBAC
{
    public const DEFAULT_PAGE_SIZE = 10;

    /**
     * Create a Permission.
     *
     * @param string $slug The slug of the Permission
     * @param string $name The name of the Permission
     * @param null|string $description The description of the Permission
     * @param null|string $resourceTypeSlug The resource type slug of the Permission
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Permission
     */
    public function createPermission(
        string $slug,
        string $name,
        ?string $description = null,
        ?string $resourceTypeSlug = null
    ) {
        $path = "authorization/permissions";

        $params = [
            "slug" => $slug,
            "name" => $name,
        ];

        if (isset($description)) {
            $params["description"] = $description;
        }
        if (isset($resourceTypeSlug)) {
            $params["resource_type_slug"] = $resourceTypeSlug;
        }

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

        return Resource\Permission::constructFromResponse($response);
    }

    /**
     * List Permissions.
     *
     * @param int $limit Maximum number of records to return
     * @param null|string $before Permission ID to look before
     * @param null|string $after Permission ID to look after
     * @param null|string $order The order in which to paginate records
     *
     * @throws Exception\WorkOSException
     *
     * @return array{?string, ?string, Resource\Permission[]}
     */
    public function listPermissions(
        int $limit = self::DEFAULT_PAGE_SIZE,
        ?string $before = null,
        ?string $after = null,
        ?string $order = null
    ) {
        $path = "authorization/permissions";

        $params = [
            "limit" => $limit,
            "before" => $before,
            "after" => $after,
            "order" => $order,
        ];

        $response = Client::request(Client::METHOD_GET, $path, null, $params, true);

        $permissions = [];
        list($before, $after) = Util\Request::parsePaginationArgs($response);
        foreach ($response["data"] as $responseData) {
            \array_push($permissions, Resource\Permission::constructFromResponse($responseData));
        }

        return [$before, $after, $permissions];
    }

    /**
     * Get a Permission.
     *
     * @param string $slug The slug of the Permission
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Permission
     */
    public function getPermission(string $slug)
    {
        $path = "authorization/permissions/{$slug}";

        $response = Client::request(Client::METHOD_GET, $path, null, null, true);

        return Resource\Permission::constructFromResponse($response);
    }

    /**
     * Update a Permission.
     *
     * @param string $slug The slug of the Permission to update
     * @param null|string $name The updated name of the Permission
     * @param null|string $description The updated description of the Permission
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Permission
     */
    public function updatePermission(
        string $slug,
        ?string $name = null,
        ?string $description = null
    ) {
        $path = "authorization/permissions/{$slug}";

        $params = [];

        if (isset($name)) {
            $params["name"] = $name;
        }
        if (isset($description)) {
            $params["description"] = $description;
        }

        $response = Client::request(Client::METHOD_PATCH, $path, null, $params, true);

        return Resource\Permission::constructFromResponse($response);
    }

    /**
     * Delete a Permission.
     *
     * @param string $slug The slug of the Permission to delete
     *
     * @throws Exception\WorkOSException
     *
     * @return array
     */
    public function deletePermission(string $slug)
    {
        $path = "authorization/permissions/{$slug}";

        $response = Client::request(Client::METHOD_DELETE, $path, null, null, true);

        return $response;
    }

    /**
     * Create an Environment Role.
     *
     * @param string $slug The slug of the Role
     * @param string $name The name of the Role
     * @param null|string $description The description of the Role
     * @param null|string $resourceTypeSlug The resource type slug of the Role
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role
     */
    public function createEnvironmentRole(
        $slug,
        $name,
        ?string $description = null,
        ?string $resourceTypeSlug = null
    ) {
        $path = "authorization/roles";

        $params = [
            "slug" => $slug,
            "name" => $name,
        ];

        if (isset($description)) {
            $params["description"] = $description;
        }
        if (isset($resourceTypeSlug)) {
            $params["resource_type_slug"] = $resourceTypeSlug;
        }

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

        return Resource\Role::constructFromResponse($response);
    }

    /**
     * List Environment Roles.
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role[]
     */
    public function listEnvironmentRoles()
    {
        $path = "authorization/roles";

        $response = Client::request(Client::METHOD_GET, $path, null, null, true);

        $roles = [];
        foreach ($response["data"] as $responseData) {
            \array_push($roles, Resource\Role::constructFromResponse($responseData));
        }

        return $roles;
    }

    /**
     * Get an Environment Role.
     *
     * @param string $slug The slug of the Role
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role
     */
    public function getEnvironmentRole($slug)
    {
        $path = "authorization/roles/{$slug}";

        $response = Client::request(Client::METHOD_GET, $path, null, null, true);

        return Resource\Role::constructFromResponse($response);
    }

    /**
     * Update an Environment Role.
     *
     * @param string $slug The slug of the Role to update
     * @param null|string $name The updated name of the Role
     * @param null|string $description The updated description of the Role
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role
     */
    public function updateEnvironmentRole(
        $slug,
        ?string $name = null,
        ?string $description = null
    ) {
        $path = "authorization/roles/{$slug}";

        $params = [];

        if (isset($name)) {
            $params["name"] = $name;
        }
        if (isset($description)) {
            $params["description"] = $description;
        }

        $response = Client::request(Client::METHOD_PATCH, $path, null, $params, true);

        return Resource\Role::constructFromResponse($response);
    }

    /**
     * Set permissions for an Environment Role.
     *
     * @param string $slug The slug of the Role
     * @param array<string> $permissions The permission slugs to set on the Role
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role
     */
    public function setEnvironmentRolePermissions($slug, array $permissions)
    {
        $path = "authorization/roles/{$slug}/permissions";

        $params = [
            "permissions" => $permissions,
        ];

        $response = Client::request(Client::METHOD_PUT, $path, null, $params, true);

        return Resource\Role::constructFromResponse($response);
    }

    /**
     * Add a permission to an Environment Role.
     *
     * @param string $roleSlug The slug of the Role
     * @param string $permissionSlug The slug of the Permission to add
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role
     */
    public function addEnvironmentRolePermission($roleSlug, $permissionSlug)
    {
        $path = "authorization/roles/{$roleSlug}/permissions";

        $params = [
            "slug" => $permissionSlug,
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

        return Resource\Role::constructFromResponse($response);
    }

    /**
     * Create an Organization Role.
     *
     * @param string $organizationId WorkOS Organization ID
     * @param string $slug The slug of the Role
     * @param string $name The name of the Role
     * @param null|string $description The description of the Role
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role
     */
    public function createOrganizationRole(
        $organizationId,
        $slug,
        $name,
        ?string $description = null
    ) {
        $path = "authorization/organizations/{$organizationId}/roles";

        $params = [
            "slug" => $slug,
            "name" => $name,
        ];

        if (isset($description)) {
            $params["description"] = $description;
        }

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

        return Resource\Role::constructFromResponse($response);
    }

    /**
     * List Organization Roles.
     *
     * @param string $organizationId WorkOS Organization ID
     *
     * @throws Exception\WorkOSException
     *
     * @return array{0: Resource\Role[]}
     */
    public function listOrganizationRoles($organizationId)
    {
        $path = "authorization/organizations/{$organizationId}/roles";

        $response = Client::request(Client::METHOD_GET, $path, null, null, true);

        $roles = [];
        foreach ($response["data"] as $responseData) {
            \array_push($roles, Resource\Role::constructFromResponse($responseData));
        }

        return [$roles];
    }

    /**
     * Get an Organization Role.
     *
     * @param string $organizationId WorkOS Organization ID
     * @param string $slug The slug of the Role
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role
     */
    public function getOrganizationRole($organizationId, $slug)
    {
        $path = "authorization/organizations/{$organizationId}/roles/{$slug}";

        $response = Client::request(Client::METHOD_GET, $path, null, null, true);

        return Resource\Role::constructFromResponse($response);
    }

    /**
     * Update an Organization Role.
     *
     * @param string $organizationId WorkOS Organization ID
     * @param string $slug The slug of the Role to update
     * @param null|string $name The updated name of the Role
     * @param null|string $description The updated description of the Role
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role
     */
    public function updateOrganizationRole(
        $organizationId,
        $slug,
        ?string $name = null,
        ?string $description = null
    ) {
        $path = "authorization/organizations/{$organizationId}/roles/{$slug}";

        $params = [];

        if (isset($name)) {
            $params["name"] = $name;
        }
        if (isset($description)) {
            $params["description"] = $description;
        }

        $response = Client::request(Client::METHOD_PATCH, $path, null, $params, true);

        return Resource\Role::constructFromResponse($response);
    }

    /**
     * Set permissions for an Organization Role.
     *
     * @param string $organizationId WorkOS Organization ID
     * @param string $slug The slug of the Role
     * @param array<string> $permissions The permission slugs to set on the Role
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role
     */
    public function setOrganizationRolePermissions($organizationId, $slug, array $permissions)
    {
        $path = "authorization/organizations/{$organizationId}/roles/{$slug}/permissions";

        $params = [
            "permissions" => $permissions,
        ];

        $response = Client::request(Client::METHOD_PUT, $path, null, $params, true);

        return Resource\Role::constructFromResponse($response);
    }

    /**
     * Add a permission to an Organization Role.
     *
     * @param string $organizationId WorkOS Organization ID
     * @param string $roleSlug The slug of the Role
     * @param string $permissionSlug The slug of the Permission to add
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\Role
     */
    public function addOrganizationRolePermission($organizationId, $roleSlug, $permissionSlug)
    {
        $path = "authorization/organizations/{$organizationId}/roles/{$roleSlug}/permissions";

        $params = [
            "slug" => $permissionSlug,
        ];

        $response = Client::request(Client::METHOD_POST, $path, null, $params, true);

        return Resource\Role::constructFromResponse($response);
    }

    /**
     * Remove a permission from an Organization Role.
     *
     * @param string $organizationId WorkOS Organization ID
     * @param string $roleSlug The slug of the Role
     * @param string $permissionSlug The slug of the Permission to remove
     *
     * @throws Exception\WorkOSException
     *
     * @return array
     */
    public function removeOrganizationRolePermission($organizationId, $roleSlug, $permissionSlug)
    {
        $path = "authorization/organizations/{$organizationId}/roles/{$roleSlug}/permissions/{$permissionSlug}";

        $response = Client::request(Client::METHOD_DELETE, $path, null, null, true);

        return $response;
    }
}
