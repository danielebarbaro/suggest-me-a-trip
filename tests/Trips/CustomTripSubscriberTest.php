<?php

use App\Trips\CustomTripSubscriber;

it('builds a subscriber from a complete row', function () {
    $sub = CustomTripSubscriber::fromRow([
        'email' => 'a@b.com',
        'unsubscribe_token' => 'tok',
        'custom_country' => 'italy',
        'custom_direction' => 'departure',
        'custom_max_km' => 1500,
        'custom_date_from' => '2026-07-14',
        'custom_date_to' => '2026-09-09',
    ]);

    expect($sub)->toBeInstanceOf(CustomTripSubscriber::class)
        ->and($sub->email)->toBe('a@b.com')
        ->and($sub->country)->toBe('italy')
        ->and($sub->direction)->toBe('departure')
        ->and($sub->maxKm)->toBe(1500)
        ->and($sub->dateFrom->toDateString())->toBe('2026-07-14')
        ->and($sub->dateTo->toDateString())->toBe('2026-09-09');
});

it('returns null when required config is missing', function () {
    $sub = CustomTripSubscriber::fromRow([
        'email' => 'a@b.com',
        'custom_country' => null,
        'custom_direction' => 'departure',
        'custom_max_km' => 1500,
        'custom_date_from' => '2026-07-14',
        'custom_date_to' => '2026-09-09',
    ]);

    expect($sub)->toBeNull();
});

it('returns null when direction is invalid', function () {
    $sub = CustomTripSubscriber::fromRow([
        'email' => 'a@b.com',
        'custom_country' => 'italy',
        'custom_direction' => 'sideways',
        'custom_max_km' => 1500,
        'custom_date_from' => '2026-07-14',
        'custom_date_to' => '2026-09-09',
    ]);

    expect($sub)->toBeNull();
});

it('returns null when email is missing', function () {
    $sub = CustomTripSubscriber::fromRow([
        'email' => '',
        'custom_country' => 'italy',
        'custom_direction' => 'departure',
        'custom_max_km' => 1500,
        'custom_date_from' => '2026-07-14',
        'custom_date_to' => '2026-09-09',
    ]);

    expect($sub)->toBeNull();
});

it('returns null when max km is missing', function () {
    $sub = CustomTripSubscriber::fromRow([
        'email' => 'a@b.com',
        'custom_country' => 'italy',
        'custom_direction' => 'departure',
        'custom_max_km' => '',
        'custom_date_from' => '2026-07-14',
        'custom_date_to' => '2026-09-09',
    ]);

    expect($sub)->toBeNull();
});
