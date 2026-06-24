<?php

declare(strict_types=1);

namespace App\Trips\Services;

use App\Trips\CustomTripSubscriber;
use App\Trips\Trip;
use Carbon\Carbon;

class FilterCustomTripsService
{
    /**
     * @param Trip[] $trips
     *
     * @return Trip[]
     */
    public function execute(array $trips, CustomTripSubscriber $sub, ?Carbon $today = null): array
    {
        $today = $today ?? Carbon::today();

        $matched = array_filter($trips, function (Trip $trip) use ($sub, $today): bool {
            $country = $sub->direction === 'arrival'
                ? strtolower($trip->dropoffStation->country)
                : strtolower($trip->pickupStation->country);

            if ($country !== $sub->country) {
                return false;
            }

            if ($trip->length > $sub->maxKm) {
                return false;
            }

            $start = $trip->timeframes['startDate'];
            $end = $trip->timeframes['endDate'];

            if (!$start->greaterThan($today)) {
                return false;
            }

            return $start->lessThanOrEqualTo($sub->dateTo)
                && $end->greaterThanOrEqualTo($sub->dateFrom);
        });

        $matched = array_values($matched);

        usort(
            $matched,
            static fn (Trip $a, Trip $b): int => $a->timeframes['startDate'] <=> $b->timeframes['startDate']
        );

        return $matched;
    }
}
