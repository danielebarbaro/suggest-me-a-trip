<?php

namespace App\Stations\Services;

use App\Shared\Exceptions\GeoCoderException;
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
            try {
                $coordinates = $this->geoCoderService->execute($station->fullName);
            } catch (GeoCoderException $e) {
                // TODO: Log the exception
                continue;
            }

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
