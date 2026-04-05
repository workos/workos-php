<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS;

class PaginatedResponse implements \IteratorAggregate
{
    public function __construct(
        public readonly array $data,
        public readonly array $listMetadata,
        private readonly ?\Closure $fetchPage = null,
    ) {
    }

    public static function fromArray(array $response, ?string $modelClass = null): self
    {
        $data = $response['data'] ?? [];
        if ($modelClass !== null) {
            $data = array_map(fn ($item) => $modelClass::fromArray($item), $data);
        }
        return new self($data, $response['list_metadata'] ?? []);
    }

    public function hasMore(): bool
    {
        return ($this->listMetadata['after'] ?? null) !== null;
    }

    public function autoPagingIterator(): \Generator
    {
        return $this->getIterator();
    }

    public function getIterator(): \Generator
    {
        $page = $this;
        while (true) {
            yield from $page->data;
            if (!$page->hasMore() || $page->fetchPage === null) {
                break;
            }
            $page = ($page->fetchPage)(['after' => $page->listMetadata['after']]);
        }
    }
}
