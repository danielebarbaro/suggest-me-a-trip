<?php

namespace App\Stations\Services;

use App\Stations\Station;
use App\Utils\GeoCoderService;

class GetStationsService
{
    private GeoCoderService $geoCoderService;

    public function __construct(
        GeoCoderService $geoCoderService
    ) {
        $this->geoCoderService = $geoCoderService;
    }

    public function execute(array $stations): array
    {
        $results = [];
        foreach ($stations as $station) {
            $coordinates = $this->geoCoderService->execute($station->fullName);
            $results[$station->id] = new Station(
                $station->id,
                $station->name,
                $station->fullName,
                $station->city->countryName,
                $coordinates,
            );
        }

        return $results;
    }
}
