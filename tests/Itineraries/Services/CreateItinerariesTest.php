<?php

use App\Itineraries\Services\CreateItinerariesService;
use App\Stations\Station;
use App\Trips\Trip;

beforeEach(function () {
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
                'name' => 'Antwerp',
                'fullName' => 'Antwerp, Belgium',
                'country' => 'Belgium',
                'coordinates' => [45.4642, 9.1900],
            ]
        );
    $this->station5 =
        Mockery::mock(
            Station::class,
            [
                'id' => '5',
                'name' => 'Milan',
                'fullName' => 'Milan, Italy',
                'country' => 'Italy',
                'coordinates' => [45.4642700, 9.1895100],
            ]
        );

    $this->trip1 = Mockery::mock(
        Trip::class,
        [
            'pickupStation' => $this->station1,
            'dropoffStation' => $this->station2,
            'countries' => ['italy', 'germany'],
            'timeframes' => [],
        ]
    );
    $this->trip2 = Mockery::mock(
        Trip::class,
        [
            'pickupStation' => $this->station2,
            'dropoffStation' => $this->station3,
            'countries' => ['germany', 'france'],
            'timeframes' => [],
        ]
    );
    $this->trip3 = Mockery::mock(
        Trip::class,
        [
            'pickupStation' => $this->station3,
            'dropoffStation' => $this->station4,
            'countries' => ['france', 'italy'],
            'timeframes' => [],
        ]
    );
    $this->trip4 = Mockery::mock(
        Trip::class,
        [
            'pickupStation' => $this->station4,
            'dropoffStation' => $this->station1,
            'countries' => ['belgium', 'france'],
            'timeframes' => [],
        ]
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

    expect($routes)->toBeArray()->and($routes)->toHaveCount(4);
});

it('ensures that trips connect different countries', function () {
    $routes = $this->createItinerariesService->execute([
        'noSameCountry' => true,
        'minDaysDifferenceBetweenStartAndEnd' => 4,
        'checkTimeFrame' => true,
        'minSteps' => 2,
    ]);

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
