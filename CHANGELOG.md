# Changelog

## [7.0.1](https://github.com/workos/workos-php/compare/v7.0.0...v7.0.1) (2026-05-28)


### Bug Fixes

* renamed misleading Object to VaultObject ([c7cef60](https://github.com/workos/workos-php/commit/c7cef60df1e19e8664b3713b07ac44c5a491a5a7))
* **renovate:** explicitly enable minor and patch updates ([#393](https://github.com/workos/workos-php/issues/393)) ([e1705f1](https://github.com/workos/workos-php/commit/e1705f1d0b6cadf84f5a540673b59868d4b82fec))
* **sdk:** omit defaulted screen_hint from auth URLs ([#396](https://github.com/workos/workos-php/issues/396)) ([d583c7c](https://github.com/workos/workos-php/commit/d583c7ce64920da86153fe19c0e8b5f3a1069604))

## [7.0.0](https://github.com/workos/workos-php/compare/v6.0.2...v7.0.0) (2026-05-26)

### Miscellaneous Chores

* **deps:** update googleapis/release-please-action action to v5 ([#389](https://github.com/workos/workos-php/issues/389)) ([f973f21](https://github.com/workos/workos-php/commit/f973f21cccdddbf1a22feae7db4f91b95be59173))
* **deps:** update shivammathur/setup-php action to v2.37.0 ([#388](https://github.com/workos/workos-php/issues/388)) ([f9e113f](https://github.com/workos/workos-php/commit/f9e113fbfc5842895b65ce80cde67be392c34ec1))
* use shared workos/renovate-config preset ([99aad6c](https://github.com/workos/workos-php/commit/99aad6c4cbb3c3f2267849fe09f72491da026eaf))

* [#392](https://github.com/workos/workos-php/pull/392) feat(generated)!: regenerate from spec (10 changes)

  **⚠️ Breaking**
  * **user_management:** Remove organization membership methods from UserManagement service
    * Removed `listOrganizationMemberships`, `createOrganizationMembership`, `getOrganizationMembership`, `updateOrganizationMembership`, `deleteOrganizationMembership`, `deactivateOrganizationMembership`, and `reactivateOrganizationMembership` methods
    * These methods have been moved to the new `OrganizationMembershipService`
    * Change to `getAuthorizationUrl()` parameter `screenHint` from `UserManagementAuthenticationScreenHint` to `RadarStandaloneAssessRequestAction` type
    * Removed `UserManagementAuthenticationScreenHint` enum
  * **user_management:** Remove `UserManagementOrganizationMembershipGroups` service
    * Removed `$workos->userManagementOrganizationMembershipGroups()` accessor
    * `listOrganizationMembershipGroups()` has been moved to the new `OrganizationMembershipService`
  * **radar:** Remove device fingerprint and bot score from RadarStandaloneAssessRequest
    * Removed `deviceFingerprint` parameter from `Radar.createAttempt()` method
    * Removed `botScore` parameter from `Radar.createAttempt()` method
    * Removed `deviceFingerprint` and `botScore` fields from `RadarStandaloneAssessRequest` model
    * Removed deprecated enum values from `RadarStandaloneAssessRequestAction`: `Login`, `Signup`, `SignUp2`, `SignUp3`, `SignIn2`, `SignIn3`
    * Changed enum values in `RadarStandaloneAssessRequestAction`: `SignUp` from 'sign up' to 'sign-up', `SignIn` from 'sign in' to 'sign-in'
    * Removed enum values from `RadarStandaloneResponseControl`: `CredentialStuffing`, `IpSignUpRateLimit`
  * **radar:** Rename Radar list enums
    * Renamed `RadarAction` to `RadarListAction`
    * Renamed `RadarType` to `RadarListType`
  * **audit_logs:** Rename audit log models for consistency
    * Renamed `AuditLogActionJson` to `AuditLogAction`
    * Renamed `AuditLogExportJson` to `AuditLogExport`
    * Renamed `AuditLogExportJsonState` to `AuditLogExportState`
    * Renamed `AuditLogSchemaJson` to `AuditLogSchema`
    * Renamed `AuditLogSchemaJsonActor` to `AuditLogSchemaActorInput`
    * Renamed `AuditLogSchemaJsonTarget` to `AuditLogSchemaTargetInput`
    * Renamed `AuditLogsRetentionJson` to `AuditLogsRetention`
    * `createSchema()` method parameter types changed: `AuditLogSchemaActor` → `AuditLogSchemaActorInput`, `AuditLogSchemaTarget` → `AuditLogSchemaTargetInput`
  * **webhooks:** Rename webhook endpoint models and update status field type
    * Renamed `WebhookEndpointJson` to `WebhookEndpoint`
    * Renamed `WebhookEndpointJsonStatus` to `WebhookEndpointStatus`
    * Changed `UpdateWebhookEndpoint.status` field type from `WebhookEndpointJsonStatus` to `WebhookEndpointStatus`
    * Updated webhook event enums: added `PIPES_CONNECTED_ACCOUNT_CONNECTED`, `PIPES_CONNECTED_ACCOUNT_DISCONNECTED`, `PIPES_CONNECTED_ACCOUNT_REAUTHORIZATION_NEEDED` events
  * **authorization:** Update Authorization API with new filters and remove search parameter
    * Added `resourceId`, `resourceExternalId`, `resourceTypeSlug` filter parameters to `listRoleAssignments()`
    * Added `roleSlug` filter parameter to `listRoleAssignmentsForResourceByExternalId()` and `listRoleAssignmentsForResource()`
    * Removed `search` parameter from `listResources()` method
  * **vault:** Replace hand-maintained `WorkOS\Vault` class with generated `WorkOS\Service\Vault`
    * The old `WorkOS\Vault` class (`lib/Vault.php`) with client-side encrypt/decrypt helpers has been removed
    * `$workos->vault()` now returns `WorkOS\Service\Vault` with a different API surface
    * New generated methods: `createDataKey()`, `createDecrypt()`, `createRekey()`, `listKv()`, `createKv()`, `getName()`, `getKv()`, `updateKv()`, `deleteKv()`, `listKvMetadata()`, `listKvVersions()`

  **Features**
  * **organization_membership:** Introduce OrganizationMembershipService with membership and group operations
    * New service `OrganizationMembershipService` with methods: `listOrganizationMemberships()`, `createOrganizationMembership()`, `getOrganizationMembership()`, `updateOrganizationMembership()`, `deleteOrganizationMembership()`, `deactivateOrganizationMembership()`, `reactivateOrganizationMembership()`, and `listOrganizationMembershipGroups()`
    * Accessible via `$workos->organizationMembership()`
    * Replaces functionality previously in `UserManagement` and `UserManagementOrganizationMembershipGroups` services
  * **vault:** Add new Vault service for encrypted key-value storage
    * New `Vault` service with methods: `createDataKey()`, `createDecrypt()`, `createRekey()`, `listKv()`, `createKv()`, `getName()`, `getKv()`, `updateKv()`, `deleteKv()`, `listKvMetadata()`, and `listKvVersions()`
    * Support for encrypted object storage and management with key rotation
    * New models: `CreateDataKeyResponse`, `DecryptResponse`, `ObjectMetadata`, `ObjectModel`, `ObjectSummary`, `ObjectWithoutValue`, `ObjectVersion`, `VersionListResponse`
    * New enum: `VaultOrder` for sort direction
    * Accessible via `$workos->vault()`
  * **api_keys:** Add expires_at field to API key models and creation methods
    * Added `expires_at` field to `ApiKey`, `OrganizationApiKey`, `OrganizationApiKeyWithValue`, `UserApiKey`, `UserApiKeyWithValue` models
    * Added `expires_at` field to `ApiKeyCreatedData` and `ApiKeyRevokedData` event data models
    * Added optional `expiresAt` parameter to `createOrganizationApiKey()` and `createUserApiKey()` methods
    * Support for setting API key expiration timestamps
  * **applications:** Update ApplicationCredentialsListItem and NewConnectApplicationSecret field types
    * Changed `ApplicationCredentialsListItem.lastUsedAt` field type from `?string` to `?\DateTimeImmutable`
    * Changed `NewConnectApplicationSecret.lastUsedAt` field type from `?string` to `?\DateTimeImmutable`
  * **pipes:** Add connected account event models and related types
    * New models for pipe events: `PipeConnectedAccount`, `PipesConnectedAccountConnected`, `PipesConnectedAccountDisconnected`, `PipesConnectedAccountReauthorizationNeeded`
    * New enum: `PipeConnectedAccountState` with values `connected` and `needs_reauthorization`
    * Support for monitoring data integration connection status changes

## [6.0.2](https://github.com/workos/workos-php/compare/v6.0.1...v6.0.2) (2026-05-11)


### Bug Fixes

* harden JWT signature verification and URL path encoding ([#386](https://github.com/workos/workos-php/issues/386)) ([aa64119](https://github.com/workos/workos-php/commit/aa6411916fd2d70ce043085bcc9f4ba21e9e85eb))

## [6.0.1](https://github.com/workos/workos-php/compare/6.0.0...v6.0.1) (2026-05-07)


### Bug Fixes

* Preserve full error response body on ApiException ([#385](https://github.com/workos/workos-php/issues/385)) ([57052ab](https://github.com/workos/workos-php/commit/57052ab3d844beb35d5088fb55beca79bf2d752f))


### Miscellaneous Chores

* **release:** include v prefix in tags ([5517b8b](https://github.com/workos/workos-php/commit/5517b8b9acd404cc987db61fb5ed26405cf1eb0d))

## [6.0.0](https://github.com/workos/workos-php/compare/5.2.1...6.0.0) (2026-05-06)


### ⚠ BREAKING CHANGES

* **user_management:** Add user API keys and update ordering enum
* **api_keys:** Restructure API key models and rename ordering enum
* **vault:** Rename BYOK key provider enum and add vault key deleted event
* **authorization:** Rename RoleAssignment to UserRoleAssignment

### Features

* Add API docs generation and llms.txt index ([#382](https://github.com/workos/workos-php/issues/382)) ([ae03f03](https://github.com/workos/workos-php/commit/ae03f03a671b5ec9ec1d569457c96dca9aca6cc7))
* **api_keys:** Restructure API key models and rename ordering enum ([1bb2fe4](https://github.com/workos/workos-php/commit/1bb2fe4ba91575bc0db7eb056fd48f10c6653be1))
* **authorization:** Rename EventsOrder to PaginationOrder ([1bb2fe4](https://github.com/workos/workos-php/commit/1bb2fe4ba91575bc0db7eb056fd48f10c6653be1))
* **authorization:** Rename RoleAssignment to UserRoleAssignment ([1bb2fe4](https://github.com/workos/workos-php/commit/1bb2fe4ba91575bc0db7eb056fd48f10c6653be1))
* **directory_sync:** Add name field to directory users ([1bb2fe4](https://github.com/workos/workos-php/commit/1bb2fe4ba91575bc0db7eb056fd48f10c6653be1))
* **sso:** Add full name support to user profiles ([1bb2fe4](https://github.com/workos/workos-php/commit/1bb2fe4ba91575bc0db7eb056fd48f10c6653be1))
* **user_management:** Add user API keys and update ordering enum ([1bb2fe4](https://github.com/workos/workos-php/commit/1bb2fe4ba91575bc0db7eb056fd48f10c6653be1))
* **user_management:** Add user context to organization memberships ([1bb2fe4](https://github.com/workos/workos-php/commit/1bb2fe4ba91575bc0db7eb056fd48f10c6653be1))
* **vault:** Rename BYOK key provider enum and add vault key deleted event ([1bb2fe4](https://github.com/workos/workos-php/commit/1bb2fe4ba91575bc0db7eb056fd48f10c6653be1))


### Bug Fixes

* **ci:** install composer deps before building docs ([8f439e3](https://github.com/workos/workos-php/commit/8f439e3283f7f51cbe883f0871a28dc3142e6c9f))
* **events:** Add admin_portal to actor source enum ([1bb2fe4](https://github.com/workos/workos-php/commit/1bb2fe4ba91575bc0db7eb056fd48f10c6653be1))

## [5.2.1](https://github.com/workos/workos-php/compare/5.2.0...5.2.1) (2026-04-28)


### Bug Fixes

* **generated:** Add default values to optional object fields ([#376](https://github.com/workos/workos-php/issues/376)) ([1597af6](https://github.com/workos/workos-php/commit/1597af675fac63b5a309e163145e175f48b056dc))

## [5.2.0](https://github.com/workos/workos-php/compare/5.1.0...5.2.0) (2026-04-27)


### Features

* Add script/setup for pre-generation dependency installation ([edc2543](https://github.com/workos/workos-php/commit/edc25438e11031062f5458d4e183c4baaa77d4fb))
* surface error metadata from API responses ([#369](https://github.com/workos/workos-php/issues/369)) ([ece118b](https://github.com/workos/workos-php/commit/ece118b00a537f0b0b54161c2dafe106ff76ab13))


### Bug Fixes

* **generated:** update generated SDK from spec changes ([#372](https://github.com/workos/workos-php/issues/372)) ([0ee912a](https://github.com/workos/workos-php/commit/0ee912aeb14fb9e150aa1016b3b12a8cef5f48f5))

## [5.1.0](https://github.com/workos/workos-php/compare/5.0.3...5.1.0) (2026-04-20)


### Features

* add group, pipes, and authorization additive API support ([#367](https://github.com/workos/workos-php/issues/367)) ([c90a5d6](https://github.com/workos/workos-php/commit/c90a5d6ecaa887da7d0f5437269850f272cc5ba6))


### Bug Fixes

* redesign authorization resource targeting and default order handling ([b435ced](https://github.com/workos/workos-php/commit/b435cedbbaf7a2cf0bd2ef964e065de78e272750))
* Remove extractVersion from matchUpdateTypes rules ([#365](https://github.com/workos/workos-php/issues/365)) ([f4ad142](https://github.com/workos/workos-php/commit/f4ad142094c280ea57da9542d422c522dbee99bf))
* type tweaks ([780ee85](https://github.com/workos/workos-php/commit/780ee85f4bb338695196b4b5ea0073ec572d323c))


### Miscellaneous Chores

* **deps:** update minor and patch updates ([#361](https://github.com/workos/workos-php/issues/361)) ([7e439f7](https://github.com/workos/workos-php/commit/7e439f70afca166b64041ef218153f935de77c9d))

## [5.0.3](https://github.com/workos/workos-php/compare/5.0.2...5.0.3) (2026-04-14)


### Bug Fixes

* build redirect endpoint URLs locally instead of making HTTP requests ([#358](https://github.com/workos/workos-php/issues/358)) ([eae3949](https://github.com/workos/workos-php/commit/eae39490c79b2ab921d479a5b2686946b61a7b24))
* **docs:** add `[@throws](https://github.com/throws)` to PHPDoc when appropriate ([#360](https://github.com/workos/workos-php/issues/360)) ([ed68872](https://github.com/workos/workos-php/commit/ed68872bce0c62486f05890e464b1f8adfbdb6de))

## [5.0.2](https://github.com/workos/workos-php/compare/5.0.1...5.0.2) (2026-04-14)


### Bug Fixes

* slight rename ([496f75b](https://github.com/workos/workos-php/commit/496f75b3d1034d1dd68fb55eeb88333ec020b809))


### Miscellaneous Chores

* mark non-spec service accessors with `@oagen-ignore-start/end` ([#354](https://github.com/workos/workos-php/issues/354)) ([084d0d1](https://github.com/workos/workos-php/commit/084d0d125f7f7cfcf0cbc9530063009831c303a1))

## [5.0.1](https://github.com/workos/workos-php/compare/5.0.0...5.0.1) (2026-04-13)


### Bug Fixes

* add default User-Agent header and optional override ([#352](https://github.com/workos/workos-php/issues/352)) ([8e202db](https://github.com/workos/workos-php/commit/8e202db6920a4cd918ebbea44fd8006916968c1f))
* one more regen ([44906fd](https://github.com/workos/workos-php/commit/44906fdbf2263b9b7b8c075636d12bf2540064f8))

## [5.0.0](https://github.com/workos/workos-php/compare/4.32.0...5.0.0) (2026-04-13)

### Top-Level Notices

* v5 is a major SDK redesign centered on an instantiated `WorkOS` client with service accessors like `$workos->sso()`, `$workos->userManagement()`, and `$workos->authorization()`. Direct use of many legacy top-level service classes and transport internals has been removed or renamed.
* The minimum runtime is now PHP 8.2. The SDK now depends on `guzzlehttp/guzzle:^7`, `paragonie/halite:^5.1`, and `ext-curl:^8.2`.
* Responses and resources are now typed, generated models, pagination now uses `PaginatedResponse`, and named arguments are strongly recommended because many method signatures changed in v5.
* The v5 release also expands and reorganizes the SDK surface across areas like Authorization, Audit Logs, Feature Flags, Organization Domains, Connect, Events, Pipes, Radar, API Keys, Session Manager, PKCE, Webhooks, and Vault helpers.

### Migration Guide

* For upgrade steps, renamed APIs, and side-by-side examples for moving from v4 to v5, see [V5_MIGRATION_GUIDE](docs/V5_MIGRATION_GUIDE.md).

## [4.32.0](https://github.com/workos/workos-php/compare/4.31.0...4.32.0) (2026-03-09)


### Features

* Add accept invitation endpoint ([#343](https://github.com/workos/workos-php/issues/343)) ([36e9322](https://github.com/workos/workos-php/commit/36e93227cda5e81211bec2fad27a46b74dbc7ab0))


### Bug Fixes

* listEnvironmentRoles should not use PaginatedResource ([#341](https://github.com/workos/workos-php/issues/341)) ([52f0602](https://github.com/workos/workos-php/commit/52f0602921c4e985c281ad13d67b0a24e4db176c))

## [4.31.0](https://github.com/workos/workos-php/compare/4.30.1...4.31.0) (2026-03-06)


### Features

* Add directoryManaged to OrganizationMembership ([#334](https://github.com/workos/workos-php/issues/334)) ([1451af1](https://github.com/workos/workos-php/commit/1451af14f8429a3e23b0527ca2c1f6223f023add))
* Add RBAC environment roles API support ([#338](https://github.com/workos/workos-php/issues/338)) ([31fb41d](https://github.com/workos/workos-php/commit/31fb41da7435a03c1c1f416fd2f1bc5379bebf7c))
* Add RBAC permissions API support ([#337](https://github.com/workos/workos-php/issues/337)) ([dd7dca2](https://github.com/workos/workos-php/commit/dd7dca27d24424f632a78eb366d9bf51f3276409))
* **api:** Return state field for directory users ([#27](https://github.com/workos/workos-php/issues/27)) ([250887e](https://github.com/workos/workos-php/commit/250887e7fd33451f8a93b1b1335c72fa0555d7c3))
* Introduce a dedicated PaginatedResource object ([#316](https://github.com/workos/workos-php/issues/316)) ([a3ccfde](https://github.com/workos/workos-php/commit/a3ccfde78b5957313b25e5a66965ba993bcc0fba))
* **workos-php:** Add `connection` to `getAuthorizationUrl` ([#29](https://github.com/workos/workos-php/issues/29)) ([ade7019](https://github.com/workos/workos-php/commit/ade7019c5549a91bab0021420bcc9115eefeabee))


### Bug Fixes

* correct return types ([#340](https://github.com/workos/workos-php/issues/340)) ([0e9fb95](https://github.com/workos/workos-php/commit/0e9fb9517955c4348c99ec9a0f735a2990df9e12))
* update renovate rules ([#330](https://github.com/workos/workos-php/issues/330)) ([b6f9006](https://github.com/workos/workos-php/commit/b6f90064962258712971cd226718d34aa74f0c83))


### Miscellaneous Chores

* Add DX as Codeowners ([#335](https://github.com/workos/workos-php/issues/335)) ([5317c50](https://github.com/workos/workos-php/commit/5317c50746ccb6b59d75bcc982ef3396f687a394))
* **deps:** update actions/cache action to v5 ([#321](https://github.com/workos/workos-php/issues/321)) ([533ffa6](https://github.com/workos/workos-php/commit/533ffa67e1084c0e11b8071cc3185aac28f918a9))
* Pin GitHub Actions ([#332](https://github.com/workos/workos-php/issues/332)) ([8a1cc20](https://github.com/workos/workos-php/commit/8a1cc200e1ad06cde17e27a4ebab4a3fe9fe24b6))
