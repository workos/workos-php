# WorkOS workload identity for PHP: GCP

An identity token provider for [workos-php](https://github.com/workos/workos-php) that lets a workload running on GCP authenticate to WorkOS without a stored API key.

## Installation

```
composer require workos/workos-php-workload-identity-gcp
```

## Usage

Build credentials from the provider, and the client from the credentials:

```php
use WorkOS\WorkloadIdentity\Client;
use WorkOS\WorkloadIdentity\Credentials;
use WorkOS\WorkloadIdentity\Gcp\GcpMetadata;

$credentials = new Credentials(
    identityTokenProvider: GcpMetadata::create('https://api.workos.com'),
);

$workos = Client::create($credentials);
```

The argument to `GcpMetadata::create()` is the audience the token is requested for. It must match the audience of the application's trust rule.

`Credentials` reads the client ID, token URL and environment ID from `WORKOS_CLIENT_ID`, `WORKOS_WIF_TOKEN_URL` and `WORKOS_ENVIRONMENT_ID` when they are not passed. See [Workload identity](https://github.com/workos/workos-php#workload-identity) in the SDK's README for every setting.

## How it works

On each call the provider asks the GCP metadata server for an identity token for the workload's default service account. It works wherever the metadata server is reachable: Compute Engine, GKE, Cloud Run, Cloud Functions and App Engine. It keeps no cache and does not retry; the SDK calls it again for every exchange attempt.

## Development

This package is developed in the [workos-php](https://github.com/workos/workos-php) repository, in `packages/workload-identity-gcp`. The `workos/workos-php-workload-identity-gcp` repository is a read-only copy for Packagist, updated on each release. Open issues and pull requests against `workos-php`.

In `workos-php`, from the package's directory:

```
composer install
composer typecheck
composer test
```

`./script/ci` at the root of `workos-php` checks the SDK and every package.
