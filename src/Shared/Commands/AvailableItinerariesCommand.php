<?php

namespace App\Shared\Commands;

use App\Itineraries\Services\CreateItinerariesService;
use App\Stations\Station;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AvailableItinerariesCommand extends Command
{
    private CreateItinerariesService $createItinerariesService;

    public function __construct(CreateItinerariesService $createItinerariesService)
    {
        parent::__construct();
        $this->createItinerariesService = $createItinerariesService;
    }

    protected function configure(): void
    {
        $this
            ->setName('available-itineraries')
            ->setDescription('Lists available itineraries')
            ->addOption(
                'min-steps',
                's',
                InputOption::VALUE_OPTIONAL,
                'Minimum number of steps',
                2
            )
            ->addOption(
                'order',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Order by distance asc|desc',
                null
            )
            ->addOption(
                'check-time-frame',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Check time frame',
                true
            )
            ->addOption(
                'visit-country-just-once',
                'sc',
                InputOption::VALUE_OPTIONAL,
                'Visit a country just once',
                true
            )
            ->setHelp('This command use the trips to generate smart itinerary.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $counter = 1;
        $order = $input->getOption('order') ?? null;

        $options = [
            'noSameCountry' => $input->getOption('visit-country-just-once') !== 'off',
            'minDaysDifferenceBetweenStartAndEnd' => 4,
            'checkTimeFrame' => $input->getOption('check-time-frame') !== 'off',
            'minSteps' => $input->getOption('min-steps') ?? 2,
        ];

        $itineraries = $this->createItinerariesService->execute($options);
        $steps = $options['minSteps'];

        if (empty($itineraries)) {
            $output->writeln("<error>No itinerary found with {$steps} steps </error>");

            return Command::FAILURE;
        } else {
            $itineraries = $this->orderResults(
                $itineraries,
                $order
            );

            $output->writeln("<info>Itineraries found with {$steps} steps </info>");

            foreach ($itineraries as $key => $itinerary) {
                list($distance, $_) = explode('_', $key);
                $output->writeln("\n#{$counter}. Total distance: $distance km");
                foreach ($itinerary->trips as $trip) {
                    $pickup = $this->highlightStations($trip->pickupStation);
                    $dropoff = $this->highlightStations($trip->dropoffStation);

                    $output->write("\t<fg=cyan>{$pickup} -> {$dropoff}</>");
                    $output->write(
                        " | <fg=green>{$trip->timeframes['startDate']->format('Y-m-d')} {$trip->timeframes['endDate']->format('Y-m-d') }</>"
                    );
                    $output->writeln(
                        " | <fg=bright-white>[{$trip->length} Km]</>"
                    );
                }

                ++$counter;
            }
        }

        return Command::SUCCESS;
    }

    private function orderResults(array $trips, ?string $order): array
    {
        if ($order !== null) {
            switch ($order) {
                case 'asc':
                    ksort($trips);
                    break;
                case 'desc':
                    krsort($trips);
                    break;
            }
        }

        return $trips;
    }

    private function highlightStations(Station $station, ?string $country = 'Italy'): string
    {
        return str_contains($station->fullName, $country)
            ? "<fg=yellow>{$station->fullName}</>"
            : $station->fullName;
    }
}
