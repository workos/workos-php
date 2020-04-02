 # workos-php

PHP SDK to conveniently access the [WorkOS API](https://workos.com).

## Installation

To install via composer, run the following:
```
composer require workos/workos-php
```

## Getting Started

The package will need to be configured with your [api key](https://dashboard.workos.com/api-keys) and [project id](https://dashboard.workos.com/sso/configuration):

```php
\WorkOS\WorkOS::setApiKey('sk_123secret456');
\WorkOS\WorkOS::setProjectId('project_456demo789');
```

### SSO
The package offers the following convenience functions to utilize WorkOS SSO.

First we'll want to generate an OAuth 2.0 authorization URL to initiate the SSO workflow with:

```php
$url = \WorkOS\SSO::instance()->getAuthorizationUrl(
    'foo-corp.com',
    'http://my.cool.co/auth/callback',
    ['things' => 'gonna get this back'],
    null // Pass along provider if we don't have a domain
);
```

After directing the user to the authorization url and successfully completing the SSO workflow, use 
the code passed back from WorkOS to grab the profile of the authenticated user to verify all is good:

```php
$profile = \WorkOS\SSO::instance()->getProfile($code);
```
