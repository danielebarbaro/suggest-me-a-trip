<?php

declare(strict_types=1);

namespace App\Itineraries\Commands;

use App\Itineraries\Services\CreateItinerariesService;
use App\Shared\Services\EmailServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class SendDailyItinerariesEmailCommand extends Command
{
    private CreateItinerariesService $itinerariesService;
    private EmailServiceInterface $emailService;

    public function __construct(CreateItinerariesService $itinerariesService, EmailServiceInterface $emailService)
    {
        parent::__construct();
        $this->itinerariesService = $itinerariesService;
        $this->emailService = $emailService;
    }

    protected function configure(): void
    {
        $this
            ->setName('send-daily-itineraries-email')
            ->setDescription('Sends daily available itineraries via email')
            ->setHelp('This command sends a daily itineraries list via email.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $options = [
                'noSameCountry' => true,
                'minDaysDifferenceBetweenStartAndEnd' => 3,
                'checkTimeFrame' => true,
                'minSteps' => 2,
            ];

            $itineraries = $this->itinerariesService->execute($options);
            ksort($itineraries);

            if (empty($itineraries)) {
                $output->writeln('<error>No itineraries found.</error>');

                return Command::SUCCESS;
            }

            $results = [];
            $counter = 1;

            foreach ($itineraries as $key => $itinerary) {
                list($distance, $_) = explode('_', $key);
                $tripResult = [];

                foreach ($itinerary->trips as $trip) {
                    $pickup = $trip->pickupStation->fullName;
                    $dropoff = $trip->dropoffStation->fullName;

                    $tripResult[] = [
                        'title' => "\t{$pickup} -> {$dropoff}",
                        'timeframes' => [
                            'startDate' => $trip->timeframes['startDate']->format('d F'),
                            'endDate' => $trip->timeframes['endDate']->format('d F'),
                            'distance' => "[{$trip->length} Km]",
                        ],
                    ];
                }
                $results[] = [
                    'title' => "#{$counter}. Total distance: $distance km",
                    'trips' => $tripResult,
                ];

                ++$counter;
            }

            $templateData = [
                'groupedItineraries' => $results,
                'template' => 'emails/available_itineraries.html.twig',
            ];

            $recipientEmails = explode(',', $_ENV['NOTIFICATION_EMAILS']);

            foreach ($recipientEmails as $email) {
                $this->emailService->send(
                    'emails/available_itineraries.html.twig',
                    $_ENV['NOTIFICATION_FROM_EMAIL'],
                    trim($email),
                    'Daily Available Itineraries Report - '.date('Y-m-d'),
                    json_encode($templateData)
                );
            }

            $output->writeln('Daily itineraries email sent successfully!');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln('<error>Error sending daily itineraries email: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
