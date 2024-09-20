<?php

use App\Itineraries\Services\CreateItinerariesService;
use App\Stations\Station;
use App\Trips\Trip;

beforeEach(function () {
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

    $this->trip1 = new Trip(
        $this->station1,
        $this->station2,
        ['italy', 'germany'],
        [],
    );
    $this->trip2 = new Trip(
        $this->station2,
        $this->station3,
        ['germany', 'france'],
        [],
    );
    $this->trip3 = new Trip(
        $this->station3,
        $this->station4,
        ['france', 'italy'],
        [],
    );
    $this->trip4 = new Trip(
        $this->station4,
        $this->station1,
        ['belgium', 'france'],
        [],
    );

    $this->trips = [$this->trip1, $this->trip2, $this->trip3, $this->trip4];
    $this->createItinerariesService = new CreateItinerariesService($this->trips);
    $this->baseClassOptions = [
        'noSameCountry' => true,
        'minDaysDifferenceBetweenStartAndEnd' => 4,
        'checkTimeFrame' => true,
        'minSteps' => 2,
    ];
});

afterEach(function () {
    Mockery::close();
});

it('finds trips with multiple steps', function () {
    $routes = $this->createItinerariesService->execute($this->baseClassOptions);

    expect($routes)->toBeArray()->and($routes)->toHaveCount(1);
});

it('ensures that trips connect different countries', function () {
    $routes = $this->createItinerariesService->execute([
        'noSameCountry' => true,
        'minDaysDifferenceBetweenStartAndEnd' => 4,
        'checkTimeFrame' => true,
        'minSteps' => 2,
    ]);
    foreach ($routes as $route) {
        $visitedCountries = array_map(fn($trip) => $trip->pickupStation->country, $route->trips);

        expect($visitedCountries)->toBeArray()
            ->and($visitedCountries)->toHaveCount(2);
    }
    expect($routes)->toBeArray()
        ->and($routes)->toHaveCount(1);
});

it('checks if trips can connect based on dropoff and pickup stations', function () {
    $canConnect = $this->createItinerariesService->canConnect(
        $this->trip1,
        $this->trip2,
        [$this->trip1],
        ['italy', 'germany'],
        $this->baseClassOptions
    );
    expect($canConnect)->toBeTrue();

    $cannotConnect = $this->createItinerariesService->canConnect(
        $this->trip1,
        $this->trip4,
        [$this->trip1],
        ['italy', 'france'],
        $this->baseClassOptions
    );
    expect($cannotConnect)->toBeFalse();
});
