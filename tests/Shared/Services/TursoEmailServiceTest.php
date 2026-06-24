<?php

use App\Shared\Services\TursoEmailService;

it('returns true when both parameters are provided', function () {
    $service = new TursoEmailService('https://test.turso.io', 'test-token');

    expect($service->isConfigured())->toBeTrue();
});

it('returns false when url is empty', function () {
    $service = new TursoEmailService('', 'test-token');

    expect($service->isConfigured())->toBeFalse();
});

it('returns false when token is empty', function () {
    $service = new TursoEmailService('https://test.turso.io', '');

    expect($service->isConfigured())->toBeFalse();
});

it('returns false when both parameters are empty', function () {
    $service = new TursoEmailService('', '');

    expect($service->isConfigured())->toBeFalse();
});

it('returns empty array on connection error', function () {
    $service = new TursoEmailService('invalid-url', 'test-token');
    $emails = $service->getActiveEmails();

    expect($emails)
        ->toBeArray()
        ->toBeEmpty();
});

it('returns empty array of custom subscribers on connection error', function () {
    $service = new TursoEmailService('invalid-url', 'test-token');

    expect($service->getCustomTripSubscribers())
        ->toBeArray()
        ->toBeEmpty();
});

it('throws InvalidArgumentException when an unknown flag is passed to getActiveEmails', function () {
    $service = new TursoEmailService('', '');

    $service->getActiveEmails('not_a_real_flag');
})->throws(InvalidArgumentException::class);
