# WorkOS PHP Library

![Packagist Version](https://img.shields.io/packagist/v/workos/workos-php)
[![CI](https://github.com/workos/workos-php/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/workos/workos-php/actions/workflows/ci.yml)

The WorkOS library for PHP provides convenient access to the WorkOS API from applications written in PHP.

## Documentation

See the [API Reference](https://workos.com/docs/reference/client-libraries) for PHP usage examples.

## Installation

To install via composer, run the following:

```
composer require workos/workos-php
```

## Configuration

The package will need to be configured with your [API Key](https://dashboard.workos.com/api-keys) and [Client ID](https://dashboard.workos.com/configuration). By default, the packages looks for a `WORKOS_API_KEY` and `WORKOS_CLIENT_ID` environment variable.

## Retries and idempotency

The SDK retries requests that fail with a connection error or a `429`, `500`, `502`, `503`, or `504` response. Every `POST` carries an `Idempotency-Key`: the one you provide, or one the SDK generates before the first attempt and reuses on every retry. Provide your own key through `RequestOptions`:

```php
$workos->pipes()->createDataIntegrationApiKey(
    slug: 'github',
    userId: 'user_01EHZNVPK3SFK441A1RGBFSHRT',
    secret: 'sk-1234567890abcdef',
    connectionIntent: 'add',
    options: new \WorkOS\RequestOptions(idempotencyKey: '0b6c3f0e-7a51-4c3a-9d8e-2f1b5a6c7d80'),
);
```

> [!NOTE]
> The WorkOS API currently honors `Idempotency-Key` only on [Create Audit Log Event](https://workos.com/docs/reference/audit-logs/event) and on Pipes explicit adds (`createDataIntegrationApiKey`, `createDataIntegrationClientCredential`, and `createUserConnectedAccount` / `createOrganizationConnectedAccount` with `connectionIntent: 'add'`). Other endpoints accept the header but do not deduplicate requests, so a retried mutation elsewhere can still create a duplicate.

## Workload identity

A workload that runs on GitHub Actions, GCP or AWS can authenticate to WorkOS with the identity token its platform issues, so it needs no stored API key. The SDK exchanges that token for a short-lived WorkOS access token, caches it in memory, and replaces it before it expires.

Build credentials from an identity token provider, then build the client from the credentials:

```php
use WorkOS\WorkloadIdentity\Client;
use WorkOS\WorkloadIdentity\Credentials;
use WorkOS\WorkloadIdentity\GitHubActions\GitHubActions;

$credentials = new Credentials(
    identityTokenProvider: GitHubActions::create('https://api.workos.com'),
    clientId: 'client_01EHZNVPK3SFK441A1RGBFSHRT',
    tokenUrl: 'https://auth.example.com/oauth2/token',
    environmentId: 'environment_01EHZNVPK3SFK441A1RGBFSHRT',
);

$workos = Client::create($credentials);
$organizations = $workos->organizations()->listOrganizations();
```

| Argument | Meaning | Default |
| --- | --- | --- |
| `identityTokenProvider` | Returns the workload's identity token | Required |
| `clientId` | The M2M application's client ID | `WORKOS_CLIENT_ID` |
| `tokenUrl` | The token exchange endpoint. It must be an `https` URL | `WORKOS_WIF_TOKEN_URL` |
| `environmentId` | Optional. The environment the application is bound to, sent as a safety check | `WORKOS_ENVIRONMENT_ID`, otherwise not sent |
| `resource` | The API the access token is for | `https://api.workos.com` |
| `scope` | The scopes to request | `WORKOS_WIF_SCOPE`, otherwise none |

`clientId` and `tokenUrl` are required: constructing the credentials throws when one is missing from both the arguments and the environment.

`environmentId` is optional. WorkOS binds a workload identity client to exactly one environment and takes the environment from that binding, so the ID selects nothing. When it is set, the SDK sends it as the `x-url-environment-id` header on every API request, as a safety check: a value that names a different environment gets `403 environment_mismatch`. When it is not set, or is empty, the header is not sent.

### Providers

Each platform's provider is a separate package, developed in this repository under [`packages/`](packages). The argument is the audience the token is requested for, which must match the application's trust rule.

```
composer require workos/workos-php-workload-identity-github-actions
composer require workos/workos-php-workload-identity-gcp
composer require workos/workos-php-workload-identity-aws
```

```php
// GitHub Actions. The workflow or job needs `permissions: id-token: write`.
use WorkOS\WorkloadIdentity\GitHubActions\GitHubActions;

$provider = GitHubActions::create('https://api.workos.com');
```

```php
// GCP, wherever the metadata server is reachable: Compute Engine, GKE, Cloud Run.
use WorkOS\WorkloadIdentity\Gcp\GcpMetadata;

$provider = GcpMetadata::create('https://api.workos.com');
```

```php
// AWS. The IAM principal needs `sts:GetWebIdentityToken`. The region is
// optional when the AWS SDK can resolve one.
use WorkOS\WorkloadIdentity\Aws\AwsSts;

$provider = AwsSts::create('https://api.workos.com', 'us-east-1');
```

For any other source, use a file, an environment variable or your own callable. All three are read again on every exchange, so a rotated token is picked up:

```php
use WorkOS\WorkloadIdentity\TokenEnv;
use WorkOS\WorkloadIdentity\TokenFile;

// A file, such as a projected Kubernetes service account token.
$provider = TokenFile::create('/var/run/secrets/tokens/workos');

// An environment variable.
$provider = TokenEnv::create('WORKLOAD_IDENTITY_TOKEN');

// Any callable that returns the token.
$provider = fn (): string => $vault->read('workos/identity-token');
```

WorkOS accepts an identity token for one exchange. A long-running process exchanges again about every four minutes, and under PHP-FPM every web request that calls WorkOS makes its own exchange. If the file or the variable still holds the same token at the next exchange, that exchange fails with `invalid_grant`. So use `TokenFile` and `TokenEnv` for a source that something rewrites before each exchange, or for a job shorter than five minutes. The platform providers (GitHub Actions, GCP and AWS) fetch a new token every time.

### Behavior

- Workload identity is used only when you build credentials. No environment variable switches it on, and a client built from credentials never sends `WORKOS_API_KEY`.
- The access token is cached on the `Credentials` object, for as long as that object lives. A long-running worker reuses it across requests. Under PHP-FPM the object lives for one web request, so each web request that calls WorkOS makes one exchange.
- When the API answers `401`, the SDK exchanges for a new access token and retries the request once.
- The access token and the environment ID go only to the origin the request is addressed to. When a redirect leads to another origin, the SDK follows it without them.
- A failure to get an access token throws `WorkOS\Exception\WorkloadIdentityException`. Its `errorCode` is the OAuth error code from the token endpoint (such as `invalid_grant`), `hint` says what to check, and `description` is the server's own text. A failing exchange never falls back to an API key.
- Operations that send the API key itself, such as `userManagement()->authenticateWithCode()`, are not available on a client built from credentials. They throw `WorkOS\Exception\ConfigurationException`.

## SDK Versioning

For our SDKs WorkOS follows a Semantic Versioning ([SemVer](https://semver.org/)) process where all releases will have a version X.Y.Z (like 1.0.0) pattern wherein Z would be a bug fix (e.g., 1.0.1), Y would be a minor release (1.1.0) and X would be a major release (2.0.0). We permit any breaking changes to only be released in major versions and strongly recommend reading changelogs before making any major version upgrades.

## Beta Releases

WorkOS has features in Beta that can be accessed via Beta releases. We would love for you to try these
and share feedback with us before these features reach general availability (GA). To install a Beta version,
please follow the [installation steps](#installation) above using the Beta release version.

> Note: there can be breaking changes between Beta versions. Therefore, we recommend pinning the package version to a
> specific version. This way you can install the same version each time without breaking changes unless you are
> intentionally looking for the latest Beta version.

We highly recommend keeping an eye on when the Beta feature you are interested in goes from Beta to stable so that you
can move to using the stable version.

## More Information

- [Single Sign-On Guide](https://workos.com/docs/sso/guide)
- [Directory Sync Guide](https://workos.com/docs/directory-sync/guide)
- [Admin Portal Guide](https://workos.com/docs/admin-portal/guide)
- [Magic Link Guide](https://workos.com/docs/magic-link/guide)
