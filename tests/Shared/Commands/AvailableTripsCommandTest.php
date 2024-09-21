<?php

use App\Shared\Commands\AvailableTripsCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;

it('displays an error if no trips are available', function () {
    $command = new AvailableTripsCommand([]);
    $commandTester = new CommandTester($command);

    $commandTester->execute([]);

    $output = $commandTester->getDisplay();

    expect($output)->toContain('No trip found.')
        ->and($commandTester->getStatusCode())->toBe(Command::FAILURE);
});

it('lists available trips', function () {
    $trips = [
        (object) [
            'pickupStation' => (object) ['fullName' => 'Station A'],
            'dropoffStation' => (object) ['fullName' => 'Station B'],
            'countries' => ['Country1', 'Country2'],
        ],
        (object) [
            'pickupStation' => (object) ['fullName' => 'Station C'],
            'dropoffStation' => (object) ['fullName' => 'Station D'],
            'countries' => ['Country3'],
        ],
    ];

    $command = new AvailableTripsCommand($trips);
    $commandTester = new CommandTester($command);

    $commandTester->execute([]);

    $output = $commandTester->getDisplay();

    expect($output)->toContain('Station A > Station B')
        ->and($output)->toContain('Station C > Station D')
        ->and($commandTester->getStatusCode())->toBe(Command::SUCCESS);
});

it('filters trips by country', function () {
    $trips = [
        (object) [
            'pickupStation' => (object) ['fullName' => 'Station A'],
            'dropoffStation' => (object) ['fullName' => 'Station B'],
            'countries' => ['Country1'],
        ],
        (object) [
            'pickupStation' => (object) ['fullName' => 'Station C'],
            'dropoffStation' => (object) ['fullName' => 'Station D'],
            'countries' => ['Country2'],
        ],
    ];

    $command = new AvailableTripsCommand($trips);
    $commandTester = new CommandTester($command);

    $commandTester->execute(['--filter-by-country' => 'Country1']);

    $output = $commandTester->getDisplay();

    expect($output)->toContain('Station A > Station B')
        ->and($output)->not->toContain('Station C > Station D')
        ->and($commandTester->getStatusCode())->toBe(Command::SUCCESS);
});
