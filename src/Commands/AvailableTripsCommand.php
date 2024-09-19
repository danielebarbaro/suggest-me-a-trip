<?php

namespace App\Commands;

use App\Trips\Services\CreateTripsService;
use Library\RoadSurfer\Cache\Cache;
use Library\RoadSurfer\HttpClient\Client;
use Library\RoadSurfer\RoadSurfer;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\HttpCache\Store;

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

        $httpStoreCache = new Store(__DIR__.'/../../var/cache');
        $adapter = new FilesystemAdapter('trip', $_ENV['CACHE_TTL']);

        $roadSurfer = new RoadSurfer(new Client($httpStoreCache), new Cache($adapter));

        $trips = (new CreateTripsService($roadSurfer))->execute();

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
