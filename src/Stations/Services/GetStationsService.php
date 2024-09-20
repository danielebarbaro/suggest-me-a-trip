<?php

namespace App\Stations\Services;

use App\Shared\Exceptions\GeoCoderException;
use App\Stations\Station;
use App\Utils\GeoCoderService;
use Geocoder\Provider\Provider;
use Psr\Cache\CacheItemPoolInterface;

class GetStationsService
{
    private GeoCoderService $geoCoderService;
    private Provider $provider;
    private CacheItemPoolInterface $cacheAdapter;

    public function __construct(
        Provider $provider,
        CacheItemPoolInterface $cacheAdapter,
    ) {
        $this->provider = $provider;
        $this->cacheAdapter = $cacheAdapter;
    }

    public function execute(array $stations): array
    {
        $results = [];
        $geoCoderService = new GeoCoderService($this->provider, $this->cacheAdapter);

        foreach ($stations as $station) {
            try {
                $coordinates = $geoCoderService->execute($station->fullName);
            } catch (GeoCoderException $e) {
                // TODO: Log the exception
                continue;
            }

            $results[$station->id] = new Station(
                $station->id,
                $station->name,
                $station->fullName,
                $station->city->countryName,
                $coordinates,
            );
        }

        return $results;
    }
}
