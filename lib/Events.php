<?php

namespace WorkOS;

/**
 * Class Events
 *
 * This class facilitates the use of WorkOS Events API.
 * 
 * Security considerations:
 * - All input parameters are validated and sanitized
 * - Event types are validated against a whitelist
 * - Parameter limits are enforced to prevent abuse
 * - API authentication is handled by the Client class
 */
class Events
{
    /**
     * Maximum number of events that can be requested in a single API call
     */
    private const MAX_EVENTS_LIMIT = 100;
    
    /**
     * List events with optional filtering and pagination.
     *
     * @param array $params Optional parameters:
     *   - **events** (string|array): Comma-separated string or array of event types to filter by (required - at least one event type must be specified)
     *   - **organization_id** (string): Filter events by organization ID
     *   - **limit** (int): Number of events to return (default: 50, max: 100)
     *   - **after** (string): Cursor for pagination (object ID)
     *   - **before** (string): Cursor for pagination (object ID)
     *   - **order** (string): Sort order - 'asc' or 'desc' (default: 'desc')
     *
     * @throws Exception\WorkOSException
     *
     * @return array<string, mixed>
     */
    public function listEvents($params = [])
    {
        $eventsPath = "events";
        
        // Validate and sanitize input parameters
        $params = $this->validateAndSanitizeParams($params);
        
        // Handle events parameter - convert array to comma-separated string
        if (isset($params['events']) && is_array($params['events'])) {
            $params['events'] = implode(',', $params['events']);
        }
        
        // Note: The WorkOS Events API requires at least one event type to be specified
        // If no events parameter is provided, the API will return a 400 error
        // Consider using getValidEventTypes() to get available event types
        
        return Client::request(Client::METHOD_GET, $eventsPath, null, $params, true);
    }



    /**
     * Get available event types.
     *
     * @return array<string> Array of valid event types
     */
    public function getValidEventTypes()
    {
        return [
            // Authentication Events
            'authentication.email_verification_succeeded',
            'authentication.magic_auth_failed',
            'authentication.magic_auth_succeeded',
            'authentication.mfa_failed',
            'authentication.mfa_succeeded',
            'authentication.oauth_failed',
            'authentication.oauth_succeeded',
            'authentication.password_failed',
            'authentication.password_succeeded',
            'authentication.passkey_failed',
            'authentication.passkey_succeeded',
            'authentication.sso_failed',
            'authentication.sso_succeeded',
            'authentication.radar_risk_detected',
            
            // Connection Events
            'connection.activated',
            'connection.deactivated',
            'connection.deleted',
            'connection.saml_certificate_renewed',
            'connection.saml_certificate_renewal_required',
            
            // DSync Events
            'dsync.activated',
            'dsync.deleted',
            'dsync.group.created',
            'dsync.group.deleted',
            'dsync.group.updated',
            'dsync.group.user_added',
            'dsync.group.user_removed',
            'dsync.user.created',
            'dsync.user.deleted',
            'dsync.user.updated',
            
            // Email Verification Events
            'email_verification.created',
            
            // Flag Events
            'flag.created',
            'flag.updated',
            'flag.deleted',
            'flag.rule_updated',
            
            // Invitation Events
            'invitation.accepted',
            'invitation.created',
            'invitation.revoked',
            
            // Organization Events
            'organization.created',
            'organization.updated',
            'organization.deleted',
            'organization_domain.created',
            'organization_domain.updated',
            'organization_domain.deleted',
            'organization_domain.verified',
            'organization_domain.verification_failed',
            'organization_membership.created',
            'organization_membership.deleted',
            'organization_membership.updated',
            
            // Password Reset Events
            'password_reset.created',
            'password_reset.succeeded',
            
            // Role Events
            'role.created',
            'role.deleted',
            'role.updated',
            
            // Session Events
            'session.created',
            'session.revoked',
            
            // User Events
            'user.created',
            'user.deleted',
            'user.updated',
        ];
    }

