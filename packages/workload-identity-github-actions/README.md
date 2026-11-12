# WorkOS workload identity for PHP: GitHub Actions

An identity token provider for [workos-php](https://github.com/workos/workos-php) that lets a workload running on GitHub Actions authenticate to WorkOS without a stored API key.

## Installation

```
composer require workos/workos-php-workload-identity-github-actions
```

## Usage

Give the workflow or job permission to request an OIDC token:

```yaml
permissions:
  id-token: write
```

Then build credentials from the provider, and the client from the credentials:

```php
use WorkOS\WorkloadIdentity\Client;
use WorkOS\WorkloadIdentity\Credentials;
use WorkOS\WorkloadIdentity\GitHubActions\GitHubActions;

$credentials = new Credentials(
    identityTokenProvider: GitHubActions::create('https://api.workos.com'),
);

$workos = Client::create($credentials);
```

The argument to `GitHubActions::create()` is the audience the token is requested for. It must match the audience of the application's trust rule.

`Credentials` reads the client ID, token URL and environment ID from `WORKOS_CLIENT_ID`, `WORKOS_WIF_TOKEN_URL` and `WORKOS_ENVIRONMENT_ID` when they are not passed. See [Workload identity](https://github.com/workos/workos-php#workload-identity) in the SDK's README for every setting.

## How it works

On each call the provider reads `ACTIONS_ID_TOKEN_REQUEST_URL` and `ACTIONS_ID_TOKEN_REQUEST_TOKEN`, which GitHub sets for jobs that have the permission, and requests a token for the audience. It keeps no cache and does not retry; the SDK calls it again for every exchange attempt.

## Development

This package is developed in the [workos-php](https://github.com/workos/workos-php) repository, in `packages/workload-identity-github-actions`. The `workos/workos-php-workload-identity-github-actions` repository is a read-only copy for Packagist, updated on each release. Open issues and pull requests against `workos-php`.

In `workos-php`, from the package's directory:

```
composer install
composer typecheck
composer test
```

`./script/ci` at the root of `workos-php` checks the SDK and every package.
