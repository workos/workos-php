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

    const VERSION = "v0.0.1";

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
}
