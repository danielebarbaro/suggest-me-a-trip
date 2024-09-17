<?php

namespace App\Services;

use App\Core\CacheManager;
use App\Dto\StationDto;
use App\Helpers\HttpClientHelper;
use Psr\Cache\InvalidArgumentException;

class TimeFrameService
{
    private const string CACHE_KEY = 'timeframes';
    private CacheManager $cacheManager;
    private HttpClientHelper $httpClientHelper;

    public function __construct(
        HttpClientHelper $httpClientHelper,
        CacheManager $cacheManager
    ) {
        $this->httpClientHelper = $httpClientHelper;
        $this->cacheManager = $cacheManager;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function execute(StationDto $pickupStation, StationDto $dropoffStation): array
    {
        $id = "{$pickupStation->id}-{$dropoffStation->id}";

        return $this->cacheManager->retrieve(
            self::CACHE_KEY.str_replace('-', '__', $id),
            function () use ($id) {
                return $this->httpClientHelper->fetchFromApi(
                    'rally/timeframes',
                    'rally.timeframes',
                    $id
                );
            }
        );
    }
}
