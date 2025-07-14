<?php

namespace WorkOS\Resource;

/**
 * Class UserIdentityProviderType
 *
 * This class represents the type of user identity provider.
 */
class UserIdentityProviderType
{
    public const OAuth = 'OAuth';

    private $type;

    /**
     * Constructor for UserIdentityProviderType.
     *
     * @param string $type The type of user identity provider.
     */
    public function __construct($type)
    {
        // Map of lowercase API response values to our standardized constants
        $typeMap = [
            'oauth' => self::OAuth,
            // Add future types here in the format: 'lowercase_value' => self::ConstantName
        ];

        $lowercaseType = strtolower($type);

        // Use our mapped constant if available, otherwise keep the original value
        $this->type = isset($typeMap[$lowercaseType]) ? $typeMap[$lowercaseType] : $type;
    }

    public function __toString()
    {
        return $this->type;
    }
}
