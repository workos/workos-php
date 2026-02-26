---
name: generate-sdk-tests
description: Generate PHPUnit test files for the WorkOS PHP SDK
arguments:
  - name: spec_path
    description: Path to the YAML OpenAPI specification file
    required: true
---

# /generate-sdk-tests

Generate PHPUnit test files for the WorkOS PHP SDK.

> **Design Reference**: Follow all patterns in [SDK_DESIGN.md](../../SDK_DESIGN.md)

## Input

The user will provide a path to a YAML OpenAPI specification file.

## Instructions

1. **Read the OpenAPI spec** at the provided path
2. **Identify all resources** and their operations
3. **Extract example data** from schema `example` or `examples` fields
4. **For each resource**, generate:
   - A test file in `tests/Service/`
   - Fixture files in `tests/fixtures/` derived from OpenAPI examples

## Quick Reference

### PHPUnit Assertions

```php
// Type assertion
$this->assertInstanceOf(Organization::class, $result);

// Collection assertion
$this->assertInstanceOf(Collection::class, $result);
$this->assertContainsOnlyInstancesOf(Organization::class, $result->data);

// Property assertion
$this->assertEquals('org_123', $org->id);

// Exception assertion
$this->expectException(NotFoundException::class);
```

### HTTP Mocking

The test base class should provide HTTP mocking methods:

```php
// Single response
$this->mockResponse(200, $this->loadFixture('organizations/list.json'));

// Response with headers
$this->mockResponse(429, '', ['Retry-After' => '60']);

// Sequence of responses (for retry testing)
$this->mockResponseSequence([
    [429, ''],
    [200, $this->loadFixture('organizations/list.json')],
]);
```

## Test Categories

Each test file should include:

1. **CRUD operation tests** - all, retrieve, create, update, delete
2. **Error tests** - 401, 404, 422, 429, 500
3. **Retry tests** - retry on 429/5xx, no retry on 4xx
4. **Idempotency tests** - key sent in header, auto-generated, reused on retry

## Output Template

### Test File (`tests/Service/{Name}ServiceTest.php`)

