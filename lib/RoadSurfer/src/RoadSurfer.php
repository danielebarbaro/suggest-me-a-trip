<?php

namespace Library\RoadSurfer;

use Library\RoadSurfer\Cache\CacheInterface;
use Library\RoadSurfer\DTO\StationDTO;
use Library\RoadSurfer\HttpClient\Client;
use Library\RoadSurfer\HttpClient\ClientInterface;

class RoadSurfer
{
    private const string CACHE_PREFIX = __CLASS__;

    private ClientInterface $client;
    private CacheInterface $cache;

    public function __construct(
        ClientInterface $client,
        CacheInterface $cache
    ) {
        $this->client = $client;
        $this->cache = $cache;
    }

    public function getStations(): array
    {
        return $this->cache->retrieve(
            $this->uniqueCacheKey(self::CACHE_PREFIX.'__stations'),
            function () {
                return $this->client->getStations();
            }
        );
    }

    public function getStationById(string $id): StationDTO
    {
        return $this->cache->retrieve(
            $this->uniqueCacheKey(self::CACHE_PREFIX.'__station__'.$id),
            function () use ($id) {
                return $this->client->getStationById($id);
            }
        );
    }

    public function getRallyStations(bool $enabled = true): array
    {
        return $this->cache->retrieve(
            $this->uniqueCacheKey(self::CACHE_PREFIX.'__rally__'.$enabled ? 'enabled' : 'full'),
            function () use ($enabled) {
                $stations = $this->client->getRallyStations();

                return array_filter(
                    $stations,
                    function (StationDTO $station) use ($enabled) {
                        return !$enabled || $this->isEnabled($station);
                    }
                );
            }
        );
    }

    public function getReturnStationsByStationId(string $id): array
    {
        $station = $this->getStationById($id);

        $destinations = $station?->returns;

        if (empty($destinations)) {
            return [];
        }

        return array_filter(
            $this->getStations(),
            function ($key) use ($destinations) {
                return in_array($key, $destinations, true);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    public function getStationTimeFramesByStationIds(string $resourceId): array
    {
        return $this->cache->retrieve(
            $this->uniqueCacheKey(self::CACHE_PREFIX.'__timeframes__'.$resourceId),
            function () use ($resourceId) {
                $results = [];
                $timeStamps = $this->client->getStationTimeFramesByStationIds($resourceId);
                foreach ($timeStamps as $timeStamp) {
                    $results[] = [
                        'startDate' => $timeStamp->startDate,
                        'endDate' => $timeStamp->endDate,
                    ];
                }

                if (empty($results)) {
                    return [];
                }

                return $results[0] ?? [];
            }
        );
    }

    private function isEnabled(StationDTO $station): bool
    {
        return $station->enabled && $station->isPublic && $station->oneWay;
    }

    private function uniqueCacheKey($string): string
    {
        return strtolower(
            str_replace(
                ['{', '}', '(', ')', '/', '\\', '@', ':', '-'],
                '_',
                $string
            )
        );
    }
}
