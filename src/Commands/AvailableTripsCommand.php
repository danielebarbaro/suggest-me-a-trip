<?php

namespace App\Commands;

use App\Core\CacheManager;
use App\Helpers\HttpClientHelper;
use App\Services\GeoCoderService;
use App\Services\StationService;
use App\Services\TripService;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Symfony\Component\HttpClient\Psr18Client;

class AvailableTripsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('available-trips')
            ->setDescription('Lists available trips')
            ->addOption(
                'filter-by-country',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Filter trips by country',
                null
            )
            ->setHelp('This command lists all available trips.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $counter = 1;
        $latestStation = null;

        $provider = new GoogleMaps(new Psr18Client(), null, $_ENV['GOOGLE_MAPS_API_KEY']);
        $cache = new FilesystemAdapter('_smtrips', $_ENV['CACHE_TTL']);
        $client = new HttpClientHelper();

        $stationService = new StationService(new GeoCoderService($provider), $client, new CacheManager($cache));

        $tripService = new TripService($stationService, $client, $cache);
        $trips = $tripService->execute();

        if (empty($trips)) {
            $output->writeln('<error>No trip found.</error>');

            return Command::FAILURE;
        }

        foreach ($trips as $trip) {
            if (!empty($input->getOption('filter-by-country')) && !in_array(
                $input->getOption('filter-by-country'),
                $trip['countries']
            )) {
                continue;
            }

            if ($latestStation === null || $trip->pickupStation->fullName !== $latestStation) {
                $output->writeln('');
            }

            $output->writeln("<fg=bright-cyan>{$counter}: {$trip->pickupStation->fullName} > {$trip->dropoffStation->fullName}</>");

            $latestStation = $trip->pickupStation->fullName;
            ++$counter;
        }

        return Command::SUCCESS;
    }
}
