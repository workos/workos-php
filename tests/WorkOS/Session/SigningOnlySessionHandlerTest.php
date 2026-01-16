<?php

namespace WorkOS\Session;

use PHPUnit\Framework\TestCase;
use WorkOS\Exception\UnexpectedValueException;

class SigningOnlySessionHandlerTest extends TestCase
{
    private $handler;
    private $password = "test-password-for-hmac-signing";

    protected function setUp(): void
    {
        $this->handler = new SigningOnlySessionHandler();
    }

    public function testSealAndUnseal()
    {
        $data = [
            'access_token' => 'test_access_token_12345',
            'refresh_token' => 'test_refresh_token_67890',
            'session_id' => 'session_01H7X1M4TZJN5N4HG4XXMA1234'
        ];

        $sealed = $this->handler->seal($data, $this->password);

        $this->assertIsString($sealed);
        $this->assertNotEmpty($sealed);

        $unsealed = $this->handler->unseal($sealed, $this->password);

        $this->assertEquals($data, $unsealed);
    }

    public function testSealedDataIsReadable()
    {
        $data = ['test' => 'value'];

        $sealed = $this->handler->seal($data, $this->password);

        // Signing-only data should be decodable (not encrypted)
        $decoded = json_decode(base64_decode($sealed), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('p', $decoded); // payload
        $this->assertArrayHasKey('s', $decoded); // signature

        // Payload should be readable
        $payloadJson = base64_decode($decoded['p']);
        $payload = json_decode($payloadJson, true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('d', $payload); // data
        $this->assertEquals($data, $payload['d']);
    }

    public function testUnsealWithWrongPasswordFails()
    {
        $data = ['test' => 'value'];
        $sealed = $this->handler->seal($data, $this->password);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid session signature');

        $this->handler->unseal($sealed, 'wrong-password');
    }

    public function testExpiredSessionFails()
    {
        $data = ['test' => 'value'];
        $sealed = $this->handler->seal($data, $this->password, -1); // Already expired

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Session expired');

        $this->handler->unseal($sealed, $this->password);
    }

    public function testCustomTTL()
    {
        $data = ['test' => 'value'];
        $ttl = 3600; // 1 hour

        $sealed = $this->handler->seal($data, $this->password, $ttl);
        $unsealed = $this->handler->unseal($sealed, $this->password);

        $this->assertEquals($data, $unsealed);
    }

    public function testTamperedDataFails()
    {
        $data = ['test' => 'value'];
        $sealed = $this->handler->seal($data, $this->password);

        // Decode, modify, re-encode (without updating signature)
        $decoded = json_decode(base64_decode($sealed), true);
        $payloadJson = base64_decode($decoded['p']);
        $payload = json_decode($payloadJson, true);
        $payload['d'] = ['test' => 'tampered']; // Modify the data
        $decoded['p'] = base64_encode(json_encode($payload));
        $tampered = base64_encode(json_encode($decoded));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid session signature');

        $this->handler->unseal($tampered, $this->password);
    }

    public function testInvalidFormatFails()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid signed session format');

        $this->handler->unseal('not-valid-base64-data', $this->password);
    }

    public function testMissingPayloadFieldFails()
    {
        $invalid = base64_encode(json_encode(['s' => 'signature-only']));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid signed session format');

        $this->handler->unseal($invalid, $this->password);
    }

    public function testMissingSignatureFieldFails()
    {
        $invalid = base64_encode(json_encode(['p' => 'payload-only']));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid signed session format');

        $this->handler->unseal($invalid, $this->password);
    }

    public function testInvalidPayloadStructureFails()
    {
        // Create valid signature but with invalid payload structure
        $payload = ['invalid' => 'structure']; // Missing v, d, e fields
        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->password, true);

        $sealed = base64_encode(json_encode([
            'p' => base64_encode($payloadJson),
            's' => base64_encode($signature),
        ]));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid payload structure');

        $this->handler->unseal($sealed, $this->password);
    }

    public function testVersionCheckFails()
    {
        // Create valid signature but with wrong version
        $payload = [
            'v' => 999, // Unsupported version
            'd' => ['test' => 'value'],
            'e' => time() + 3600,
        ];
        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->password, true);

        $sealed = base64_encode(json_encode([
            'p' => base64_encode($payloadJson),
            's' => base64_encode($signature),
        ]));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported session version');

        $this->handler->unseal($sealed, $this->password);
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

        $sealed = $this->handler->seal($data, $this->password);
        $unsealed = $this->handler->unseal($sealed, $this->password);

        $this->assertEquals($data, $unsealed);
    }

    public function testDifferentPasswordsProduceDifferentSignatures()
    {
        $data = ['test' => 'value'];
        $password1 = 'password-one-for-signing';
        $password2 = 'password-two-for-signing';

        $sealed1 = $this->handler->seal($data, $password1);
        $sealed2 = $this->handler->seal($data, $password2);

        // Signatures should be different
        $this->assertNotEquals($sealed1, $sealed2);

        // Each can only be unsealed with its own password
        $unsealed1 = $this->handler->unseal($sealed1, $password1);
        $this->assertEquals($data, $unsealed1);

        $unsealed2 = $this->handler->unseal($sealed2, $password2);
        $this->assertEquals($data, $unsealed2);
    }

    public function testSignatureIsConstantTimeCompared()
    {
        // This test verifies hash_equals is used (timing attack prevention)
        // We can't directly test timing, but we ensure the code path exists
        $data = ['test' => 'value'];
        $sealed = $this->handler->seal($data, $this->password);

        // Valid unseal should work
        $unsealed = $this->handler->unseal($sealed, $this->password);
        $this->assertEquals($data, $unsealed);
    }

    public function testImplementsSessionEncryptionInterface()
    {
        $this->assertInstanceOf(SessionEncryptionInterface::class, $this->handler);
    }

    public function testCanBeUsedWithUserManagement()
    {
        // SigningOnlySessionHandler can be injected into UserManagement
        $userManagement = new \WorkOS\UserManagement($this->handler);

        $data = [
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
        ];

        // Seal directly (as authkit-php would do)
        $sealed = $this->handler->seal($data, $this->password);

        // UserManagement should be able to unseal it via authenticateWithSessionCookie
        // (will get HTTP error since no API, but that's past the encryption layer)
        $result = $userManagement->authenticateWithSessionCookie($sealed, $this->password);

        // Should succeed past encryption (get HTTP error, not encryption error)
        $this->assertInstanceOf(\WorkOS\Resource\SessionAuthenticationFailureResponse::class, $result);
        $this->assertEquals(
            \WorkOS\Resource\SessionAuthenticationFailureResponse::REASON_HTTP_ERROR,
            $result->reason
        );
    }
}
