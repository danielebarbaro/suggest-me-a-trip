<?php

use Library\RoadSurfer\HttpClient\CachingDecorator;
use Library\RoadSurfer\HttpClient\Client;
use Library\RoadSurfer\HttpClient\RetryDecorator;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;

global $rallyStations;
global $stations;
global $station;
global $timeFrames;

require_once __DIR__.'/../DummyEntitiesConfig.php';

beforeEach(function () {
    $this->cacheDir = __DIR__.'/../.cache';
    $this->baseUrl = 'http://127.0.0.1:9501';
    $this->lang = 'it';
    $this->defaultClientOptions = [
        'default_ttl' => 10,
        'debug' => true,
        'verify_host' => false,
        'verify_peer' => false,
    ];

    $this->symfonyClient = SymfonyHttpClient::create([
        'verify_host' => false,
        'verify_peer' => false,
    ]);
});

it('fetches data: Stations List', function () use ($stations) {
    $retryableHttpClient = new RetryableHttpClient(
        $this->symfonyClient,
        new GenericRetryStrategy([429], mt_rand(4, 100)),
        5
    );

    $domainClient = new Client($retryableHttpClient, $this->baseUrl, $this->lang);

    $cachingHttpClient = new CachingHttpClient(
        $retryableHttpClient,
        new Store($this->cacheDir),
        $this->defaultClientOptions
    );

    $retryDecorator = new RetryDecorator($domainClient, $retryableHttpClient);
    $cachingDecorator = new CachingDecorator($retryDecorator, $cachingHttpClient);

    $result = $cachingDecorator->fetch(
        'translations/stations',
        'station.fetchTranslations',
        null,
        true
    );

    expect($result)
        ->toBeArray()
        ->toHaveCount(1)
        ->toBe($stations);
});

it('fetches data: Stations List - NO CACHE with RETRY', function () use ($stations) {
    $retryableHttpClient = new RetryableHttpClient(
        $this->symfonyClient,
        new GenericRetryStrategy([429], mt_rand(4, 100)),
        5
    );

    $domainClient = new Client($retryableHttpClient, $this->baseUrl, $this->lang);

    $result = $domainClient->fetch(
        'translations/stations',
        'station.fetchTranslations',
        null,
        true
    );

    expect($result)
        ->toBeArray()
        ->toHaveCount(1)
        ->toBe($stations);
});

it('fetches data: Stations List - NO CACHE', function () use ($stations) {
    $domainClient = new Client($this->symfonyClient, $this->baseUrl, $this->lang);

    $result = $domainClient->fetch(
        'translations/stations',
        'station.fetchTranslations',
        null,
        true
    );

    expect($result)
        ->toBeArray()
        ->toHaveCount(1)
        ->toBe($stations);
});

it('fetches data: Rally Stations List', function () use ($rallyStations) {
    $retryableHttpClient = new RetryableHttpClient(
        $this->symfonyClient,
        new GenericRetryStrategy([429], mt_rand(4, 100)),
        5
    );

    $domainClient = new Client($retryableHttpClient, $this->baseUrl, $this->lang);

    $cachingHttpClient = new CachingHttpClient(
        $retryableHttpClient,
        new Store($this->cacheDir),
        $this->defaultClientOptions
    );

    $retryDecorator = new RetryDecorator($domainClient, $retryableHttpClient);
    $cachingDecorator = new CachingDecorator($retryDecorator, $cachingHttpClient);

    $result = $cachingDecorator->fetch(
        'rally/stations',
        'rally.startStations',
        null,
        false
    );

    expect($result)
        ->toBeArray()
        ->toHaveCount(1)
        ->toBe($rallyStations);
});

it('fetches data: Station', function () {
    $retryableHttpClient = new RetryableHttpClient(
        $this->symfonyClient,
        new GenericRetryStrategy([429], mt_rand(4, 100)),
        5
    );

    $domainClient = new Client($retryableHttpClient, $this->baseUrl, $this->lang);

    $cachingHttpClient = new CachingHttpClient(
        $retryableHttpClient,
        new Store($this->cacheDir),
        $this->defaultClientOptions
    );

    $retryDecorator = new RetryDecorator($domainClient, $retryableHttpClient);
    $cachingDecorator = new CachingDecorator($retryDecorator, $cachingHttpClient);

    $result = $cachingDecorator->fetch(
        'rally/stations',
        'rally.fetchRoutes',
        1,
        false
    );

    expect($result)
        ->toBeObject()
        ->toHaveProperty('id')
        ->toHaveProperty('enabled')
        ->toHaveProperty('public')
        ->toHaveProperty('one_way')
        ->toHaveProperty('returns')
        ->toHaveProperty('name')
        ->toHaveProperty('city')
    ;
});

it('fetches data: Station timeframes', function () use ($timeFrames) {
    $retryableHttpClient = new RetryableHttpClient(
        $this->symfonyClient,
        new GenericRetryStrategy([429], mt_rand(4, 100)),
        5
    );

    $domainClient = new Client($retryableHttpClient, $this->baseUrl, $this->lang);

    $cachingHttpClient = new CachingHttpClient(
        $retryableHttpClient,
        new Store($this->cacheDir),
        $this->defaultClientOptions
    );

    $retryDecorator = new RetryDecorator($domainClient, $retryableHttpClient);
    $cachingDecorator = new CachingDecorator($retryDecorator, $cachingHttpClient);

    $result = $cachingDecorator->fetch(
        'rally/timeframes',
        'rally.timeframes',
        '1-2',
        false
    );

    expect($result)
        ->toBeArray()
        ->toHaveCount(1)
        ->toBe($timeFrames);
});
