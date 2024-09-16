<?php

namespace App\Services;

use App\Dto\StationDto;
use App\Helpers\HttpClientHelper;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Exception;

class StationService
{
    private const string CACHE_KEY = 'station';
    private CacheInterface $cache;
    private HttpClientHelper $httpClientHelper;
    private GeoCoderService $geocoder;

    public function __construct(
        GeoCoderService $geocoder,
        HttpClientHelper $httpClientHelper,
        CacheInterface $cache
    ) {
        $this->geocoder = $geocoder;
        $this->cache = $cache;
        $this->httpClientHelper = $httpClientHelper;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getRally(): array
    {
        $cachedValue = $this->cache->getItem(self::CACHE_KEY.'__rally');

        if (!$cachedValue->isHit()) {
            $stations = $this->httpClientHelper->fetchFromApi(
                'rally/stations',
                'rally.startStations',
            );
            $cachedValue->set($this->enabledStationDTOs($stations));
            $cachedValue->expiresAfter(3600);
            $this->cache->save($cachedValue);
        }

        return $cachedValue->get();
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getAll(?string $lang = 'en'): array
    {
        $cachedValue = $this->cache->getItem(self::CACHE_KEY);
        if (!$cachedValue->isHit()) {
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

            $cachedValue->set($results);
            $cachedValue->expiresAfter(3600);
            $this->cache->save($cachedValue);
        }

        return $cachedValue->get();
    }

    public function getById(string $id, ?string $lang = 'en'): array
    {
        $cachedValue = $this->cache->getItem(self::CACHE_KEY."__{$id}");

        if (!$cachedValue->isHit()) {
            $cachedValue->set(
                $this->httpClientHelper->fetchFromApi(
                    'rally/stations',
                    'rally.fetchRoutes',
                    $id
                )
            );
            $cachedValue->expiresAfter(3600);
            $this->cache->save($cachedValue);
        }

        return $cachedValue->get();
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
