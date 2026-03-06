<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;
use WorkOS\Resource\PaginatedResource;

class PaginatedResourceTest extends TestCase
{
    private function makePaginatedResource(
        ?string $before = null,
        ?string $after = null,
        array $data = [],
        string $dataKey = 'items'
    ): PaginatedResource {
        return new PaginatedResource($before, $after, $data, $dataKey);
    }

    // -- Construction --

    public function testConstructWithValidDataKey()
    {
        $resource = $this->makePaginatedResource(null, 'cursor_abc', ['a', 'b'], 'users');
        $this->assertInstanceOf(PaginatedResource::class, $resource);
    }

    public function testConstructRejectsReservedDataKeyBefore()
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaginatedResource(null, null, [], 'before');
    }

    public function testConstructRejectsReservedDataKeyAfter()
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaginatedResource(null, null, [], 'after');
    }

    public function testConstructRejectsReservedDataKeyData()
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaginatedResource(null, null, [], 'data');
    }

    // -- Bare destructuring (numeric offsets) --

    public function testBareDestructuring()
    {
        $resource = $this->makePaginatedResource('cur_before', 'cur_after', ['x', 'y']);

        [$before, $after, $items] = $resource;

        $this->assertSame('cur_before', $before);
        $this->assertSame('cur_after', $after);
        $this->assertSame(['x', 'y'], $items);
    }

    public function testBareDestructuringWithNullCursors()
    {
        $resource = $this->makePaginatedResource(null, null, ['x']);

        [$before, $after, $items] = $resource;

        $this->assertNull($before);
        $this->assertNull($after);
        $this->assertSame(['x'], $items);
    }

    // -- Named destructuring (string offsets) --

    public function testNamedDestructuring()
    {
        $resource = $this->makePaginatedResource('b', 'a', [1, 2], 'users');

        ["before" => $before, "after" => $after, "users" => $users] = $resource;

        $this->assertSame('b', $before);
        $this->assertSame('a', $after);
        $this->assertSame([1, 2], $users);
    }

    public function testGenericDataKeyAccess()
    {
        $resource = $this->makePaginatedResource(null, null, [1, 2], 'users');

        $this->assertSame($resource['data'], $resource['users']);
    }

    // -- Fluent property access (__get) --

    public function testFluentPropertyAccess()
    {
        $resource = $this->makePaginatedResource('b', 'a', [1], 'directories');

        $this->assertSame('b', $resource->before);
        $this->assertSame('a', $resource->after);
        $this->assertSame([1], $resource->directories);
        $this->assertSame([1], $resource->data);
    }

    public function testFluentAccessUnknownPropertyReturnsNull()
    {
        $resource = $this->makePaginatedResource(null, null, []);

        $this->assertNull($resource->nonexistent);
        $this->assertNull($resource->typo_property);
    }

    // -- offsetExists --

    public function testOffsetExistsNumericIndices()
    {
        $resource = $this->makePaginatedResource();

        $this->assertTrue(isset($resource[0]));
        $this->assertTrue(isset($resource[1]));
        $this->assertTrue(isset($resource[2]));
        $this->assertFalse(isset($resource[3]));
        $this->assertFalse(isset($resource[-1]));
    }

    public function testOffsetExistsStringKeys()
    {
        $resource = $this->makePaginatedResource(null, null, [], 'users');

        $this->assertTrue(isset($resource['before']));
        $this->assertTrue(isset($resource['after']));
        $this->assertTrue(isset($resource['data']));
        $this->assertTrue(isset($resource['users']));
        $this->assertFalse(isset($resource['nonexistent']));
    }

    // -- __isset --

    public function testIssetReturnsFalseForNullCursors()
    {
        $resource = $this->makePaginatedResource(null, null, []);

        $this->assertFalse(isset($resource->before));
        $this->assertFalse(isset($resource->after));
    }

    public function testIssetReturnsTrueForNonNullCursors()
    {
        $resource = $this->makePaginatedResource('b', 'a', []);

        $this->assertTrue(isset($resource->before));
        $this->assertTrue(isset($resource->after));
    }

    public function testIssetReturnsTrueForDataKey()
    {
        $resource = $this->makePaginatedResource(null, null, [], 'users');

        $this->assertTrue(isset($resource->data));
        $this->assertTrue(isset($resource->users));
    }

    public function testIssetReturnsFalseForUnknownProperties()
    {
        $resource = $this->makePaginatedResource();

        $this->assertFalse(isset($resource->nonexistent));
    }

    // -- Immutability --

    public function testOffsetSetThrows()
    {
        $resource = $this->makePaginatedResource();

        $this->expectException(\BadMethodCallException::class);
        $resource[0] = 'value';
    }

    public function testOffsetUnsetThrows()
    {
        $resource = $this->makePaginatedResource();

        $this->expectException(\BadMethodCallException::class);
        unset($resource[0]);
    }

    // -- Countable --

    public function testCountReturnsDataItemCount()
    {
        $this->assertCount(0, $this->makePaginatedResource(null, null, []));
        $this->assertCount(3, $this->makePaginatedResource(null, null, ['a', 'b', 'c']));
        $this->assertCount(1, $this->makePaginatedResource(null, null, ['x']));
    }

    // -- IteratorAggregate --

    public function testForeachIteratesDataItems()
    {
        $data = ['item1', 'item2', 'item3'];
        $resource = $this->makePaginatedResource(null, null, $data);

        $collected = [];
        foreach ($resource as $item) {
            $collected[] = $item;
        }

        $this->assertSame($data, $collected);
    }

    public function testForeachOnEmptyData()
    {
        $resource = $this->makePaginatedResource();

        $collected = [];
        foreach ($resource as $item) {
            $collected[] = $item;
        }

        $this->assertSame([], $collected);
    }

    // -- offsetGet edge cases --

    public function testOffsetGetReturnsNullForUnknownOffset()
    {
        $resource = $this->makePaginatedResource();

        $this->assertNull($resource[99]);
        $this->assertNull($resource['unknown_key']);
    }
}
