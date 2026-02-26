# WorkOS PHP SDK Design Philosophy

This document defines the architectural decisions, patterns, and conventions for the WorkOS PHP SDK. All code generation skills reference this document for consistency.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Naming Conventions](#naming-conventions)
3. [Type System](#type-system)
4. [Resource Model Pattern](#resource-model-pattern)
5. [Service Pattern](#service-pattern)
6. [Error Handling](#error-handling)
7. [Retry Logic](#retry-logic)
8. [Global Configuration](#global-configuration)
9. [Documentation Standards](#documentation-standards)
10. [Webhook Verification](#webhook-verification)
11. [Custom HTTP Clients](#custom-http-clients)
12. [Middleware/Hooks System](#middlewarehooks-system)
13. [Request/Response Objects](#requestresponse-objects)
14. [Thread Safety](#thread-safety)
15. [Directory Structure](#directory-structure)
16. [Testing Patterns](#testing-patterns)

---

## Architecture Overview

The SDK follows a service-based architecture similar to Stripe PHP:

```
WorkOS\Client
├── ->organizations      → WorkOS\Service\OrganizationService
├── ->users              → WorkOS\Service\UserService
└── ->{resource}         → WorkOS\Service\{Resource}Service

WorkOS\Resource
├── Organization         → Response model
├── User                 → Response model
└── {Resource}           → BaseResource subclass

WorkOS (class)
├── ::setApiKey()        → Global configuration
├── ::getApiKey()        → Global configuration
└── ::getDefaultClient() → Lazy-loaded default client
```

### Design Principles

1. **Explicit over implicit** - Prefer explicit client instantiation for multi-tenant safety
2. **Convenience without sacrifice** - Global config for simple use cases, explicit clients for complex ones
3. **Type-safe with PHPDoc** - Full PHPDoc annotations for IDE support and static analysis
4. **Idiomatic PHP** - camelCase methods, PSR-4 autoloading, PSR-12 coding style
5. **Retry by default** - Automatic retries with exponential backoff (opt-out)

---

## Naming Conventions

### Class Names

| OpenAPI Name            | PHP Class Name                 | File Name                        |
| ----------------------- | ------------------------------ | -------------------------------- |
| `Organization`          | `Organization`                 | `Organization.php`               |
| `CreateOrganizationDto` | `OrganizationCreateParams`     | `OrganizationCreateParams.php`   |
| `UpdateOrganizationDto` | `OrganizationUpdateParams`     | `OrganizationUpdateParams.php`   |
| `UserProfile`           | `UserProfile`                  | `UserProfile.php`                |
| `SSO`                   | `SSO`                          | `SSO.php`                        |
| `APIKey`                | `ApiKey`                       | `ApiKey.php`                     |

### Naming Rules

1. **Response models**: Use the resource name directly (`Organization`, `User`)
2. **Request params**: Use `{Resource}CreateParams` or `{Resource}UpdateParams`
3. **Convert DTO suffix**: `CreateWidgetDto` → `WidgetCreateParams`
4. **File names**: PascalCase matching class name (`UserProfile.php`)
5. **Acronyms**: PascalCase in class names (`ApiKey`), preserve in constants (`API_KEY`)

### Method Names

| HTTP Method | Path Pattern      | PHP Method   | Notes                    |
| ----------- | ----------------- | ------------ | ------------------------ |
| GET         | `/resources`      | `all`        | Returns Collection       |
| GET         | `/resources/{id}` | `retrieve`   | Not `get` or `find`      |
| POST        | `/resources`      | `create`     | Consistent               |
| PUT/PATCH   | `/resources/{id}` | `update`     | Consistent               |
| DELETE      | `/resources/{id}` | `delete`     | Not `destroy`            |

### Field Names

- Convert OpenAPI `camelCase` to PHP `snake_case` for array keys
- Use camelCase for class properties
- Example: `externalId` → `external_id` in arrays, `$externalId` as property

---

## Type System

### Type Mapping: OpenAPI → PHP → PHPDoc

| OpenAPI Type                   | PHP Type        | PHPDoc Type                    |
| ------------------------------ | --------------- | ------------------------------ |
| `string`                       | `string`        | `string`                       |
| `string` + `format: date-time` | `\DateTime`     | `\DateTime`                    |
| `string` + `format: date`      | `\DateTime`     | `\DateTime`                    |
| `string` + `enum`              | `string`        | `string` (with const class)    |
| `integer`                      | `int`           | `int`                          |
| `number`                       | `float`         | `float`                        |
| `boolean`                      | `bool`          | `bool`                         |
| `array` of primitives          | `array`         | `string[]` or `int[]`          |
| `array` of objects             | `array`         | `Resource[]`                   |
| `object` (typed via $ref)      | `Resource`      | `\WorkOS\Resource\Name`        |
| `object` (untyped/freeform)    | `array`         | `array<string, mixed>`         |
| `oneOf`/`anyOf`                | `mixed`         | `TypeA\|TypeB`                 |
| Optional field                 | `?type`         | `null\|type`                   |
| Nullable field                 | `?type`         | `null\|type`                   |

---

## Resource Model Pattern

Resources are data classes that represent API responses.

### Base Resource Class

```php
<?php

namespace WorkOS\Resource;

abstract class BaseResource
{
    /**
     * @var array<string, mixed> Raw response data
     */
    public array $raw;

    /**
     * @var ApiResponse|null Last API response metadata
     */
    protected ?ApiResponse $lastResponse = null;

    protected function __construct()
    {
    }

    /**
     * Create instance from API response data.
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function constructFromResponse(array $data): static
    {
        $instance = new static();
        $instance->raw = $data;
        $instance->populateFromResponse($data);
        return $instance;
    }

    /**
     * Populate instance properties from response data.
     *
     * @param array<string, mixed> $data
     */
    abstract protected function populateFromResponse(array $data): void;

    /**
     * Get the last API response.
     */
    public function getLastResponse(): ?ApiResponse
    {
        return $this->lastResponse;
    }

    /**
     * Set the last API response.
     */
    public function setLastResponse(ApiResponse $response): void
    {
        $this->lastResponse = $response;
    }
}
```

### Resource Implementation

```php
<?php

namespace WorkOS\Resource;

/**
 * Represents a WorkOS Organization.
 *
 * @property-read string $id The organization's unique identifier.
 * @property-read string $name The organization's display name.
 * @property-read string|null $externalId An external identifier for the organization.
 * @property-read string $state The organization's state (active, inactive).
 * @property-read \DateTime $createdAt When the organization was created.
 * @property-read \DateTime $updatedAt When the organization was last updated.
 */
class Organization extends BaseResource
{
    public string $id;
    public string $name;
    public ?string $externalId = null;
    public string $state;
    public \DateTime $createdAt;
    public \DateTime $updatedAt;

    /** @var Domain[] */
    public array $domains = [];

    protected function populateFromResponse(array $data): void
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->externalId = $data['external_id'] ?? null;
        $this->state = $data['state'] ?? 'active';
        $this->createdAt = new \DateTime($data['created_at']);
        $this->updatedAt = new \DateTime($data['updated_at']);

        if (isset($data['domains'])) {
            $this->domains = array_map(
                fn($d) => Domain::constructFromResponse($d),
                $data['domains']
            );
        }
    }
}
```

### Enum Pattern

```php
<?php

namespace WorkOS\Resource;

/**
 * Organization state constants.
 */
final class OrganizationState
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const PENDING = 'pending';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::PENDING,
        ];
    }
}
```

---

## Service Pattern

Services are classes that expose API operations for a resource.

### Abstract Service

```php
<?php

namespace WorkOS\Service;

use WorkOS\WorkOSClient;

abstract class AbstractService
{
    protected WorkOSClient $client;

    public function __construct(WorkOSClient $client)
    {
        $this->client = $client;
    }

    public function getClient(): WorkOSClient
    {
        return $this->client;
    }

    /**
     * @param array<string, mixed>|null $params
     * @param array<string, mixed>|null $opts
     * @return mixed
     */
    protected function request(string $method, string $path, ?array $params = null, ?array $opts = null)
    {
        return $this->client->request($method, $path, $params, $opts);
    }

    /**
     * @param array<string, mixed>|null $params
     * @param array<string, mixed>|null $opts
     * @return Collection
     */
    protected function requestCollection(string $method, string $path, ?array $params = null, ?array $opts = null): Collection
    {
        return $this->client->requestCollection($method, $path, $params, $opts);
    }

    /**
     * Build a path with URL-encoded IDs.
     *
     * @param string $basePath Path with %s placeholders
     * @param string ...$ids IDs to interpolate
     * @return string
     */
    protected function buildPath(string $basePath, string ...$ids): string
    {
        foreach ($ids as $id) {
            if ($id === '' || trim($id) === '') {
                throw new \WorkOS\Exception\InvalidArgumentException(
                    'The resource ID cannot be null or whitespace.'
                );
            }
        }
        return sprintf($basePath, ...array_map('urlencode', $ids));
    }
}
```

### Service Implementation

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
     * @throws \WorkOS\Exception\ApiException
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
     * @throws \WorkOS\Exception\ApiException
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
     * @throws \WorkOS\Exception\ApiException
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
     * @throws \WorkOS\Exception\ApiException
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
     * @throws \WorkOS\Exception\ApiException
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

### Pagination (Collection)

```php
<?php

namespace WorkOS;

/**
 * @template T
 * @implements \IteratorAggregate<T>
 */
class Collection implements \IteratorAggregate, \Countable
{
    /** @var T[] */
    public array $data = [];

    public ?string $after = null;
    public ?string $before = null;

    private WorkOSClient $client;
    private string $path;
    private array $params;
    private string $resourceClass;

    /**
     * Auto-paginate through all results.
     *
     * @return \Generator<T>
     */
    public function autoPagingIterator(): \Generator
    {
        $page = $this;
        while (true) {
            foreach ($page->data as $item) {
                yield $item;
            }
            if ($page->after === null) {
                break;
            }
            $page = $page->nextPage();
        }
    }

    public function hasMore(): bool
    {
        return $this->after !== null;
    }

    /**
     * @return self<T>
     */
    public function nextPage(): self
    {
        if ($this->after === null) {
            throw new Exception\InvalidArgumentException('No more pages available');
        }
        $params = array_merge($this->params, ['after' => $this->after]);
        return $this->client->requestCollection('get', $this->path, $params, []);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }

    public function count(): int
    {
        return count($this->data);
    }
}
```

---

## Error Handling

### Error Hierarchy

```php
<?php

namespace WorkOS\Exception;

class ApiException extends \Exception
{
    public ?int $httpStatus;
    public ?string $requestId;
    public ?array $errorBody;

    public function __construct(
        string $message,
        ?int $httpStatus = null,
        ?string $requestId = null,
        ?array $errorBody = null
    ) {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->requestId = $requestId;
        $this->errorBody = $errorBody;
    }
}

class AuthenticationException extends ApiException {}      // 401
class AuthorizationException extends ApiException {}       // 403
class NotFoundException extends ApiException {}            // 404
class UnprocessableEntityException extends ApiException {} // 422

class RateLimitException extends ApiException              // 429
{
    public ?int $retryAfter;

    public function __construct(
        string $message,
        ?int $httpStatus = null,
        ?string $requestId = null,
        ?array $errorBody = null,
        ?int $retryAfter = null
    ) {
        parent::__construct($message, $httpStatus, $requestId, $errorBody);
        $this->retryAfter = $retryAfter;
    }
}

class ServerException extends ApiException {}              // 5xx
class ConnectionException extends ApiException {}          // Network errors
class InvalidArgumentException extends \InvalidArgumentException {}
class ConfigurationException extends \Exception {}
```

### HTTP Status Mapping

| Status | Exception Class              | Retryable? |
| ------ | ---------------------------- | ---------- |
| 401    | `AuthenticationException`    | No         |
| 403    | `AuthorizationException`     | No         |
| 404    | `NotFoundException`          | No         |
| 422    | `UnprocessableEntityException` | No       |
| 429    | `RateLimitException`         | Yes        |
| 500    | `ServerException`            | Yes        |
| 502    | `ServerException`            | Yes        |
| 503    | `ServerException`            | Yes        |
| 504    | `ServerException`            | Yes        |

---

## Retry Logic

### Configuration

```php
class WorkOSClient
{
    public const DEFAULT_MAX_RETRIES = 2;
    public const RETRY_SLEEP_BASE = 0.5;
    public const MAX_RETRY_DELAY = 30;

    public const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];
}
```

### Backoff Strategy

Exponential backoff with jitter:

```
delay = min((2^attempt * 0.5) + random_jitter, 30)
```

| Attempt | Base Delay | With Jitter |
| ------- | ---------- | ----------- |
| 1       | 1.0s       | 1.0-1.1s    |
| 2       | 2.0s       | 2.0-2.2s    |
| 3       | 4.0s       | 4.0-4.4s    |
| 4       | 8.0s       | 8.0-8.8s    |
| 5       | 16.0s      | 16.0-17.6s  |

### Retry-After Header

When API returns `429` with `Retry-After`, respect that value (capped at MAX_RETRY_DELAY).

### Idempotency Keys

- Auto-generated UUID for POST requests if not provided
- Same key reused across retry attempts
- Users can provide custom keys for business-level idempotency

```php
// Auto-generated
$client->organizations->create(['name' => 'Acme']);

// Manual key
$client->organizations->create(
    ['name' => 'Acme'],
    ['idempotency_key' => 'user_action_12345']
);
```

---

## Global Configuration

### Pattern 1: Global Configuration (Simple)

```php
<?php

use WorkOS\WorkOS;

WorkOS::setApiKey('sk_live_...');

// Or via environment variable
// $_ENV['WORKOS_API_KEY'] = 'sk_live_...';

// Use default client
WorkOS::getDefaultClient()->organizations->all();
```

### Pattern 2: Explicit Client (Multi-tenant Safe)

```php
<?php

use WorkOS\WorkOSClient;

$client = new WorkOSClient(['api_key' => 'sk_...']);
$client->organizations->all();

// Multiple clients for different environments
$prodClient = new WorkOSClient(['api_key' => $_ENV['WORKOS_PROD_KEY']]);
$stagingClient = new WorkOSClient(['api_key' => $_ENV['WORKOS_STAGING_KEY']]);
```

### Configuration Options

| Option               | Default                    | Description                   |
| -------------------- | -------------------------- | ----------------------------- |
| `api_key`            | `$_ENV['WORKOS_API_KEY']`  | API key for authentication    |
| `max_network_retries`| `2`                        | Retry attempts (0 to disable) |
| `api_base`           | `https://api.workos.com`   | API base URL                  |

### WorkOS Class

```php
<?php

namespace WorkOS;

class WorkOS
{
    private static ?string $apiKey = null;
    private static ?string $clientId = null;
    private static string $apiBase = 'https://api.workos.com';
    private static int $maxNetworkRetries = 2;
    private static ?WorkOSClient $defaultClient = null;

    public static function setApiKey(?string $apiKey): void
    {
        self::$apiKey = $apiKey;
        self::$defaultClient = null; // Reset cached client
    }

    public static function getApiKey(): ?string
    {
        if (self::$apiKey !== null) {
            return self::$apiKey;
        }
        return $_ENV['WORKOS_API_KEY'] ?? getenv('WORKOS_API_KEY') ?: null;
    }

    public static function setClientId(?string $clientId): void
    {
        self::$clientId = $clientId;
    }

    public static function getClientId(): ?string
    {
        if (self::$clientId !== null) {
            return self::$clientId;
        }
        return $_ENV['WORKOS_CLIENT_ID'] ?? getenv('WORKOS_CLIENT_ID') ?: null;
    }

    public static function setApiBase(string $apiBase): void
    {
        self::$apiBase = $apiBase;
        self::$defaultClient = null;
    }

    public static function getApiBase(): string
    {
        return self::$apiBase;
    }

    public static function setMaxNetworkRetries(int $maxRetries): void
    {
        self::$maxNetworkRetries = $maxRetries;
        self::$defaultClient = null;
    }

    public static function getMaxNetworkRetries(): int
    {
        return self::$maxNetworkRetries;
    }

    public static function getDefaultClient(): WorkOSClient
    {
        if (self::$defaultClient === null) {
            self::$defaultClient = new WorkOSClient([
                'api_key' => self::getApiKey(),
                'api_base' => self::$apiBase,
                'max_network_retries' => self::$maxNetworkRetries,
            ]);
        }
        return self::$defaultClient;
    }

    public static function resetDefaultClient(): void
    {
        self::$defaultClient = null;
    }
}
```

---

## Documentation Standards

### PHPDoc for Classes

```php
<?php

namespace WorkOS\Resource;

/**
 * Represents a WorkOS Organization.
 *
 * Organizations are collections of users that can be used to organize
 * your application's data and access control.
 *
 * @property-read string $id The organization's unique identifier.
 * @property-read string $name The organization's display name.
 * @property-read string|null $externalId An external identifier for the organization.
 */
class Organization extends BaseResource
{
```

### PHPDoc for Methods

```php
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
 *
 * @see https://workos.com/docs/reference/organization/list
 */
public function all(?array $params = null, ?array $opts = null): Collection
```

---

## Webhook Verification

```php
<?php

namespace WorkOS;

use WorkOS\Exception\SignatureVerificationException;

class Webhook
{
    public const DEFAULT_TOLERANCE = 300; // 5 minutes

    /**
     * Verify webhook signature.
     *
     * @param string $payload Raw request body
     * @param string $header WorkOS-Signature header value
     * @param string $secret Webhook signing secret
     * @param int $tolerance Timestamp tolerance in seconds
     * @return bool
     *
     * @throws SignatureVerificationException if verification fails
     */
    public static function verifySignature(
        string $payload,
        string $header,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE
    ): bool {
        [$timestamp, $signatures] = self::parseHeader($header);
        self::verifyTimestamp($timestamp, $tolerance);

        $expectedSig = self::computeSignature($timestamp, $payload, $secret);
        self::verifySignatureMatch($signatures, $expectedSig);

        return true;
    }

    /**
     * Safe signature verification (returns boolean).
     */
    public static function isSignatureValid(
        string $payload,
        string $header,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE
    ): bool {
        try {
            return self::verifySignature($payload, $header, $secret, $tolerance);
        } catch (SignatureVerificationException) {
            return false;
        }
    }

    private static function parseHeader(string $header): array
    {
        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[$key] = $value;
        }

        $timestamp = isset($parts['t']) ? (int) $parts['t'] : null;
        $signatures = array_filter($parts, fn($k) => str_starts_with($k, 'v1'), ARRAY_FILTER_USE_KEY);

        if ($timestamp === null || empty($signatures)) {
            throw new SignatureVerificationException('Invalid signature header format');
        }

        return [$timestamp, array_values($signatures)];
    }

    private static function verifyTimestamp(int $timestamp, int $tolerance): void
    {
        if (abs(time() - $timestamp) > $tolerance) {
            throw new SignatureVerificationException('Timestamp outside tolerance');
        }
    }

    private static function computeSignature(int $timestamp, string $payload, string $secret): string
    {
        return hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
    }

    private static function verifySignatureMatch(array $signatures, string $expected): void
    {
        foreach ($signatures as $sig) {
            if (hash_equals($sig, $expected)) {
                return;
            }
        }
        throw new SignatureVerificationException('Signature mismatch');
    }
}
```

---

## Custom HTTP Clients

### HTTP Client Interface

```php
<?php

namespace WorkOS\HttpClient;

interface HttpClientInterface
{
    /**
     * Execute an HTTP request.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $url Full URL
     * @param array<string, string> $headers Request headers
     * @param string|null $body Request body
     * @param int $timeout Timeout in seconds
     * @return HttpResponse
     */
    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout
    ): HttpResponse;
}

class HttpResponse
{
    public function __construct(
        public int $status,
        public array $headers,
        public string $body
    ) {}
}
```

### Default cURL Client

```php
<?php

namespace WorkOS\HttpClient;

class CurlClient implements HttpClientInterface
{
    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout
    ): HttpResponse {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HEADER => true,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \WorkOS\Exception\ConnectionException("cURL error: {$error}");
        }

        curl_close($ch);

        $responseHeaders = $this->parseHeaders(substr($response, 0, $headerSize));
        $responseBody = substr($response, $headerSize);

        return new HttpResponse($status, $responseHeaders, $responseBody);
    }

    private function formatHeaders(array $headers): array
    {
        return array_map(
            fn($k, $v) => "{$k}: {$v}",
            array_keys($headers),
            array_values($headers)
        );
    }

    private function parseHeaders(string $headerBlock): array
    {
        $headers = [];
        foreach (explode("\r\n", trim($headerBlock)) as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }
        return $headers;
    }
}
```

### Usage

```php
// Default (cURL)
$client = new WorkOSClient(['api_key' => 'sk_...']);

// Custom HTTP client (e.g., Guzzle)
$guzzleClient = new GuzzleHttpClient($guzzle);
$client = new WorkOSClient([
    'api_key' => 'sk_...',
    'http_client' => $guzzleClient,
]);
```

---

## Middleware/Hooks System

### Configuration

```php
<?php

$client = new WorkOSClient([
    'api_key' => 'sk_...',
    'on_request' => function (RequestInfo $request) {
        error_log("WorkOS Request: {$request->method} {$request->path}");
    },
    'on_response' => function (ResponseInfo $response) {
        StatsD::timing('workos.request', $response->duration * 1000);
        StatsD::increment("workos.status.{$response->status}");
    },
    'on_error' => function (ApiException $error, RequestInfo $request) {
        Sentry::captureException($error, ['extra' => ['request_id' => $error->requestId]]);
    },
]);
```

### Hook Objects

```php
<?php

namespace WorkOS;

class RequestInfo
{
    public string $method;
    public string $path;
    public array $headers;
    public ?string $body;
    public ?string $idempotencyKey;
    public int $attempt;
}

class ResponseInfo
{
    public int $status;
    public array $headers;
    public string $body;
    public ?string $requestId;
    public float $duration;
    public int $attempt;
    public bool $retried;
}
```

---

## Request/Response Objects

### ApiResponse

```php
<?php

namespace WorkOS;

class ApiResponse
{
    public int $status;
    public array $headers;
    public ?string $requestId;
    public float $duration;
    public int $retryCount;

    public function __construct(
        int $status,
        array $headers,
        float $duration,
        int $retryCount = 0
    ) {
        $this->status = $status;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->requestId = $this->headers['x-request-id'] ?? null;
        $this->duration = $duration;
        $this->retryCount = $retryCount;
    }
}
```

### Usage

```php
$org = $client->organizations->retrieve('org_123');

// Access response metadata
$org->getLastResponse()->requestId;    // "req_abc123"
$org->getLastResponse()->status;       // 200
$org->getLastResponse()->duration;     // 0.234 (seconds)
$org->getLastResponse()->retryCount;   // 0
```

---

## Thread Safety

PHP doesn't have true multi-threading in typical usage, but the SDK should be safe for:

1. **Immutable configuration** - Client configuration cannot be changed after construction
2. **No shared mutable state** - Each request uses its own state
3. **Multiple clients** - Different client instances don't interfere

```php
class WorkOSClient
{
    private readonly string $apiKey;
    private readonly string $apiBase;
    private readonly int $maxNetworkRetries;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? WorkOS::getApiKey()
            ?? throw new ConfigurationException('API key is required');
        $this->apiBase = $config['api_base'] ?? WorkOS::getApiBase();
        $this->maxNetworkRetries = $config['max_network_retries'] ?? WorkOS::getMaxNetworkRetries();
    }
}
```

---

## Directory Structure

```
lib/
├── WorkOS.php              # Global configuration
├── WorkOSClient.php        # Main client class
├── Collection.php          # Pagination wrapper
├── ApiResponse.php         # Response metadata
├── Webhook.php             # Webhook verification
├── Version.php             # SDK version
├── Exception/              # Exception classes
│   ├── ApiException.php
│   ├── AuthenticationException.php
│   ├── NotFoundException.php
│   └── ...
├── HttpClient/             # HTTP client abstraction
│   ├── HttpClientInterface.php
│   ├── CurlClient.php
│   └── HttpResponse.php
├── Resource/               # Response models
│   ├── BaseResource.php
│   ├── Organization.php
│   ├── User.php
│   └── ...
└── Service/                # API services
    ├── AbstractService.php
    ├── ServiceFactory.php
    ├── OrganizationService.php
    ├── UserService.php
    └── ...

tests/
├── TestCase.php
├── fixtures/
│   └── organizations/
│       ├── list.json
│       └── retrieve.json
└── Service/
    └── OrganizationServiceTest.php
```

---

## Testing Patterns

Tests use PHPUnit with HTTP mocking.

### Test Base Class

```php
<?php

namespace WorkOS\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use WorkOS\WorkOSClient;

abstract class TestCase extends BaseTestCase
{
    protected WorkOSClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new WorkOSClient([
            'api_key' => 'sk_test_xxx',
            'max_network_retries' => 0,
        ]);
    }

    protected function loadFixture(string $path): string
    {
        return file_get_contents(__DIR__ . '/fixtures/' . $path);
    }

    protected function mockResponse(int $status, string $body, array $headers = []): void
    {
        // Implementation depends on HTTP mocking library
    }
}
```

### Service Tests

```php
<?php

namespace WorkOS\Tests\Service;

use WorkOS\Tests\TestCase;
use WorkOS\Resource\Organization;
use WorkOS\Collection;

class OrganizationServiceTest extends TestCase
{
    public function testAll(): void
    {
        $this->mockResponse(200, $this->loadFixture('organizations/list.json'));

        $result = $this->client->organizations->all();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertContainsOnlyInstancesOf(Organization::class, $result->data);
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

    public function testNotFound(): void
    {
        $this->mockResponse(404, json_encode(['message' => 'Not found']));

        $this->expectException(\WorkOS\Exception\NotFoundException::class);

        $this->client->organizations->retrieve('invalid');
    }

    public function testAuthenticationError(): void
    {
        $this->mockResponse(401, json_encode(['message' => 'Unauthorized']));

        $this->expectException(\WorkOS\Exception\AuthenticationException::class);

        $this->client->organizations->all();
    }
}
```

### Error Handling Tests

```php
<?php

namespace WorkOS\Tests\Service;

use WorkOS\Tests\TestCase;
use WorkOS\Exception\RateLimitException;
use WorkOS\Exception\ServerException;

class ErrorHandlingTest extends TestCase
{
    public function testRateLimitError(): void
    {
        $this->mockResponse(429, '', ['Retry-After' => '60']);

        try {
            $this->client->organizations->all();
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertEquals(429, $e->httpStatus);
            $this->assertEquals(60, $e->retryAfter);
        }
    }

    public function testServerError(): void
    {
        $this->mockResponse(500, json_encode(['message' => 'Internal error']));

        $this->expectException(ServerException::class);

        $this->client->organizations->all();
    }

    public function testErrorIncludesRequestId(): void
    {
        $this->mockResponse(
            500,
            json_encode(['message' => 'Error']),
            ['x-request-id' => 'req_abc123']
        );

        try {
            $this->client->organizations->all();
        } catch (ServerException $e) {
            $this->assertEquals('req_abc123', $e->requestId);
        }
    }
}
```

### Retry Logic Tests

```php
<?php

namespace WorkOS\Tests;

use WorkOS\WorkOSClient;

class RetryTest extends TestCase
{
    public function testRetriesOn429(): void
    {
        $client = new WorkOSClient([
            'api_key' => 'sk_test',
            'max_network_retries' => 2,
        ]);

        // Mock: first call returns 429, second returns 200
        $this->mockResponseSequence([
            [429, ''],
            [200, $this->loadFixture('organizations/list.json')],
        ]);

        $result = $client->organizations->all();

        $this->assertCount(2, $this->getRequestHistory());
    }

    public function testNoRetryOn404(): void
    {
        $client = new WorkOSClient([
            'api_key' => 'sk_test',
            'max_network_retries' => 2,
        ]);

        $this->mockResponse(404, json_encode(['message' => 'Not found']));

        try {
            $client->organizations->retrieve('invalid');
        } catch (\WorkOS\Exception\NotFoundException $e) {
            // Expected
        }

        $this->assertCount(1, $this->getRequestHistory());
    }

    public function testIdempotencyKeyReusedOnRetry(): void
    {
        $client = new WorkOSClient([
            'api_key' => 'sk_test',
            'max_network_retries' => 1,
        ]);

        $this->mockResponseSequence([
            [500, ''],
            [201, $this->loadFixture('organizations/create.json')],
        ]);

        $client->organizations->create(['name' => 'Test']);

        $requests = $this->getRequestHistory();
        $this->assertEquals(
            $requests[0]['headers']['Idempotency-Key'],
            $requests[1]['headers']['Idempotency-Key']
        );
    }
}
```

### Webhook Verification Tests

```php
<?php

namespace WorkOS\Tests;

use WorkOS\Webhook;
use WorkOS\Exception\SignatureVerificationException;

class WebhookTest extends TestCase
{
    private string $secret = 'whsec_test_secret';
    private string $payload = '{"type":"organization.created","data":{}}';

    public function testValidSignature(): void
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$this->payload}", $this->secret);
        $header = "t={$timestamp},v1={$signature}";

        $result = Webhook::verifySignature($this->payload, $header, $this->secret);

        $this->assertTrue($result);
    }

    public function testInvalidSignature(): void
    {
        $timestamp = time();
        $header = "t={$timestamp},v1=invalid_signature";

        $this->expectException(SignatureVerificationException::class);

        Webhook::verifySignature($this->payload, $header, $this->secret);
    }

    public function testExpiredTimestamp(): void
    {
        $timestamp = time() - 400; // 6+ minutes ago
        $signature = hash_hmac('sha256', "{$timestamp}.{$this->payload}", $this->secret);
        $header = "t={$timestamp},v1={$signature}";

        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Timestamp outside tolerance');

        Webhook::verifySignature($this->payload, $header, $this->secret, 300);
    }

    public function testIsSignatureValidReturnsBoolean(): void
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$this->payload}", $this->secret);
        $header = "t={$timestamp},v1={$signature}";

        $this->assertTrue(Webhook::isSignatureValid($this->payload, $header, $this->secret));
        $this->assertFalse(Webhook::isSignatureValid($this->payload, "t={$timestamp},v1=invalid", $this->secret));
    }
}
```

### Response Metadata Tests

```php
<?php

namespace WorkOS\Tests;

class ResponseMetadataTest extends TestCase
{
    public function testModelHasLastResponse(): void
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

    public function testLastResponseIncludesDuration(): void
    {
        $this->mockResponse(200, $this->loadFixture('organizations/retrieve.json'));

        $org = $this->client->organizations->retrieve('org_123');

        $this->assertGreaterThan(0, $org->getLastResponse()->duration);
    }
}
```