    /**
     * Validate event types against the list of valid types.
     *
     * @param string|array $eventTypes Event type(s) to validate
     *
     * @return bool True if all event types are valid
     */
    public function validateEventTypes($eventTypes)
    {
        // Handle null or empty input
        if (empty($eventTypes)) {
            return false;
        }
        
        $validTypes = $this->getValidEventTypes();
        
        if (is_string($eventTypes)) {
            $eventTypes = explode(',', $eventTypes);
        }
        
        // Ensure we have an array
        if (!is_array($eventTypes)) {
            return false;
        }
        
        foreach ($eventTypes as $eventType) {
            $eventType = trim($eventType);
            
            // Skip empty strings after trimming
            if (empty($eventType)) {
                continue;
            }
            
            // Validate against whitelist
            if (!in_array($eventType, $validTypes, true)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get events filtered by type.
     *
     * @param string|array $eventTypes Required event types to filter by (comma-separated string or array)
     * @param array $params Optional parameters (same as listEvents)
     *
     * @throws Exception\BadRequestException if invalid event types are provided
     * @throws Exception\WorkOSException
     *
     * @return array Events filtered by the specified types
     */
    public function getEventsByType($eventTypes, $params = [])
    {
        // Validate event types before proceeding
        if (!$this->validateEventTypes($eventTypes)) {
            throw new Exception\BadRequestException(
                new Resource\Response(json_encode(['error' => 'One or more event types are invalid']), [], 400)
            );
        }
        
        // Add the required events parameter
        $params['events'] = $eventTypes;
        
        return $this->listEvents($params);
    }

    /**
     * Validate and sanitize input parameters for API requests.
     *
     * @param array $params Raw input parameters
     *
     * @return array Sanitized parameters
     *
     * @throws Exception\BadRequestException if invalid parameters are provided
     */
    private function validateAndSanitizeParams($params)
    {
        $sanitized = [];
        
        // Validate limit parameter
        if (isset($params['limit'])) {
            $limit = filter_var($params['limit'], FILTER_VALIDATE_INT);
            if ($limit === false || $limit < 1 || $limit > self::MAX_EVENTS_LIMIT) {
                throw new Exception\BadRequestException(
                    new Resource\Response(json_encode(['error' => 'Limit must be an integer between 1 and ' . self::MAX_EVENTS_LIMIT]), [], 400)
                );
            }
            $sanitized['limit'] = $limit;
        }
        
        // Validate order parameter
        if (isset($params['order'])) {
            $order = strtolower(trim($params['order']));
            if (!in_array($order, ['asc', 'desc'], true)) {
                throw new Exception\BadRequestException(
                    new Resource\Response(json_encode(['error' => 'Order must be "asc" or "desc"']), [], 400)
                );
            }
            $sanitized['order'] = $order;
        }
        
        // Validate organization_id parameter
        if (isset($params['organization_id'])) {
            $orgId = trim($params['organization_id']);
            if (empty($orgId) || strlen($orgId) > 255) {
                throw new Exception\BadRequestException(
                    new Resource\Response(json_encode(['error' => 'Organization ID must be a non-empty string up to 255 characters']), [], 400)
                );
            }
            $sanitized['organization_id'] = $orgId;
        }
        
        // Validate after/before cursor parameters
        if (isset($params['after'])) {
            $after = trim($params['after']);
            if (strlen($after) > 255) {
                throw new Exception\BadRequestException(
                    new Resource\Response(json_encode(['error' => 'After cursor must be a string up to 255 characters']), [], 400)
                );
            }
            $sanitized['after'] = $after;
        }
        
        if (isset($params['before'])) {
            $before = trim($params['before']);
            if (strlen($before) > 255) {
                throw new Exception\BadRequestException(
                    new Resource\Response(json_encode(['error' => 'Before cursor must be a string up to 255 characters']), [], 400)
                );
            }
            $sanitized['before'] = $before;
        }
        
        // Validate events parameter
        if (isset($params['events'])) {
            $events = $params['events'];
            
            // Convert string to array for validation
            if (is_string($events)) {
                $events = explode(',', $events);
            }
            
            if (!is_array($events) || empty($events)) {
                throw new Exception\BadRequestException(
                    new Resource\Response(json_encode(['error' => 'Events parameter must be a non-empty array or comma-separated string']), [], 400)
                );
            }
            
            // Validate each event type
            if (!$this->validateEventTypes($events)) {
                throw new Exception\BadRequestException(
                    new Resource\Response(json_encode(['error' => 'One or more event types are invalid']), [], 400)
                );
            }
            
            $sanitized['events'] = $events;
        }
        
        return $sanitized;
    }

}
