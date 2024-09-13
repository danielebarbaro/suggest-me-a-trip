<?php

namespace App\Services;

use App\Helpers\HttpClientHelper;
use Symfony\Contracts\Cache\CacheInterface;

class TripService
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function execute(): array
    {
        $results = [];
        $client = new HttpClientHelper();
        $stationsService = new StationService($client, $this->cache);

        $rallyStations = $stationsService->getRally();

        foreach ($rallyStations as $stationId => $station) {
            $destinations = $stationsService->getById($stationId);
            if (empty($destinations)) {
                continue;
            }

            $results[] = [
                'pickup_station' => $station['name'],
                'dropoff_station' => $destinations,
                'countries' => array_merge([$station['country']], array_column($destinations, 'country')),
            ];
        }

        return $results;
    }
}
