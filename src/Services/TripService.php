<?php

namespace App\Services;

use App\Core\CacheManager;
use App\Dto\TripDto;
use App\Helpers\HttpClientHelper;
use Symfony\Contracts\Cache\CacheInterface;

class TripService
{
    private StationService $stationsService;
    private HttpClientHelper $httpClientHelper;
    private CacheInterface $cache;
    private HaversineService $haversineService;

    public function __construct(
        StationService $stationsService,
        HttpClientHelper $httpClientHelper,
        CacheInterface $cache
    ) {
        $this->stationsService = $stationsService;
        $this->httpClientHelper = $httpClientHelper;
        $this->cache = $cache;
        $this->haversineService = new HaversineService();
    }

    public function execute(): array
    {
        $results = [];
        $rallyStations = $this->stationsService->getRally();
        $timeFrameService = new TimeFrameService($this->httpClientHelper, new CacheManager($this->cache));

        foreach ($rallyStations as $stationId => $station) {
            $destinations = $this->stationsService->getDestinationsById($stationId);

            if (empty($destinations)) {
                continue;
            }

            $countries = $this->getUniqueCountries($station, $destinations);

            foreach ($destinations as $destination) {
                $timeframes = $timeFrameService->execute($station, $destination);
                $tripDto = new TripDto(
                    $station,
                    $destination,
                    $countries,
                    $timeframes
                );
                $tripDto->length = $this->calculateTripHaversineLength([$station, $destination]);
                $results[] = $tripDto;
            }
        }

        return $results;
    }

    public function getUniqueCountries($station, array $destinations): array
    {
        $countries = array_merge(
            [$station->country],
            array_map(fn ($destination) => $destination->country, $destinations)
        );

        return array_unique(array_map('strtolower', $countries));
    }

    public function calculateTripHaversineLength(array $cities, int $roundPrecision = 2): float
    {
        $totalDistance = array_reduce(
            array_keys($cities),
            function ($totalDistance, $index) use ($cities) {
                if ($index === count($cities) - 1) {
                    return $totalDistance;
                }

                $startCity = $cities[$index];
                $endCity = $cities[$index + 1];

                $startCoordinates = $startCity->coordinates;
                $endCoordinates = $endCity->coordinates;

                if ($startCoordinates && $endCoordinates) {
                    $distance = $this->haversineService->execute($startCoordinates, $endCoordinates);

                    return $totalDistance + $distance;
                }

                return $totalDistance;
            },
            0.0
        );

        return round($totalDistance, $roundPrecision, PHP_ROUND_HALF_UP);
    }
}
