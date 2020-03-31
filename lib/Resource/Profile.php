<?php

namespace WorkOS\Resource;

/**
 * Class Profile.
 * 
 * Representation of a WorkOS Profile.
 */
class Profile
{
    /**
     * @var string $id
     */
    public $id;

    /**
     * @var string $email
     */
    public $email;

    /**
     * @var string $firstName
     */
    public $firstName;

    /**
     * @var string $lastName
     */
    public $lastName;

    /**
     * @var \WorkOS\Resource\ConnectionType $connectionType
     */
    public $connectionType;

    /**
     * @var string $idpId
     */
    public $idpId;

    const RESOURCE_ATTRIBUTES = [
        "id",
        "email",
        "firstName",
        "lastName",
        "connectionType",
        "idpId"
    ];

    const RESOURCE_TYPE = "profile";

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "email" => "email",
        "first_name" => "firstName",
        "last_name" => "lastName",
        "connection_type" => "connectionType",
        "idp_id" => "idpId"
    ];

    private function __construct()
    {
    }

    /**
     * Creates a Profile from a Rseponse.
     * 
     * @param \WorkOS\Resource\Response $response
     * 
     * @return \WorkOS\Resource\Profile
     */
    public static function constructFromResponse($response)
    {
        $instance = new self();
        $responseJson = $response->json();
        $profileJson = $responseJson[self::RESOURCE_TYPE];

        foreach (self::RESPONSE_TO_RESOURCE_KEY as $responseKey => $resourceKey) {
            $instance->{$resourceKey} = $profileJson[$responseKey];
        }

        return $instance;
    }

    public function toArray()
    {
        return array_reduce(self::RESOURCE_ATTRIBUTES, function ($arr, $key) {
            $arr[$key] = $this->{$key};
            return $arr;
        }, []);
    }
}
