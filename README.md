# WorkOS PHP Library

![Packagist Version](https://img.shields.io/packagist/v/workos/workos-php)
[![Build Status](https://workos.semaphoreci.com/badges/workos-php/branches/master.svg?style=shields)](https://workos.semaphoreci.com/projects/workos-php)

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

## SDK Versioning

For our SDKs WorkOS follows a Semantic Versioning process where all releases will have a version X.Y.Z (like 1.0.0) pattern wherein Z would be a bug fix (I.e. 1.0.1), Y would be a minor release (1.1.0) and X would be a major release (2.0.0). We permit any breaking changes to only be released in major versions and strongly recommend reading changelogs before making any major version upgrades.

## More Information

- [Single Sign-On Guide](https://workos.com/docs/sso/guide)
- [Directory Sync Guide](https://workos.com/docs/directory-sync/guide)
- [Admin Portal Guide](https://workos.com/docs/admin-portal/guide)
- [Magic Link Guide](https://workos.com/docs/magic-link/guide)
