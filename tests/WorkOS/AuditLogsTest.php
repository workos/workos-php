<?php

namespace WorkOS;

class AuditLogsTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as protected traitSetUp;

    }

    protected function setUp(): void
    {
        $this->traitSetUp();

        $this->withApiKeyAndClientId();
        $this->al = new AuditLogs();
    }

    public function testCreateEvent()
    {
        $path = "audit_logs/events";

        $organizationId = "org_123";
        $auditLogEvent =
        [
            "action" => "document.updated",
            "occurred_at" => time(),
            "version" => 1,
            "actor" =>
            [
                "Id" => "user_123",
                "Type" => "user",
                "Name" => "User",
            ],
            "targets" =>
            [
                    "id" => "team_123",
                    "type" => "team",
                    "name" => "team",
            ]];
        $params = [
            "organization_id" => $organizationId,
            "event" => $auditLogEvent,
            "idempotency_key" => null
        ];

        $result = $this->createEventResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );



        $eventStatus = $this->al->createEvent($organizationId, $auditLogEvent);
        $eventFixture = $this->createEventFixture();

        $this->assertSame($eventFixture, $eventStatus->toArray());
    }

    public function testCreateExport()
    {
        $path = "audit_logs/exports";

        $organizationId = "org_123";
        $rangeStart = "";
        $rangeEnd = "";
        $targets = [
            "id" => "team_123",
            "type" => "team",
            "name" => "team",
        ];
        $actions = ["document.updated"];
        $actors = ["user_123"];
        $params = [
            "actions" => $actions,
            "actors" => $actors,
            "organization_id" => $organizationId,
            "range_end" => $rangeEnd,
            "range_start" => $rangeStart,
            "targets" => $targets
        ];

        $result = $this->createExportResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );



        $auditLogExport = $this->al->createExport($organizationId, $rangeStart, $rangeEnd, $actions, $actors, $targets);
        $exportFixture = $this->createExportFixture();

        $this->assertSame($exportFixture, $auditLogExport->toArray());
    }

    public function testGetExport()
    {
        $auditLogExportId = "123";

        $path = "audit_logs/exports/${auditLogExportId}";

        $result = $this->getExportResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );



        $auditLogGetExport = $this->al->getExport($auditLogExportId);
        $getExportFixture = $this->getExportFixture();

        $this->assertSame($getExportFixture, $auditLogGetExport->toArray());
    }
    // Fixtures

    private function createEventFixture()
    {
        return [
        "success" => true
    ];
    }

    private function createEventResponseFixture()
    {
        return json_encode([
        "success" => true
    ]);
    }

    private function createExportFixture()
    {
        return [
        "object" => "audit_log_export",
        "id" => "audit_log_export_123",
        "state" => "ready",
        "url" => "https://audit-logs.com/download.csv",
        "createdAt" => "2022-08-18T18:07:10.822Z",
        "updatedAt" => "2022-08-18T18:07:10.822Z",
    ];
    }

    private function createExportResponseFixture()
    {
        return json_encode([
        "object" => "audit_log_export",
        "id" => "audit_log_export_123",
        "state" => "ready",
        "url" => "https://audit-logs.com/download.csv",
        "created_at" => "2022-08-18T18:07:10.822Z",
        "updated_at" => "2022-08-18T18:07:10.822Z",
    ]);
    }

    private function getExportFixture()
    {
        return [
        "object" => "audit_log_export",
        "id" => "audit_log_export_123",
        "state" => "ready",
        "url" => "https://audit-logs.com/download.csv",
        "createdAt" => "2022-08-18T18:07:10.822Z",
        "updatedAt" => "2022-08-18T18:07:10.822Z",
    ];
    }

    private function getExportResponseFixture()
    {
        return json_encode([
        "object" => "audit_log_export",
        "id" => "audit_log_export_123",
        "state" => "ready",
        "url" => "https://audit-logs.com/download.csv",
        "created_at" => "2022-08-18T18:07:10.822Z",
        "updated_at" => "2022-08-18T18:07:10.822Z",
    ]);
    }
}
