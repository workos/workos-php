<?php

namespace WorkOS;

/**
 * Class Events
 *
 * This class facilitates the use of WorkOS Events API.
 */
class Events
{
    /**
     * List events with optional filtering and pagination.
     *
     * @param array $params Optional parameters:
     *   - **events** (string|array): Comma-separated string or array of event types to filter by (required - at least one event type must be specified)
     *   - **organization_id** (string): Filter events by organization ID
     *   - **limit** (int): Number of events to return
     *   - **after** (string): Cursor for pagination (object ID)
     *   - **before** (string): Cursor for pagination (object ID)
     *   - **order** (string): Sort order - 'asc' or 'desc'
     *
     * @throws Exception\WorkOSException
     *
     * @return array{null, string|null, array<Resource\Event>} Returns [before, after, events] where before is always null for Events API
     */
    public function listEvents($params = [])
    {
        $eventsPath = "events";

        // Handle events parameter - convert array to comma-separated string
        if (isset($params['events']) && is_array($params['events'])) {
            $params['events'] = implode(',', $params['events']);
        }

        $response = Client::request(Client::METHOD_GET, $eventsPath, null, $params, true);
        $events = [];
        foreach ($response["data"] as $responseData) {
            \array_push($events, Resource\Event::constructFromResponse($responseData));
        }
        $after = $response["list_metadata"]["after"] ?? null;
        return [null, $after, $events];
    }
}
