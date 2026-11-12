# WorkOS workload identity for PHP: AWS

An identity token provider for [workos-php](https://github.com/workos/workos-php) that lets a workload running on AWS authenticate to WorkOS without a stored API key.

## Installation

```
composer require workos/workos-php-workload-identity-aws
```

## Usage

Build credentials from the provider, and the client from the credentials:

```php
use WorkOS\WorkloadIdentity\Aws\AwsSts;
use WorkOS\WorkloadIdentity\Client;
use WorkOS\WorkloadIdentity\Credentials;

$credentials = new Credentials(
    identityTokenProvider: AwsSts::create('https://api.workos.com', 'us-east-1'),
);

$workos = Client::create($credentials);
```

The first argument to `AwsSts::create()` is the audience the token is requested for. It must match the audience of the application's trust rule. The second is the AWS region whose STS endpoint to call; leave it out when the AWS SDK can resolve one from `AWS_REGION` or the shared config file.

`Credentials` reads the client ID, token URL and environment ID from `WORKOS_CLIENT_ID`, `WORKOS_WIF_TOKEN_URL` and `WORKOS_ENVIRONMENT_ID` when they are not passed. See [Workload identity](https://github.com/workos/workos-php#workload-identity) in the SDK's README for every setting.

## Requirements

- Outbound identity federation is enabled for the AWS account.
- The workload's IAM principal is allowed `sts:GetWebIdentityToken`.
- The workload has AWS credentials the AWS SDK's default credential chain can find, such as an instance profile, an ECS task role or an EKS pod identity.

## How it works

On each call the provider calls STS `GetWebIdentityToken` on the region's own endpoint and asks for a token signed with `RS256`. It keeps no cache and does not retry; the SDK calls it again for every exchange attempt.

## Development

This package is developed in the [workos-php](https://github.com/workos/workos-php) repository, in `packages/workload-identity-aws`. The `workos/workos-php-workload-identity-aws` repository is a read-only copy for Packagist, updated on each release. Open issues and pull requests against `workos-php`.

In `workos-php`, from the package's directory:

```
composer install
composer typecheck
composer test
```

`./script/ci` at the root of `workos-php` checks the SDK and every package.
