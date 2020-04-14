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
    private static $apiKey = null;

    /**
     * @var null|string WorkOS Project ID
     */
    private static $projectId = null;

    /**
     * @var string WorkOS base API URL.
     */
    private static $apiBaseUrl = "https://api.workos.com/";

    /**
     * @var string SDK identifier
     */
    private static $identifier = Version::SDK_IDENTIFIER;

    /**
     * @var string SDK version
     */
    private static $version = Version::SDK_VERSION;

    /**
     * @return null|string WorkOS API key
     */
    public static function getApiKey()
    {
        if (isset(self::$apikKey)) {
            return self::$apiKey;
        }

        if (getenv("WORKOS_API_KEY")) {
            self::$apiKey = getenv("WORKOS_API_KEY");
        }

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
        if (isset(self::$projectId)) {
            return self::$projectId;
        }

        if (getenv("WORKOS_PROJECT_ID")) {
            self::$projectId = getenv("WORKOS_PROJECT_ID");
        }

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
        return self::$identifier;
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
        return self::$version;
    }
}
