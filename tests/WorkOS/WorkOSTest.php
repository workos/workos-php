<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class WorkOSTest extends TestCase
{
    protected function setUp(): void
    {
        WorkOS::setApiKey(null);
        WorkOS::setClientId(null);
        
        putenv("WORKOS_API_KEY=");
        putenv("WORKOS_CLIENT_ID=");
        
        unset($_ENV['WORKOS_API_KEY']);
        unset($_ENV['WORKOS_CLIENT_ID']);
        unset($_SERVER['WORKOS_API_KEY']);
        unset($_SERVER['WORKOS_CLIENT_ID']);
    }
    
    protected function tearDown(): void
    {
        WorkOS::setApiKey(null);
        WorkOS::setClientId(null);
        
        putenv("WORKOS_API_KEY=");
        putenv("WORKOS_CLIENT_ID=");
        
        unset($_ENV['WORKOS_API_KEY']);
        unset($_ENV['WORKOS_CLIENT_ID']);
        unset($_SERVER['WORKOS_API_KEY']);
        unset($_SERVER['WORKOS_CLIENT_ID']);
    }

    public function testGetApiKeyFromEnvSuperglobal()
    {
        $_ENV['WORKOS_API_KEY'] = "pk_test_env_superglobal";
        
        $this->assertEquals("pk_test_env_superglobal", WorkOS::getApiKey());
    }

    public function testGetClientIdFromEnvSuperglobal()
    {
        $_ENV['WORKOS_CLIENT_ID'] = "client_test_env_superglobal";
        
        $this->assertEquals("client_test_env_superglobal", WorkOS::getClientId());
    }

    public function testLaravelConfigCachingScenario()
    {
        $_ENV['WORKOS_API_KEY'] = "pk_test_laravel_cached";
        $_ENV['WORKOS_CLIENT_ID'] = "client_test_laravel_cached";
        
        $this->assertEquals("pk_test_laravel_cached", WorkOS::getApiKey());
        $this->assertEquals("client_test_laravel_cached", WorkOS::getClientId());
    }
}