<?php

namespace App\Trips;

use App\Stations\Station;

class Trip
{
    public Station $pickupStation;
    public Station $dropoffStation;
    public array $countries;
    public array $timeframes;
    public float $length;

    public function __construct(
        Station $pickupStation,
        Station $dropoffStation,
        array $countries,
        array $timeframes
    ) {
        $this->pickupStation = $pickupStation;
        $this->dropoffStation = $dropoffStation;
        $this->countries = $countries;
        $this->timeframes = $timeframes;
        $this->length = 0;
    }
}
