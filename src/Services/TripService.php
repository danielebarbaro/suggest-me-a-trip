<?php

namespace App\Services;

use App\Dto\TripDto;
use App\Helpers\HttpClientHelper;
use Symfony\Contracts\Cache\CacheInterface;

class TripService
{
    private CacheInterface $cache;
    private HttpClientHelper $httpClientHelper;
    private GeoCoderService $geocoder;

    public function __construct(
        GeoCoderService $geocoder,
        HttpClientHelper $httpClientHelper,
        CacheInterface $cache
    ) {
        $this->geocoder = $geocoder;
        $this->cache = $cache;
        $this->httpClientHelper = $httpClientHelper;
    }

    public function execute(): array
    {
        $results = [];
        $stationsService = new StationService($this->geocoder, $this->httpClientHelper, $this->cache);

        $rallyStations = $stationsService->getRally();

        foreach ($rallyStations as $stationId => $station) {
            $destinations = $stationsService->getDestinationsById($stationId);
            if (empty($destinations)) {
                continue;
            }

            $countries = array_merge(
                [$station->country],
                array_map(fn ($destination) => $destination->country, $destinations)
            );

            $results[] = new TripDto(
                $station,
                $destinations,
                array_unique(array_map('strtolower', $countries))
            );
        }

        return $results;
    }
}
