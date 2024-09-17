<?php

namespace App\Services;

use App\Core\CacheManager;
use App\Dto\StationDto;
use App\Helpers\HttpClientHelper;
use Psr\Cache\InvalidArgumentException;
use Exception;

class StationService
{
    private const string CACHE_KEY = 'station';
    private CacheManager $cacheManager;
    private HttpClientHelper $httpClientHelper;
    private GeoCoderService $geocoder;

    public function __construct(
        GeoCoderService $geocoder,
        HttpClientHelper $httpClientHelper,
        CacheManager $cacheManager
    ) {
        $this->geocoder = $geocoder;
        $this->httpClientHelper = $httpClientHelper;
        $this->cacheManager = $cacheManager;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getRally(): array
    {
        return $this->cacheManager->retrieve(
            self::CACHE_KEY.'__rally',
            function () {
                $stations = $this->httpClientHelper->fetchFromApi(
                    'rally/stations',
                    'rally.startStations'
                );

                return $this->enabledStationDTOs($stations);
            }
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getAll(?string $lang = 'en'): array
    {
        return $this->cacheManager->retrieve(
            self::CACHE_KEY,
            function () {
                $results = [];
                $stations = $this->httpClientHelper->fetchFromApi(
                    'translations/stations',
                    'station.fetchTranslations',
                    null,
                    true
                );

                foreach ($stations as $station) {
                    $countryName = $station['country_translations']['en']['name'] ?? 'No Country Name';
                    $cityName = $station['translations']['en']['name'] ?? 'No City Name';
                    $cityCountryName = $this->formattedStationName($cityName, $countryName);

                    $results[$station['id']] = new StationDto(
                        $station['id'],
                        $cityName,
                        $cityCountryName,
                        $countryName,
                        $this->getCoordinates($cityCountryName)
                    );
                }

                return $results;
            }
        );
    }

    public function getById(string $id, ?string $lang = 'en'): array
    {
        return $this->cacheManager->retrieve(
            self::CACHE_KEY."__{$id}",
            function () use ($id) {
                return $this->httpClientHelper->fetchFromApi(
                    'rally/stations',
                    'rally.fetchRoutes',
                    $id
                );
            }
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getDestinationsById(string $id, ?string $lang = 'en'): array
    {
        $stations = $this->getAll($lang);
        $station = $this->getById($id, $lang);

        $destinations = $station['returns'];

        return array_filter(
            $stations,
            function ($key) use ($destinations) {
                return in_array($key, $destinations, true);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    private function enabledStationDTOs(array $stations): array
    {
        $results = [];

        foreach ($stations as $station) {
            if (!$this->isEnabled($station)) {
                continue;
            }

            $countryName = $station['city']['country_name'] ?? 'No City Name';
            $cityName = $station['city']['name'] ?? 'No Country Name';
            $cityCountryName = $this->formattedStationName($cityName, $countryName);

            $results[$station['id']] = new StationDto(
                $station['id'],
                $cityName,
                $cityCountryName,
                $countryName,
                $this->getCoordinates($cityCountryName)
            );
        }

        return $results;
    }

    private function getCoordinates(string $stationName): array
    {
        return $this->geocoder->execute($stationName);
    }

    private function isEnabled(array $station): bool
    {
        return $station['enabled'] && $station['public'] && $station['one_way'];
    }

    private function formattedStationName(string $cityName, string $countryName): string
    {
        return "{$cityName}, {$countryName}";
    }
}
