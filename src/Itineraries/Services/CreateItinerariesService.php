<?php

namespace App\Itineraries\Services;

use App\Itineraries\Itinerary;
use App\Trips\Trip;

class CreateItinerariesService
{
    public const int MIN_STEPS = 2;

    private array $trips;

    public function __construct(array $trips)
    {
        $this->trips = $trips;
    }

    public function execute(int $steps = self::MIN_STEPS, ?bool $checkTimeFrame = true): array
    {
        $routes = [];
        $results = [];

        $routes = $this->findItineraries($routes, $steps, $checkTimeFrame);

        foreach ($routes as $route) {
            $itinerary = new Itinerary($route);
            $key = strtr($itinerary->totalLength, '.', '_');
            $results[$key] = $itinerary;
        }

        return $results;
    }

    public function buildRoute(
        Trip $currentTrip,
        array $visitedTrips,
        array $visitedCountries,
        array &$routes,
        int $minSteps,
        bool $checkTimeFrame = true
    ): void {
        if (count($visitedTrips) >= $minSteps) {
            $routes[] = $visitedTrips;
        }

        foreach ($this->trips as $nextTrip) {
            if ($this->canConnect($currentTrip, $nextTrip, $visitedTrips, $visitedCountries, $checkTimeFrame)) {
                $newVisitedTrips = array_merge($visitedTrips, [$nextTrip]);
                $newVisitedCountries = array_merge($visitedCountries, [$nextTrip->dropoffStation->country]);
                $this->buildRoute($nextTrip, $newVisitedTrips, $newVisitedCountries, $routes, $minSteps);
            }
        }
    }

    public function canConnect(
        Trip $currentTrip,
        Trip $nextTrip,
        array $visitedTrips,
        array $visitedCountries,
        ?bool $checkTimeFrame = true
    ): bool {
        if ($currentTrip->dropoffStation->fullName === $nextTrip->pickupStation->fullName) {
            if (in_array($nextTrip, $visitedTrips, true)) {
                return false;
            }

            if (in_array($nextTrip->dropoffStation->country, $visitedCountries)) {
                return false;
            }

            if ($checkTimeFrame
                && !empty($currentTrip->timeframes)
                && !empty($nextTrip->timeframes)
                && !$this->isTimeFrameCompatible($currentTrip, $nextTrip)) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function findItineraries(array $routes, int $steps, ?bool $checkTimeFrame): array
    {
        foreach ($this->trips as $trip) {
            $visitedTrips = [$trip];
            $visitedCountries = [$trip->pickupStation->country, $trip->dropoffStation->country];
            $this->buildRoute($trip, $visitedTrips, $visitedCountries, $routes, $steps, $checkTimeFrame);
        }

        return $routes;
    }

    private function isTimeFrameCompatible(Trip $currentTrip, Trip $nextTrip, int $minDaysDifference = 4): bool
    {
        $pickupStartAt = $currentTrip->timeframes[0]->clone();
        $pickupEndAt = $currentTrip->timeframes[1]->clone();
        $dropoffStartAt = $nextTrip->timeframes[0]->clone();
        $dropoffEndAt = $nextTrip->timeframes[1]->clone();

        if ($pickupStartAt->addDay($minDaysDifference)->lessThanOrEqualTo($dropoffEndAt)
            && $dropoffStartAt->lessThanOrEqualTo($pickupEndAt)
        ) {
            return true;
        }

        return false;
    }
}
