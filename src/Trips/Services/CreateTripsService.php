<?php

namespace App\Trips\Services;

use App\Stations\Station;
use App\Trips\Trip;
use App\Utils\GeoCoderService;
use App\Utils\HaversineService;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Library\RoadSurfer\RoadSurfer;
use Symfony\Component\HttpClient\Psr18Client;

class CreateTripsService
{
    private RoadSurfer $roadSurfer;

    public function __construct(
        RoadSurfer $roadSurfer,
    ) {
        $this->roadSurfer = $roadSurfer;
    }

    public function execute(): array
    {
        $results = [];
        $rallyStations = $this->roadSurfer->getRallyStations();

        foreach ($rallyStations as $stationId => $pickupStation) {
            $dropoffStations = $this->roadSurfer->getStationById($stationId);
            if (empty($dropoffStations)) {
                continue;
            }

            $countries = $this->getUniqueCountries($pickupStation, $dropoffStations);

            foreach ($dropoffStations as $dropoffStation) {
                $id = "{$pickupStation->id}-{$dropoffStation->id}";
                $timeframes = $this->roadSurfer->getStationTimeFramesByStationIds($id);
                if (empty($timeframes)) {
                    continue;
                }

                $trip = new Trip(
                    new Station(
                        $pickupStation->id,
                        $pickupStation->name,
                        $pickupStation->fullName,
                        $pickupStation->city->countryName,
                        $this->getCoordinates($pickupStation->fullName),
                    ),
                    new Station(
                        $dropoffStation->id,
                        $dropoffStation->name,
                        $dropoffStation->fullName,
                        $dropoffStation->city->countryName,
                        $this->getCoordinates($dropoffStation->fullName),
                    ),
                    $countries,
                    $timeframes
                );
                $trip->length = $this->calculateDistance($trip);
                $results[] = $trip;
            }
        }

        return $results;
    }

    private function getCoordinates(string $stationName): array
    {
        $provider = new GoogleMaps(new Psr18Client(), null, $_ENV['GOOGLE_MAPS_API_KEY']);

        return (new GeoCoderService($provider))->execute($stationName);
    }

    public function getUniqueCountries($station, array $destinations): array
    {
        $countries = array_merge(
            [$station->city->countryName],
            array_map(fn ($destination) => $destination->city->countryName, $destinations)
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
