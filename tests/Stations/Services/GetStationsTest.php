<?php

use App\Stations\Services\GetStationsService;
use App\Stations\Station;
use App\Utils\GeoCoderService;
use Library\RoadSurfer\DTO\CityDTO;
use Library\RoadSurfer\DTO\StationDTO;
use PHPUnit\Framework\TestCase;

beforeEach(function () {

    $this->getStationsService = new GetStationsService();

    $this->stations =
        [
            new StationDTO(
                1,
                'Turin',
                new CityDTO(
                    12,
                    'Turin',
                    'IT',
                    'Italy',
                    'Italia',
                ),
                true,
                true,
                false,
                [],
            ),
            new StationDTO(
                2,
                'Frankfurt',
                new CityDTO(
                    23,
                    'Frankfurt',
                    'DE',
                    'Germany',
                    'Germania',
                ),
                true,
                true,
                false,
                [],
            ),
        ];
});

it('returns an array of Station instances', function () {
    $mockGeoCoderService = Mockery::mock(GeoCoderService::class);
    $mockGeoCoderService->shouldReceive('execute');

    $this->getStationsService = Mockery::mock(GetStationsService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $this->getStationsService
        ->shouldReceive('getCoordinates')
        ->andReturn([45.0, 9.0]);

    $result = $this->getStationsService->execute($this->stations);

    expect($result)->toBeArray()
        ->and($result[0])->toBeInstanceOf(Station::class)
        ->and($result[0]->id)->toBe('1')
        ->and($result[0]->name)->toBe('Turin')
        ->and($result[0]->coordinates)->toBe([45.0, 9.0]);
});

it('calls GeoCoderService for each station', function () {
    $mockGeoCoderService = Mockery::mock(GeoCoderService::class);
    $mockGeoCoderService->shouldReceive('execute')
        ->once()
        ->with('Station One Full')
        ->andReturn([45.0, 9.0]);

    $result = $this->getStationsService->execute($this->stations);

    expect($result[0]->coordinates)->toBe([45.0, 9.0]);
});

it('returns an empty array if no stations are passed', function () {
    $result = $this->getStationsService->execute([]);

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});

