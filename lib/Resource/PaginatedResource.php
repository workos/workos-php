<?php

namespace WorkOS\Resource;

/**
 * Class PaginatedResource
 *
 * A flexible paginated resource that supports multiple access patterns:
 * 1. Bare destructuring (backwards compatible): [$before, $after, $data] = $result
 * 2. Named destructuring: ["users" => $users, "after" => $after] = $result
 * 3. Fluent property access: $result->users, $result->after, $result->before
 *
 * This class standardizes pagination across all WorkOS resources while maintaining
 * backwards compatibility with existing code.
 */
class PaginatedResource implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var ?string Before cursor for pagination
     */
    private $before;

    /**
     * @var ?string After cursor for pagination
     */
    private $after;

    /**
     * @var array The paginated data items
     */
    private $data;

    /**
     * @var string The key name for the data array (e.g., 'users', 'directories')
     */
    private $dataKey;

    /**
     * PaginatedResource constructor.
     *
     * @param ?string $before Before cursor
     * @param ?string $after After cursor
     * @param array $data Array of resource objects
     * @param string $dataKey The key name for accessing the data
     */
    public function __construct(?string $before, ?string $after, array $data, string $dataKey)
    {
        $this->before = $before;
        $this->after = $after;
        $this->data = $data;
        $this->dataKey = $dataKey;
    }

    /**
     * Construct a PaginatedResource from an API response
     *
     * @param array $response The API response containing 'data', 'list_metadata', etc.
     * @param string $resourceClass The fully qualified class name of the resource type
     * @param string $dataKey The key name for the data array (e.g., 'users', 'directories')
     * @return self
     */
    public static function constructFromResponse(array $response, string $resourceClass, string $dataKey): self
    {
        $data = [];
        list($before, $after) = \WorkOS\Util\Request::parsePaginationArgs($response);

        foreach ($response["data"] as $responseData) {
            \array_push($data, $resourceClass::constructFromResponse($responseData));
        }

        return new self($before, $after, $data, $dataKey);
    }

    /**
     * Magic getter for fluent property access
     *
     * @param string $name Property name
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'before') {
            return $this->before;
        }

        if ($name === 'after') {
            return $this->after;
        }

        if ($name === 'data' || $name === $this->dataKey) {
            return $this->data;
        }

        return null;
    }

    /**
     * ArrayAccess: Check if offset exists
     *
     * @param mixed $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        // Support numeric indices for bare destructuring
        if (is_int($offset)) {
            return $offset >= 0 && $offset <= 2;
        }

        // Support named keys for named destructuring
        return in_array($offset, ['before', 'after', 'data', $this->dataKey], true);
    }

    /**
     * ArrayAccess: Get value at offset
     *
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        // Support numeric indices for bare destructuring: [0 => before, 1 => after, 2 => data]
        if ($offset === 0) {
            return $this->before;
        }

        if ($offset === 1) {
            return $this->after;
        }

        if ($offset === 2) {
            return $this->data;
        }

        // Support named keys for named destructuring
        if ($offset === 'before') {
            return $this->before;
        }

        if ($offset === 'after') {
            return $this->after;
        }

        if ($offset === 'data' || $offset === $this->dataKey) {
            return $this->data;
        }

        return null;
    }

    /**
     * ArrayAccess: Set value at offset (not supported)
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException('PaginatedResource is immutable');
    }

    /**
     * ArrayAccess: Unset offset (not supported)
     *
     * @param mixed $offset
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException('PaginatedResource is immutable');
    }

    /**
     * IteratorAggregate: Get iterator for the data array
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Magic isset for property checking
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return in_array($name, ['before', 'after', 'data', $this->dataKey], true);
    }
}
