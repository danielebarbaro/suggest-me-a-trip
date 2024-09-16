<?php

namespace App\Dto;

final class TripDto
{
    public StationDto $pickupStation;
    public array $dropoffStation;
    public array $countries;

    public function __construct(StationDto $pickupStation, array $dropoffStation, array $countries)
    {
        $this->pickupStation = $pickupStation;
        $this->dropoffStation = $dropoffStation;
        $this->countries = $countries;
    }
}
