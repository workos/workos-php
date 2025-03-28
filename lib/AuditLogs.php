<?php

namespace WorkOS;

/**
 * Class AuditLogs
 *
 * This class facilitates the use of WorkOS Audit Logs.
 */
class AuditLogs
{
    /**
     * Creates an audit log event for an organization.
     *
     * @param string $organizationId The unique identifier for the organization.
     * @param array  $event          An associative array with the following keys:
     *   - **action** (string, *required*): Specific activity performed by the actor.
     *   - **occurred_at** (string, *required*): ISO-8601 datetime when the event occurred.
     *   - **actor** (array, *required*): Associative array describing the actor.
     *     - **id** (string, *required*): Unique identifier for the actor.
     *     - **name** (string, *optional*): Name of the actor.
     *     - **type** (string, *required*): Type or role of the actor.
     *     - **metadata** (array, *optional*): Arbitrary key-value data.
     *   - **targets** (array, *required*): Array of associative arrays for each target.
     *     Each target includes:
     *     - **id** (string, *required*): Unique identifier for the target.
     *     - **name** (string, *optional*): Name of the target.
     *     - **type** (string, *required*): Type or category of the target.
     *     - **metadata** (array, *optional*): Arbitrary key-value data.
     *   - **context** (array, *required*): Associative array providing additional context.
     *     - **location** (string, *required*): Location associated with the event.
     *     - **user_agent** (string, *optional*): User agent string if applicable.
     *   - **version** (int, *optional*): Event version. Required if the version is not 1.
     *   - **metadata** (array, *optional*): Additional arbitrary key-value data for the event.
     *
     * @param string $idempotencyKey A unique key ensuring idempotency of events for 24 hours.
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\AuditLogCreateEventStatus
     */
    public function createEvent($organizationId, $event, $idempotencyKey = null)
    {
        $eventsPath = "audit_logs/events";

        $params = [
            "organization_id" => $organizationId,
            "event" => $event
        ];

        $headers = [
            "idempotency_key" => $idempotencyKey
        ];

        $response = Client::request(Client::METHOD_POST, $eventsPath, $headers, $params, true);

        return Resource\AuditLogCreateEventStatus::constructFromResponse($response);
    }

    /**
     * @param array $auditLogExportOptions Associative array containing the keys detailed below
     * @var null|string $organizationId Description of the record.
     * @var null|string $rangeStart ISO-8601 Timestamp of the start of Export's the date range.
     * @var null|string $rangeEnd ISO-8601 Timestamp  of the end of Export's the date range.
     * @var null|array $actions Actions that Audit Log Events will be filtered by.
     * @var null|array $actors Actor names that Audit Log Events will be filtered by. @deprecated 3.3.0 Use $actorNames instead. This method will be removed in a future major version.
     * @var null|array $targets Target types that Audit Log Events will be filtered by.
     * @var null|array $actorNames Actor names that Audit Log Events will be filtered by.
     * @var null|array $actorIds Actor IDs that Audit Log Events will be filtered by.
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\AuditLogExport
     */

    public function createExport($organizationId, $rangeStart, $rangeEnd, $actions = null, $actors = null, $targets = null, $actorNames = null, $actorIds = null)
    {
        $createExportPath = "audit_logs/exports";

        $params = [
            "organization_id" => $organizationId,
            "range_end" => $rangeEnd,
            "range_start" => $rangeStart
        ];

        if (!is_null($actions)) {
            $params["actions"] = $actions;
        };

        if (!is_null($actors)) {
            $msg = "'actors' is deprecated. Please use 'actorNames' instead'";

            error_log($msg);

            $params["actors"] = $actors;
        };

        if (!is_null($actorNames)) {
            $params["actor_names"] = $actorNames;
        };

        if (!is_null($actorIds)) {
            $params["actor_ids"] = $actorIds;
        };

        if (!is_null($targets)) {
            $params["targets"] = $targets;
        };

        $response = Client::request(Client::METHOD_POST, $createExportPath, null, $params, true);
        return Resource\AuditLogExport::constructFromResponse($response);
    }

    /**
     * @param string $auditLogExportId Unique identifier of the Audit Log Export
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\AuditLogExport
     */
    public function getExport($auditLogExportId)
    {
        $getExportPath = "audit_logs/exports/{$auditLogExportId}";

        $response = Client::request(Client::METHOD_GET, $getExportPath, null, null, true);

        return Resource\AuditLogExport::constructFromResponse($response);
    }
}
