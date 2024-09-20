<?php

use App\Stations\Station;
use App\Trips\Services\CreateTripsService;
use App\Trips\Trip;
use App\Utils\GeoCoderService;
use Library\RoadSurfer\DTO\CityDTO;
use Library\RoadSurfer\DTO\StationDTO;
use Library\RoadSurfer\RoadSurfer;

beforeEach(function () {
    $this->roadSurfer = Mockery::mock(RoadSurfer::class);
    $this->geoCoderService = Mockery::mock(GeoCoderService::class);
    $this->createTripsService = new CreateTripsService($this->roadSurfer);

    $this->station1 =
        Mockery::mock(
            Station::class,
            [
                'id' => '1',
                'name' => 'Turin',
                'fullName' => 'Turin, Italy',
                'country' => 'Italy',
                'coordinates' => [45.0703, 7.6869],
            ]
        );
    $this->station2 =
        Mockery::mock(
            Station::class,
            [
                'id' => '2',
                'name' => 'Frankfurt',
                'fullName' => 'Frankfurt, Germany',
                'country' => 'Germany',
                'coordinates' => [50.1109, 8.6821],
            ]
        );
    $this->station3 =
        Mockery::mock(
            Station::class,
            [
                'id' => '3',
                'name' => 'Bordeaux',
                'fullName' => 'Bordeaux, France',
                'country' => 'France',
                'coordinates' => [44.8416106, -0.5810938],
            ]
        );
    $this->station4 =
        Mockery::mock(
            Station::class,
            [
                'id' => '4',
                'name' => 'Milan',
                'fullName' => 'Milan, Italy',
                'country' => 'Italy',
                'coordinates' => [45.4642700, 9.1895100],
            ]
        );
});

afterEach(function () {
    Mockery::close();
});

it('skips stations without destinations', function () {
    $pickupStation = $this->station1;

    $this->roadSurfer->shouldReceive('getRallyStations')->andReturn(['1' => $pickupStation]);
    $this->roadSurfer->shouldReceive('getStationById')->with('1')->andReturn([]);

    $result = $this->createTripsService->execute();

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});

it('returns empty array when there are no rally stations', function () {
    $this->roadSurfer->shouldReceive('getRallyStations')->andReturn([]);

    $result = $this->createTripsService->execute();

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});

it('removes duplicate countries and converts them to lowercase', function () {
    $pickupStation = Mockery::mock(StationDTO::class, [
        'city' => Mockery::mock(CityDTO::class, ['countryName' => 'Italy']),
    ]);

    $dropoffStation1 = Mockery::mock(StationDTO::class, [
        'city' => Mockery::mock(CityDTO::class, ['countryName' => 'Germany']),
    ]);

    $dropoffStation2 = Mockery::mock(StationDTO::class, [
        'city' => Mockery::mock(CityDTO::class, ['countryName' => 'France']),
    ]);

    $countries = $this->createTripsService->getUniqueCountries($pickupStation, [$dropoffStation1, $dropoffStation2]);

    expect(array_values($countries))->toBe(['italy', 'france']);
});

it('calculates haversine length between cities', function () {
    $trip = new Trip(
        new Station(
            '1',
            'Turin',
            'Turin, Italy',
            'Italy',
            [45.0703, 7.6869],
        ),
        new Station(
            '2',
            'Frankfurt',
            'Frankfurt, Germany',
            'Germany',
            [50.1109, 8.6821],
        ),
        ['italy', 'germany'],
        ['timeframe1', 'timeframe2'],
    );

    $distance = $this->createTripsService->calculateDistance($trip);

    expect($distance)->toBeNumeric()
        ->and($distance)
        ->toBeGreaterThan(0)
        ->toBe(1130.84);
});
