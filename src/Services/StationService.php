<?php

namespace App\Services;

use App\Helpers\HttpClientHelper;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StationService
{
    public const CACHE_KEY = 'station';
    private ArrayAdapter $cache;
    private HttpClientHelper $httpClientHelper;

    public function __construct(HttpClientHelper $httpClientHelper)
    {
        $this->cache = new ArrayAdapter();
        $this->httpClientHelper = $httpClientHelper;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function getAll(): array
    {
        $cachedValue = $this->cache->getItem(self::CACHE_KEY);

        if (!$cachedValue->isHit()) {
            $stations = $this->httpClientHelper->fetchFromApi(
                'rally/stations',
                'rally.startStations',
            );
            $cachedValue->set($this->processData($stations));
            $cachedValue->expiresAfter(3600);
            $this->cache->save($cachedValue);
            echo "Fetching from API\n";
        }

        return $cachedValue->get();
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function getById(string $id): array
    {
        $stations = $this->getAll();

        $cachedValue = $this->cache->getItem(self::CACHE_KEY."__{$id}");

        if (!$cachedValue->isHit()) {
            $cachedValue->set($this->httpClientHelper->fetchFromApi(
                'rally/stations',
                'rally.fetchRoutes',
                $id
            )
            );
            $cachedValue->expiresAfter(3600);
            $this->cache->save($cachedValue);
        }

        $station = $cachedValue->get();

        $destinations = $station['returns'];

        return array_filter(
            $stations,
            function ($key) use ($destinations) {
                return in_array($key, $destinations, true);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    private function processData(array $stations): array
    {
        $results = [];

        foreach ($stations as $station) {
            if (!$this->isEnabled($station)) {
                continue;
            }

            $id = $station['id'];
            $results[$id] = $this->formattedStationName($station);
        }

        return $results;
    }

    private function isEnabled(array $station): bool
    {
        return $station['enabled'] && $station['public'] && $station['one_way'];
    }

    private function cachedDate(
        array $stations,
        string $key = 'all'
    ): array {
        $cachedValue = $this->cache->getItem(self::CACHE_KEY."__{$key}");

        if (!$cachedValue->isHit()) {
            $cachedValue->set($stations);
            $cachedValue->expiresAfter(3600);
            $this->cache->save($cachedValue);
        }

        return $cachedValue->get();
    }

    /**
     * @param mixed $station
     * @return array|string[]
     */
    public function formattedStationName(mixed $station): string
    {
        $countryName = $station['city']['country_translated'] ?? '';
        $translationName = $station['name'] ?? '';

        return "{$countryName} > {$translationName}";
    }
}
