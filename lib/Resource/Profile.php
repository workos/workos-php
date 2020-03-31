<?php

namespace WorkOS\Resource;

class Profile
{
    public $id;

    public $email;

    public $firstName;

    public $lastName;

    public $connectionType;

    public $idpId;

    private const RESOURCE_ATTRIBUTES = [
        "id",
        "email",
        "firstName",
        "lastName",
        "connectionType",
        "idpId"
    ];

    private const RESOURCE_TYPE = 'profile';

    private const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "email" => "email",
        "first_name" => "firstName",
        "last_name" => "lastName",
        "connection_type" => "connectionType",
        "idp_id" => "idpId"
    ];

    private $values;

    private function __construct()
    {
    }

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
