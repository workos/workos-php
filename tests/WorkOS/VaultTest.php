<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class VaultTest extends TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }

    /**
     * @var Vault
     */
    protected $vault;

    protected function setUp(): void
    {
        $this->traitSetUp();

        $this->withApiKey();
        $this->vault = new Vault();
    }

    public function testGetVaultObject()
    {
        $vaultObjectPath = "vault/v1/kv/vault_obj_01EHQMYV6MBK39QC5PZXHY59C3";

        $result = $this->vaultObjectResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $vaultObjectPath,
            null,
            null,
            true,
            $result
        );

        $vaultObject = $this->vaultObjectFixture();

        $response = $this->vault->getVaultObject("vault_obj_01EHQMYV6MBK39QC5PZXHY59C3");
        $this->assertSame($vaultObject, $response->toArray());
    }

    public function testListVaultObjects()
    {
        $vaultObjectsPath = "vault/v1/kv";

        $result = $this->vaultObjectsResponseFixture();

        $params = [
            "limit" => 10,
            "before" => null,
            "after" => null,
            "order" => null
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $vaultObjectsPath,
            null,
            $params,
            true,
            $result
        );

        $vaultObjects = $this->vaultObjectsFixture();

        list($before, $after, $response) = $this->vault->listVaultObjects();
        $this->assertSame($vaultObjects, $response[0]->toArray());
    }

    public function testListVaultObjectsPaginatedResourceAccessPatterns()
    {
        $vaultObjectsPath = "vault/v1/kv";
        $result = $this->vaultObjectsResponseFixture();
        $params = [
            "limit" => 10,
            "before" => null,
            "after" => null,
            "order" => null
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $vaultObjectsPath,
            null,
            $params,
            true,
            $result
        );

        // Test 1: Bare destructuring (indexed)
        [$before1, $after1, $objects1] = $this->vault->listVaultObjects();
        $this->assertNull($before1);
        $this->assertNull($after1);
        $this->assertIsArray($objects1);
        $this->assertCount(1, $objects1);

        // Mock the request again for the next test
        $this->mockRequest(
            Client::METHOD_GET,
            $vaultObjectsPath,
            null,
            $params,
            true,
            $result
        );

        // Test 2: Named destructuring
        ["before" => $before2, "after" => $after2, "vault_objects" => $objects2] = $this->vault->listVaultObjects();
        $this->assertNull($before2);
        $this->assertNull($after2);
        $this->assertIsArray($objects2);
        $this->assertCount(1, $objects2);

        // Mock the request again for the next test
        $this->mockRequest(
            Client::METHOD_GET,
            $vaultObjectsPath,
            null,
            $params,
            true,
            $result
        );

        // Test 3: Fluent access
        $response = $this->vault->listVaultObjects();
        $this->assertNull($response->before);
        $this->assertNull($response->after);
        $this->assertIsArray($response->vault_objects);
        $this->assertCount(1, $response->vault_objects);

        // Test 4: Generic data accessor
        $this->assertIsArray($response->data);
        $this->assertSame($response->vault_objects, $response->data);
    }







    // Fixtures

    private function vaultObjectResponseFixture()
    {
        return json_encode([
            "id" => "vault_obj_01EHQMYV6MBK39QC5PZXHY59C3",
            "name" => "Test Vault Object",
            "updated_at" => "2024-01-01T00:00:00.000Z",
            "value" => null,
            "metadata" => []
        ]);
    }

    private function vaultObjectFixture()
    {
        return [
            "id" => "vault_obj_01EHQMYV6MBK39QC5PZXHY59C3",
            "name" => "Test Vault Object",
            "updatedAt" => "2024-01-01T00:00:00.000Z",
            "value" => null,
            "metadata" => []
        ];
    }

    private function vaultObjectsResponseFixture()
    {
        return json_encode([
            "object" => "list",
            "data" => [
                [
                    "id" => "vault_obj_01EHQMYV6MBK39QC5PZXHY59C3",
                    "name" => "Test Vault Object",
                    "updated_at" => "2024-01-01T00:00:00.000Z",
                    "value" => null,
                    "metadata" => []
                ]
            ],
            "list_metadata" => [
                "before" => null,
                "after" => null
            ]
        ]);
    }

    private function vaultObjectsFixture()
    {
        return [
            "id" => "vault_obj_01EHQMYV6MBK39QC5PZXHY59C3",
            "name" => "Test Vault Object",
            "updatedAt" => "2024-01-01T00:00:00.000Z",
            "value" => null,
            "metadata" => []
        ];
    }




}
