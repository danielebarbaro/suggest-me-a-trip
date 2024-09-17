<?php

use App\Core\CacheManager;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

beforeEach(function () {
    $this->cacheMock = Mockery::mock(CacheInterface::class);
    $this->cacheManager = new CacheManager($this->cacheMock);
});

afterEach(function () {
    Mockery::close();
});

it('retrieves an item from cache if it exists', function () {
    $cacheKey = 'test_key';
    $cacheItemMock = Mockery::mock(CacheItemInterface::class);

    $cacheItemMock->shouldReceive('isHit')->andReturn(true);
    $cacheItemMock->shouldReceive('get')->andReturn('cached_value');

    $this->cacheMock->shouldReceive('getItem')->with($cacheKey)->andReturn($cacheItemMock);

    $result = $this->cacheManager->get($cacheKey);

    expect($result)->toBe('cached_value');
});

it('stores and retrieves an item in the cache', function () {
    $cacheKey = 'test_key_2';
    $cacheItemMock = Mockery::mock(CacheItemInterface::class);

    $cacheItemMock->shouldReceive('isHit')->andReturn(false);
    $cacheItemMock->shouldReceive('set')->with('new_value');
    $cacheItemMock->shouldReceive('get')->andReturn('new_value');

    $this->cacheMock->shouldReceive('getItem')->with($cacheKey)->andReturn($cacheItemMock);
    $this->cacheMock->shouldReceive('save')->with($cacheItemMock);

    $result = $this->cacheManager->retrieve($cacheKey, fn () => 'new_value');

    expect($result)->toBe('new_value');
});

it('deletes a specific item from the cache', function () {
    $cacheKey = 'test_key';

    $this->cacheMock->shouldReceive('deleteItem')->with($cacheKey)->andReturn(true);

    $result = $this->cacheManager->delete($cacheKey);

    expect($result)->toBeTrue();
});

it('clears all items from the cache', function () {
    $this->cacheMock->shouldReceive('clear')->andReturn(true);

    $result = $this->cacheManager->deleteAll();

    expect($result)->toBeTrue();
});
