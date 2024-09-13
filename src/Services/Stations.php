<?php

namespace App\Services;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Stations
{
    public const CACHE_KEY = 'stations';
    private HttpClientInterface $client;
    private ArrayAdapter $cache;
    private string $baseUrl;
    private string $lang;

    public function __construct(HttpClientInterface $client, string $baseUrl, string $lang)
    {
        $this->client = $client;
        $this->cache = new ArrayAdapter();
        $this->baseUrl = $baseUrl;
        $this->lang = $lang;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function execute(): array
    {
        $cachedValue = $this->cache->getItem(self::CACHE_KEY);

        if (!$cachedValue->isHit()) {
            $results = $this->fetchFromApi();

            $cachedValue->set($results);
            $cachedValue->expiresAfter(3600);
            $this->cache->save($cachedValue);
        }

        return $cachedValue->get();
    }

    private function fetchFromApi(): array
    {
        $uri = "{$this->baseUrl}/{$this->lang}/rally/stations";

        try {
            $response = $this->client->request('GET', $uri, [
                'headers' => [
                    'X-Requested-Alias' => 'rally.startStations',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('API Fail, status code: ' . $response->getStatusCode());
            }

            $stations = $response->toArray();

            return $this->processData($stations);
        } catch (TransportExceptionInterface | ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface $e) {
            throw new \Exception('API error: ' . $e->getMessage());
        }
    }

    private function processData(array $stations): array
    {
        $results = [];

        foreach ($stations as $station) {
            if (! $this->isEnabled($station)) {
                continue;
            }

            $id = $station['id'];
            $countryName = $station['city']['country_translated'] ?? '';
            $translationName = $station['name'] ?? '';
            $results[$id] = "[{$id}] - {$countryName} > {$translationName}";
        }

        return $results;
    }

    private function isEnabled(array $station): bool
    {
        return $station['enabled'] && $station['public'] && $station['one_way'];
    }
}
