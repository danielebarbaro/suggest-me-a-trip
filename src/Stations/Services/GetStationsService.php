<?php

namespace App\Stations\Services;

use App\Stations\Station;
use App\Utils\GeoCoderService;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Symfony\Component\HttpClient\Psr18Client;

class GetStationsService
{
    public function execute(array $stations): array
    {
        $results = [];
        foreach ($stations as $station) {
            $results[] = new Station(
                $station->id,
                $station->name,
                $station->fullName,
                $station->city->countryName,
                $this->getCoordinates($station->fullName),
            );
        }

        return $results;
    }

    public function getCoordinates(string $stationName): array
    {
        $provider = new GoogleMaps(new Psr18Client(), null, $_ENV['GOOGLE_MAPS_API_KEY']);

        return (new GeoCoderService($provider))->execute($stationName);
    }
}
