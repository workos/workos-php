<?php

namespace WorkOS;

class SSO
{
    public function __construct()
    {
        \WorkOS\Util\Validator::validateSettings(\WorkOS\Util\Validator::MODULE_SSO);
    }

    public function getAuthorizationUrl()
    {
    }

    public function getProfile()
    {
    }
}
