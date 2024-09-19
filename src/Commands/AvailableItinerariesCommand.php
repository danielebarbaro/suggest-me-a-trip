<?php

namespace App\Commands;

use App\Dto\StationDto;
use App\Services\ItineraryService;
use App\Services\StationService;
use App\Services\TripService;
use App\Utils\GeoCoderService;
use App\Utils\HaversineService;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Library\RoadSurfer\src\CacheManager;
use Library\RoadSurfer\src\src\HttpClient\Client;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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

        $provider = new GoogleMaps(new Psr18Client(), null, $_ENV['GOOGLE_MAPS_API_KEY']);
        $geocoder = new GeoCoderService($provider);

        $cache = new FilesystemAdapter('_smitineraries', $_ENV['CACHE_TTL']);
        $client = new Client();

        $stationService = new StationService($geocoder, $client, new CacheManager($cache));
        $tripService = new TripService($stationService, $client, $cache);

        $trips = $tripService->execute();
        $tripFinderService = new ItineraryService($trips, new HaversineService());

        $itineraries = $tripFinderService->findTripsWithMultipleSteps($steps, $checkTimeFrame, true);

        if (empty($itineraries)) {
            $output->writeln("<error>No itinerary found with {$steps} steps </error>");

            return Command::FAILURE;
        } else {
            $itineraries = $this->orderResults(
                $itineraries,
                $order
            );

            $output->writeln("<info>Itineraries found with {$steps} steps </info>");
            $output->writeln('');

            foreach ($itineraries as $distance => $routes) {
                $output->writeln("\n#{$counter}. Total distance: $distance km");
                foreach ($routes as $route) {
                    $pickup = $this->highlightStations($route->pickupStation);
                    $dropoff = $this->highlightStations($route->dropoffStation);
                    $output->write("\t<fg=cyan>{$pickup} -> {$dropoff}</>");
                    $output->write(
                        " | <fg=green>{$route->timeframes[0]->format('Y-m-d')} {$route->timeframes[1]->format('Y-m-d') }</>"
                    );
                    $output->writeln(
                        " | <fg=bright-white>[{$route->length} Km]</>"
                    );
                }

                if ($counter === 40) {
                    dump($routes);
                    /*
                     * #40. Total distance: 2164 km
	                 * Venice, Italy -> Munich, Germany | 2024-10-21 2024-10-28
	                 * Munich, Germany -> Seville, Spain | 2024-10-08 2024-10-15
                     * */
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

    private function highlightStations(StationDto $station, ?string $country = 'Italy'): string
    {
        return str_contains($station->fullName, $country)
            ? "<fg=yellow>{$station->fullName}</>"
            : $station->fullName;
    }
}
