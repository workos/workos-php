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
        
        $now = (new \DateTime())->format(\DateTime::ISO8601);

        $this->event = [
            "group" => "organization_1",
            "action" => "user.login",
            "action_type" => "C",
            "actor_name" => "user@email.com",
            "actor_id" => "user_1",
            "target_name" => "user@email.com",
            "target_id" => "user_1",
            "location" =>  "1.1.1.1",
            "occurred_at" => $now,
        ];
    }
    
    public function testCreateAuditTrailEvent()
    {
        $this->withApiKeyAndProjectId();

        $path = "events";
        $headers = ["Authorization: Bearer " . WorkOS::getApiKey()];

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            $headers,
            $this->event
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
}
