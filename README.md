# workos-php

PHP SDK to conveniently access the [WorkOS API](https://workos.com).

## Installation

To install via composer, run the following:
```
composer require workos/workos-php
```

## Getting Started

The package will need to be configured with your [api key](https://dashboard.workos.com/api-keys) and [project id](https://dashboard.workos.com/sso/configuration). By default, the packages looks for a `WORKOS_API_KEY` and `WORKOS_PROJECT_ID` environment variable.

### SSO
The package offers the following convenience functions to utilize WorkOS SSO.

First we'll want to generate an OAuth 2.0 Authorization URL to initiate the SSO workflow with:

```php
$url = (new \WorkOS\SSO())->getAuthorizationUrl(
    "foo-corp.com",
    "http://my.cool.co/auth/callback",
    ["things" => "gonna get this back"],
    null
);
```

After directing the user to the Authorization URL and successfully completing the SSO workflow, use 
the code passed back from WorkOS to grab the profile of the authenticated user to verify all is good:

```php
$profile = (new \WorkOS\SSO())->getProfile($code);
```

### Audit Trail
Creating an Audit Trail event requires a descriptive action name and annotating the event with its CRUD identifier. The action name must contain an action category and an action name separated by a period, for example, user.login.

Creating an Audit Trail event in WorkOS is as simple as running the following:

```php
$now = (new \DateTime())->format(\DateTime::ISO8601);

$event = [
    "group" => "organization_1",
    "action" => "user.login",
    "action_type" => "C",
    "actor_name" => "user@email.com",
    "actor_id" => "user_1",
    "target_name" => "user@email.com",
    "target_id" => "user_1",
    "location" =>  "1.1.1.1",
    "occurred_at" => $now,
];

(new \WorkOS\AuditTrail())->createEvent($event);
```
