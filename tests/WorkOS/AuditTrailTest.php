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
        
        $this->event = $this->eventFixture();
    }
    
    public function testCreateAuditTrailEvent()
    {
        $this->withApiKeyAndProjectId();

        $path = "events";

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $this->event,
            true
        );

        $this->assertTrue((new AuditTrail())->createEvent($this->event));
    }

    public function testCreateAuditTrailEventFailsWithTooMuchMetadata()
    {
        $this->withApiKeyAndProjectId();

        $this->event["metadata"] = \array_pad(
            ["things" => "stuff"],
            AuditTrail::METADATA_SIZE_LIMIT + 1,
            "even_more_stuff"
        );

        $this->expectException(Exception\UnexpectedValueException::class);
        (new AuditTrail())->createEvent($this->event);
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
}
