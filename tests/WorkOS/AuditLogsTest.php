<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class AuditLogsTest extends TestCase
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

        $idempotencyKey = null;
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
            "event" => $auditLogEvent
        ];

        $headers = [
            "idempotency_key" => $idempotencyKey
        ];

        $result = $this->createEventResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            $headers,
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
        $rangeStart = "2022-08-18T18:07:10.822Z";
        $rangeEnd = "2022-08-18T18:07:10.822Z";
        $targets = [
            "id" => "team_123",
            "type" => "team",
            "name" => "team",
        ];
        $actions = ["document.updated"];
        $actors = ["Smith"];
        $actorNames = ["Smith"];
        $actorIds = ["user_123"];
        $params = [
            "organization_id" => $organizationId,
            "range_end" => $rangeEnd,
            "range_start" => $rangeStart,
            "actions" => $actions,
            "actors" => $actors,
            "actor_names" => $actorNames,
            "actor_ids" => $actorIds,
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

        $auditLogExport = $this->al->createExport($organizationId, $rangeStart, $rangeEnd, $actions, $actors, $targets, $actorNames, $actorIds);
        $exportFixture = $this->createExportFixture();

        $this->assertSame($exportFixture, $auditLogExport->toArray());
    }

    public function testGetExport()
    {
        $auditLogExportId = "123";

        $path = "audit_logs/exports/{$auditLogExportId}";

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

    public function testCreateSchema()
    {
        $path = "audit_logs/actions/document.updated/schemas";

        $action = "document.updated";
        $schema = [
            "targets" => [
                [
                    "type" => "document"
                ],
                [
                    "type" => "user"
                ]
            ]
        ];

        $params = $schema;

        $result = $this->createSchemaResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            true,
            $result
        );

        $response = $this->al->createSchema($action, $schema);
        $schemaFixture = $this->createSchemaFixture();

        $this->assertSame($schemaFixture, $response);
    }

    public function testSchemaExists()
    {
        $path = "audit_logs/actions/document.updated/schemas";
        $action = "document.updated";

        $result = $this->schemaExistsResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            $result
        );

        $exists = $this->al->schemaExists($action);

        $this->assertTrue($exists);
    }

    public function testSchemaExistsNotFound()
    {
        $path = "audit_logs/actions/nonexistent.action/schemas";
        $action = "nonexistent.action";

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            true,
            null,
            null,
            404
        );

        $exists = $this->al->schemaExists($action);

        $this->assertFalse($exists);
    }

    public function testListActions()
    {
        $path = "audit_logs/actions";

        $params = [
            "limit" => 100
        ];

        $result = $this->listActionsResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true,
            $result
        );

        $response = $this->al->listActions();
        $actionsFixture = $this->listActionsFixture();

        $this->assertSame($actionsFixture, $response);
    }

    public function testListActionsWithPagination()
    {
        $path = "audit_logs/actions";

        $params = [
            "limit" => 50,
            "before" => "action_123",
            "after" => "action_456",
            "order" => "desc"
        ];

        $result = $this->listActionsResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            $params,
            true,
            $result
        );

        $response = $this->al->listActions(50, "action_123", "action_456", "desc");
        $actionsFixture = $this->listActionsFixture();

        $this->assertSame($actionsFixture, $response);
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

    private function createSchemaFixture()
    {
        return [
            "object" => "audit_log_schema",
            "id" => "schema_123",
            "action" => "document.updated",
            "targets" => [
                ["type" => "document"],
                ["type" => "user"]
            ],
            "created_at" => "2022-08-18T18:07:10.822Z",
            "updated_at" => "2022-08-18T18:07:10.822Z",
        ];
    }

    private function createSchemaResponseFixture()
    {
        return json_encode([
            "object" => "audit_log_schema",
            "id" => "schema_123",
            "action" => "document.updated",
            "targets" => [
                ["type" => "document"],
                ["type" => "user"]
            ],
            "created_at" => "2022-08-18T18:07:10.822Z",
            "updated_at" => "2022-08-18T18:07:10.822Z",
        ]);
    }

    private function schemaExistsResponseFixture()
    {
        return json_encode([
            "object" => "audit_log_schema",
            "id" => "schema_123",
            "action" => "document.updated",
            "targets" => [
                ["type" => "document"]
            ],
            "created_at" => "2022-08-18T18:07:10.822Z",
            "updated_at" => "2022-08-18T18:07:10.822Z",
        ]);
    }

    private function listActionsFixture()
    {
        return [
            "object" => "list",
            "data" => [
                [
                    "object" => "audit_log_action",
                    "id" => "action_123",
                    "name" => "document.updated",
                    "description" => "Document was updated",
                    "created_at" => "2022-08-18T18:07:10.822Z",
                    "updated_at" => "2022-08-18T18:07:10.822Z",
                ],
                [
                    "object" => "audit_log_action",
                    "id" => "action_456",
                    "name" => "user.created",
                    "description" => "User was created",
                    "created_at" => "2022-08-18T18:07:10.822Z",
                    "updated_at" => "2022-08-18T18:07:10.822Z",
                ]
            ],
            "list_metadata" => [
                "before" => null,
                "after" => "action_456",
                "limit" => 100
            ]
        ];
    }

    private function listActionsResponseFixture()
    {
        return json_encode([
            "object" => "list",
            "data" => [
                [
                    "object" => "audit_log_action",
                    "id" => "action_123",
                    "name" => "document.updated",
                    "description" => "Document was updated",
                    "created_at" => "2022-08-18T18:07:10.822Z",
                    "updated_at" => "2022-08-18T18:07:10.822Z",
                ],
                [
                    "object" => "audit_log_action",
                    "id" => "action_456",
                    "name" => "user.created",
                    "description" => "User was created",
                    "created_at" => "2022-08-18T18:07:10.822Z",
                    "updated_at" => "2022-08-18T18:07:10.822Z",
                ]
            ],
            "list_metadata" => [
                "before" => null,
                "after" => "action_456",
                "limit" => 100
            ]
        ]);
    }
}
