<?php

use App\Stations\Services\GetStationsService;
use App\Stations\Station;
use App\Trips\Services\CreateTripsService;
use App\Trips\Trip;
use Library\RoadSurfer\RoadSurfer;

beforeEach(function () {
    $_ENV['GEO_CACHE_TTL'] = '3600';

    $this->roadSurfer = Mockery::mock(RoadSurfer::class);
    $this->stationService = Mockery::mock(GetStationsService::class);

    $this->createTripsService = new CreateTripsService(
        $this->roadSurfer,
        $this->stationService,
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

    $this->stationService->shouldReceive('execute')->andReturn([
        $this->station1,
        $this->station2,
        $this->station4,
    ]);
});

afterEach(function () {
    Mockery::close();
});

it('skips stations without destinations', function () {
    $this->roadSurfer->shouldReceive('getRallyStations')
        ->andReturn(['1' => $this->station1]);
    $this->roadSurfer->shouldReceive('getReturnStationsByStationId')
        ->andReturn([]);
    $this->roadSurfer->shouldReceive('getStationTimeFramesByStationIds')
        ->andReturn([
            'startDate' => '2021-01-01',
            'endDate' => '2021-01-02',
        ]);

    $result = $this->createTripsService->execute();

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});

it('returns empty array when there are no rally stations', function () {
    $this->roadSurfer->shouldReceive('getRallyStations')->andReturn([]);
    $this->roadSurfer->shouldReceive('getReturnStationsByStationId')
        ->andReturn([
            $this->station2,
            $this->station3,
        ]);
    $this->roadSurfer->shouldReceive('getStationTimeFramesByStationIds')
        ->andReturn([]);
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
        ->toBe(565.42);
});

it('builds trips and returns an array of trips', function () {
    $this->roadSurfer->shouldReceive('getRallyStations')->andReturn([
        '1' => $this->station1,
        '3' => $this->station3,
    ]);
    $this->roadSurfer->shouldReceive('getReturnStationsByStationId')
        ->andReturn([
            $this->station1,
        ]);
    $this->roadSurfer->shouldReceive('getStationTimeFramesByStationIds')
        ->andReturn([
            'startDate' => '2021-01-01',
            'endDate' => '2021-01-02',
        ]);

    $results = $this->createTripsService->execute();

    $trip = $results[0];
    expect($trip)->toBeInstanceOf(Trip::class)
        ->and($trip->pickupStation->id)->toBe('1')
        ->and($trip->dropoffStation->id)->toBe('1')
        ->and(array_values($trip->countries))->toBe(['italy', 'germany'])
        ->and($trip->timeframes)->toBe([
            'startDate' => '2021-01-01',
            'endDate' => '2021-01-02',
        ]);
});
