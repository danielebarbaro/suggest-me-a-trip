<?php

namespace Library\RoadSurfer\Cache;

interface CacheInterface
{
    public function retrieve(string $cacheKey, callable $callback): mixed;

    public function get(string $cacheKey): mixed;

    public function set(string $cacheKey, $data): void;

    public function delete(string $cacheKey): bool;

    public function deleteAll(): bool;
}
