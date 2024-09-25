<?php

namespace Library\RoadSurfer\HttpClient;

use Symfony\Component\HttpClient\CachingHttpClient;

class CachingDecorator extends ClientDecorator
{
    private CachingHttpClient $cachingClient;
    private int $ttl;

    public function __construct(ClientInterface $client, CachingHttpClient $cachingClient, int $ttl = 3600)
    {
        parent::__construct($client);
        $this->cachingClient = $cachingClient;
        $this->ttl = $ttl;
    }

    public function fetch(string $resourcePath, string $operationType, ?string $resourceId = null, ?bool $ignoreLanguage = false): array|object
    {
        $uri = $this->client->buildUri($resourcePath, $resourceId, $ignoreLanguage);

        $response = $this->cachingClient->request('GET', $uri, [
            'headers' => ['X-Requested-Alias' => $operationType],
            'extra' => ['cache_ttl' => $this->ttl],
        ]);

        return $this->client->parseResponse($response);
    }
}
