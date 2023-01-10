<?php

namespace WorkOS\Resource;

class BaseWorkOSResource
{
    /**
     * @var array $values;
     */
    protected $values;

    /**
     * @var array $raw;
     */
    public $raw;

    private function __construct()
    {
    }

    /**
     * Creates a Resource from a Response.
     *
     * @param \WorkOS\Resource\Response $response
     *
     * @return \WorkOS\Resource\*
     */
    public static function constructFromResponse($response)
    {
        $instance = new static();

        $instance->raw = $response;
        $instance->values = [];

        foreach (static::RESPONSE_TO_RESOURCE_KEY as $responseKey => $resourceKey) {
            try {
                $instance->values[$resourceKey] = $instance->raw[$responseKey] ?? null;
            } catch (\OutOfBoundsException $e) {
                $instance->values[$resourceKey] = null;
            }
        }

        return $instance;
    }

    public function toArray()
    {
        return \array_reduce(static::RESOURCE_ATTRIBUTES, function ($arr, $key) {
            $arr[$key] = $this->values[$key];
            return $arr;
        }, []);
    }

    /**
     * Magic method overrides.
     */
    public function __set($key, $value)
    {
        if (\in_array($key, static::RESOURCE_ATTRIBUTES)) {
            $this->values[$key] = $value;
        }

        $msg = "{$key} does not exist on " . static::class;
        throw new \WorkOS\Exception\UnexpectedValueException($msg);
    }

    public function __isset($key)
    {
        return isset($this->values[$key]);
    }

    public function __unset($key)
    {
        unset($this->values[$key]);
    }

    public function &__get($key)
    {
        if (\in_array($key, static::RESOURCE_ATTRIBUTES)) {
            return $this->values[$key];
        }

        if ($this->raw[$key]) {
            return $this->raw[$key];
        }

        $msg = "{$key} does not exist on " . static::class;
        throw new \WorkOS\Exception\UnexpectedValueException($msg);
    }
}
