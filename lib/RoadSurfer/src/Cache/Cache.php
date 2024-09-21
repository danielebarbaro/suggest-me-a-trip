<?php

namespace Library\RoadSurfer\Cache;

use Symfony\Contracts\Cache\CacheInterface as SymfonyCacheInterface;

class Cache implements CacheInterface
{
    private SymfonyCacheInterface $cache;

    public function __construct(SymfonyCacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function retrieve(string $cacheKey, callable $callback): mixed
    {
        $cachedValue = $this->cache->getItem($cacheKey);

        if (!$cachedValue->isHit()) {
            $cachedValue->set($callback());
            $this->cache->save($cachedValue);
        }

        return $cachedValue->get();
    }

    public function get(string $cacheKey): mixed
    {
        $cachedValue = $this->cache->getItem($cacheKey);

        return $cachedValue->isHit() ? $cachedValue->get() : null;
    }

    public function set(string $cacheKey, $data): void
    {
        $cachedValue = $this->cache->getItem($cacheKey);
        $cachedValue->set($data);
        $this->cache->save($cachedValue);
    }

    public function delete(string $cacheKey): bool
    {
        return $this->cache->deleteItem($cacheKey);
    }

    public function deleteAll(): bool
    {
        return $this->cache->clear();
    }
}
