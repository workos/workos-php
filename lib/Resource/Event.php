<?php

namespace WorkOS\Resource;

/**
 * Class Event
 *
 * Represents a WorkOS Event resource.
 */
class Event extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "event";

    public const RESOURCE_ATTRIBUTES = [
        "id",
        "object",
        "event",
        "data",
        "created_at",
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "object" => "object", 
        "event" => "event",
        "data" => "data",
        "created_at" => "createdAt",
    ];

    /**
     * Get the event ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->values["id"];
    }

    /**
     * Get the event object type.
     *
     * @return string
     */
    public function getObject()
    {
        return $this->values["object"];
    }

    /**
     * Get the event type.
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->values["event"];
    }

    /**
     * Get the event data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->values["data"];
    }

    /**
     * Get the event creation timestamp.
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->values["createdAt"];
    }

    /**
     * Get formatted creation date.
     *
     * @param string $format PHP date format (default: 'Y-m-d H:i:s')
     *
     * @return string
     */
    public function getFormattedCreatedAt($format = 'Y-m-d H:i:s')
    {
        return date($format, strtotime($this->values["createdAt"]));
    }

    /**
     * Check if event is of a specific type.
     *
     * @param string $eventType The event type to check
     *
     * @return bool
     */
    public function isEventType($eventType)
    {
        return $this->values["event"] === $eventType;
    }

    /**
     * Check if event is an authentication event.
     *
     * @return bool
     */
    public function isAuthenticationEvent()
    {
        return strpos($this->values["event"], 'authentication.') === 0;
    }

    /**
     * Check if event is a user event.
     *
     * @return bool
     */
    public function isUserEvent()
    {
        return strpos($this->values["event"], 'user.') === 0;
    }

    /**
     * Check if event is an organization event.
     *
     * @return bool
     */
    public function isOrganizationEvent()
    {
        return strpos($this->values["event"], 'organization') === 0;
    }

    /**
     * Check if event is a DSync event.
     *
     * @return bool
     */
    public function isDSyncEvent()
    {
        return strpos($this->values["event"], 'dsync.') === 0;
    }

    /**
     * Get a specific data field from the event data.
     *
     * @param string $key The data key to retrieve
     * @param mixed $default Default value if key doesn't exist
     *
     * @return mixed
     */
    public function getDataField($key, $default = null)
    {
        $data = $this->values["data"];
        
        if (is_array($data) && array_key_exists($key, $data)) {
            return $data[$key];
        }
        
        return $default;
    }

    /**
     * Get the event data as JSON string.
     *
     * @param int $options JSON encoding options
     *
     * @return string
     */
    public function getDataAsJson($options = JSON_PRETTY_PRINT)
    {
        return json_encode($this->values["data"], $options);
    }
}
