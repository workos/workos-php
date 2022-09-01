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
            * @var string "action" Specific activity performed by the actor. REQUIRED.
            * @var string "occurred_at" ISO-8601 datetime at which the event happened. REQUIRED.
            * @var array "actor" Associative array describing Actor of the event. REQUIRED.
                * KEYS:
                * "id" - string - REQUIRED
                * "name" - string - NOT REQUIRED
                * "type" - string - REQUIRED
                * "metadata" - associative array ["Any Key" => "Any Value] - NOT REQUIRED
            * @var array "targets" Targets of the event. REQUIRED.
            * Nested array as there can be multiple targets.
                * KEYS:
                * "id" - string - REQUIRED
                * "name" - string - NOT REQUIRED
                * "type" - string - REQUIRED
                * "metadata" - associative array ["Any Key" => "Any Value] - NOT REQUIRED
            * @var array "context" Context of the event. REQUIRED.
                * KEYS:
                * "location" - string - REQUIRED
                * "user_agent" - string - NOT REQUIRED
            * @var int "version" Version of the event. Required if version is anything other than 1. NOT REQUIRED.
            * @var array "metadata" Arbitrary key-value data containing information associated with the event. NOT REQUIRED
     * @param string $idempotencyKey Unique key guaranteeing idempotency of events for 24 hours.
     *
     * @return  \WorkOS\Resource\AuditLogCreateEventStatus
     */

    public function createEvent($organizationId, $event, $idempotencyKey = null)
    {
        $eventsPath = "audit_logs/events";

        $params = [
            "organization_id" => $organizationId,
            "event" => $event,
            "idempotency_key" => $idempotencyKey
        ];

        $response = Client::request(Client::METHOD_POST, $eventsPath, null, $params, true);

        return Resource\AuditLogCreateEventStatus::constructFromResponse($response);
    }

    /**
     * @param array $auditLogExportOptions Associative array containing the keys detailed below
        * @var null|string $organizationId Description of the record.
        * @var null|string $rangeStart The start of Export's the date range.
        * @var null|string $rangeEnd The end of Export's the date range.
        * @var null|array $actions Actions that Audit Log Events will be filtered by.
        * @var null|array $actors Actor names that Audit Log Events will be filtered by.
        * @var null|array $targets Target types that Audit Log Events will be filtered by.
        *
        * @return Resource\AuditLogExport
     */

    public function createExport($organizationId, $rangeStart, $rangeEnd, $actions = null, $actors = null, $targets = null)
    {
        $createExportPath = "audit_logs/exports";

        $params = [
         "actions" => $actions,
         "actors" => $actors,
         "organization_id" => $organizationId,
         "range_end" => $rangeEnd,
         "range_start" => $rangeStart,
         "targets" => $targets
        ];

        $response = Client::request(Client::METHOD_POST, $createExportPath, null, $params, true);
        return Resource\AuditLogExport::constructFromResponse($response);
    }

    /**
       * @param string $auditLogExportId Unique identifier of the Audit Log Export
       *
       * @return Resource\AuditLogExport
    */

    public function getExport($auditLogExportId)
    {
        $getExportPath = "audit_logs/exports/${auditLogExportId}";

        $response = Client::request(Client::METHOD_GET, $getExportPath, null, null, true);

        return Resource\AuditLogExport::constructFromResponse($response);
    }
}
