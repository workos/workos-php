<?php

declare(strict_types=1);
// @oagen-ignore-file
// Hand-maintained tests for the Vault module.

namespace Tests;

use PHPUnit\Framework\TestCase;
use WorkOS\TestHelper;
use WorkOS\Vault;

class VaultTest extends TestCase
{
    use TestHelper;

    public function testReadObject(): void
    {
        $fixture = ['id' => 'obj_1', 'name' => 'test', 'value' => 'secret'];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $result = $client->vault()->readObject('obj_1');
        $this->assertSame('obj_1', $result['id']);
        $request = $this->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('vault/v1/kv/obj_1', $request->getUri()->getPath());
    }

    public function testReadObjectByName(): void
    {
        $fixture = ['id' => 'obj_1', 'name' => 'my-secret'];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $result = $client->vault()->readObjectByName('my-secret');
        $this->assertSame('my-secret', $result['name']);
        $request = $this->getLastRequest();
        $this->assertStringEndsWith('vault/v1/kv/name/my-secret', $request->getUri()->getPath());
    }

    public function testGetObjectMetadata(): void
    {
        $fixture = ['id' => 'obj_1', 'metadata' => []];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $client->vault()->getObjectMetadata('obj_1');
        $request = $this->getLastRequest();
        $this->assertStringEndsWith('vault/v1/kv/obj_1/metadata', $request->getUri()->getPath());
    }

    public function testListObjects(): void
    {
        $fixture = ['data' => [], 'list_metadata' => []];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $client->vault()->listObjects();
        $request = $this->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('vault/v1/kv', $request->getUri()->getPath());
    }

    public function testListObjectVersions(): void
    {
        $fixture = ['data' => []];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $client->vault()->listObjectVersions('obj_1');
        $request = $this->getLastRequest();
        $this->assertStringEndsWith('vault/v1/kv/obj_1/versions', $request->getUri()->getPath());
    }

    public function testCreateObject(): void
    {
        $fixture = ['id' => 'obj_1', 'name' => 'test'];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $client->vault()->createObject('test', 'secret_value', ['env' => 'production']);
        $request = $this->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('vault/v1/kv', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('test', $body['name']);
        $this->assertSame('secret_value', $body['value']);
    }

    public function testUpdateObject(): void
    {
        $fixture = ['id' => 'obj_1', 'value' => 'new_value'];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $client->vault()->updateObject('obj_1', 'new_value');
        $request = $this->getLastRequest();
        $this->assertSame('PUT', $request->getMethod());
        $this->assertStringEndsWith('vault/v1/kv/obj_1', $request->getUri()->getPath());
    }

    public function testDeleteObject(): void
    {
        $client = $this->createMockClient([['status' => 204]]);
        $client->vault()->deleteObject('obj_1');
        $request = $this->getLastRequest();
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertStringEndsWith('vault/v1/kv/obj_1', $request->getUri()->getPath());
    }

    public function testCreateDataKey(): void
    {
        $fixture = ['id' => 'key_1', 'data_key' => 'base64key', 'context' => [], 'encrypted_keys' => 'enc'];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $client->vault()->createDataKey(['env' => 'prod']);
        $request = $this->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('vault/v1/keys/data-key', $request->getUri()->getPath());
    }

    public function testDecryptDataKey(): void
    {
        $fixture = ['id' => 'key_1', 'data_key' => 'decrypted_key'];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $client->vault()->decryptDataKey('encrypted_keys_value');
        $request = $this->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('vault/v1/keys/decrypt', $request->getUri()->getPath());
    }

    public function testVaultAccessibleFromClient(): void
    {
        $client = $this->createMockClient([]);
        $this->assertInstanceOf(Vault::class, $client->vault());
    }
}
