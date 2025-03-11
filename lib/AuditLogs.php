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
     *
     * @param string $organizationId the unique identifier for the organization.
     * @param array $event Associative array containing the keys detailed below
            * string "action" Specific activity performed by the actor. REQUIRED.
            * string "occurred_at" ISO-8601 datetime at which the event happened. REQUIRED.
            * array "actor" Associative array describing Actor of the event. REQUIRED.
                * KEYS:
                * string "id" - REQUIRED
                * string "name" - NOT REQUIRED
                * string "type" - REQUIRED
                * array "metadata" - Associative array ["Any Key" => "Any Value] - NOT REQUIRED
            * array "targets" Targets of the event. Nested array as there can be multiple targets. REQUIRED
                * KEYS:
                * string "id" - REQUIRED
                * string "name" - NOT REQUIRED
                * string "type" - REQUIRED
                * array "metadata" - Associative array ["Any Key" => "Any Value] - NOT REQUIRED
            * array "context" Context of the event. REQUIRED.
                * KEYS:
                * string "location" -  REQUIRED
                * string "user_agent" -  NOT REQUIRED
            * int "version" Version of the event. Required if version is anything other than 1. NOT REQUIRED.
            * array "metadata" Arbitrary key-value data containing information associated with the event. NOT REQUIRED
     * @param string $idempotencyKey Unique key guaranteeing idempotency of events for 24 hours.
     *
     * @throws Exception\WorkOSException
     *
     * @return  Resource\AuditLogCreateEventStatus
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
     * @var null|array $actors Actor names that Audit Log Events will be filtered by.
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
