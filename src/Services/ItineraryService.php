<?php

namespace App\Services;

use App\Dto\TripDto;

class ItineraryService
{
    public const int MIN_STEPS = 2;
    private HaversineService $haversineService;
    private array $trips;

    public function __construct(
        array $trips,
        HaversineService $haversineService,
    ) {
        $this->haversineService = $haversineService;
        $this->trips = $trips;
    }

    public function findTripsWithMultipleSteps(int $steps = self::MIN_STEPS, ?bool $useLengthKeys = false): array
    {
        $routes = [];

        foreach ($this->trips as $trip) {
            $visitedTrips = [$trip];
            $visitedCountries = [$trip->pickupStation->country, $trip->dropoffStation->country];
            $this->buildRoute($trip, $visitedTrips, $visitedCountries, $routes, $steps);
        }

        return $useLengthKeys ?
            $this->withLengthKeys($routes) :
            $routes;
    }

    public function buildRoute(
        TripDto $currentTrip,
        array $visitedTrips,
        array $visitedCountries,
        array &$routes,
        int $minSteps
    ): void {
        if (count($visitedTrips) >= $minSteps) {
            $routes[] = $visitedTrips;
        }

        foreach ($this->trips as $nextTrip) {
            if ($this->canConnect($currentTrip, $nextTrip, $visitedTrips, $visitedCountries)) {
                $newVisitedTrips = array_merge($visitedTrips, [$nextTrip]);
                $newVisitedCountries = array_merge($visitedCountries, [$nextTrip->dropoffStation->country]);
                $this->buildRoute($nextTrip, $newVisitedTrips, $newVisitedCountries, $routes, $minSteps);
            }
        }
    }

    public function canConnect(
        TripDto $currentTrip,
        TripDto $nextTrip,
        array $visitedTrips,
        array $visitedCountries
    ): bool {
        if ($currentTrip->dropoffStation->fullName === $nextTrip->pickupStation->fullName) {
            if (in_array($nextTrip, $visitedTrips, true)) {
                return false;
            }

            if (in_array($nextTrip->dropoffStation->country, $visitedCountries)) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function withLengthKeys(array $routes): array
    {
        $trips = [];
        foreach ($routes as $route) {
            $totalLength = array_reduce($route, function ($carry, $trip) {
                return $carry + $trip->length;
            }, 0);

            $trips[round($totalLength)] = $route;
        }

        return $trips;
    }
}
