TARGET CODE:
<?php

namespace Lib\UserManagement;

use Lib\UserManagement\Interfaces\UpdateUserOptions;
use Lib\UserManagement\Interfaces\SerializedUpdateUserOptions;

class UpdateUserOptionsSerializer
{
    public static function serializeUpdateUserOptions(UpdateUserOptions $options): SerializedUpdateUserOptions
    {
        return new SerializedUpdateUserOptions([
            'email' => $options->getEmail(),
            'email_verified' => $options->getEmailVerified(),
            'first_name' => $options->getFirstName(),
            'last_name' => $options->getLastName(),
            'password' => $options->getPassword(),
            'password_hash' => $options->getPasswordHash(),
            'password_hash_type' => $options->getPasswordHashType(),
            'external_id' => $options->getExternalId(),
        ]);
    }
}

NOTES:
1. PHP does not have direct support for JavaScript's object literal syntax. Instead, we use an associative array to represent the serialized options.
2. In PHP, we typically use getter methods to access object properties, assuming that the `UpdateUserOptions` class follows the common practice of encapsulating its properties with getter and setter methods.
3. The `serializeUpdateUserOptions` function is made static, as it does not depend on any instance-specific data.
4. The `SerializedUpdateUserOptions` is assumed to be a class that accepts an associative array in its constructor. If this is not the case, the implementation of this function may need to be adjusted accordingly.