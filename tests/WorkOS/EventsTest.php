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

        list($before, $after, $eventsList) = $events->listEvents(['events' => ['user.created']]);

        $this->assertNull($before);
        $this->assertNull($after);
        $this->assertIsArray($eventsList);
        $this->assertCount(1, $eventsList);
        $this->assertInstanceOf(\WorkOS\Resource\Event::class, $eventsList[0]);
        $this->assertEquals('event_123', $eventsList[0]->getId());
        $this->assertEquals('user.created', $eventsList[0]->getEvent());
    }

    public function testListEventsWithFilters()
    {
        $events = new Events();

        $this->mockRequest(
            Client::METHOD_GET,
            "events",
            null,
            ['events' => 'user.created,user.updated', 'limit' => 10],
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

        list($before, $after, $eventsList) = $events->listEvents([
            'events' => 'user.created,user.updated',
            'limit' => 10
        ]);

        $this->assertNull($before);
        $this->assertNull($after);
        $this->assertIsArray($eventsList);
        $this->assertCount(1, $eventsList);
        $this->assertInstanceOf(\WorkOS\Resource\Event::class, $eventsList[0]);
        $this->assertEquals('user.created', $eventsList[0]->getEvent());
    }

    public function testListEventsWithArrayFilter()
    {
        $events = new Events();

        $this->mockRequest(
            Client::METHOD_GET,
            "events",
            null,
            ['events' => 'user.created,user.updated', 'limit' => 5],
            true,
            json_encode([
                'object' => 'list',
                'data' => [],
                'list_metadata' => ['after' => null]
            ])
        );

        list($before, $after, $eventsList) = $events->listEvents([
            'events' => ['user.created', 'user.updated'],
            'limit' => 5
        ]);

        $this->assertNull($before);
        $this->assertNull($after);
        $this->assertIsArray($eventsList);
        $this->assertCount(0, $eventsList);
    }

    public function testListEventsWithAllParameters()
    {
        $events = new Events();

        $this->mockRequest(
            Client::METHOD_GET,
            "events",
            null,
            [
                'events' => 'user.created,user.updated',
                'limit' => 25,
                'order' => 'asc',
                'organization_id' => 'org_123',
                'after' => 'cursor_after',
                'before' => 'cursor_before'
            ],
            true,
            json_encode([
                'object' => 'list',
                'data' => [],
                'list_metadata' => ['after' => 'cursor_next']
            ])
        );

        list($before, $after, $eventsList) = $events->listEvents([
            'events' => ['user.created', 'user.updated'],
            'limit' => 25,
            'order' => 'asc',
            'organization_id' => 'org_123',
            'after' => 'cursor_after',
            'before' => 'cursor_before'
        ]);

        $this->assertNull($before);
        $this->assertEquals('cursor_next', $after);
        $this->assertIsArray($eventsList);
        $this->assertCount(0, $eventsList);
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
            ['events' => 'user.created', 'limit' => 1],
            true,
            json_encode([
                'object' => 'list',
                'data' => [$eventData],
                'list_metadata' => ['after' => null]
            ])
        );

        list($before, $after, $eventsList) = $events->listEvents(['events' => ['user.created'], 'limit' => 1]);

        $this->assertNotEmpty($eventsList);
        $this->assertCount(1, $eventsList);
        $event = $eventsList[0];

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

    public function testEventTypesConstants()
    {
        // Test that EventTypes constants are accessible and have correct values
        $this->assertEquals('user.created', EventTypes::USER_CREATED);
        $this->assertEquals('user.updated', EventTypes::USER_UPDATED);
        $this->assertEquals('user.deleted', EventTypes::USER_DELETED);
        $this->assertEquals('authentication.sso_succeeded', EventTypes::AUTHENTICATION_SSO_SUCCEEDED);
        $this->assertEquals('organization.created', EventTypes::ORGANIZATION_CREATED);
    }

    public function testListEventsWithEventTypesConstants()
    {
        $events = new Events();

        $this->mockRequest(
            Client::METHOD_GET,
            "events",
            null,
            ['events' => EventTypes::USER_CREATED . ',' . EventTypes::USER_UPDATED],
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

        list($before, $after, $eventsList) = $events->listEvents([
            'events' => [EventTypes::USER_CREATED, EventTypes::USER_UPDATED]
        ]);

        $this->assertNull($before);
        $this->assertNull($after);
        $this->assertIsArray($eventsList);
        $this->assertCount(1, $eventsList);
        $this->assertInstanceOf(\WorkOS\Resource\Event::class, $eventsList[0]);
    }
}
