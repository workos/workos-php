<?php

namespace WorkOS;

/**
 * Class WorkOS.
 *
 * This class allows users to get and set configuration for the package.
 */
class WorkOS
{
    /**
     * @var null|string WorkOS API key
     */
    public static $apiKey = null;

    /**
     * @var null|string WorkOS Project ID
     */
    public static $projectId = null;

    /**
     * @var string WorkOS base API URL.
     */
    public static $apiBaseUrl = "https://api.workos.com/";

    /**
     * @var string SDK identifier
     */
    private static $identifier = "";

    /**
     * @var string SDK version
     */
    private static $version = "";

    /**
     * @return null|string WorkOS API key
     */
    public static function getApiKey()
    {
        return self::$apiKey;
    }

    /**
     * @param null|string $apiKey WorkOS API key
     */
    public static function setApiKey($apiKey)
    {
        self::$apiKey = $apiKey;
    }

    /**
     * @return null|string WorkOS Project ID
     */
    public static function getProjectId()
    {
        return self::$projectId;
    }

    /**
     * @param string $projectId WorkOS Project ID
     */
    public static function setProjectId($projectId)
    {
        self::$projectId = $projectId;
    }

    /**
     * @return string WorkOS base API URL
     */
    public static function getApiBaseURL()
    {
        return self::$apiBaseUrl;
    }

    /**
     * @param string $apiBaseUrl WorkOS base API URL
     */
    public static function setApiBaseUrl($apiBaseUrl)
    {
        self::$apiBaseUrl = $apiBaseUrl;
    }

    /**
     * @param string $identifier SDK identifier
     */
    public static function setIdentifier($identifier)
    {
        self::$identifier = $identifier;
    }

    /**
     * @return string SDK identifier
     */
    public static function getIdentifier()
    {
        if (self::$identifier) {
            return self::$identifier;
        }

        return Version::SDK_IDENTIFIER;
    }

    /**
     * @param string $version SDK version
     */
    public static function setVersion($version)
    {
        self::$version = $version;
    }

    /**
     * @return string SDK version
     */
    public static function getVersion()
    {
        if (self::$version) {
            return self::$version;
        }

        return Version::SDK_VERSION;
    }
}
