<?php

use App\Dto\StationDto;
use App\Services\TripService;
use App\Dto\TripDto;
use App\Services\StationService;
use App\Helpers\HttpClientHelper;
use Symfony\Contracts\Cache\CacheInterface;

it('skips stations without destinations', function () {
    $stationServiceMock = Mockery::mock(StationService::class);

    $stationServiceMock->shouldReceive('getRally')->andReturn([
        1 => (object) ['country' => 'Italy'],
    ]);

    $stationServiceMock->shouldReceive('getDestinationsById')->with(1)->andReturn([]);

    $httpClientHelperMock = Mockery::mock(HttpClientHelper::class);
    $cacheMock = Mockery::mock(CacheInterface::class);

    $tripService = new TripService(
        $stationServiceMock,
        $httpClientHelperMock,
        $cacheMock
    );

    $results = $tripService->execute();

    expect($results)->toBe([]);
});

it('processes stations with valid destinations', function () {
    $stationServiceMock = Mockery::mock(StationService::class);

    $stationServiceMock->shouldReceive('getRally')->andReturn([
        1 => new StationDto(1, 'Turin', 'Turin, Italy', 'Italy', [1, 1]),
    ]);

    $stationServiceMock->shouldReceive('getDestinationsById')->with(1)->andReturn([
        new StationDto(1, 'Paris', 'Paris, France', 'France', [1, 1]),
        new StationDto(2, 'Berlin', 'Berlin, Germany', 'Germany', [1, 1]),
    ]);

    $httpClientHelperMock = Mockery::mock(HttpClientHelper::class);
    $cacheMock = Mockery::mock(CacheInterface::class);

    $tripService = new TripService(
        $stationServiceMock,
        $httpClientHelperMock,
        $cacheMock
    );

    $results = $tripService->execute();

    expect($results)
        ->toBeArray()
        ->toHaveCount(1);

    $tripDto = $results[0];
    expect($tripDto)
        ->toBeInstanceOf(TripDto::class)
        ->and(array_values($tripDto->countries))
        ->toBe(['italy', 'france', 'germany']);
});

it('removes duplicate countries and converts them to lowercase', function () {
    $stationServiceMock = Mockery::mock(StationService::class);

    $stationServiceMock->shouldReceive('getRally')->andReturn([
        1 => new StationDto(1, 'Turin', 'Turin, Italy', 'Italy', [1, 1]),
    ]);

    $stationServiceMock->shouldReceive('getDestinationsById')->with(1)->andReturn([
        new StationDto(1, 'Paris', 'Paris, France', 'France', [1, 1]),
        new StationDto(2, 'Nice', 'Nice, France', 'France', [1, 1]),
        new StationDto(3, 'Berlin', 'Berlin, Germany', 'Germany', [1, 1]),
        new StationDto(4, 'Milan', 'Milan, Italy', 'Italy', [1, 1]),
    ]);

    $httpClientHelperMock = Mockery::mock(HttpClientHelper::class);
    $cacheMock = Mockery::mock(CacheInterface::class);

    $tripService = new TripService(
        $stationServiceMock,
        $httpClientHelperMock,
        $cacheMock
    );

    $results = $tripService->execute();

    $tripDto = $results[0];
    expect($results)->toBeArray()
        ->and(array_values($tripDto->countries))
        ->toBe(['italy', 'france', 'germany']);
});
