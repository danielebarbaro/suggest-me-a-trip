<?php

use App\Utils\GeoCoderService;
use Geocoder\Model\Address;
use Geocoder\Model\Coordinates;
use Geocoder\Provider\Provider;
use Geocoder\StatefulGeocoder;

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

it('returns null when the city cannot be found', function () {
    $providerMock = Mockery::mock(Provider::class);

    $geocoder = new StatefulGeocoder($providerMock, 'en');

    $queryResultMock = Mockery::mock('Geocoder\Collection');
    $queryResultMock->shouldReceive('isEmpty')->andReturn(true);
    $providerMock->shouldReceive('geocodeQuery')->andReturn($queryResultMock);

    $geoCoderService = new GeoCoderService($geocoder);

    $coordinates = $geoCoderService->execute('dummy');

    expect($coordinates)->toBeNull();
});

it('returns coordinates from the first result when multiple results are found', function () {
    $providerMock = Mockery::mock(Provider::class);

    $geocoder = new StatefulGeocoder($providerMock, 'en');

    $coordinates1 = new Coordinates(45.4642, 9.1900);
    $coordinates2 = new Coordinates(41.9028, 12.4964);

    $address1 = Mockery::mock(Address::class);
    $address1->shouldReceive('getCoordinates')->andReturn($coordinates1);

    $address2 = Mockery::mock(Address::class);
    $address2->shouldReceive('getCoordinates')->andReturn($coordinates2);

    $queryResultMock = Mockery::mock('Geocoder\Collection');
    $queryResultMock->shouldReceive('isEmpty')->andReturn(false);
    $queryResultMock->shouldReceive('first')->andReturn($address1);

    $providerMock->shouldReceive('geocodeQuery')->andReturn($queryResultMock);

    $geoCoderService = new GeoCoderService($geocoder);

    $coordinates = $geoCoderService->execute('Milan');

    expect($coordinates)->toBe([45.4642, 9.1900]);
});

it('handles invalid city input gracefully', function () {
    $providerMock = Mockery::mock(Provider::class);

    $geocoder = new StatefulGeocoder($providerMock, 'en');

    $queryResultMock = Mockery::mock('Geocoder\Collection');
    $queryResultMock->shouldReceive('isEmpty')->andReturn(true);
    $providerMock->shouldReceive('geocodeQuery')->andReturn($queryResultMock);

    $geoCoderService = new GeoCoderService($geocoder);

    $coordinates = $geoCoderService->execute('');

    expect($coordinates)->toBeNull();
});

// it('returns null on geocoding error', function () {
//    $providerMock = Mockery::mock(Provider::class);
//
//    $geocoder = new StatefulGeocoder($providerMock, 'en');
//
//    $providerMock->shouldReceive('geocodeQuery')->andThrow(Exception::class);
//
//    $geoCoderService = new GeoCoderService($geocoder);
//
//    $coordinates = $geoCoderService->execute('Milan');
//
//    expect($coordinates)->toBeNull();
// });
