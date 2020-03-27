<?php

namespace WorkOS;

class SSO
{
    const PATH_AUTHORIZATION = "sso/authorize";
    const PATH_PROFILE = "sso/token";

    const PROVIDER_ADFS_SAML = "ADFSSAML";
    const PROVIDER_AZURE_SAML = "AzureSAML";
    const PROVIDER_GOOGLE_OAUTH = "GoogleOAuth";
    const PROVIDER_OKTA_SAML = "OktaSAML";

    private static $instance;

    private function __construct()
    {
        \WorkOS\Util\Validator::validateSettings(\WorkOS\Util\Validator::MODULE_SSO);
    }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getAuthorizationUrl($domain, $redirectUri, $state, $provider)
    {
        if (!isset($domain) && !isset($provider)) {
            $msg = "Either \$domain or \$provider is required";

            throw new Exception\UnexpectedValueException($msg);
        }

        $params = [
            "client_id" => \WorkOS\WorkOS::getProjectId(),
            "response_type" => "code"
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

        return \WorkOS\Util\Curl::generateUrl(self::PATH_AUTHORIZATION, $params);
    }

    public function getProfile($code)
    {
        $params = [
            "client_id" => \WorkOS\WorkOS::getProjectId(),
            "client_secret" => \WorkOS\WorkOS::getApikey(),
            "code" => $code,
            "grant_type" => "authorization_code"
        ];

        return \WorkOS\Util\Curl::request(\WorkOS\Util\Curl::METHOD_TYPE_POST, self::PATH_PROFILE, $params);
    }
}
