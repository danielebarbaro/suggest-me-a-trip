<?php

use App\Shared\Services\EmailServiceInterface;
use App\Shared\Services\TursoEmailService;
use App\Stations\Station;
use App\Trips\Commands\SendCustomTripsEmailCommand;
use App\Trips\CustomTripSubscriber;
use App\Trips\Services\FilterCustomTripsService;
use App\Trips\Trip;
use Carbon\Carbon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RecordingEmailService implements EmailServiceInterface
{
    public array $sent = [];

    public function send(string $template, string $from, string $to, string $subject, string $htmlContent, array $headers = []): void
    {
        $this->sent[] = ['to' => $to, 'content' => $htmlContent];
    }
}

class FakeTurso extends TursoEmailService
{
    /** @var CustomTripSubscriber[] */
    public array $subscribers = [];

    public function __construct(array $subscribers)
    {
        parent::__construct('https://x.turso.io', 'tok');
        $this->subscribers = $subscribers;
    }

    public function getCustomTripSubscribers(): array
    {
        return $this->subscribers;
    }
}

function customTrip(string $from, string $to, float $length, string $start, string $end): Trip
{
    $trip = new Trip(
        new Station('p', 'P', 'Pickup '.$from, $from, [0.0, 0.0]),
        new Station('d', 'D', 'Dropoff '.$to, $to, [1.0, 1.0]),
        [$from, $to],
        ['startDate' => Carbon::parse($start), 'endDate' => Carbon::parse($end)]
    );
    $trip->length = $length;

    return $trip;
}

function customSub(string $email, string $direction = 'departure'): CustomTripSubscriber
{
    return new CustomTripSubscriber(
        $email,
        'tok-'.$email,
        'italy',
        $direction,
        1500,
        Carbon::parse('2026-07-14')->startOfDay(),
        Carbon::parse('2026-09-09')->endOfDay(),
    );
}

beforeEach(function () {
    $_ENV['NOTIFICATION_FROM_EMAIL'] = 'noreply@test.com';
    Carbon::setTestNow(Carbon::parse('2026-06-24'));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('sends a personalized email to a subscriber with matches', function () {
    $trips = [customTrip('italy', 'france', 800, '2026-07-20', '2026-07-25')];
    $email = new RecordingEmailService();
    $turso = new FakeTurso([customSub('a@b.com')]);

    $command = new SendCustomTripsEmailCommand($trips, $email, $turso, new FilterCustomTripsService());
    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($email->sent)->toHaveCount(1)
        ->and($email->sent[0]['to'])->toBe('a@b.com')
        ->and($tester->getStatusCode())->toBe(Command::SUCCESS);
});

it('skips subscribers with no matching trips', function () {
    $trips = [customTrip('spain', 'france', 800, '2026-07-20', '2026-07-25')];
    $email = new RecordingEmailService();
    $turso = new FakeTurso([customSub('a@b.com')]);

    $command = new SendCustomTripsEmailCommand($trips, $email, $turso, new FilterCustomTripsService());
    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($email->sent)->toBeEmpty()
        ->and($tester->getStatusCode())->toBe(Command::SUCCESS);
});

it('succeeds with a notice when there are no subscribers', function () {
    $trips = [customTrip('italy', 'france', 800, '2026-07-20', '2026-07-25')];
    $email = new RecordingEmailService();
    $turso = new FakeTurso([]);

    $command = new SendCustomTripsEmailCommand($trips, $email, $turso, new FilterCustomTripsService());
    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($email->sent)->toBeEmpty()
        ->and($tester->getDisplay())->toContain('No custom trip subscribers')
        ->and($tester->getStatusCode())->toBe(Command::SUCCESS);
});
