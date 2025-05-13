TARGET CODE:
<?php

namespace lib\UserManagement;

use lib\UserManagement\PasswordHashTypeInterface;

interface UpdateUserOptionsInterface
{
    public function getUserId(): string;

    public function getEmail(): ?string;

    public function getFirstName(): ?string;

    public function getLastName(): ?string;

    public function getEmailVerified(): ?bool;

    public function getPassword(): ?string;

    public function getPasswordHash(): ?string;

    public function getPasswordHashType(): ?PasswordHashTypeInterface;

    public function getExternalId(): ?string;
}

interface SerializedUpdateUserOptionsInterface
{
    public function getEmail(): ?string;

    public function getFirstName(): ?string;

    public function getLastName(): ?string;

    public function getEmailVerified(): ?bool;

    public function getPassword(): ?string;

    public function getPasswordHash(): ?string;

    public function getPasswordHashType(): ?PasswordHashTypeInterface;

    public function getExternalId(): ?string;
}

In PHP, interfaces are used to specify what methods a class must implement. In this case, the UpdateUserOptionsInterface and SerializedUpdateUserOptionsInterface interfaces are defined with getter methods for each property. The "?" before the type means that the return type can be that type or null. The PasswordHashTypeInterface is assumed to be in the same namespace and is used as a return type for getPasswordHashType() method.