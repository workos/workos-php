<?php

namespace Lib\UserManagement\Interfaces;

interface UpdateUserOptionsInterface
{
    public function getUserId(): string;
    public function getEmail(): ?string;
    public function getFirstName(): ?string;
    public function getLastName(): ?string;
    public function isEmailVerified(): ?bool;
    public function getPassword(): ?string;
    public function getPasswordHash(): ?string;
    public function getPasswordHashType(): ?PasswordHashType;
    public function getExternalId(): ?string;
}

interface SerializedUpdateUserOptionsInterface
{
    public function getEmail(): ?string;
    public function getFirstName(): ?string;
    public function getLastName(): ?string;
    public function isEmailVerified(): ?bool;
    public function getPassword(): ?string;
    public function getPasswordHash(): ?string;
    public function getPasswordHashType(): ?PasswordHashType;
    public function getExternalId(): ?string;
}