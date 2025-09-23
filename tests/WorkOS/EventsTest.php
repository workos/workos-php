<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class EventsTest extends TestCase
{
    use TestHelper {
        setUp as traitSetUp;
    }

    protected function setUp(): void
    {
        $this->traitSetUp();
        $this->withApiKeyAndClientId();
    }

    public function testListEvents()
    {
        $events = new Events();
        
        $this->mockRequest(
            Client::METHOD_GET,
            "events",
            null,
            ['events' => 'user.created'],
            true,
            json_encode([
                'object' => 'list',
                'data' => [
                    [
                        'id' => 'event_123',
                        'event' => 'user.created',
                        'object' => 'event',
                        'data' => ['user' => ['id' => 'user_123']],
                        'created_at' => '2023-01-01T00:00:00Z'
                    ]
                ],
                'list_metadata' => ['after' => null]
            ])
        );
        
        $response = $events->listEvents(['events' => ['user.created']]);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('object', $response);
        $this->assertEquals('list', $response['object']);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
        $this->assertCount(1, $response['data']);
    }

    public function testListEventsWithFilters()
    {
        $events = new Events();
        
        $this->mockRequest(
            Client::METHOD_GET,
            "events",
            null,
            ['limit' => 10, 'events' => 'user.created,user.updated'],
            true,
            json_encode([
                'object' => 'list',
                'data' => [
                    [
                        'id' => 'event_123',
                        'event' => 'user.created',
                        'object' => 'event',
                        'data' => ['user' => ['id' => 'user_123']],
                        'created_at' => '2023-01-01T00:00:00Z'
                    ]
                ],
                'list_metadata' => ['after' => null]
            ])
        );
        
        $response = $events->listEvents([
            'events' => 'user.created,user.updated',
            'limit' => 10
        ]);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertCount(1, $response['data']);
        $this->assertEquals('user.created', $response['data'][0]['event']);
    }

    public function testListEventsWithArrayFilter()
    {
        $events = new Events();
        
        $this->mockRequest(
            Client::METHOD_GET,
            "events",
            null,
            ['limit' => 5, 'events' => 'user.created,user.updated'],
            true,
            json_encode([
                'object' => 'list',
                'data' => [],
                'list_metadata' => ['after' => null]
            ])
        );
        
        $response = $events->listEvents([
            'events' => ['user.created', 'user.updated'],
            'limit' => 5
        ]);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
    }

    public function testListEventsWithAllParameters()
    {
        $events = new Events();
        
        $this->mockRequest(
            Client::METHOD_GET,
            "events",
            null,
            [
                'limit' => 25,
                'order' => 'asc',
                'organization_id' => 'org_123',
                'after' => 'cursor_after',
                'before' => 'cursor_before',
                'events' => 'user.created,user.updated'
            ],
            true,
            json_encode([
                'object' => 'list',
                'data' => [],
                'list_metadata' => ['after' => null]
            ])
        );
        
        $response = $events->listEvents([
            'events' => ['user.created', 'user.updated'],
            'limit' => 25,
            'order' => 'asc',
            'organization_id' => 'org_123',
            'after' => 'cursor_after',
            'before' => 'cursor_before'
        ]);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
    }

    // Parameter validation tests
    public function testListEventsWithInvalidLimit()
    {
        $events = new Events();
        
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->listEvents(['events' => ['user.created'], 'limit' => 'invalid']);
    }

    public function testListEventsWithLimitTooLow()
    {
        $events = new Events();
        
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->listEvents(['events' => ['user.created'], 'limit' => 0]);
    }

    public function testListEventsWithLimitTooHigh()
    {
        $events = new Events();
        
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->listEvents(['events' => ['user.created'], 'limit' => 101]);
    }

    public function testListEventsWithInvalidOrder()
    {
        $events = new Events();
        
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->listEvents(['events' => ['user.created'], 'order' => 'invalid']);
    }

    public function testListEventsWithEmptyOrganizationId()
    {
        $events = new Events();
        
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->listEvents(['events' => ['user.created'], 'organization_id' => '']);
    }

    public function testListEventsWithLongOrganizationId()
    {
        $events = new Events();
        
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->listEvents(['events' => ['user.created'], 'organization_id' => str_repeat('a', 256)]);
    }

    public function testListEventsWithLongAfterCursor()
    {
        $events = new Events();
        
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->listEvents(['events' => ['user.created'], 'after' => str_repeat('a', 256)]);
    }

    public function testListEventsWithLongBeforeCursor()
    {
        $events = new Events();
        
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->listEvents(['events' => ['user.created'], 'before' => str_repeat('a', 256)]);
    }

    public function testListEventsWithInvalidEventsParameter()
    {
        $events = new Events();
        
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->listEvents(['events' => []]);
    }

    public function testListEventsWithInvalidEventTypes()
    {
        $events = new Events();
        
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->listEvents(['events' => ['invalid.event.type']]);
    }

    public function testGetValidEventTypes()
    {
        $events = new Events();
        
        $validTypes = $events->getValidEventTypes();
        
        $this->assertIsArray($validTypes);
        $this->assertNotEmpty($validTypes);
        
        // Test some specific event types exist
        $this->assertContains('user.created', $validTypes);
        $this->assertContains('user.updated', $validTypes);
        $this->assertContains('organization.created', $validTypes);
        $this->assertContains('authentication.sso_succeeded', $validTypes);
        $this->assertContains('dsync.user.created', $validTypes);
    }

    public function testValidateEventTypes()
    {
        $events = new Events();
        
        // Test valid event types
        $this->assertTrue($events->validateEventTypes('user.created'));
        $this->assertTrue($events->validateEventTypes(['user.created', 'user.updated']));
        $this->assertTrue($events->validateEventTypes('user.created,user.updated'));
        
        // Test invalid event types
        $this->assertFalse($events->validateEventTypes('invalid.event.type'));
        $this->assertFalse($events->validateEventTypes(['user.created', 'invalid.event.type']));
    }

    public function testValidateEventTypesWithNull()
    {
        $events = new Events();
        
        $this->assertFalse($events->validateEventTypes(null));
    }

    public function testValidateEventTypesWithEmptyString()
    {
        $events = new Events();
        
        $this->assertFalse($events->validateEventTypes(''));
    }

    public function testValidateEventTypesWithEmptyArray()
    {
        $events = new Events();
        
        $this->assertFalse($events->validateEventTypes([]));
    }

    public function testValidateEventTypesWithWhitespace()
    {
        $events = new Events();
        
        $this->assertTrue($events->validateEventTypes(' user.created '));
        $this->assertTrue($events->validateEventTypes('user.created , user.updated'));
    }

    public function testValidateEventTypesWithMixedValidInvalid()
    {
        $events = new Events();
        
        $this->assertFalse($events->validateEventTypes(['user.created', 'invalid.event.type']));
    }

    public function testGetEventsByType()
    {
        $events = new Events();
        
        $this->mockRequest(
            Client::METHOD_GET,
            "events",
            null,
            ['limit' => 20, 'events' => 'user.created,user.updated'],
            true,
            json_encode([
                'object' => 'list',
                'data' => [
                    [
                        'id' => 'event_123',
                        'event' => 'user.created',
                        'object' => 'event',
                        'data' => ['user' => ['id' => 'user_123']],
                        'created_at' => '2023-01-01T00:00:00Z'
                    ]
                ],
                'list_metadata' => ['after' => null]
            ])
        );
        
        $response = $events->getEventsByType(['user.created', 'user.updated'], ['limit' => 20]);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertCount(1, $response['data']);
        $this->assertEquals('user.created', $response['data'][0]['event']);
    }

    public function testGetEventsByTypeWithInvalidTypes()
    {
        $events = new Events();
        
        // Test with invalid event types
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->getEventsByType(['invalid.event.type'], ['limit' => 20]);
    }

    public function testGetEventsByTypeWithEmptyTypes()
    {
        $events = new Events();
        
        // Test with empty event types
        $this->expectException(\WorkOS\Exception\BadRequestException::class);
        $events->getEventsByType([]);
    }

    public function testEventResourceCreation()
    {
        $events = new Events();
        
        $eventData = [
            'id' => 'event_123',
            'event' => 'user.created',
            'object' => 'event',
            'data' => ['user' => ['id' => 'user_123']],
            'created_at' => '2023-01-01T00:00:00Z'
        ];
        
        $this->mockRequest(
            Client::METHOD_GET,
            "events",
            null,
            ['limit' => 1, 'events' => 'user.created'],
            true,
            json_encode([
                'object' => 'list',
                'data' => [$eventData],
                'list_metadata' => ['after' => null]
            ])
        );
        
        $response = $events->listEvents(['events' => ['user.created'], 'limit' => 1]);
        
        $this->assertNotEmpty($response['data']);
        $eventData = $response['data'][0];
        
        // Test creating Event resource
        $event = \WorkOS\Resource\Event::constructFromResponse($eventData);
        
        $this->assertInstanceOf(\WorkOS\Resource\Event::class, $event);
        $this->assertEquals($eventData['id'], $event->getId());
        $this->assertEquals($eventData['event'], $event->getEvent());
        $this->assertEquals($eventData['data'], $event->getData());
        
        // Test helper methods
        $this->assertTrue($event->isUserEvent());
        
        // Test JSON formatting
        $jsonData = $event->getDataAsJson();
        $this->assertIsString($jsonData);
        $this->assertJson($jsonData);
    }

    public function testEventResourceHelperMethods()
    {
        $eventData = [
            'id' => 'event_123',
            'event' => 'authentication.sso_succeeded',
            'object' => 'event',
            'data' => ['user' => ['id' => 'user_123']],
            'created_at' => '2023-01-01T00:00:00Z'
        ];
        
        $event = \WorkOS\Resource\Event::constructFromResponse($eventData);
        
        // Test event type checks
        $this->assertTrue($event->isAuthenticationEvent());
        $this->assertFalse($event->isUserEvent());
        $this->assertFalse($event->isOrganizationEvent());
        $this->assertFalse($event->isDSyncEvent());
        
        // Test specific event type
        $this->assertTrue($event->isEventType('authentication.sso_succeeded'));
        $this->assertFalse($event->isEventType('user.created'));
    }

    public function testEventResourceDataAccess()
    {
        $eventData = [
            'id' => 'event_123',
            'event' => 'user.created',
            'object' => 'event',
            'data' => [
                'user' => [
                    'id' => 'user_123',
                    'email' => 'test@example.com'
                ]
            ],
            'created_at' => '2023-01-01T00:00:00Z'
        ];
        
        $event = \WorkOS\Resource\Event::constructFromResponse($eventData);
        
        // Test data field access
        $userData = $event->getDataField('user');
        $this->assertIsArray($userData);
        $this->assertEquals('user_123', $userData['id']);
        $this->assertEquals('test@example.com', $userData['email']);
        $this->assertNull($event->getDataField('nonexistent'));
        
        // Test formatted date
        $formattedDate = $event->getFormattedCreatedAt('Y-m-d H:i:s');
        $this->assertIsString($formattedDate);
        $this->assertStringContainsString('2023-01-01', $formattedDate);
    }
}