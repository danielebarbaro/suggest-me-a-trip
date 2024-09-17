<?php

namespace App\Services;

use App\Dto\StationDto;

class ItineraryService
{
    private HaversineService $haversineService;
    private array $trips;

    public function __construct(
        array $trips,
        HaversineService $haversineService,
    ) {
        $this->haversineService = $haversineService;
        $this->trips = $trips;
    }

    public function findTripsWithMultipleSteps(int $steps): array
    {
        $nCityTrips = [];

        foreach ($this->trips as $trip) {
            $this->searchNextStepWithDifferentCountry(
                $trip->pickupStation,
                $trip->dropoffStation,
                [],
                $nCityTrips,
                $steps
            );
        }

        return $nCityTrips;
    }

    private function searchNextStepWithDifferentCountry(
        StationDto $currentStation,
        array $dropoffStations,
        array $visited,
        array &$nCityTrips,
        int $steps
    ): void {
        $visited[] = $currentStation;

        if (count($visited) === $steps) {
            if ($this->isDifferentDestinationCountry($visited)) {
                $nCityTrips[] = $visited;
            }

            return;
        }

        foreach ($dropoffStations as $dropoff) {
            if (!in_array($dropoff->fullName, array_map(fn ($station) => $station->fullName, $visited))) {
                $this->searchNextStepWithDifferentCountry(
                    $dropoff,
                    $this->getDropoffStationsByPickup($dropoff->name),
                    $visited,
                    $nCityTrips,
                    $steps
                );
            }
        }
    }

    public function getTripsWithHaversineLength(array $trips): array
    {
        $distances = array_map(
            fn ($stations) => $this->calculateTripHaversineLength($stations, 0),
            $trips
        );

        return array_combine($distances, array_values($trips));
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

                $startCoordinates = $this->getCoordinatesByCityName($startCity->fullName);
                $endCoordinates = $this->getCoordinatesByCityName($endCity->fullName);

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

    public function isDifferentDestinationCountry(array $visited): bool
    {
        $countries = array_filter(
            array_map(
                fn ($city) => $this->getCountryByCityName($city->fullName),
                $visited
            )
        );

        return count(array_unique($countries)) === count($visited);
    }

    public function getDropoffStationsByPickup(string $pickupName): array
    {
        $trip = array_filter($this->trips, fn ($trip) => $trip->pickupStation->name === $pickupName);

        return $trip ? reset($trip)->dropoffStation : [];
    }

    public function getCountryByCityName(string $cityName): ?string
    {
        $station = $this->getStationByCityName($cityName);

        return $station?->country;
    }

    public function getCoordinatesByCityName(string $cityName): ?array
    {
        $station = $this->getStationByCityName($cityName);

        return $station?->coordinates;
    }

    private function getStationByCityName(string $cityName): ?StationDto
    {
        $allStations = $this->allAvailableStations();
        $stations = array_filter($allStations, fn ($station) => $station->fullName === $cityName);

        return $stations ? reset($stations) : null;
    }

    private function allAvailableStations(): array
    {
        return array_merge(
            array_map(fn ($trip) => $trip->pickupStation, $this->trips),
            array_merge(...array_map(fn ($trip) => $trip->dropoffStation, $this->trips))
        );
    }
}
