<?php

namespace App\Services;

use App\Dto\TripDto;
use App\Helpers\HttpClientHelper;
use Symfony\Contracts\Cache\CacheInterface;

class TripService
{
    private CacheInterface $cache;
    private HttpClientHelper $httpClientHelper;
    private StationService $stationsService;

    public function __construct(
        StationService $stationsService,
        HttpClientHelper $httpClientHelper,
        CacheInterface $cache
    ) {
        $this->stationsService = $stationsService;
        $this->cache = $cache;
        $this->httpClientHelper = $httpClientHelper;
    }

    public function execute(): array
    {
        $results = [];
        $rallyStations = $this->stationsService->getRally();

        foreach ($rallyStations as $stationId => $station) {
            $destinations = $this->stationsService->getDestinationsById($stationId);
            if (empty($destinations)) {
                continue;
            }

            $countries = $this->getUniqueCountries($station, $destinations);

            $results[] = new TripDto(
                $station,
                $destinations,
                $countries
            );
        }

        return $results;
    }

    private function getUniqueCountries($station, array $destinations): array
    {
        $countries = array_merge(
            [$station->country],
            array_map(fn ($destination) => $destination->country, $destinations)
        );

        return array_unique(array_map('strtolower', $countries));
    }
}
