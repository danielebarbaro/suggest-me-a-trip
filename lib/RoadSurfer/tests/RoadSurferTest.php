<?php

use Library\RoadSurfer\DTO\CityDTO;
use Library\RoadSurfer\RoadSurfer;
use Library\RoadSurfer\HttpClient\ClientInterface;
use Library\RoadSurfer\Cache\CacheInterface;
use Library\RoadSurfer\DTO\StationDTO;

beforeEach(function () {
    $this->clientMock = Mockery::mock(ClientInterface::class);
    $this->cacheMock = Mockery::mock(CacheInterface::class);

    $this->roadSurfer = new RoadSurfer($this->clientMock, $this->cacheMock);
});

afterEach(function () {
    Mockery::close();
});

it('retrieves stations from cache or client', function () {
    $this->cacheMock->shouldReceive('retrieve')
        ->with(
            Mockery::type('string'),
            Mockery::on(function ($callback) {
                return is_callable($callback);
            })
        )
        ->andReturn([
            new StationDTO(1, 'Turin', new CityDTO(1, 'Turin', 'IT', 'Italy', 'Italia'), true, true, false),
        ]);

    $stations = $this->roadSurfer->getStations();

    expect($stations)->toBeArray()
        ->and($stations[0]->name)->toBe('Turin');
});

it('retrieve station from cache or client', function () {
    $this->cacheMock->shouldReceive('retrieve')
        ->with(
            Mockery::type('string'),
            Mockery::on(function ($callback) {
                return is_callable($callback);
            })
        )
        ->andReturn(
            new StationDTO(1, 'Turin', new CityDTO(1, 'Turin', 'IT', 'Italy', 'Italia'), true, true, false),
        );

    $station = $this->roadSurfer->getStationById(1);

    expect($station)
        ->toBeInstanceOf(StationDTO::class)
        ->and($station->name)->toBe('Turin');
});

it('retrieves rally stations from cache or client', function () {
    $this->cacheMock->shouldReceive('retrieve')
        ->with(
            Mockery::type('string'),
            Mockery::on(function ($callback) {
                return is_callable($callback);
            })
        )
        ->andReturn([
            new StationDTO(2, 'Frankfurt', new CityDTO(2, 'Frankfurt', 'DE', 'Germany', 'Germania'), true, true, false),
        ]);

    $this->clientMock->shouldReceive('getRallyStations')
        ->andReturn([
            new StationDTO(2, 'Frankfurt', new CityDTO(2, 'Frankfurt', 'DE', 'Germany', 'Germania'), true, true, false),
        ]);

    $rallyStations = $this->roadSurfer->getRallyStations(true);

    expect($rallyStations)->toBeArray()
        ->and($rallyStations[0]->name)->toBe('Frankfurt');
});

it('retrieves station timeframes by station ids', function () {
    $resourceId = '1';

    $this->cacheMock->shouldReceive('retrieve')
        ->with(
            Mockery::type('string'),
            Mockery::on(function ($callback) {
                return is_callable($callback);
            })
        )
        ->andReturn([
            'startDate' => '2023-01-01',
            'endDate' => '2023-01-10',
        ]);

    $this->clientMock->shouldReceive('getStationTimeFramesByStationIds')
        ->with($resourceId)
        ->andReturn([
            'startDate' => '2023-01-01',
            'endDate' => '2023-01-10',
        ]);

    $timeFrames = $this->roadSurfer->getStationTimeFramesByStationIds($resourceId);

    expect($timeFrames)->toBeArray()
        ->and($timeFrames['startDate'])->toBe('2023-01-01')
        ->and($timeFrames['endDate'])->toBe('2023-01-10');
});

it('retrieves station by id and filters destinations', function () {
    $this->markTestSkipped('This test is skipped :( .');
    $stationId = '2';

    $this->cacheMock->shouldReceive('retrieve')
        ->with(
            Mockery::type('string'),
            Mockery::on(function ($callback) {
                return is_callable($callback);
            })
        )
        ->andReturn([
            1 => new StationDTO(1, 'Turin', new CityDTO(1, 'Turin', 'IT', 'Italy', 'Italia'), true, true, false, [2]),
            2 => new StationDTO(
                2,
                'Frankfurt',
                new CityDTO(2, 'Frankfurt', 'DE', 'Germany', 'Germania'),
                true,
                true,
                false,
                [1]
            ),
        ]);

    $this->clientMock->shouldReceive('getStations')
        ->andReturn([
            1 => new StationDTO(1, 'Turin', new CityDTO(1, 'Turin', 'IT', 'Italy', 'Italia'), true, true, false, [2]),
            2 => new StationDTO(
                2,
                'Frankfurt',
                new CityDTO(2, 'Frankfurt', 'DE', 'Germany', 'Germania'),
                true,
                true,
                false,
                [1]
            ),
        ]);

    $this->cacheMock->shouldReceive('retrieve')
        ->with(
            Mockery::type('string'),
            Mockery::on(function ($callback) {
                return is_callable($callback);
            })
        )
        ->andReturn(
            new StationDTO(
                2,
                'Frankfurt',
                new CityDTO(2, 'Frankfurt', 'DE', 'Germany', 'Germania'),
                true,
                true,
                false,
                [1]
            ),
        );

    $this->clientMock->shouldReceive('getStationById')
        ->with($stationId)
        ->andReturn(
            new StationDTO(
                2,
                'Frankfurt',
                new CityDTO(2, 'Frankfurt', 'DE', 'Germany', 'Germania'),
                true,
                true,
                false,
                [1]
            )
        );

    $destinations = $this->roadSurfer->getReturnStationsByStationId($stationId);

    expect($destinations)->toBeArray()
        ->and($destinations[1]->name)->toBe('Turin');
});
