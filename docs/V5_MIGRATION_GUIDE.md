# WorkOS PHP SDK v5 Migration Guide

This guide covers the changes required to migrate from the v4 PHP SDK to the next major release of `workos/workos-php`.

The biggest change is architectural: the SDK is now centered around an instantiated [`WorkOS`](../lib/WorkOS.php) client with typed request/response models, lazy client methods like `sso()` and `userManagement()`, and a Guzzle-based HTTP runtime.

## Table of Contents

- [Quick Start](#quick-start)
- [PHP and Dependency Requirements](#php-and-dependency-requirements)
- [Biggest Conceptual Changes](#biggest-conceptual-changes)
- [Breaking Changes by Area](#breaking-changes-by-area)
- [New Features and Additions](#new-features-and-additions)
- [Testing Your Migration](#testing-your-migration)

---

## Quick Start

1. Upgrade to PHP 8.2 or newer.
2. Upgrade the package:

```bash
composer require workos/workos-php:^5
```

3. Replace direct class instantiation with a `WorkOS` client:

```php
use WorkOS\WorkOS;

$workos = new WorkOS(
    apiKey: getenv('WORKOS_API_KEY'),
    clientId: getenv('WORKOS_CLIENT_ID'),
);
```

4. Update renamed APIs and methods.
5. Re-run your tests and verify auth, SSO, invitations, and webhook flows end-to-end.

---

## PHP and Dependency Requirements

### Minimum PHP version is now 8.2+

v4 supported PHP 7.3+. The new SDK requires PHP 8.2 or newer.

### Runtime dependencies changed

- `guzzlehttp/guzzle:^7.0` is now required.
- `paragonie/halite` was upgraded from `^4.0` to `^5.1`.
- `ext-curl` is now declared as `^8.2`.

If your app or deployment environment was pinned to older PHP or extension versions, upgrade those first.

---

## Biggest Conceptual Changes

### 1. The SDK now revolves around an instantiated client

Before:

```php
use WorkOS\WorkOS;
use WorkOS\UserManagement;

WorkOS::setApiKey('sk_test_...');
WorkOS::setClientId('client_...');

$userManagement = new UserManagement();
$user = $userManagement->createUser('user@example.com');
```

After:

```php
use WorkOS\WorkOS;

$workos = new WorkOS(
    apiKey: 'sk_test_...',
    clientId: 'client_...',
);

$user = $workos->userManagement()->createUsers(
    email: 'user@example.com',
);
```

`WorkOS::setApiKey()` and `WorkOS::setClientId()` still exist as defaults, but the intended integration style is now an instantiated client.

Static getters are no longer the primary configuration path. In v4, `WorkOS::getApiKey()` and `WorkOS::getClientId()` loaded environment variables and threw when unset; in v5, they are nullable compatibility shims, and credential validation happens when an operation requires them.

### 2. Most product areas are now accessed through the `WorkOS` client

Instead of instantiating `new SSO()`, `new UserManagement()`, `new MFA()`, and so on, you now call lazy client methods:

- `$workos->sso()`
- `$workos->userManagement()`
- `$workos->multiFactorAuth()`
- `$workos->directorySync()`
- `$workos->organizations()`
- `$workos->authorization()`
- `$workos->adminPortal()`
- `$workos->auditLogs()`
- `$workos->featureFlags()`
- `$workos->webhooks()`

### 3. The SDK is now typed and generated

Resources are now typed `readonly` models with `fromArray()` / `toArray()` methods. Timestamps are commonly hydrated into `DateTimeImmutable`, and many option values now use enums instead of free-form strings.

If you previously relied on mutable resource objects, dynamic properties, or `BaseWorkOSResource`, review that code carefully.

### 4. Named arguments are strongly recommended

Many generated methods now have longer signatures with optional parameters near the front. Positional argument code that compiled in v4 will often call the wrong parameter in v5.

Prefer named arguments:

```php
$workos->organizations()->listOrganizations(
    after: 'org_123',
    limit: 25,
);
```

---

## Breaking Changes by Area

### Client bootstrap and transport

#### Direct class construction is no longer the default integration pattern

Most APIs now live behind the `WorkOS` client and share an internal `HttpClient`, so code like this should be removed:

```php
new \WorkOS\SSO();
new \WorkOS\UserManagement();
new \WorkOS\Organizations();
new \WorkOS\MFA();
new \WorkOS\Portal();
new \WorkOS\RBAC();
```

Use the `WorkOS` client methods instead.

#### Imports and type hints also need to move to the new surface

It is not enough to replace `new \WorkOS\SSO()` with `$workos->sso()`. If your code imported or type-hinted old top-level service classes such as `WorkOS\UserManagement`, `WorkOS\Portal`, or `WorkOS\RBAC`, update those references to the client-accessed services instead.

#### `Client`, `RequestClientInterface`, and `CurlRequestClient` were removed

If you were customizing transport internals with:

- `Client::setRequestClient(...)`
- `Client::requestClient()`
- `RequestClientInterface`
- `CurlRequestClient`

switch to the new Guzzle-based runtime. The supported customization points are:

- `new WorkOS(..., handler: $handlerStack)`
- per-request `RequestOptions`

#### Several static `WorkOS` configuration methods were removed

These v4 methods are gone:

- `WorkOS::getApiBaseURL()`
- `WorkOS::setApiBaseUrl()`
- `WorkOS::setIdentifier()`
- `WorkOS::getIdentifier()`
- `WorkOS::setVersion()`
- `WorkOS::getVersion()`

Configure `baseUrl`, `timeout`, `maxRetries`, and `handler` via the `WorkOS` constructor instead.

#### `WorkOS::getApiKey()` and `WorkOS::getClientId()` no longer validate configuration

Before:

```php
use WorkOS\WorkOS;

WorkOS::setApiKey(getenv('WORKOS_API_KEY'));
WorkOS::setClientId(getenv('WORKOS_CLIENT_ID'));

$clientId = WorkOS::getClientId();
$apiKey = WorkOS::getApiKey();
```

After:

```php
use WorkOS\WorkOS;

$workos = new WorkOS(
    apiKey: getenv('WORKOS_API_KEY'),
    clientId: getenv('WORKOS_CLIENT_ID'),
);
```

In v4, the getters loaded env vars and threw `ConfigurationException` when missing. In v5, they only return the current static shim value. If you used them as bootstrap-time validation, move that validation to `new WorkOS(...)` or to the first API call that requires credentials.

### Pagination and list responses

#### `Resource\PaginatedResource` was replaced with `WorkOS\PaginatedResponse`

Before:

```php
[$before, $after, $users] = $userManagement->listUsers();
```

After:

```php
$page = $workos->userManagement()->listUsers();

$users = $page->data;
$after = $page->listMetadata['after'] ?? null;
```

`PaginatedResponse` also adds auto-pagination helpers:

```php
foreach ($page->autoPagingIterator() as $user) {
    // ...
}
```

Auto-pagination only follows `after` cursors. If your integration previously relied on reverse pagination with `before`, keep fetching those pages manually.

#### Array destructuring and magic list keys should be considered removed

v4 list responses supported access patterns like:

- `[$before, $after, $items] = $result`
- `$result->users`
- `$result->organizations`

In v5, use:

- `$result->data`
- `$result->listMetadata`
- `$result->hasMore()`

### Resources and exceptions

#### `BaseWorkOSResource` is gone

These v4 behaviors are no longer part of the resource model:

- `BaseWorkOSResource`
- `constructFromResponse()`
- mutable dynamic properties
- the `raw` response bag on every resource

If you previously accessed `$resource->raw`, mutated resource fields, or extended resource base classes, migrate to typed properties plus `toArray()`.

#### Resource field types are stricter

Examples of behavior changes you may notice:

- timestamps are often `DateTimeImmutable` instead of strings
- enums are used for many option and state fields
- list responses sometimes return typed wrappers like `RoleList` or `ListModel`

Common constant-to-enum migrations include:

```php
use WorkOS\Resource\EventsOrder;

$workos->organizations()->listOrganizations(
    order: EventsOrder::Asc,
);
```

```php
use WorkOS\Resource\ConnectionsConnectionType;

$workos->sso()->listConnections(
    connectionType: ConnectionsConnectionType::OktaSAML,
);
```

#### Exception types are more granular

The new runtime maps HTTP failures to explicit exception classes such as:

- `AuthenticationException`
- `AuthorizationException`
- `BadRequestException`
- `ConflictException`
- `ConnectionException`
- `NotFoundException`
- `RateLimitExceededException`
- `ServerException`
- `TimeoutException`
- `UnprocessableEntityException`

These exceptions now expose request metadata like `statusCode`, `requestId`, and for rate limits, `retryAfter`.

### SSO

#### `SSO` is now accessed through the client

Before:

```php
$sso = new \WorkOS\SSO();
```

After:

```php
$sso = $workos->sso();
```

#### `getAuthorizationUrl()` no longer builds a URL locally

In v4, `SSO::getAuthorizationUrl(...)` returned a string and implicitly used `WorkOS::getClientId()`.

In v5 it:

- makes an HTTP request
- returns `WorkOS\Resource\SSOAuthorizeUrlResponse`
- requires an instantiated client with `clientId`
- requires `redirectUri`

Before:

```php
$url = $sso->getAuthorizationUrl(
    domain: 'example.com',
    redirectUri: 'https://example.com/callback',
    state: ['return_to' => '/dashboard'],
);
```

After:

```php
$response = $workos->sso()->getAuthorizationUrl(
    redirectUri: 'https://example.com/callback',
    domain: 'example.com',
    state: json_encode(['return_to' => '/dashboard']),
);

$url = $response->url;
```

`state` is now a string parameter. If you used array state in v4, encode it yourself.

`client_id` now comes from the instantiated `WorkOS` client, and the SDK always sends `response_type=code` for you.

#### `getProfileAndToken()` now requires explicit credentials

Before:

```php
$profile = $sso->getProfileAndToken($code);
```

After:

```php
$result = $workos->sso()->getProfileAndToken(
    clientId: 'client_...',
    clientSecret: 'sk_test_...',
    code: $code,
    grantType: 'authorization_code',
);
```

#### `getProfile($accessToken)` changed shape

In v4, `getProfile()` accepted the access token directly.

In v5, the method signature no longer takes an access token argument. Based on the current API surface, pass the token via `RequestOptions` headers:

```php
use WorkOS\RequestOptions;

$profile = $workos->sso()->getProfile(
    options: new RequestOptions(
        extraHeaders: ['Authorization' => "Bearer {$accessToken}"],
    ),
);
```

### User Management and sessions

#### `UserManagement` is now accessed through the client

Before:

```php
$userManagement = new \WorkOS\UserManagement();
```

After:

```php
$userManagement = $workos->userManagement();
```

#### Session helpers moved out of `UserManagement`

These v4 methods are no longer on `UserManagement`:

- `getJwksUrl()`
- `authenticateWithSessionCookie()`
- `loadSealedSession()`
- `getSessionFromCookie()`

Use `SessionManager` instead:

```php
use WorkOS\SessionManager;

$url = SessionManager::getJwksUrl('client_...');

$result = $workos->sessionManager()->authenticate(
    sessionData: $_COOKIE['wos-session'] ?? '',
    cookiePassword: $cookiePassword,
    clientId: 'client_...',
);
```

The old `new UserManagement($encryptor)` customization point was also removed.

#### Session authentication now returns arrays instead of response models

Before:

```php
$result = $userManagement->authenticateWithSessionCookie(
    $sealedSession,
    $cookiePassword,
);
```

After:

```php
$result = $workos->sessionManager()->authenticate(
    sessionData: $sealedSession,
    cookiePassword: $cookiePassword,
    clientId: 'client_...',
);
```

The new method returns an associative array such as `['authenticated' => true, ...]` or `['authenticated' => false, 'reason' => 'invalid_jwt']`. If your code checked for `SessionAuthenticationSuccessResponse` or `SessionAuthenticationFailureResponse`, update that logic.

#### Auth methods now read credentials from the client instance

Before:

```php
$result = $userManagement->authenticateWithPassword(
    'client_...',
    'user@example.com',
    'secret',
);
```

After:

```php
$result = $workos->userManagement()->authenticateWithPassword(
    email: 'user@example.com',
    password: 'secret',
);
```

The same change applies to methods like `authenticateWithCode()` and `authenticateWithRefreshToken()`: remove leading credential arguments and ensure the `WorkOS` client was instantiated with `apiKey` and `clientId`.

#### Several User Management methods were renamed

| v4                                       | v5                                                          |
| ---------------------------------------- | ----------------------------------------------------------- |
| `createUser()`                           | `userManagement()->createUsers()`                           |
| `createOrganizationMembership()`         | `userManagement()->createOrganizationMemberships()`         |
| `sendInvitation()`                       | `userManagement()->createInvitations()`                     |
| `findInvitationByToken()`                | `userManagement()->getByToken()`                            |
| `authenticateWithSelectedOrganization()` | `userManagement()->authenticateWithOrganizationSelection()` |
| `verifyEmail()`                          | `userManagement()->confirmEmailVerification()`              |
| `resetPassword()`                        | `userManagement()->confirmPasswordReset()`                  |
| `listSessions()`                         | `userManagement()->listUserSessions()`                      |

#### Deprecated methods were removed

These methods existed in v4 but should be treated as removed in v5:

- `sendPasswordResetEmail()` -> use `createPasswordReset()`
- `sendMagicAuthCode()` -> use `createMagicAuth()`

#### Auth and logout URL helpers changed behavior

`userManagement()->getAuthorizationUrl()` and `userManagement()->getLogoutUrl()` no longer just build a local string.

Notable differences:

- they make API calls
- `getAuthorizationUrl()` now requires `redirectUri` and an instantiated client with `clientId`
- `state` is now a string, not an array that the SDK JSON-encodes for you
- `getLogoutUrl()` now returns response data instead of a locally composed URL string

Before:

```php
$url = $userManagement->getAuthorizationUrl(
    'https://example.com/callback',
    ['return_to' => '/dashboard'],
    WorkOS\UserManagement::AUTHORIZATION_PROVIDER_AUTHKIT,
);
```

After:

```php
$response = $workos->userManagement()->getAuthorizationUrl(
    redirectUri: 'https://example.com/callback',
    state: json_encode(['return_to' => '/dashboard']),
    provider: \WorkOS\Resource\UserManagementAuthenticationProvider::Authkit,
);
```

### Directory Sync

#### `DirectorySync` method names are more explicit

| v4             | v5                                       |
| -------------- | ---------------------------------------- |
| `listGroups()` | `directorySync()->listDirectoryGroups()` |
| `getGroup()`   | `directorySync()->getDirectoryGroup()`   |
| `listUsers()`  | `directorySync()->listDirectoryUsers()`  |
| `getUser()`    | `directorySync()->getDirectoryUser()`    |

The API also moved from direct construction to `$workos->directorySync()`.

### MFA

#### `MFA` became `multiFactorAuth()`

Before:

```php
$mfa = new \WorkOS\MFA();
```

After:

```php
$mfa = $workos->multiFactorAuth();
```

#### `verifyFactor()` is gone

Use `verifyChallenge()`:

```php
$result = $workos->multiFactorAuth()->verifyChallenge(
    id: $authenticationChallengeId,
    code: '123456',
);
```

#### User-scoped MFA APIs moved here from `UserManagement`

| v4                   | v5                                           |
| -------------------- | -------------------------------------------- |
| `enrollAuthFactor()` | `multiFactorAuth()->createUserAuthFactors()` |
| `listAuthFactors()`  | `multiFactorAuth()->listUserAuthFactors()`   |

### Organizations, Admin Portal, Feature Flags, and Authorization

#### `Portal` became `adminPortal()`

Before:

```php
$portal = new \WorkOS\Portal();
$link = $portal->generateLink('org_123', 'sso');
```

After:

```php
$response = $workos->adminPortal()->generateLink(
    organization: 'org_123',
    intent: \WorkOS\Resource\GenerateLinkIntent::SSO,
);

$link = $response->link;
```

`intent_options` is also now supported.

#### `RBAC` was replaced by `authorization()`

Before:

```php
$rbac = new \WorkOS\RBAC();
```

After:

```php
$authorization = $workos->authorization();
```

The environment-role method names were renamed:

| v4                                | v5                                         |
| --------------------------------- | ------------------------------------------ |
| `createEnvironmentRole()`         | `authorization()->createRoles()`           |
| `listEnvironmentRoles()`          | `authorization()->listRoles()`             |
| `getEnvironmentRole()`            | `authorization()->getRole()`               |
| `updateEnvironmentRole()`         | `authorization()->updateRole()`            |
| `setEnvironmentRolePermissions()` | `authorization()->updateRolePermissions()` |
| `addEnvironmentRolePermission()`  | `authorization()->createRolePermissions()` |

Organization-role APIs also moved from `Organizations` / `RBAC` into `authorization()`.

#### Feature flag APIs moved out of `Organizations`

If you used:

- `Organizations::listOrganizationFeatureFlags()`

switch to:

- `$workos->featureFlags()->listOrganizationFeatureFlags(...)`

#### Some idempotency keys moved into `RequestOptions`

Before:

```php
$organizations = new \WorkOS\Organizations();

$organization = $organizations->createOrganization('Acme', null, null, 'idemp_123');
```

After:

```php
use WorkOS\RequestOptions;

$organization = $workos->organizations()->createOrganizations(
    name: 'Acme',
    options: new RequestOptions(
        idempotencyKey: 'idemp_123',
    ),
);
```

The same pattern applies anywhere the new runtime uses `RequestOptions`.

If you rely on retries for write requests, set an explicit idempotency key yourself. The new runtime retries retryable responses, but it does not auto-generate idempotency keys for POST requests.

### Audit Logs

#### `AuditLogs` is now accessed through the client, and several methods were renamed

| v4               | v5                                   |
| ---------------- | ------------------------------------ |
| `createEvent()`  | `auditLogs()->createEvents()`        |
| `createExport()` | `auditLogs()->createExports()`       |
| `createSchema()` | `auditLogs()->createActionSchemas()` |
| `schemaExists()` | removed                              |

There is no direct `schemaExists()` helper in v5. Call `listActionSchemas()` and handle `NotFoundException` if you need equivalent behavior.

### Passwordless

#### `Passwordless::createSession()` changed signature

The old positional signature was:

```php
createSession($email, $redirectUri, $state, $type, $connection, $expiresIn)
```

The new signature is:

```php
$workos->passwordless()->createSession(
    email: 'user@example.com',
    type: 'MagicLink',
    redirectUri: 'https://example.com/callback',
    state: '...',
    expiresIn: 900,
);
```

Notable changes:

- `connection` is no longer an argument
- the parameter order changed completely
- the method now returns an array instead of a `PasswordlessSession` resource

#### `sendSession()` now takes a session ID string

Before:

```php
$session = $passwordless->createSession(...);
$passwordless->sendSession($session);
```

After:

```php
$session = $workos->passwordless()->createSession(...);
$workos->passwordless()->sendSession($session['id']);
```

### Widgets

`Widgets::getToken()` was renamed to `widgets()->createToken()`, and the return type is now `WidgetSessionTokenResponse`.

### Vault

The Vault API was expanded and renamed around "objects" instead of "vault objects".

| v4                   | v5                       |
| -------------------- | ------------------------ |
| `getVaultObject()`   | `vault()->readObject()`  |
| `listVaultObjects()` | `vault()->listObjects()` |

Additional Vault capabilities were added in v5, including object version listing, object creation/update/delete, data-key APIs, and local encrypt/decrypt helpers.

### Webhooks

#### Webhook CRUD and verification are now separate concerns

v4 used a single `Webhook` helper for verification.

In v5:

- `$workos->webhooks()` manages webhook endpoints
- `$workos->webhookVerification()` verifies webhook payloads

#### Verification now throws instead of returning error strings

Before:

```php
$webhook = new \WorkOS\Webhook();
$result = $webhook->verifyHeader($sigHeader, $payload, $secret, 180);

if ($result !== 'pass') {
    // handle string error
}
```

After:

```php
$event = $workos->webhookVerification()->verifyEvent(
    eventBody: $payload,
    eventSignature: $sigHeader,
    secret: $secret,
);
```

If verification fails, `verifyHeader()` / `verifyEvent()` throw `InvalidArgumentException`.

---

## New Features and Additions

These are not migration blockers, but they are new capabilities in the v5 SDK:

- `apiKeys()` for organization API key management and validation.
- `connect()` for Connect applications and OAuth completion.
- `events()` for event listing.
- `featureFlags()` for feature flag retrieval and targeting.
- `organizationDomains()` for standalone organization domain operations.
- `pipes()` for data integration flows.
- `radar()` for attempts and list management.
- `actions()` for WorkOS Actions signature verification and signed responses.
- `sessionManager()` for sealing, unsealing, session-cookie auth, JWKS helpers, and refresh flows.
- `pkce()` for PKCE verifier/challenge generation and AuthKit/SSO PKCE flows.
- expanded `vault()` support for object CRUD, data keys, and local encryption helpers.
- `RequestOptions` for per-request headers, idempotency keys, base URL overrides, timeout overrides, and retry overrides.
- automatic retries for `429` and common `5xx` responses.
- `PaginatedResponse::autoPagingIterator()` for iterating across all pages.

---

## Testing Your Migration

After migrating, verify at least the following:

1. PHP runtime is 8.2+ everywhere the SDK executes.
2. All direct `new WorkOS\...` class construction has been replaced with a `WorkOS` client.
3. Any list-response destructuring has been updated to `PaginatedResponse`.
4. Any code that accessed resource `raw` data or mutated resource objects has been updated.
5. Any code that relied on `WorkOS::getApiKey()` / `getClientId()` throwing during bootstrap has been updated.
6. SSO and User Management URL-generation code has been updated for the new request/response shape.
7. Session-cookie code now uses `SessionManager`, and any success/failure type checks were updated for the new array return shape.
8. Deprecated User Management and MFA method names have been replaced, including auth methods that no longer accept leading credential arguments.
9. Webhook verification paths were updated to exception-based handling.
10. Integration tests for SSO, AuthKit, invitations, password reset, and webhooks still pass.
