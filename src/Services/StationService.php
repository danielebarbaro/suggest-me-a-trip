<?php

namespace App\Services;

use App\Helpers\HttpClientHelper;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StationService
{
    public const CACHE_KEY = 'station';
    private CacheInterface $cache;
    private HttpClientHelper $httpClientHelper;

    public function __construct(
        HttpClientHelper $httpClientHelper,
        CacheInterface $cache
    )
    {
        $this->cache = $cache;
        $this->httpClientHelper = $httpClientHelper;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function getRally(): array
    {
        $cachedValue = $this->cache->getItem(self::CACHE_KEY . '__rally');

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
                $countryName = $station['country_translations'][$lang]['name'] ?? '';
                $translationName = $station['translations'][$lang]['name'] ?? '';
                $results[$station['id']] = [
                    'name' => "{$countryName} > {$translationName}",
                    "country" => strtolower($station['country_translations']['en']['name']),
                ];
            }

            $cachedValue->set($results);
            $cachedValue->expiresAfter(3600);
            $this->cache->save($cachedValue);
        }

        return $cachedValue->get();
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function getById(string $id, ?string $lang = 'en'): array
    {
        $stations = $this->getAll($lang);

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
            $results[$id] = [
                'name' => $this->formattedStationName($station),
                "country" => strtolower($station['city']['country_name']),
            ];
        }

        return $results;
    }

    private function isEnabled(array $station): bool
    {
        return $station['enabled'] && $station['public'] && $station['one_way'];
    }

    public function formattedStationName(array $station): string
    {
        $countryName = $station['city']['country_name'] ?? '';
        $translationName = $station['name'] ?? '';

        return "{$countryName} > {$translationName}";
    }
}
