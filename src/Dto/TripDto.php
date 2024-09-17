<?php

namespace App\Dto;

final class TripDto
{
    public StationDto $pickupStation;
    public StationDto $dropoffStation;
    public array $countries;
    public array $timeframes;
    public float $length;

    public function __construct(StationDto $pickupStation, StationDto $dropoffStation, array $countries, array $timeframes)
    {
        $this->pickupStation = $pickupStation;
        $this->dropoffStation = $dropoffStation;
        $this->countries = $countries;
        $this->timeframes = $timeframes;
        $this->length = 0;
    }
}
