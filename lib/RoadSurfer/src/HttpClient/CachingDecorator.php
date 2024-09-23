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
        return $this->cachingClient->request('GET', $resourcePath, [
            'headers' => ['X-Requested-Alias' => $operationType],
            'extra' => ['cache_ttl' => $this->ttl],
        ]);
    }
}
