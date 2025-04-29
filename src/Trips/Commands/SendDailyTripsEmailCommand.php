<?php

declare(strict_types=1);

namespace App\Trips\Commands;

use App\Shared\Services\EmailServiceInterface;
use Carbon\Carbon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class SendDailyTripsEmailCommand extends Command
{
    private array $trips;
    private EmailServiceInterface $emailService;

    public function __construct(array $trips, EmailServiceInterface $emailService)
    {
        parent::__construct();
        $this->trips = $trips;
        $this->emailService = $emailService;
    }

    protected function configure(): void
    {
        $this
            ->setName('send-daily-trips-email')
            ->setDescription('Sends daily available trips via email')
            ->setHelp('This command send a daily trip list.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $today = Carbon::today();
            $trips = $this->trips;

            if (empty($trips)) {
                $output->writeln('<error>No trip found.</error>');

                return Command::SUCCESS;
            }

            $trips = array_filter($trips, function ($trip) use ($today) {
                return $trip->timeframes['startDate'] > $today;
            });

            if (empty($trips)) {
                $output->writeln('<error>No future trips found.</error>');

                return Command::SUCCESS;
            }

            usort($trips, function ($a, $b) {
                return $a->timeframes['startDate'] <=> $b->timeframes['startDate'];
            });

            // Raggruppa i viaggi per data
            $groupedTrips = [];
            foreach ($trips as $trip) {
                $timeframes = $trip->timeframes;
                $dateKey = sprintf(
                    '%s - %s',
                    $timeframes['startDate']->format('d F'),
                    $timeframes['endDate']->format('d F')
                );

                if (!isset($groupedTrips[$dateKey])) {
                    $groupedTrips[$dateKey] = [];
                }

                $groupedTrips[$dateKey][] = $trip;
            }

            $templateData = [
                'groupedTrips' => $groupedTrips,
            ];

            $recipientEmails = explode(',', $_ENV['NOTIFICATION_EMAILS']);

            foreach ($recipientEmails as $email) {
                $this->emailService->send(
                    $_ENV['NOTIFICATION_FROM_EMAIL'],
                    trim($email),
                    'Daily Available Trips Report - '.date('Y-m-d'),
                    json_encode($templateData)
                );
            }

            $output->writeln('Daily trips email sent successfully!');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln('<error>Error sending daily trips email: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
