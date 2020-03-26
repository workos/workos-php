<?php

namespace WorkOS;

/**
 * Class WorkOS.
 */
class WorkOS
{
    /** @var string WorkOS API key */
    public static $apiKey;

    /** @var string WorkOS Project ID */
    public static $projectId;

    /** @var string WorkOS base API URL. */
    public static $apiBaseUrl = 'https://api.workos.com/';

    /**
     * @return string | null WorkOs API key
     */
    public static function getApiKey()
    {
        return self::$apiKey;
    }

    /**
     * @param string $apiKey WorkOS API key
     */
    public static function setApiKey($apiKey)
    {
        self::$apiKey = $apiKey;
    }

    /**
     * @return string | null WorkOS Project ID
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
