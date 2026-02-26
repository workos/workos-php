---
name: generate-sdk-models
description: Generate PHP resource (model) classes from OpenAPI schemas for the WorkOS PHP SDK
arguments:
  - name: spec_path
    description: Path to the YAML OpenAPI specification file
    required: true
---

# /generate-sdk-models

Generate PHP resource (model) classes from OpenAPI schemas for the WorkOS PHP SDK.

> **Design Reference**: Follow all patterns in [SDK_DESIGN.md](../../SDK_DESIGN.md)

## Input

The user will provide a path to a YAML OpenAPI specification file.

## Instructions

1. **Read the OpenAPI spec** at the provided path
2. **Extract all schemas** from `#/components/schemas`
3. **For each schema**, generate a PHP resource class in `lib/Resource/`

## Quick Reference

### Naming (see SDK_DESIGN.md for full table)

- Response types: Use resource name directly (`Organization`)
- Input types: Use `{Resource}CreateParams` or `{Resource}UpdateParams`
- Convert DTO: `CreateWidgetDto` → `WidgetCreateParams`

### Type Mapping (see SDK_DESIGN.md for full table)

| OpenAPI Type              | PHP Type        | PHPDoc Type                |
| ------------------------- | --------------- | -------------------------- |
| `string`                  | `string`        | `string`                   |
| `string` + `date-time`    | `\DateTime`     | `\DateTime`                |
| `string` + `enum`         | `string`        | `string` (use const class) |
| `integer`                 | `int`           | `int`                      |
| `boolean`                 | `bool`          | `bool`                     |
| `array`                   | `array`         | `Type[]`                   |
| `$ref`                    | `Resource`      | `\WorkOS\Resource\Name`    |

### Property Declarations

```php
// Required property
public string $id;

// Optional/nullable property
public ?string $externalId = null;

// DateTime property
public \DateTime $createdAt;

// Nested object
public ?Organization $organization = null;

// Array of objects
/** @var Domain[] */
public array $domains = [];
```

## Output Template

### PHP Resource (`lib/Resource/{Name}.php`)

```php
<?php

namespace WorkOS\Resource;

/**
 * {Description from OpenAPI}
 *
 * @property-read string $id The unique identifier.
 * @property-read string $name The display name.
 * @property-read string|null $externalId An external identifier.
 */
class Organization extends BaseResource
{
    /**
     * The organization's unique identifier.
     */
    public string $id;

    /**
     * The organization's display name.
     */
    public string $name;

    /**
     * An external identifier for the organization.
     */
    public ?string $externalId = null;

    /**
     * The organization's state.
     */
    public string $state;

    /**
     * When the organization was created.
     */
    public \DateTime $createdAt;

    /**
     * When the organization was last updated.
     */
    public \DateTime $updatedAt;

    /**
     * The organization's domains.
     *
     * @var Domain[]
     */
    public array $domains = [];

    protected function populateFromResponse(array $data): void
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->externalId = $data['external_id'] ?? null;
        $this->state = $data['state'] ?? 'active';
        $this->createdAt = new \DateTime($data['created_at']);
        $this->updatedAt = new \DateTime($data['updated_at']);

        if (isset($data['domains'])) {
            $this->domains = array_map(
                fn($d) => Domain::constructFromResponse($d),
                $data['domains']
            );
        }
    }
}
```

### Enum Constants Class

For enum types, generate a separate constants class:

```php
<?php

namespace WorkOS\Resource;

/**
 * Organization state constants.
 */
final class OrganizationState
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const PENDING = 'pending';

    /**
     * Get all valid values.
     *
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::PENDING,
        ];
    }
}
```

## Output

For each schema:
1. Create `lib/Resource/{PascalCaseName}.php`
2. If schema has enum fields, create `lib/Resource/{PascalCaseName}{FieldName}.php` constants class
3. Add to any necessary autoload configurations
