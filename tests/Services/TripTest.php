<?php

use App\Dto\StationDto;
use App\Services\TimeFrameService;
use App\Services\TripService;
use App\Dto\TripDto;
use App\Services\StationService;
use App\Helpers\HttpClientHelper;
use Symfony\Contracts\Cache\CacheInterface;

beforeEach(function () {
    $this->stationsServiceMock = Mockery::mock(StationService::class);
    $this->timeFrameServiceMock = Mockery::mock(TimeFrameService::class);
    $this->httpClientHelperMock = Mockery::mock(HttpClientHelper::class);
    $this->cacheMock = Mockery::mock(CacheInterface::class);
    $this->cacheMock->shouldReceive('isHit')->andReturn(false);
    $this->cacheMock->shouldReceive('set');

    $this->cacheMock
        ->shouldReceive('getItem')
        ->andReturn($this->cacheMock);

    $this->cacheMock
        ->shouldReceive('save');

    $this->timeFrameServiceMock
        ->shouldReceive('execute')
        ->andReturn([
            ['2024-10-23T00:00:00+00:00', '2024-10-30T00:00:00+00:00'],
        ]);

    $this->cacheMock->shouldReceive('get')
        ->with(Mockery::type('string'), Mockery::type('callable'))
        ->andReturnUsing(function ($cacheKey, $callback) {
            return $callback();  // Esegue il callback passato come secondo argomento
        });

    $this->tripService = new TripService(
        $this->stationsServiceMock,
        $this->httpClientHelperMock,
        $this->cacheMock
    );

    $this->station1 = new StationDto('1', 'Turin', 'Turin, Italy', 'Italy', [45.0703, 7.6869]);
    $this->station2 = new StationDto('2', 'Frankfurt', 'Frankfurt, Germany', 'Germany', [50.1109, 8.6821]);
    $this->station3 = new StationDto('3', 'Bordeaux', 'Bordeaux, France', 'France', [44.8416106, -0.5810938]);
    $this->station4 = new StationDto('4', 'Milan', 'Milan, Italy', 'Italy', [1, 1]);
});

afterEach(function () {
    Mockery::close();
});

it('skips stations without destinations', function () {
    $this->stationsServiceMock->shouldReceive('getRally')->andReturn([
        1 => (object) ['country' => 'Italy'],
    ]);

    $this->stationsServiceMock->shouldReceive('getDestinationsById')->with(1)->andReturn([]);

    $results = $this->tripService->execute();

    expect($results)->toBe([]);
});

it('removes duplicate countries and converts them to lowercase', function () {
    $countries = $this->tripService->getUniqueCountries($this->station1, [$this->station3, $this->station4]);
    expect(array_values($countries))->toBe(['italy', 'france']);
});

it('calculates haversine length between cities', function () {
    $distance = $this->tripService->calculateTripHaversineLength([$this->station2, $this->station1]);
    expect($distance)->toBe(565.42);
});

// it('executes and returns an array of TripDto', function () {
//    $this->stationsServiceMock
//        ->shouldReceive('getRally')
//        ->andReturn(['1' => $this->station1]);
//
//    $this->stationsServiceMock
//        ->shouldReceive('getDestinationsById')
//        ->with('1')
//        ->andReturn([$this->station2]);
//
//    $this->httpClientHelperMock
//        ->shouldReceive('fetchFromApi')
//        ->andReturn([
//            ['startDate' => '2024-10-23T00:00:00+00:00', 'endDate' => '2024-10-30T00:00:00+00:00'],
//        ]);
//
//    $trips = $this->tripService->execute();
//
//    expect($trips)->toBeArray()
//        ->and($trips)->toHaveCount(1)
//        ->and($trips[0])->toBeInstanceOf(TripDto::class)
//        ->and($trips[0]->pickupStation->name)->toBe('Turin')
//        ->and($trips[0]->dropoffStation->name)->toBe('Frankfurt')
//        ->and($trips[0]->length)->toBeGreaterThan(0);
// });
