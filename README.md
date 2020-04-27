# workos-php

PHP SDK to conveniently access the [WorkOS API](https://workos.com).

For more information on our API and WorkOS, check out our docs [here](https://docs.workos.com).

## Installation

To install via composer, run the following:
```
composer require workos/workos-php
```

## Getting Started

The package will need to be configured with your [API Key](https://dashboard.workos.com/api-keys) and [Project ID](https://dashboard.workos.com/sso/configuration). By default, the packages looks for a `WORKOS_API_KEY` and `WORKOS_PROJECT_ID` environment variable.

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

### Directory Sync
The WorkOS PHP SDK allow you to list configured Directories, Directory Groups and Directory Users.

To list Directories:
```php
list($before, $after, $directories) = (new \WorkOS\DirectorySync())->listDirectories();
```

To list Directory Groups:
```php
list($before, $after, $groups) = (new \WorkOS\DirectorySync())->listGroups();
```

To get a Directory Group:
```php
$user = (new \WorkOS\DirectorySync())->getGroup("directory_grp_id");
```

To list Directory Users:
```php
list($before, $after, $users) = (new \WorkOS\DirectorySync())->listUsers();
```

To get a Directory User:
```php
$user = (new \WorkOS\DirectorySync())->getUser("directory_user_id");
```
