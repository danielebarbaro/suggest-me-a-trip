<?php

namespace App\Utils;

use App\Shared\Exceptions\GeoCoderException;
use Geocoder\Exception\Exception;
use Geocoder\Provider\Cache\ProviderCache;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;

class GeoCoderService
{
    public Provider $provider;
    private CacheItemPoolInterface $cacheAdapter;

    public function __construct(
        Provider $provider,
        CacheItemPoolInterface $cacheAdapter,
    ) {
        $this->provider = $provider;
        $this->cacheAdapter = $cacheAdapter;
    }

    public function execute(string $city): ?array
    {
        try {
            $cachedProvider = new ProviderCache(
                $this->provider,
                new Psr16Cache($this->cacheAdapter),
                $_ENV['GEO_CACHE_TTL']
            );

            $geocoder = new StatefulGeocoder($cachedProvider, 'en');
            $result = $geocoder->geocodeQuery(GeocodeQuery::create($city));
        } catch (Exception|GeoCoderException $e) {
            // TODO: Log the exception
            return [];
        }

        if ($result->isEmpty()) {
            return [];
        }

        $coordinates = $result->first()->getCoordinates();

        return [
            $coordinates->getLatitude(),
            $coordinates->getLongitude(),
        ];
    }
}
