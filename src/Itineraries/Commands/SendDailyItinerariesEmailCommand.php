<?php

declare(strict_types=1);

namespace App\Itineraries\Commands;

use App\Itineraries\Services\CreateItinerariesService;
use App\Shared\Services\EmailServiceInterface;
use App\Shared\Services\TursoEmailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class SendDailyItinerariesEmailCommand extends Command
{
    private CreateItinerariesService $itinerariesService;
    private EmailServiceInterface $emailService;
    private ?TursoEmailService $tursoEmailService;

    public function __construct(CreateItinerariesService $itinerariesService, EmailServiceInterface $emailService, ?TursoEmailService $tursoEmailService = null)
    {
        parent::__construct();
        $this->itinerariesService = $itinerariesService;
        $this->emailService = $emailService;
        $this->tursoEmailService = $tursoEmailService;
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

            $recipients = $this->getRecipientEmails($output);

            if (empty($recipients)) {
                $output->writeln('<e>No recipient emails found. Check Turso configuration or NOTIFICATION_EMAILS env variable.</e>');

                return Command::FAILURE;
            }

            $output->writeln(sprintf('Sending emails to %d recipients...', count($recipients)));

            foreach ($recipients as $recipient) {
                $unsubscribeUrl = $this->buildUnsubscribeUrl($recipient['unsubscribe_token'] ?? null);

                $templateData = [
                    'groupedItineraries' => $results,
                    'template' => 'emails/available_itineraries.html.twig',
                    'unsubscribe_url' => $unsubscribeUrl,
                ];

                $this->emailService->send(
                    'emails/available_itineraries.html.twig',
                    $_ENV['NOTIFICATION_FROM_EMAIL'],
                    trim($recipient['email']),
                    'Daily Available Itineraries Report - '.date('Y-m-d'),
                    json_encode($templateData),
                    $this->buildUnsubscribeHeaders($unsubscribeUrl)
                );
            }

            $output->writeln('Daily itineraries email sent successfully!');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln('<error>Error sending daily itineraries email: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }

    private function getRecipientEmails(OutputInterface $output): array
    {
        if ($this->tursoEmailService && $this->tursoEmailService->isConfigured()) {
            $output->writeln('Retrieving emails from Turso database...');
            $tursoEmails = $this->tursoEmailService->getActiveEmails('sub_daily_itineraries');

            if (!empty($tursoEmails)) {
                $output->writeln(sprintf('Found %d emails from Turso database', count($tursoEmails)));

                return $tursoEmails;
            } else {
                $output->writeln('<comment>No emails found in Turso database, falling back to NOTIFICATION_EMAILS</comment>');
            }
        } else {
            $output->writeln('<comment>Turso service not configured, using NOTIFICATION_EMAILS</comment>');
        }

        if (isset($_ENV['NOTIFICATION_EMAILS']) && !empty($_ENV['NOTIFICATION_EMAILS'])) {
            $emails = array_map('trim', explode(',', $_ENV['NOTIFICATION_EMAILS']));
            $output->writeln(sprintf('Using %d emails from NOTIFICATION_EMAILS', count($emails)));

            return array_map(
                static fn (string $email): array => ['email' => $email, 'unsubscribe_token' => null],
                $emails
            );
        }

        return [];
    }

    private function buildUnsubscribeUrl(?string $token): ?string
    {
        if (empty($token)) {
            return null;
        }

        $baseUrl = rtrim($_ENV['UNSUBSCRIBE_BASE_URL'] ?? 'https://vanlife.plincode.tech', '/');

        return $baseUrl.'/api/unsubscribe?token='.rawurlencode($token);
    }

    /**
     * @return array<string, string>
     */
    private function buildUnsubscribeHeaders(?string $unsubscribeUrl): array
    {
        if (empty($unsubscribeUrl)) {
            return [];
        }

        return [
            'List-Unsubscribe' => '<'.$unsubscribeUrl.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ];
    }
}
