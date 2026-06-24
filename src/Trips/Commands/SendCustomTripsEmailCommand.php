<?php

declare(strict_types=1);

namespace App\Trips\Commands;

use App\Shared\Services\EmailServiceInterface;
use App\Shared\Services\TursoEmailService;
use App\Trips\CustomTripSubscriber;
use App\Trips\Services\FilterCustomTripsService;
use App\Trips\Trip;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendCustomTripsEmailCommand extends Command
{
    /** @var Trip[] */
    private array $trips;
    private EmailServiceInterface $emailService;
    private ?TursoEmailService $tursoEmailService;
    private FilterCustomTripsService $filterService;

    public function __construct(
        array $trips,
        EmailServiceInterface $emailService,
        ?TursoEmailService $tursoEmailService,
        FilterCustomTripsService $filterService
    ) {
        parent::__construct();
        $this->trips = $trips;
        $this->emailService = $emailService;
        $this->tursoEmailService = $tursoEmailService;
        $this->filterService = $filterService;
    }

    protected function configure(): void
    {
        $this
            ->setName('send-custom-trips-email')
            ->setDescription('Sends a personalized single-trip email to each custom-channel subscriber')
            ->setHelp('Filters trips per subscriber config (country, direction, max km, date window) and emails matches.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if (!$this->tursoEmailService || !$this->tursoEmailService->isConfigured()) {
                $output->writeln('<comment>Turso not configured, custom channel skipped.</comment>');

                return Command::SUCCESS;
            }

            $subscribers = $this->tursoEmailService->getCustomTripSubscribers();

            if (empty($subscribers)) {
                $output->writeln('<comment>No custom trip subscribers.</comment>');

                return Command::SUCCESS;
            }

            $sentCount = 0;

            foreach ($subscribers as $subscriber) {
                $matches = $this->filterService->execute($this->trips, $subscriber);

                if (empty($matches)) {
                    continue;
                }

                $this->sendEmail($subscriber, $matches);
                $sentCount++;
            }

            $output->writeln(sprintf('Custom trips emails sent to %d subscribers.', $sentCount));

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln('<error>Error sending custom trips email: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }

    /**
     * @param Trip[] $matches
     */
    private function sendEmail(CustomTripSubscriber $subscriber, array $matches): void
    {
        $unsubscribeUrl = $this->buildUnsubscribeUrl($subscriber->unsubscribeToken);

        $trips = array_map(static function (Trip $trip): array {
            return [
                'from' => $trip->pickupStation->fullName,
                'to' => $trip->dropoffStation->fullName,
                'length' => (int) round($trip->length),
                'start' => $trip->timeframes['startDate']->format('d F'),
                'end' => $trip->timeframes['endDate']->format('d F'),
            ];
        }, $matches);

        $templateData = [
            'trips' => $trips,
            'unsubscribe_url' => $unsubscribeUrl,
        ];

        $this->emailService->send(
            'emails/custom_trips.html.twig',
            $_ENV['NOTIFICATION_FROM_EMAIL'],
            trim($subscriber->email),
            'Trip su misura - '.date('Y-m-d'),
            json_encode($templateData),
            $this->buildUnsubscribeHeaders($unsubscribeUrl)
        );
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
