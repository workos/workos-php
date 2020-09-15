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
To generate an OAuth 2.0 Authorization URL to initiate the SSO workflow with:

```php
$url = (new \WorkOS\SSO())->getAuthorizationUrl(
    "foo-corp.com",
    "http://my.cool.co/auth/callback",
    ["things" => "gonna get this back"],
    null
);
```

Using the code provided by WorkOS after going through the OAuth 2.0 workflow, grab the profile of the
authenticated user to verify all is good:

```php
$profile = (new \WorkOS\SSO())->getProfile($code);
```

To create a connection using the token passed back from the WorkOS.js embed.

```php
$connection = $sso->createConnection($token);
```

### Audit Trail
To create an Audit Trail event in WorkOS:

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

To get Audit Trail events:

```php
list($before, $after, $events) = (new \WorkOS\AuditTrail())->getEvents());
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

### Admin Portal
The WorkOS PHP SDK allows you to list configured Organizations.

To list Organizations:
```php
list($before, $after, $organizations) = (new \WorkOS\Portal())->listOrganizations();
```