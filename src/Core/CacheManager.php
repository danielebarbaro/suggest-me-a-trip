<?php

namespace App\Core;

use Symfony\Contracts\Cache\CacheInterface;

class CacheManager
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function retrieve(string $cacheKey, callable $callback)
    {
        $cachedValue = $this->cache->getItem($cacheKey);

        if (!$cachedValue->isHit()) {
            $cachedValue->set($callback());
            $this->cache->save($cachedValue);
        }

        return $cachedValue->get();
    }

    public function get(string $cacheKey)
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
