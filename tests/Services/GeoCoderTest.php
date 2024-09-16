<?php

use App\Services\GeoCoderService;
use Geocoder\StatefulGeocoder;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Model\Coordinates;
use Geocoder\Model\Address;

it('returns correct coordinates for a city', function () {
    $providerMock = Mockery::mock(Provider::class);

    $queryResultMock = Mockery::mock('Geocoder\Collection');
    $coordinates = new Coordinates(45.4642, 9.1900);

    $address = Mockery::mock(Address::class);
    $address->shouldReceive('getCoordinates')->andReturn($coordinates);

    $queryResultMock->shouldReceive('isEmpty')->andReturn(false);
    $queryResultMock->shouldReceive('first')->andReturn($address);

    $providerMock->shouldReceive('geocodeQuery')->andReturn($queryResultMock);

    $geocoder = new StatefulGeocoder($providerMock, 'en');

    $geoCoderService = new GeoCoderService($geocoder);

    $coordinates = $geoCoderService->execute('Milan');

    expect($coordinates)->toBe([45.4642, 9.1900]);
});
