<?php

namespace App\Utils;

use App\Shared\Exceptions\GeoCoderException;
use Geocoder\Exception\Exception;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;

class GeoCoderService
{
    public Provider $provider;
    private StatefulGeocoder $geocoder;

    public function __construct(Provider $provider)
    {
        $this->geocoder = new StatefulGeocoder($provider, 'en');
    }

    public function execute(string $city): ?array
    {
        try {
            $result = $this->geocoder->geocodeQuery(GeocodeQuery::create($city));
        } catch (Exception|GeoCoderException $e) {
            // TODO: Log the exception
            return null;
        }

        if ($result->isEmpty()) {
            return null;
        }

        $coordinates = $result->first()->getCoordinates();

        return [
            $coordinates->getLatitude(),
            $coordinates->getLongitude(),
        ];
    }
}
