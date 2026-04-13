# Changelog

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
