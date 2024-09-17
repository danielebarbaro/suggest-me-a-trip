<?php

namespace App\Commands;

use App\Helpers\HttpClientHelper;
use App\Services\GeoCoderService;
use App\Services\HaversineService;
use App\Services\StationService;
use App\Services\ItineraryService;
use App\Services\TripService;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Symfony\Component\HttpClient\Psr18Client;

class AvailableItinerariesCommand extends Command
{
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
            ->setHelp('This command use the trips to generate smart itinerary.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $counter = 1;
        $steps = $input->getOption('steps') ?? 3;
        $order = $input->getOption('order') ?? null;

        $provider = new GoogleMaps(new Psr18Client(), null, $_ENV['GOOGLE_MAPS_API_KEY']);
        $geocoder = new GeoCoderService($provider);

        $cache = new FilesystemAdapter('_smitineraries', $_ENV['CACHE_TTL']);
        $client = new HttpClientHelper();

        $stationService = new StationService($geocoder, $client, $cache);
        $tripService = new TripService($stationService, $client, $cache);

        $trips = $tripService->execute();
        $tripFinderService = new ItineraryService($trips, new HaversineService());

        $results = $tripFinderService->findTripsWithMultipleSteps($steps);

        if (empty($results)) {
            $output->writeln("<error>No itinerary found with {$steps} steps </error>");

            return Command::FAILURE;
        } else {
            $trips = $this->orderResults(
                $tripFinderService->getTripsWithHaversineLength($results),
                $order
            );

            $output->writeln("<info>Itineraries found with {$steps} steps </info>");
            $output->writeln('');

            foreach ($trips as $distance => $trip) {
                $highlightedStations = array_map(
                    fn ($station) => str_contains($station->fullName, 'Italy')
                        ? "<fg=yellow>{$station->fullName}</>"
                        : $station->fullName,
                    $trip
                );
                $stations = implode(
                    ' -> ',
                    $highlightedStations
                );
                $output->writeln("{$counter}. {$stations} - [$distance km]");
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
}
