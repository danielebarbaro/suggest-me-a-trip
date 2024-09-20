<?php

namespace App\Itineraries\Services;

use App\Itineraries\Itinerary;
use App\Trips\Trip;

class CreateItinerariesService
{
    private array $trips;

    public function __construct(array $trips)
    {
        $this->trips = $trips;
    }

    public function execute(array $options): array
    {
        $routes = [];
        $results = [];

        $routes = $this->findItineraries($routes, $options);

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
        array $options,
    ): void {
        if (count($visitedTrips) <= $options['minSteps']
            && count($visitedTrips) > 1
        ) {
            $routes[] = $visitedTrips;
        }

        foreach ($this->trips as $nextTrip) {
            if (
                $this->canConnect(
                    $currentTrip,
                    $nextTrip,
                    $visitedTrips,
                    $visitedCountries,
                    $options
                )
            ) {
                $newVisitedTrips = array_merge($visitedTrips, [$nextTrip]);
                $newVisitedCountries = array_merge($visitedCountries, [$nextTrip->dropoffStation->country]);
                $this->buildRoute(
                    $nextTrip,
                    $newVisitedTrips,
                    $newVisitedCountries,
                    $routes,
                    $options
                );
            }
        }
    }

    public function canConnect(
        Trip $currentTrip,
        Trip $nextTrip,
        array $visitedTrips,
        array $visitedCountries,
        array $options
    ): bool {
        if ($this->areTripsConnectedByCity($currentTrip, $nextTrip)) {
            if ($options['noSameCountry']
                && in_array($nextTrip->dropoffStation->country, $visitedCountries)
            ) {
                return false;
            }

            if ($options['checkTimeFrame']
                && !empty($currentTrip->timeframes)
                && !empty($nextTrip->timeframes)
                && $this->isTimeFrameCompatible($currentTrip, $nextTrip, $options)
            ) {
                return false;
            }

            if (in_array($nextTrip, $visitedTrips)) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function findItineraries(array $routes, array $options): array
    {
        foreach ($this->trips as $trip) {
            $visitedTrips = [$trip];
            $this->buildRoute(
                $trip,
                $visitedTrips,
                [
                    $trip->pickupStation->country,
                    $trip->dropoffStation->country
                ],
                $routes,
                $options
            );
        }

        return $routes;
    }

    private function areTripsConnectedByCity(Trip $currentTrip, Trip $nextTrip): bool
    {
        return $currentTrip->dropoffStation->fullName === $nextTrip->pickupStation->fullName;
    }

    private function isTimeFrameCompatible(Trip $currentTrip, Trip $nextTrip, array $options): bool
    {
        $minDaysDifferenceBetweenStartAndEnd = $options['minDaysDifferenceBetweenStartAndEnd'];
        $pickupStartAt = $currentTrip->timeframes['startDate']->clone();
        $pickupEndAt = $currentTrip->timeframes['endDate']->clone();

        $dropoffStartAt = $nextTrip->timeframes['startDate']->clone();
        $dropoffEndAt = $nextTrip->timeframes['endDate']->clone();

        if ($pickupEndAt <= $dropoffStartAt) {
            return true;
        }

        if (
            $dropoffEndAt->lessThan($pickupStartAt->addDay($minDaysDifferenceBetweenStartAndEnd))
            && $dropoffStartAt->lessThanOrEqualTo($pickupEndAt->subDay($minDaysDifferenceBetweenStartAndEnd - 2))
        ) {
            return true;
        }

        return false;
    }
}
