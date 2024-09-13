<?php

namespace App\Commands;

use App\Helpers\ClientHttpHelper;
use App\Helpers\HttpClientHelper;
use App\Services\StationService;
use App\Services\TripService;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

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
        $cache = new ArrayAdapter();
        $tripService = new TripService($cache);
        $trips = $tripService->execute($input->getOption('filter-by-country', null));

        $output->getFormatter()->setStyle(
            'fire',
            new OutputFormatterStyle('red', null, ['bold'])
        );

        $output->getFormatter()->setStyle(
            'ocean',
            new OutputFormatterStyle('blue', null, ['bold'])
        );

        foreach ($trips as $trip) {
            if (!empty($input->getOption('filter-by-country')) && !in_array($input->getOption('filter-by-country'), $trip['countries'])) {
                continue;
            }

            $io = new SymfonyStyle($input, $output);
            $output->writeln("<fire>{$counter}: {$trip['pickup_station']}</>");

            $output->writeln("<ocean>====================================</>");
            foreach ($trip['dropoff_station'] as $station) {
                $output->writeln("<ocean>{$station['name']}</>");
            }

            $io->newLine(2);
            ++$counter;
        }

        return Command::SUCCESS;
    }
}
