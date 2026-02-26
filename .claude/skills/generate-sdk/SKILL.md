---
name: generate-sdk
description: Generate a complete PHP SDK (models, services, types, tests) from an OpenAPI specification
arguments:
  - name: spec_path
    description: Path to the YAML OpenAPI specification file
    required: true
---

# /generate-sdk

Generate a complete PHP SDK from an OpenAPI specification.

> **Design Reference**: Follow all patterns in [SDK_DESIGN.md](../../SDK_DESIGN.md)

## Input

The user will provide a path to a YAML OpenAPI specification file.

## CRITICAL: Complete Coverage Requirement

**Generate files for EVERY schema and EVERY path group. Do not skip any items.**

## Execution Steps

### Step 0: Extract Complete Inventory (REQUIRED FIRST)

Before writing any code:

1. **Extract ALL schema names** from `#/components/schemas`
2. **Extract ALL path groups** from `paths` (group by first segment)
3. **Display inventory** before proceeding:

```
=== INVENTORY ===
Schemas (47): ValidateApiKeyDto, BaseCreateApplicationDto, ...
Path groups (18): api_keys, audit_logs, connect, ...

Will generate 47 resources and 18 services.
```

### Step 1: Create Directory Structure

```
lib/
├── Exception/
├── HttpClient/
├── Resource/
└── Service/
tests/
├── fixtures/
└── Service/
```

### Step 2: Generate Resources (Models)

For EACH schema in inventory:

- PHP resource class in `lib/Resource/`
- Follow BaseResource pattern from SDK_DESIGN.md

Use `/generate-sdk-models` patterns.

### Step 3: Generate Services

For EACH path group in inventory:

- PHP service class in `lib/Service/`
- Include `idempotency_key` option for POST methods

Use `/generate-sdk-resources` patterns.

### Step 4: Generate Infrastructure

Generate client infrastructure per SDK_DESIGN.md:

- `lib/WorkOS.php` - Global configuration class
- `lib/WorkOSClient.php` - Client with retry logic
- `lib/Collection.php` - Pagination wrapper
- `lib/ApiResponse.php` - Response metadata
- `lib/Webhook.php` - Webhook verification
- `lib/Exception/*.php` - Exception class hierarchy

### Step 5: Generate Tests

For EACH service:

- Test file in `tests/Service/`
- Fixtures in `tests/fixtures/`
- Include CRUD, error, retry, and idempotency tests

Use `/generate-sdk-tests` patterns.

### Step 6: Update Autoloading

Update `composer.json` autoload section:

```json
{
    "autoload": {
        "psr-4": {
            "WorkOS\\": "lib/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "WorkOS\\Tests\\": "tests/"
        }
    }
}
```

### Step 7: Verification

```
=== VERIFICATION ===
Inventory:  47 schemas, 18 path groups
Resources:  47 files ✓
Services:   18 files ✓
Tests:      18 files ✓

All counts match. Generation complete.
```

## Output Summary

```
SDK Generation Complete!

Resources (47):
- lib/Resource/Organization.php
- lib/Resource/User.php
...

Services (18):
- lib/Service/OrganizationService.php
...

Infrastructure:
- lib/WorkOS.php (global config)
- lib/WorkOSClient.php (client with retry logic)
- lib/Collection.php (pagination)
- lib/Exception/*.php (error classes)

Tests (18):
- tests/Service/OrganizationServiceTest.php
...

Next Steps:
1. composer dump-autoload
2. ./vendor/bin/phpcs
3. ./vendor/bin/phpunit
```

## Error Handling

- **Missing fields**: Log warning, generate stub (don't skip)
- **Circular refs**: Handle with lazy loading if needed
- **Invalid spec**: Report error and stop

Never silently skip schemas or paths.
