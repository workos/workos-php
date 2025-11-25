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
