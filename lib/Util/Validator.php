<?php

namespace WorkOS\Util;

/**
 * Class Validator.
 *
 * Helper class to validate that required settings are configured for WorkOS modules.
 */
class Validator
{
    const MODULE_SSO = "SSO";

    const SETTING_API_KEY = "apiKey";
    const SETTING_PROJECT_ID = "projectId";

    /**
     * Array of required settings for the SSO module.
     */
    const REQUIRED_SETTINGS_SSO = [self::SETTING_API_KEY, self::SETTING_PROJECT_ID];

    /**
     * @param string $module name of module to validate settings for
     *
     * @throws \WorkOS\Exception\UnexpectedValueException if an unsupported module is passed in
     * @throws \WorkOS\Exception\ConfigurationException if a module fails validation
     */
    public static function validateSettings($module)
    {
        if ($module === self::MODULE_SSO) {
            $requiredSettings = self::REQUIRED_SETTINGS_SSO;
        } else {
            $msg = "Invalid \$module specified";

            throw new \WorkOS\Exception\UnexpectedValueException($msg);
        }

        $missingSettings = array();
        foreach ($requiredSettings as $setting) {
            if (null === self::getSetting($setting)) {
                \array_push($missingSettings, $setting);
            }
        }

        if (!empty($missingSettings)) {
            $msg = "The following settings are required for {$module}: ";
            foreach ($missingSettings as $setting) {
                $msg .= "{$setting} ";
            }

            throw new \WorkOS\Exception\ConfigurationException($msg);
        }
    }

    private static function getSetting($setting)
    {
        switch ($setting) {
            case self::SETTING_API_KEY:
                return \WorkOS\WorkOS::getApiKey();

            case self::SETTING_PROJECT_ID:
                return \WorkOS\WorkOS::getProjectId();
        }
    }
}
