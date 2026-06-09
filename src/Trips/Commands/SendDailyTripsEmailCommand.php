<?php

declare(strict_types=1);

namespace App\Trips\Commands;

use App\Shared\Services\EmailServiceInterface;
use App\Shared\Services\TursoEmailService;
use Carbon\Carbon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class SendDailyTripsEmailCommand extends Command
{
    private array $trips;
    private EmailServiceInterface $emailService;
    private ?TursoEmailService $tursoEmailService;

    public function __construct(array $trips, EmailServiceInterface $emailService, ?TursoEmailService $tursoEmailService = null)
    {
        parent::__construct();
        $this->trips = $trips;
        $this->emailService = $emailService;
        $this->tursoEmailService = $tursoEmailService;
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

            $recipients = $this->getRecipientEmails($output);

            if (empty($recipients)) {
                $output->writeln('<e>No recipient emails found. Check Turso configuration or NOTIFICATION_EMAILS env variable.</e>');

                return Command::FAILURE;
            }

            $output->writeln(sprintf('Sending emails to %d recipients...', count($recipients)));

            foreach ($recipients as $recipient) {
                $unsubscribeUrl = $this->buildUnsubscribeUrl($recipient['unsubscribe_token'] ?? null);

                $templateData = [
                    'groupedTrips' => $groupedTrips,
                    'unsubscribe_url' => $unsubscribeUrl,
                ];

                $this->emailService->send(
                    'emails/daily_trips.html.twig',
                    $_ENV['NOTIFICATION_FROM_EMAIL'],
                    trim($recipient['email']),
                    'Daily Available Trips Report - '.date('Y-m-d'),
                    json_encode($templateData),
                    $this->buildUnsubscribeHeaders($unsubscribeUrl)
                );
            }

            $output->writeln('Daily trips email sent successfully!');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln('<error>Error sending daily trips email: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }

    private function getRecipientEmails(OutputInterface $output): array
    {
        if ($this->tursoEmailService && $this->tursoEmailService->isConfigured()) {
            $output->writeln('Retrieving emails from Turso database...');
            $tursoEmails = $this->tursoEmailService->getActiveEmails();

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
