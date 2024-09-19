<?php

namespace App\Commands;

use App\Itineraries\Services\CreateItinerariesService;
use App\Stations\Station;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AvailableItinerariesCommand extends Command
{
    public array $trips;

    public function __construct(array $trips)
    {
        parent::__construct();
        $this->trips = $trips;
    }

    protected function configure(): void
    {
        $this
            ->setName('available-itineraries')
            ->setDescription('Lists available itineraries')
            ->addOption(
                'steps',
                's',
                InputOption::VALUE_OPTIONAL,
                'Number of steps',
                null
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
                null
            )
            ->setHelp('This command use the trips to generate smart itinerary.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $counter = 1;
        $steps = $input->getOption('steps') ?? 2;
        $order = $input->getOption('order') ?? null;
        $checkTimeFrame = $input->getOption('check-time-frame') !== 'off';

        $itineraries = (new CreateItinerariesService($this->trips))->execute(
            $steps,
            $checkTimeFrame,
            true
        );

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
                list($distance,) = explode('_', $key);
                $output->writeln("\n#{$counter}. Total distance: $distance km");
                foreach ($itinerary->trips as $trip) {
                    $pickup = $this->highlightStations($trip->pickupStation);
                    $dropoff = $this->highlightStations($trip->dropoffStation);

                    $output->write("\t<fg=cyan>{$pickup} -> {$dropoff}</>");
                    $output->write(
                        " | <fg=green>{$trip->timeframes[0]->format('Y-m-d')} {$trip->timeframes[1]->format('Y-m-d') }</>"
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
