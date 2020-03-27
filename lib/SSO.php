<?php

namespace WorkOS;

class SSO
{
    const PATH_AUTHORIZATION = "sso/authorize";

    const PROVIDER_ADFS_SAML = "ADFSSAML";
    const PROVIDER_AZURE_SAML = "AzureSAML";
    const PROVIDER_GOOGLE_OAUTH = "GoogleOAuth";
    const PROVIDER_OKTA_SAML = "OktaSAML";

    public function __construct()
    {
        \WorkOS\Util\Validator::validateSettings(\WorkOS\Util\Validator::MODULE_SSO);
    }

    public function getAuthorizationUrl($domain, $redirectUri, $state, $provider)
    {
        if (!isset($domain) && !isset($provider)) {
            $msg = "Either \$domain or \$provider is required";

            throw new Exception\UnexpectedValueException($msg);
        }

        $params = [
            "client_id" => \WorkOS\WorkOS::getProjectId(),
            "response_type" => \WorkOS\Util\Request::RESPONSE_TYPE_CODE
        ];

        if (isset($domain)) {
            $params["domain"] = $domain;
        }

        if (isset($redirectUri)) {
            $params["redirect_uri"] = $redirectUri;
        }

        if (isset($state) and !empty($state)) {
            $params["state"] = $state;
        }

        if (isset($provider)) {
            $params["provider"] = $provider;
        }

        $queryParams = http_build_query($params);
        return \WorkOS\WorkOS::getApiBaseURL() . self::PATH_AUTHORIZATION . "?${queryParams}";
    }

    public function getProfile()
    {
    }
}
