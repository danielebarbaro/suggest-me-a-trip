<?php

namespace App\Itineraries;

class Itinerary
{
    public array $trips;
    public float $totalLength;

    public function __construct(
        array $trips
    ) {
        $this->trips = $trips;
        $this->totalLength = $this->calculateTotalLength($trips);
    }

    private function calculateTotalLength(array $trips): float
    {
        return array_reduce($trips, function ($carry, $trip) {
            return $carry + $trip->length;
        }, 0) ?? 0.0;
    }
}
