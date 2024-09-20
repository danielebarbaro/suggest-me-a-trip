<?php

namespace App\Trips\Services;

use App\Stations\Services\GetStationsService;
use App\Trips\Trip;
use App\Utils\HaversineService;
use Geocoder\Provider\Provider;
use Library\RoadSurfer\RoadSurfer;
use Psr\Cache\CacheItemPoolInterface;

class CreateTripsService
{
    private RoadSurfer $roadSurfer;
    private Provider $provider;
    private CacheItemPoolInterface $cacheAdapter;

    public function __construct(
        RoadSurfer $roadSurfer,
        Provider $provider,
        CacheItemPoolInterface $cacheAdapter,
    ) {
        $this->roadSurfer = $roadSurfer;
        $this->provider = $provider;
        $this->cacheAdapter = $cacheAdapter;
    }

    public function execute(): array
    {
        $stationService = new GetStationsService($this->provider, $this->cacheAdapter);
        $results = [];
        $rallyStations = $stationService->execute($this->roadSurfer->getRallyStations());

        foreach ($rallyStations as $stationId => $pickupStation) {
            $dropoffStations = $this->roadSurfer->getStationById($stationId);
            if (empty($dropoffStations)) {
                continue;
            }

            $dropoffStations = $stationService->execute($dropoffStations);
            $countries = $this->getUniqueCountries($pickupStation, $dropoffStations);

            foreach ($dropoffStations as $dropoffStation) {
                $id = "{$pickupStation->id}-{$dropoffStation->id}";
                $timeframes = $this->roadSurfer->getStationTimeFramesByStationIds($id);
                if (empty($timeframes)) {
                    continue;
                }

                $trip = new Trip(
                    $pickupStation,
                    $dropoffStation,
                    $countries,
                    $timeframes
                );
                $trip->length = $this->calculateDistance($trip);
                $results[] = $trip;
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

    public function calculateDistance(Trip $trip, int $roundPrecision = 2): float
    {
        $haversineService = new HaversineService();
        $totalDistance = array_reduce(
            [$trip->pickupStation->id, $trip->dropoffStation->id],
            function ($totalDistance, $index) use ($trip, $haversineService) {
                if ($index === 1) {
                    return $totalDistance;
                }

                $startCoordinates = $trip->pickupStation->coordinates;
                $endCoordinates = $trip->dropoffStation->coordinates;

                if ($startCoordinates && $endCoordinates) {
                    $distance = $haversineService->execute($startCoordinates, $endCoordinates);

                    return $totalDistance + $distance;
                }

                return $totalDistance;
            },
            0.0
        );

        return round($totalDistance, $roundPrecision, PHP_ROUND_HALF_UP);
    }
}
