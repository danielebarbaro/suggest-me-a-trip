<?php

use App\Stations\Station;
use App\Trips\CustomTripSubscriber;
use App\Trips\Services\FilterCustomTripsService;
use App\Trips\Trip;
use Carbon\Carbon;

function makeTrip(string $fromCountry, string $toCountry, float $length, string $start, string $end): Trip
{
    $pickup = new Station('p', 'P', 'Pickup', $fromCountry, [0.0, 0.0]);
    $dropoff = new Station('d', 'D', 'Dropoff', $toCountry, [1.0, 1.0]);
    $trip = new Trip($pickup, $dropoff, [$fromCountry, $toCountry], [
        'startDate' => Carbon::parse($start),
        'endDate' => Carbon::parse($end),
    ]);
    $trip->length = $length;

    return $trip;
}

function italySubscriber(string $direction = 'departure', int $maxKm = 1500): CustomTripSubscriber
{
    return new CustomTripSubscriber(
        'a@b.com',
        'tok',
        'italy',
        $direction,
        $maxKm,
        Carbon::parse('2026-07-14')->startOfDay(),
        Carbon::parse('2026-09-09')->endOfDay(),
    );
}

it('keeps a departure trip from the chosen country within distance and window', function () {
    $trips = [makeTrip('italy', 'france', 800, '2026-07-20', '2026-07-25')];
    $result = (new FilterCustomTripsService())->execute($trips, italySubscriber(), Carbon::parse('2026-06-24'));

    expect($result)->toHaveCount(1);
});

it('drops trips that depart from another country', function () {
    $trips = [makeTrip('spain', 'france', 800, '2026-07-20', '2026-07-25')];
    $result = (new FilterCustomTripsService())->execute($trips, italySubscriber(), Carbon::parse('2026-06-24'));

    expect($result)->toBeEmpty();
});

it('matches on arrival country when direction is arrival', function () {
    $trips = [makeTrip('france', 'italy', 800, '2026-07-20', '2026-07-25')];
    $result = (new FilterCustomTripsService())->execute($trips, italySubscriber('arrival'), Carbon::parse('2026-06-24'));

    expect($result)->toHaveCount(1);
});

it('drops trips longer than max km', function () {
    $trips = [makeTrip('italy', 'norway', 2400, '2026-07-20', '2026-07-25')];
    $result = (new FilterCustomTripsService())->execute($trips, italySubscriber('departure', 1500), Carbon::parse('2026-06-24'));

    expect($result)->toBeEmpty();
});

it('drops trips outside the date window', function () {
    $trips = [makeTrip('italy', 'france', 800, '2026-10-01', '2026-10-05')];
    $result = (new FilterCustomTripsService())->execute($trips, italySubscriber(), Carbon::parse('2026-06-24'));

    expect($result)->toBeEmpty();
});

it('keeps trips that overlap the window edge', function () {
    $trips = [makeTrip('italy', 'france', 800, '2026-09-08', '2026-09-15')];
    $result = (new FilterCustomTripsService())->execute($trips, italySubscriber(), Carbon::parse('2026-06-24'));

    expect($result)->toHaveCount(1);
});

it('drops past trips', function () {
    $trips = [makeTrip('italy', 'france', 800, '2026-07-20', '2026-07-25')];
    $result = (new FilterCustomTripsService())->execute($trips, italySubscriber(), Carbon::parse('2026-08-01'));

    expect($result)->toBeEmpty();
});

it('sorts results by start date ascending', function () {
    $trips = [
        makeTrip('italy', 'france', 800, '2026-08-10', '2026-08-15'),
        makeTrip('italy', 'austria', 700, '2026-07-20', '2026-07-25'),
    ];
    $result = (new FilterCustomTripsService())->execute($trips, italySubscriber(), Carbon::parse('2026-06-24'));

    expect($result)->toHaveCount(2)
        ->and($result[0]->timeframes['startDate']->toDateString())->toBe('2026-07-20');
});
