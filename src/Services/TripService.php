<?php

namespace App\Services;

use App\Helpers\HttpClientHelper;
use Symfony\Contracts\Cache\CacheInterface;

class TripService
{
    private CacheInterface $cache;
    private HttpClientHelper $httpClientHelper;

    public function __construct(
        HttpClientHelper $httpClientHelper,
        CacheInterface $cache
    ) {
        $this->cache = $cache;
        $this->httpClientHelper = $httpClientHelper;
    }

    public function execute(): array
    {
        $results = [];
        $stationsService = new StationService($this->httpClientHelper, $this->cache);

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
