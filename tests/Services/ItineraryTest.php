<?php

use App\Dto\StationDto;
use App\Dto\TripDto;
use App\Services\HaversineService;
use App\Services\ItineraryService;

beforeEach(function () {
    $this->station1 = new StationDto('1', 'Turin', 'Turin, Italy', 'Italy', [45.0703, 7.6869]);
    $this->station2 = new StationDto('2', 'Frankfurt', 'Frankfurt, Germany', 'Germany', [50.1109, 8.6821]);
    $this->station3 = new StationDto('3', 'Bordeaux', 'Bordeaux, France', 'France', [44.8416106, -0.5810938]);
    $this->station4 = new StationDto('4', 'Antwerp', 'Antwerp, Belgium', 'Belgium', [45.4642, 9.1900]);

    $this->trip1 = new TripDto($this->station1, $this->station2, ['italy', 'germany'], []);
    $this->trip2 = new TripDto($this->station2, $this->station3, ['germany', 'france'], []);
    $this->trip3 = new TripDto($this->station3, $this->station4, ['france', 'italy'], []);
    $this->trip4 = new TripDto($this->station4, $this->station1, ['belgium', 'france'], []);

    $this->trips = [$this->trip1, $this->trip2, $this->trip3, $this->trip4];

    $this->haversineServiceMock = Mockery::mock(HaversineService::class);
    $this->itineraryService = new ItineraryService($this->trips, $this->haversineServiceMock);
});

afterEach(function () {
    Mockery::close();
});

it('finds trips with multiple steps', function () {
    $routes = $this->itineraryService->findTripsWithMultipleSteps(3);

    expect($routes)->toBeArray()->and($routes)->toHaveCount(4);
});

it('ensures that trips connect different countries', function () {
    $routes = $this->itineraryService->findTripsWithMultipleSteps(2);

    foreach ($routes as $index => $route) {
        $visitedCountries = array_map(fn ($trip) => $trip->pickupStation->country, $route);

        expect($visitedCountries)->toBeArray();

        if ($index === 0) {
            expect($visitedCountries)->toHaveCount(2);
        } elseif ($index === 1) {
            expect($visitedCountries)->toHaveCount(3);
        }
    }

    expect($routes)->toBeArray()
        ->and($routes)->toHaveCount(8);
});

it('checks if trips can connect based on dropoff and pickup stations', function () {
    $canConnect = $this->itineraryService->canConnect($this->trip1, $this->trip2, [$this->trip1], ['italy', 'germany']);
    expect($canConnect)->toBeTrue();

    $cannotConnect = $this->itineraryService->canConnect(
        $this->trip1,
        $this->trip4,
        [$this->trip1],
        ['italy', 'france']
    );
    expect($cannotConnect)->toBeFalse();
});
