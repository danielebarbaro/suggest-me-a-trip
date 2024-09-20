<?php

use App\Stations\Station;
use App\Trips\Services\CreateTripsService;
use App\Trips\Trip;
use App\Utils\GeoCoderService;
use Geocoder\Provider\Provider;
use Library\RoadSurfer\DTO\CityDTO;
use Library\RoadSurfer\DTO\StationDTO;
use Library\RoadSurfer\RoadSurfer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

beforeEach(function () {
    $_ENV['GEO_CACHE_TTL'] = '3600';

    $this->roadSurfer = Mockery::mock(RoadSurfer::class);
    $this->geoCoderService = Mockery::mock(GeoCoderService::class);
    $this->providerMock = Mockery::mock(Provider::class);
    $this->cacheAdapter = new ArrayAdapter();
    $this->createTripsService = new CreateTripsService(
        $this->roadSurfer,
        $this->providerMock,
        $this->cacheAdapter
    );

    $this->station1 = new Station(
        1,
        'Turin',
        'Turin, Italy',
        'Italy',
        [45.0703, 7.6869],
    );
    $this->station2 = new Station(
        2,
        'Frankfurt',
        'Frankfurt, Germany',
        'Germany',
        [50.1109, 8.6821],
    );
    $this->station3 = new Station(
        3,
        'Bordeaux',
        'Bordeaux, France',
        'France',
        [44.8416106, -0.5810938],
    );
    $this->station4 = new Station(
        4,
        'Milan',
        'Milan, Italy',
        'Italy',
        [45.4642700, 9.1895100],
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
    $countries = $this->createTripsService->getUniqueCountries($this->station1, [$this->station4, $this->station3]);

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
