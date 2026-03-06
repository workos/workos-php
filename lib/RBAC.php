<?php

namespace WorkOS;

class RBAC
{
    public const DEFAULT_PAGE_SIZE = 10;

    public function createPermission(
        $slug,
        $name,
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

    public function listPermissions(
        $limit = self::DEFAULT_PAGE_SIZE,
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

    public function getPermission($slug)
    {
        $path = "authorization/permissions/{$slug}";

        $response = Client::request(Client::METHOD_GET, $path, null, null, true);

        return Resource\Permission::constructFromResponse($response);
    }

    public function updatePermission(
        $slug,
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

    public function deletePermission($slug)
    {
        $path = "authorization/permissions/{$slug}";

        $response = Client::request(Client::METHOD_DELETE, $path, null, null, true);

        return $response;
    }
}
