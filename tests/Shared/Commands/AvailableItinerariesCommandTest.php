<?php

use App\Shared\Commands\AvailableItinerariesCommand;
use App\Itineraries\Services\CreateItinerariesService;
use App\Stations\Station;
use App\Trips\Trip;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;

it('displays an error if no itineraries are available', function () {
    $mockService = Mockery::mock(CreateItinerariesService::class);
    $mockService->shouldReceive('execute')->andReturn([]);

    $command = new AvailableItinerariesCommand($mockService);

    $commandTester = new CommandTester($command);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();

    expect($output)->toContain('No itinerary found with 2 steps')
        ->and($commandTester->getStatusCode())->toBe(Command::FAILURE);
});

it('lists available itineraries', function () {
    $itineraries = [
        '100_1' => (object) [
            'trips' => [
                new Trip(
                    new Station('1', 'Turin', 'Turin, Italy', 'Italy', [45.0703, 7.6869]),
                    new Station('2', 'Frankfurt', 'Frankfurt, Germany', 'Germany', [50.1109, 8.6821]),
                    ['italy', 'germany'],
                    ['startDate' => new DateTime('2023-01-01'), 'endDate' => new DateTime('2023-01-05')]
                ),
            ],
            'length' => 100.1,
        ],
    ];

    $mockService = Mockery::mock(CreateItinerariesService::class);
    $mockService->shouldReceive('execute')->andReturn($itineraries);

    $command = new AvailableItinerariesCommand($mockService);

    $commandTester = new CommandTester($command);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();

    expect($output)->toContain('Itineraries found with 2 steps')
        ->and($output)->toContain('Turin, Italy -> Frankfurt, Germany')
        ->and($output)->toContain('Total distance: 100 km')
        ->and($commandTester->getStatusCode())->toBe(Command::SUCCESS);
});

it('orders itineraries by ascending distance', function () {
    $itineraries = [
        '200_1' => (object) [
            'trips' => [
                new Trip(
                    new Station('1', 'Milan', 'Milan, Italy', 'Italy', [45.4643, 9.1895]),
                    new Station('2', 'Munich', 'Munich, Germany', 'Germany', [48.1351, 11.5820]),
                    ['italy', 'germany'],
                    ['startDate' => new DateTime('2023-01-01'), 'endDate' => new DateTime('2023-01-05')]
                ),
            ],
            'length' => 200,
        ],
        '100_1' => (object) [
            'trips' => [
                new Trip(
                    new Station('1', 'Turin', 'Turin, Italy', 'Italy', [45.0703, 7.6869]),
                    new Station('2', 'Frankfurt', 'Frankfurt, Germany', 'Germany', [50.1109, 8.6821]),
                    ['italy', 'germany'],
                    ['startDate' => new DateTime('2023-01-01'), 'endDate' => new DateTime('2023-01-05')]
                ),
            ],
            'length' => 100,
        ],
    ];

    $mockService = Mockery::mock(CreateItinerariesService::class);
    $mockService->shouldReceive('execute')->andReturn($itineraries);

    $command = new AvailableItinerariesCommand($mockService);

    $commandTester = new CommandTester($command);
    $commandTester->execute(['--order' => 'asc']);

    $output = $commandTester->getDisplay();

    expect($output)->toContain('Turin, Italy -> Frankfurt, Germany')
        ->and($output)->toContain('Milan, Italy -> Munich, Germany')
        ->and($output)->toContain('Total distance: 100 km')
        ->and($commandTester->getStatusCode())->toBe(Command::SUCCESS);
});

it('filters itineraries by minimum steps', function () {
    $itineraries = [
        '200_2' => (object) [
            'trips' => [
                new Trip(
                    new Station('1', 'Turin', 'Turin, Italy', 'Italy', [45.0703, 7.6869]),
                    new Station('2', 'Frankfurt', 'Frankfurt, Germany', 'Germany', [50.1109, 8.6821]),
                    ['italy', 'germany'],
                    ['startDate' => new DateTime('2023-01-01'), 'endDate' => new DateTime('2023-01-05')]
                ),
                new Trip(
                    new Station('2', 'Frankfurt', 'Frankfurt, Germany', 'Germany', [50.1109, 8.6821]),
                    new Station('3', 'Paris', 'Paris, France', 'France', [48.8566, 2.3522]),
                    ['germany', 'france'],
                    ['startDate' => new DateTime('2023-01-06'), 'endDate' => new DateTime('2023-01-10')]
                ),
            ],
            'length' => 200,
        ],
    ];

    $mockService = Mockery::mock(CreateItinerariesService::class);
    $mockService->shouldReceive('execute')->andReturn($itineraries);

    $command = new AvailableItinerariesCommand($mockService);

    $commandTester = new CommandTester($command);
    $commandTester->execute(['--min-steps' => 2]);

    $output = $commandTester->getDisplay();

    expect($output)->toContain('Itineraries found with 2 steps')
        ->and($commandTester->getStatusCode())->toBe(Command::SUCCESS);
});
