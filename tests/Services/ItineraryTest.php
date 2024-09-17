<?php

use App\Services\ItineraryService;
use App\Services\HaversineService;
use App\Dto\StationDto;
use App\Dto\TripDto;

beforeEach(function () {
    $this->station1 = new StationDto('1', 'Turin', 'Turin, Italy', 'Italy', [45.0703, 7.6869]);
    $this->station2 = new StationDto('2', 'Frankfurt', 'Frankfurt, Germany', 'Germany', [50.1109, 8.6821]);
    $this->station3 = new StationDto('3', 'Bordeaux', 'Bordeaux, France', 'France', [44.8416106, -0.5810938]);

    $this->trip1 = new TripDto($this->station1, [$this->station2], ['Italy', 'Germany']);
    $this->trip2 = new TripDto($this->station2, [$this->station3], ['Germany', 'France']);

    $this->trips = [$this->trip1, $this->trip2];

    $this->haversineService = Mockery::mock(HaversineService::class);
    $this->service = new ItineraryService($this->trips, $this->haversineService);
});

it('finds trips with N cities and different countries', function () {
    $result = $this->service->findTripsWithMultipleSteps(2);

    expect($result)->toBeArray()
        ->and(count($result))->toBe(2)
        ->and($result[0])->toBeArray()
        ->and(count($result[0]))->toBe(2);
});

it('returns dropoff stations for a given pickup station', function () {
    $dropoffStations = $this->service->getDropoffStationsByPickup('Turin');

    expect($dropoffStations)->toBeArray()
        ->and($dropoffStations)->toContain($this->station2);
});

it('checks if all visited cities are in different countries', function () {
    $visited = [$this->station1, $this->station2];
    $result = $this->service->isDifferentDestinationCountry($visited);

    expect($result)->toBeTrue();
});

it('returns the country for a given city name', function () {
    $country = $this->service->getCountryByCityName('Turin, Italy');

    expect($country)->toBe('Italy');
});

it('returns null if city coordinates are not found', function () {
    $this->haversineService->shouldReceive('execute')->never();

    // Simula una città inesistente
    $coordinates = $this->service->getCoordinatesByCityName('NonExistentCity');

    expect($coordinates)->toBeNull();
});

it('handles trips with duplicate destinations', function () {
    $trip = new TripDto($this->station1, [$this->station2, $this->station2], ['Italy', 'Germany']);

    $this->service = new ItineraryService([$trip], $this->haversineService);

    $dropoffStations = $this->service->getDropoffStationsByPickup('Turin');

    expect($dropoffStations)->toBeArray()
        ->and($dropoffStations)->toHaveLength(2);
});

it('returns 0 distance if there are no cities in the trip', function () {
    $distance = $this->service->calculateTripHaversineLength([]);

    expect($distance)->toBe(0.0); // Nessuna città, quindi distanza 0
});

it('returns false if all cities are in the same country', function () {
    $station4 = new StationDto('4', 'Milan', 'Milan, Italy', 'Italy', [1, 1]);

    $visited = [$this->station1, $station4];
    $result = $this->service->isDifferentDestinationCountry($visited);

    expect($result)->toBeFalse();
});

it('returns an empty array if no dropoff stations are found', function () {
    $dropoffStations = $this->service->getDropoffStationsByPickup('NonExistentStation');

    expect($dropoffStations)->toBeArray()
        ->and($dropoffStations)->toBeEmpty();
});
