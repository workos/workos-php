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
        "oauthUid",
        "oauthSecret",
        "oauthRedirectUri",
        "samlEntityId",
        "samlIdpUrl",
        "samlRelyingPartyTrustCert",
        "samlX509Certs"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "status" => "status",
        "name" => "name",
        "connection_type" => "connectionType",
        "oauth_uid" => "oauthUid",
        "oauth_secret" => "oauthSecret",
        "oauth_redirect_uri" => "oauthRedirectUri",
        "saml_entity_id" => "samlEntityId",
        "saml_idp_url" => "samlIdpUrl",
        "saml_relying_party_trust_cert" => "samlRelyingPartyTrustCert",
        "saml_x509_certs" => "samlX509Certs"
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
