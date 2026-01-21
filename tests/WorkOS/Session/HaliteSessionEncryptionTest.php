<?php

namespace WorkOS\Session;

use PHPUnit\Framework\TestCase;
use WorkOS\Exception\UnexpectedValueException;

class HaliteSessionEncryptionTest extends TestCase
{
    private $encryptor;
    private $password = "test-password-for-encryption-with-minimum-length";

    protected function setUp(): void
    {
        $this->encryptor = new HaliteSessionEncryption();
    }

    public function testSealAndUnseal()
    {
        $data = [
            'access_token' => 'test_access_token_12345',
            'refresh_token' => 'test_refresh_token_67890',
            'session_id' => 'session_01H7X1M4TZJN5N4HG4XXMA1234'
        ];

        $sealed = $this->encryptor->seal($data, $this->password);

        $this->assertIsString($sealed);
        $this->assertNotEmpty($sealed);
        $this->assertGreaterThan(0, strlen($sealed));

        $unsealed = $this->encryptor->unseal($sealed, $this->password);

        $this->assertEquals($data, $unsealed);
    }

    public function testSealedDataIsDifferentEachTime()
    {
        $data = ['test' => 'value'];

        $sealed1 = $this->encryptor->seal($data, $this->password);
        $sealed2 = $this->encryptor->seal($data, $this->password);

        // Encrypted data should be different each time due to random nonce
        $this->assertNotEquals($sealed1, $sealed2);

        // But both should decrypt to the same value
        $unsealed1 = $this->encryptor->unseal($sealed1, $this->password);
        $unsealed2 = $this->encryptor->unseal($sealed2, $this->password);

        $this->assertEquals($data, $unsealed1);
        $this->assertEquals($data, $unsealed2);
    }

    public function testUnsealWithWrongPasswordFails()
    {
        $data = ['test' => 'value'];
        $sealed = $this->encryptor->seal($data, $this->password);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Failed to unseal session');

        $this->encryptor->unseal($sealed, 'wrong-password-that-should-not-work');
    }

    public function testExpiredSessionFails()
    {
        $data = ['test' => 'value'];
        $sealed = $this->encryptor->seal($data, $this->password, -1); // Already expired (TTL of -1 second)

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Session has expired');

        $this->encryptor->unseal($sealed, $this->password);
    }

    public function testCustomTTL()
    {
        $data = ['test' => 'value'];
        $ttl = 3600; // 1 hour

        $sealed = $this->encryptor->seal($data, $this->password, $ttl);
        $unsealed = $this->encryptor->unseal($sealed, $this->password);

        $this->assertEquals($data, $unsealed);
    }

    public function testLongTTL()
    {
        $data = ['test' => 'value'];
        $ttl = 2592000; // 30 days (WorkOS session default)

        $sealed = $this->encryptor->seal($data, $this->password, $ttl);
        $unsealed = $this->encryptor->unseal($sealed, $this->password);

        $this->assertEquals($data, $unsealed);
    }

    public function testComplexDataStructures()
    {
        $data = [
            'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...',
            'refresh_token' => 'refresh_01H7X1M4TZJN5N4HG4XXMA1234',
            'session_id' => 'session_01H7X1M4TZJN5N4HG4XXMA1234',
            'user' => [
                'id' => 'user_123',
                'email' => 'test@example.com',
                'first_name' => 'Test',
                'last_name' => 'User'
            ],
            'organization_id' => 'org_123',
            'roles' => ['admin', 'user'],
            'permissions' => ['read', 'write', 'delete']
        ];

        $sealed = $this->encryptor->seal($data, $this->password);
        $unsealed = $this->encryptor->unseal($sealed, $this->password);

        $this->assertEquals($data, $unsealed);
    }

    public function testInvalidBase64Fails()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Failed to unseal session');

        $this->encryptor->unseal('not-valid-base64-!@#$%^&*()', $this->password);
    }

    public function testCorruptedDataFails()
    {
        $data = ['test' => 'value'];
        $sealed = $this->encryptor->seal($data, $this->password);

        // Corrupt the sealed data by modifying a character
        $corrupted = substr($sealed, 0, -5) . 'XXXXX';

        $this->expectException(UnexpectedValueException::class);

        $this->encryptor->unseal($corrupted, $this->password);
    }

    public function testEmptyDataArray()
    {
        $data = [];

        $sealed = $this->encryptor->seal($data, $this->password);
        $unsealed = $this->encryptor->unseal($sealed, $this->password);

        $this->assertEquals($data, $unsealed);
    }

    public function testDifferentPasswordsProduceDifferentResults()
    {
        $data = ['test' => 'value'];
        $password1 = 'password-one-for-testing-encryption';
        $password2 = 'password-two-for-testing-encryption';

        $sealed1 = $this->encryptor->seal($data, $password1);
        $sealed2 = $this->encryptor->seal($data, $password2);

        $this->assertNotEquals($sealed1, $sealed2);

        // Each can only be unsealed with its own password
        $unsealed1 = $this->encryptor->unseal($sealed1, $password1);
        $this->assertEquals($data, $unsealed1);

        $unsealed2 = $this->encryptor->unseal($sealed2, $password2);
        $this->assertEquals($data, $unsealed2);

        // Trying to unseal with the wrong password should fail
        $this->expectException(UnexpectedValueException::class);
        $this->encryptor->unseal($sealed1, $password2);
    }
}
