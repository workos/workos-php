---
name: generate-sdk-resources
description: Generate PHP service classes from OpenAPI paths/operations for the WorkOS PHP SDK
arguments:
  - name: spec_path
    description: Path to the YAML OpenAPI specification file
    required: true
---

# /generate-sdk-resources

Generate PHP service classes from OpenAPI paths/operations for the WorkOS PHP SDK.

> **Design Reference**: Follow all patterns in [SDK_DESIGN.md](../../SDK_DESIGN.md)

## Input

The user will provide a path to a YAML OpenAPI specification file.

## Instructions

1. **Read the OpenAPI spec** at the provided path
2. **Extract all paths** from the `paths` section
3. **Group operations by resource** (e.g., `/organizations/*` → `OrganizationService`)
4. **For each resource**, generate a PHP service class in `lib/Service/`

## Quick Reference

### Method Naming

| HTTP Method | Path Pattern      | PHP Method   |
| ----------- | ----------------- | ------------ |
| GET         | `/resources`      | `all`        |
| GET         | `/resources/{id}` | `retrieve`   |
| POST        | `/resources`      | `create`     |
| PUT/PATCH   | `/resources/{id}` | `update`     |
| DELETE      | `/resources/{id}` | `delete`     |

### Client Request Interface

| Option            | Type             | Description                        |
| ----------------- | ---------------- | ---------------------------------- |
| `method`          | `string`         | `get`, `post`, `put`, `delete`     |
| `path`            | `string`         | URL path, use buildPath() for IDs  |
| `params`          | `array\|null`    | Query or body parameters           |
| `opts`            | `array\|null`    | Request options (idempotency_key)  |

## Output Template

### PHP Service (`lib/Service/{Name}Service.php`)

```php
<?php

namespace WorkOS\Service;

use WorkOS\Collection;
use WorkOS\Resource\Organization;

/**
 * Service for managing WorkOS Organizations.
 */
class OrganizationService extends AbstractService
{
    /**
     * List all organizations.
     *
     * @param array{
     *     limit?: int,
     *     after?: string,
     *     before?: string,
     *     domains?: string[],
     *     order?: string
     * }|null $params Query parameters
     * @param array<string, mixed>|null $opts Request options
     * @return Collection<Organization>
     *
     * @throws \WorkOS\Exception\ApiException if the request fails
     */
    public function all(?array $params = null, ?array $opts = null): Collection
    {
        return $this->requestCollection('get', '/organizations', $params, $opts);
    }

    /**
     * Retrieve an organization by ID.
     *
     * @param string $id Organization ID
     * @param array<string, mixed>|null $opts Request options
     * @return Organization
     *
     * @throws \WorkOS\Exception\ApiException if the request fails
     */
    public function retrieve(string $id, ?array $opts = null): Organization
    {
        $response = $this->request(
            'get',
            $this->buildPath('/organizations/%s', $id),
            null,
            $opts
        );
        return Organization::constructFromResponse($response);
    }

    /**
     * Create a new organization.
     *
     * @param array{
     *     name: string,
     *     domain_data?: array,
     *     external_id?: string,
     *     metadata?: array<string, string>
     * } $params Organization parameters
     * @param array{idempotency_key?: string}|null $opts Request options
     * @return Organization
     *
     * @throws \WorkOS\Exception\ApiException if the request fails
     */
    public function create(array $params, ?array $opts = null): Organization
    {
        $response = $this->request('post', '/organizations', $params, $opts);
        return Organization::constructFromResponse($response);
    }

    /**
     * Update an organization.
     *
     * @param string $id Organization ID
     * @param array{
     *     name?: string,
     *     domain_data?: array,
     *     external_id?: string,
     *     metadata?: array<string, string>
     * } $params Update parameters
     * @param array<string, mixed>|null $opts Request options
     * @return Organization
     *
     * @throws \WorkOS\Exception\ApiException if the request fails
     */
    public function update(string $id, array $params, ?array $opts = null): Organization
    {
        $response = $this->request(
            'put',
            $this->buildPath('/organizations/%s', $id),
            $params,
            $opts
        );
        return Organization::constructFromResponse($response);
    }

    /**
     * Delete an organization.
     *
     * @param string $id Organization ID
     * @param array<string, mixed>|null $opts Request options
     * @return void
     *
     * @throws \WorkOS\Exception\ApiException if the request fails
     */
    public function delete(string $id, ?array $opts = null): void
    {
        $this->request(
            'delete',
            $this->buildPath('/organizations/%s', $id),
            null,
            $opts
        );
    }
}
```

### Path Parameter Interpolation

```php
// Single parameter
$this->buildPath('/organizations/%s', $id)

// Multiple parameters
$this->buildPath('/organizations/%s/members/%s', $organizationId, $memberId)
```

### Nested Resources

For nested resources like `/organizations/{id}/roles`:

```php
/**
 * List roles for an organization.
 *
 * @param string $organizationId Organization ID
 * @param array<string, mixed>|null $params Query parameters
 * @param array<string, mixed>|null $opts Request options
 * @return Collection<Role>
 */
public function allRoles(string $organizationId, ?array $params = null, ?array $opts = null): Collection
{
    return $this->requestCollection(
        'get',
        $this->buildPath('/organizations/%s/roles', $organizationId),
        $params,
        $opts
    );
}
```

## Output

For each resource group:

1. Create `lib/Service/{PascalCaseName}Service.php`
2. Add service property to `WorkOSClient.php`
3. Update `ServiceFactory.php` if using factory pattern
