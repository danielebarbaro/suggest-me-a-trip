<?php

use App\Stations\Services\GetStationsService;
use App\Stations\Station;
use App\Utils\GeoCoderService;
use Library\RoadSurfer\DTO\CityDTO;
use Library\RoadSurfer\DTO\StationDTO;

beforeEach(function () {
    $this->geoCoderService = Mockery::mock(GeoCoderService::class);
    $this->geoCoderService->shouldReceive('execute')->andReturn([45.0, 9.0]);

    $this->getStationsService = new GetStationsService($this->geoCoderService);

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
    $result = $this->getStationsService->execute($this->stations);
    expect($result)->toBeArray()
        ->and($result[1])->toBeInstanceOf(Station::class)
        ->and($result[1]->id)->toBe('1')
        ->and($result[1]->name)->toBe('Turin')
        ->and($result[1]->coordinates)->toBe([45.0, 9.0]);
});

it('calls GeoCoderService for each station', function () {
    $result = $this->getStationsService->execute($this->stations);
    expect($result[1]->coordinates)
        ->toBeArray()
        ->toBe([45.0, 9.0]);
});

it('returns an empty array if no stations are passed', function () {
    $result = $this->getStationsService->execute([]);

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});
