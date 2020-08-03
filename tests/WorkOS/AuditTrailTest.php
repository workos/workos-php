<?php

namespace WorkOS;

class AuditTrailTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;
    }

    protected function setUp()
    {
        $this->traitSetUp();
        
        $this->at = new AuditTrail();
    }
    
    public function testCreateAuditTrailEvent()
    {
        $this->withApiKeyAndProjectId();

        $path = "events";
        
        $eventFixture = $this->eventFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $eventFixture,
            true
        );

        $this->assertTrue((new AuditTrail())->createEvent($eventFixture));
    }

    public function testCreateAuditTrailEventFailsWithTooMuchMetadata()
    {
        $this->withApiKeyAndProjectId();

        $eventFixture = $this->eventFixture();
        $eventFixture["metadata"] = \array_pad(
            ["things" => "stuff"],
            AuditTrail::METADATA_SIZE_LIMIT + 1,
            "even_more_stuff"
        );

        $this->expectException(Exception\UnexpectedValueException::class);
        (new AuditTrail())->createEvent($eventFixture);
    }

    public function testGetEvents()
    {
        $this->withApiKeyAndProjectId();

        $eventsPath = "events";

        $result = $this->getEventsResponseFixture();
        $params = [
            "limit" => AuditTrail::DEFAULT_EVENT_LIMIT,
            "before" => null,
            "after" => null
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $eventsPath,
            null,
            $params,
            true,
            $result
        );

        $getEventsEventFixture = $this->getEventsEventFixture();

        list($before, $after, $events) = $this->at->getEvents();
        $this->assertSame($getEventsEventFixture, $events[0]->toArray());
    }

    public function testGetEventsCorrectlyIncludesOccurredAtFilter()
    {
        $this->withApiKeyAndProjectId();

        $eventsPath = "events";

        $result = $this->getEventsResponseFixture();
        $params = [
            "limit" => AuditTrail::DEFAULT_EVENT_LIMIT,
            "before" => null,
            "after" => null,
            "occurred_at" => "2020-02-21T00:32:14.443Z"
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $eventsPath,
            null,
            $params,
            true,
            $result
        );

        $getEventsEventFixture = $this->getEventsEventFixture();

        $this->at->getEvents(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            "2020-02-21T00:32:14.443Z",
            "2020-02-21T00:32:14.443Z",
            "2020-02-21T00:32:14.443Z",
            "2020-02-21T00:32:14.443Z",
            "2020-02-21T00:32:14.443Z"
        );
    }

    public function testGetEventsCorrectlyIncludesOccurredAtGte()
    {
        $this->withApiKeyAndProjectId();

        $eventsPath = "events";

        $result = $this->getEventsResponseFixture();
        $params = [
            "limit" => AuditTrail::DEFAULT_EVENT_LIMIT,
            "before" => null,
            "after" => null,
            "occurred_at_gte" => "2020-02-21T00:32:14.443Z"
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $eventsPath,
            null,
            $params,
            true,
            $result
        );

        $getEventsEventFixture = $this->getEventsEventFixture();

        $this->at->getEvents(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            "2020-02-21T00:32:14.443Z",
            "2020-02-21T00:32:14.443Z"
        );
    }

    public function testGetEventsCorrectlyIncludesOccurredAtLte()
    {
        $this->withApiKeyAndProjectId();

        $eventsPath = "events";

        $result = $this->getEventsResponseFixture();
        $params = [
            "limit" => AuditTrail::DEFAULT_EVENT_LIMIT,
            "before" => null,
            "after" => null,
            "occurred_at_lte" => "2020-02-21T00:32:14.443Z"
        ];

        $this->mockRequest(
            Client::METHOD_GET,
            $eventsPath,
            null,
            $params,
            true,
            $result
        );

        $getEventsEventFixture = $this->getEventsEventFixture();

        $this->at->getEvents(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            "2020-02-21T00:32:14.443Z",
            "2020-02-21T00:32:14.443Z"
        );
    }

    // Fixtures

    private function eventFixture()
    {
        return [
            "group" => "organization_1",
            "action" => "user.login",
            "action_type" => "C",
            "actor_name" => "user@email.com",
            "actor_id" => "user_1",
            "target_name" => "user@email.com",
            "target_id" => "user_1",
            "location" =>  "1.1.1.1",
            "occurred_at" => (new \DateTime())->format(\DateTime::ISO8601)
        ];
    }

    private function getEventsEventFixture()
    {
        return [
            "id" => "evt_123",
            "action" => [
                "id" => "evt_action_123",
                "name" => "user.login",
                "projectId" => "project_123",
            ],
            "group" => "organization_1",
            "location" => "1.1.1.1",
            "latitude" => null,
            "longitude" => null,
            "type" => "C",
            "actorName" => "user@email.com",
            "actorId" => "user_1",
            "targetName" => "user@email.com",
            "targetId" => "user_1",
            "metadata" => [
                "a" => "b"
            ],
            "occurredAt" => "2020-02-21T00:32:14.443Z"
        ];
    }

    private function getEventsResponseFixture()
    {
        return json_encode([
            "data" => [
                [
                    "id" => "evt_123",
                    "group" => "organization_1",
                    "location" => "1.1.1.1",
                    "latitude" => null,
                    "longitude" => null,
                    "action" => [
                        "id" => "evt_action_123",
                        "name" => "user.login",
                        "project_id" => "project_123",
                    ],
                    "type" => "C",
                    "actor_name" => "user@email.com",
                    "actor_id" => "user_1",
                    "target_name" => "user@email.com",
                    "target_id" => "user_1",
                    "occurred_at" => "2020-02-21T00:32:14.443Z",
                    "metadata" => [
                        "a" => "b"
                    ]
                ]
            ],
            "listMetadata" => [
                "before" => null,
                "after" => null
            ],
        ]);
    }
}
