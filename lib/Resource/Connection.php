<?php

namespace WorkOS\Resource;

/**
 * Class Connection.
 */
class Connection extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "connection";

    const RESOURCE_ATTRIBUTES = [
        "id",
        "domains",
        "status",
        "name",
        "connectionType",
        "oidcClientId",
        "oidcClientSecret",
        "oidcDiscoveryEndpoint",
        "oidcRedirectUri",
        "samlEntityId",
        "samlIdpUrl",
        "samlRelyingPartyPrivateKey",
        "samlRelyingPartyPublicKey",
        "samlX509Certs",
        "organizationId",
        "oauthUid",
        "oauthSecret",
        "oauthRedirectUri"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "status" => "status",
        "name" => "name",
        "connection_type" => "connectionType",
        "oidc_client_id" => "oidcClientId",
        "oidc_client_secret" => "oidcClientSecret",
        "oidc_discovery_endpoint" => "oidcDiscoveryEndpoint",
        "oidc_redirect_uri" => "oidcRedirectUri",
        "saml_entity_id" => "samlEntityId",
        "saml_idp_url" => "samlIdpUrl",
        "saml_relying_party_private_key" => "samlRelyingPartyPrivateKey",
        "saml_relying_party_public_key" => "samlRelyingPartyPublicKey",
        "saml_x509_certs" => "samlX509Certs",
        "organization_id" => "organizationId",
        "oauth_uid" => "oauthUid",
        "oauth_secret" => "oauthSecret",
        "oauth_redirect_uri" => "oauthRedirectUri"
    ];

    public static function constructFromResponse($response)
    {
        $instance = parent::constructFromResponse($response);

        $rawDomains = $response["domains"];
        $domains = [];
        foreach ($rawDomains as $rawDomain) {
            \array_push($domains, Domain::constructFromResponse($rawDomain));
        }

        $instance->values["domains"] = $domains;

        return $instance;
    }

    public function toArray()
    {
        $connectionArray = parent::toArray();

        $domains = [];
        foreach ($connectionArray["domains"] as $domain) {
            \array_push($domains, $domain->toArray());
        }

        $connectionArray["domains"] = $domains;

        return $connectionArray;
    }
}