```php
<?php

namespace WorkOS\Tests\Service;

use WorkOS\Tests\TestCase;
use WorkOS\Resource\Organization;
use WorkOS\Collection;
use WorkOS\Exception\NotFoundException;
use WorkOS\Exception\AuthenticationException;
use WorkOS\Exception\ServerException;
use WorkOS\WorkOSClient;

class OrganizationServiceTest extends TestCase
{
    // === CRUD Tests ===

    public function testAll(): void
    {
        $this->mockResponse(200, $this->loadFixture('organizations/list.json'));

        $result = $this->client->organizations->all();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertIsArray($result->data);
    }

    public function testAllWithParams(): void
    {
        $this->mockResponse(200, $this->loadFixture('organizations/list.json'));

        $result = $this->client->organizations->all([
            'limit' => 10,
            'after' => 'cursor_abc',
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertRequestWasMade('GET', '/organizations', [
            'limit' => '10',
            'after' => 'cursor_abc',
        ]);
    }

    public function testRetrieve(): void
    {
        $this->mockResponse(200, $this->loadFixture('organizations/retrieve.json'));

        $org = $this->client->organizations->retrieve('org_123');

        $this->assertInstanceOf(Organization::class, $org);
        $this->assertEquals('org_123', $org->id);
    }

    public function testCreate(): void
    {
        $this->mockResponse(201, $this->loadFixture('organizations/create.json'));

        $org = $this->client->organizations->create(['name' => 'Test Org']);

        $this->assertInstanceOf(Organization::class, $org);
    }

    public function testUpdate(): void
    {
        $this->mockResponse(200, $this->loadFixture('organizations/update.json'));

        $org = $this->client->organizations->update('org_123', ['name' => 'Updated']);

        $this->assertInstanceOf(Organization::class, $org);
    }

    public function testDelete(): void
    {
        $this->mockResponse(204, '');

        $this->client->organizations->delete('org_123');

        $this->assertTrue(true); // No exception means success
    }

    // === Error Tests ===

    public function testNotFound(): void
    {
        $this->mockResponse(404, json_encode(['message' => 'Not found']));

        $this->expectException(NotFoundException::class);

        $this->client->organizations->retrieve('invalid');
    }

    public function testAuthenticationError(): void
    {
        $this->mockResponse(401, json_encode(['message' => 'Unauthorized']));

        $this->expectException(AuthenticationException::class);

        $this->client->organizations->all();
    }

    public function testServerError(): void
    {
        $this->mockResponse(500, json_encode(['message' => 'Internal error']));

        $this->expectException(ServerException::class);

        $this->client->organizations->all();
    }

    // === Retry Tests ===

    public function testRetryOnRateLimit(): void
    {
        $this->mockResponseSequence([
            [429, '', ['Retry-After' => '1']],
            [200, $this->loadFixture('organizations/list.json')],
        ]);

        $client = new WorkOSClient([
            'api_key' => 'sk_test_xxx',
            'max_network_retries' => 2,
        ]);
        $result = $client->organizations->all();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertRequestCount(2);
    }

    public function testNoRetryOn404(): void
    {
        $this->mockResponse(404, json_encode(['message' => 'Not found']));

        $client = new WorkOSClient([
            'api_key' => 'sk_test_xxx',
            'max_network_retries' => 2,
        ]);

        try {
            $client->organizations->retrieve('invalid');
        } catch (NotFoundException $e) {
            // Expected
        }

        $this->assertRequestCount(1);
    }

    // === Idempotency Tests ===

    public function testIdempotencyKeySent(): void
    {
        $this->mockResponse(201, $this->loadFixture('organizations/create.json'));

        $this->client->organizations->create(
            ['name' => 'Test'],
            ['idempotency_key' => 'my_key']
        );

        $this->assertRequestHasHeader('Idempotency-Key', 'my_key');
    }

    public function testIdempotencyKeyAutoGenerated(): void
    {
        $this->mockResponse(201, $this->loadFixture('organizations/create.json'));

        $this->client->organizations->create(['name' => 'Test']);

        $key = $this->getLastRequestHeader('Idempotency-Key');
        $this->assertNotNull($key);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/i', $key);
    }

    public function testIdempotencyKeyReusedOnRetry(): void
    {
        $this->mockResponseSequence([
            [500, ''],
            [201, $this->loadFixture('organizations/create.json')],
        ]);

        $client = new WorkOSClient([
            'api_key' => 'sk_test_xxx',
            'max_network_retries' => 1,
        ]);
        $client->organizations->create(['name' => 'Test']);

        $requests = $this->getRequestHistory();
        $this->assertEquals(
            $requests[0]['headers']['Idempotency-Key'] ?? null,
            $requests[1]['headers']['Idempotency-Key'] ?? null
        );
    }

    // === Response Metadata Tests ===

    public function testLastResponseAvailable(): void
    {
        $this->mockResponse(
            200,
            $this->loadFixture('organizations/retrieve.json'),
            ['x-request-id' => 'req_abc123']
        );

        $org = $this->client->organizations->retrieve('org_123');

        $this->assertNotNull($org->getLastResponse());
        $this->assertEquals(200, $org->getLastResponse()->status);
        $this->assertEquals('req_abc123', $org->getLastResponse()->requestId);
    }
}
```

### Fixtures from OpenAPI Examples

Extract fixture data directly from the OpenAPI spec's `example` fields:

```yaml
# OpenAPI spec input
components:
  schemas:
    Organization:
      type: object
      properties:
        id:
          type: string
          example: "org_01FCPEJXEZR4DSBA625YMGQT9N"
        name:
          type: string
          example: "Acme Corp"
        state:
          type: string
          enum: [active, inactive]
          example: "active"
        created_at:
          type: string
          format: date-time
          example: "2024-01-01T00:00:00Z"
```

↓ generates ↓

```json
// tests/fixtures/organizations/retrieve.json
{
  "id": "org_01FCPEJXEZR4DSBA625YMGQT9N",
  "name": "Acme Corp",
  "state": "active",
  "created_at": "2024-01-01T00:00:00Z"
}
```

For list endpoints, wrap in pagination structure:

```json
// tests/fixtures/organizations/list.json
{
  "data": [
    {
      "id": "org_01FCPEJXEZR4DSBA625YMGQT9N",
      "name": "Acme Corp",
      "state": "active",
      "created_at": "2024-01-01T00:00:00Z"
    }
  ],
  "list_metadata": {
    "after": null,
    "before": null
  }
}
```

If a field lacks an `example`, generate a sensible default based on type:
- `string` → `"string"`
- `string` + `format: uuid` → `"00000000-0000-0000-0000-000000000000"`
- `string` + `format: date-time` → `"2024-01-01T00:00:00Z"`
- `integer` → `0`
- `boolean` → `true`
- `array` → `[]`

## Output

For each resource:
1. Create `tests/Service/{PascalCaseName}ServiceTest.php`
2. Create fixture files in `tests/fixtures/{snake_case_name}/` derived from OpenAPI examples
