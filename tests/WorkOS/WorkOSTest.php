<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class WorkOSTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear any previously set programmatic values and environment variables  
        WorkOS::setApiKey(null);
        WorkOS::setClientId(null);
        
        // Clear environment variables from previous tests
        putenv("WORKOS_API_KEY=");
        putenv("WORKOS_CLIENT_ID=");
        
        // Clear superglobals
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

    public function testGetApiKeyFromProgrammaticSetting()
    {
        $expectedApiKey = "pk_test_programmatic";
        WorkOS::setApiKey($expectedApiKey);
        
        $this->assertEquals($expectedApiKey, WorkOS::getApiKey());
    }

    public function testGetApiKeyFromSystemEnvironment()
    {
        putenv("WORKOS_API_KEY=pk_test_system_env");
        
        $this->assertEquals("pk_test_system_env", WorkOS::getApiKey());
        
        putenv("WORKOS_API_KEY=");
    }

    public function testGetApiKeyFromEnvSuperglobal()
    {
        $_ENV['WORKOS_API_KEY'] = "pk_test_env_superglobal";
        
        $this->assertEquals("pk_test_env_superglobal", WorkOS::getApiKey());
        
        unset($_ENV['WORKOS_API_KEY']);
    }

    public function testGetApiKeyFromServerSuperglobal()
    {
        $_SERVER['WORKOS_API_KEY'] = "pk_test_server_superglobal";
        
        $this->assertEquals("pk_test_server_superglobal", WorkOS::getApiKey());
        
        unset($_SERVER['WORKOS_API_KEY']);
    }

    public function testGetApiKeyPrecedenceOrder()
    {
        putenv("WORKOS_API_KEY=pk_test_system_env");
        $_ENV['WORKOS_API_KEY'] = "pk_test_env_superglobal";
        $_SERVER['WORKOS_API_KEY'] = "pk_test_server_superglobal";
        
        $this->assertEquals("pk_test_system_env", WorkOS::getApiKey());
        
        // Clear cached value and system env to test $_ENV precedence
        WorkOS::setApiKey(null);
        putenv("WORKOS_API_KEY=");
        $this->assertEquals("pk_test_env_superglobal", WorkOS::getApiKey());
        
        // Clear cached value and $_ENV to test $_SERVER precedence
        WorkOS::setApiKey(null);
        unset($_ENV['WORKOS_API_KEY']);
        $this->assertEquals("pk_test_server_superglobal", WorkOS::getApiKey());
        
        unset($_SERVER['WORKOS_API_KEY']);
    }

    public function testGetApiKeyProgrammaticTakesPrecedence()
    {
        putenv("WORKOS_API_KEY=pk_test_system_env");
        $_ENV['WORKOS_API_KEY'] = "pk_test_env_superglobal";
        WorkOS::setApiKey("pk_test_programmatic");
        
        $this->assertEquals("pk_test_programmatic", WorkOS::getApiKey());
        
        putenv("WORKOS_API_KEY=");
        unset($_ENV['WORKOS_API_KEY']);
    }

    public function testGetApiKeyThrowsExceptionWhenNotFound()
    {
        $this->expectException(\WorkOS\Exception\ConfigurationException::class);
        $this->expectExceptionMessage('$apiKey is required');
        
        WorkOS::getApiKey();
    }

    public function testGetClientIdFromProgrammaticSetting()
    {
        $expectedClientId = "client_test_programmatic";
        WorkOS::setClientId($expectedClientId);
        
        $this->assertEquals($expectedClientId, WorkOS::getClientId());
    }

    public function testGetClientIdFromSystemEnvironment()
    {
        putenv("WORKOS_CLIENT_ID=client_test_system_env");
        
        $this->assertEquals("client_test_system_env", WorkOS::getClientId());
        
        putenv("WORKOS_CLIENT_ID=");
    }

    public function testGetClientIdFromEnvSuperglobal()
    {
        $_ENV['WORKOS_CLIENT_ID'] = "client_test_env_superglobal";
        
        $this->assertEquals("client_test_env_superglobal", WorkOS::getClientId());
        
        unset($_ENV['WORKOS_CLIENT_ID']);
    }

    public function testGetClientIdFromServerSuperglobal()
    {
        $_SERVER['WORKOS_CLIENT_ID'] = "client_test_server_superglobal";
        
        $this->assertEquals("client_test_server_superglobal", WorkOS::getClientId());
        
        unset($_SERVER['WORKOS_CLIENT_ID']);
    }

    public function testGetClientIdPrecedenceOrder()
    {
        putenv("WORKOS_CLIENT_ID=client_test_system_env");
        $_ENV['WORKOS_CLIENT_ID'] = "client_test_env_superglobal";
        $_SERVER['WORKOS_CLIENT_ID'] = "client_test_server_superglobal";
        
        $this->assertEquals("client_test_system_env", WorkOS::getClientId());
        
        // Clear cached value and system env to test $_ENV precedence
        WorkOS::setClientId(null);
        putenv("WORKOS_CLIENT_ID=");
        $this->assertEquals("client_test_env_superglobal", WorkOS::getClientId());
        
        // Clear cached value and $_ENV to test $_SERVER precedence
        WorkOS::setClientId(null);
        unset($_ENV['WORKOS_CLIENT_ID']);
        $this->assertEquals("client_test_server_superglobal", WorkOS::getClientId());
        
        unset($_SERVER['WORKOS_CLIENT_ID']);
    }

    public function testGetClientIdProgrammaticTakesPrecedence()
    {
        putenv("WORKOS_CLIENT_ID=client_test_system_env");
        $_ENV['WORKOS_CLIENT_ID'] = "client_test_env_superglobal";
        WorkOS::setClientId("client_test_programmatic");
        
        $this->assertEquals("client_test_programmatic", WorkOS::getClientId());
        
        putenv("WORKOS_CLIENT_ID=");
        unset($_ENV['WORKOS_CLIENT_ID']);
    }

    public function testGetClientIdThrowsExceptionWhenNotFound()
    {
        $this->expectException(\WorkOS\Exception\ConfigurationException::class);
        $this->expectExceptionMessage('$clientId is required');
        
        WorkOS::getClientId();
    }

    public function testSimulateLaravelCachedConfigScenario()
    {
        $_ENV['WORKOS_API_KEY'] = "pk_test_laravel_cached";
        $_ENV['WORKOS_CLIENT_ID'] = "client_test_laravel_cached";
        
        $this->assertEquals("pk_test_laravel_cached", WorkOS::getApiKey());
        $this->assertEquals("client_test_laravel_cached", WorkOS::getClientId());
        
        unset($_ENV['WORKOS_API_KEY']);
        unset($_ENV['WORKOS_CLIENT_ID']);
    }
}