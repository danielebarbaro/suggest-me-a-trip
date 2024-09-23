<?php

namespace Library\RoadSurfer\HttpClient;

use Symfony\Component\HttpClient\RetryableHttpClient;

class RetryDecorator extends ClientDecorator
{
    private RetryableHttpClient $retryClient;

    public function __construct(ClientInterface $client, RetryableHttpClient $retryClient)
    {
        parent::__construct($client);
        $this->retryClient = $retryClient;
    }

    public function fetch(string $resourcePath, string $operationType, ?string $resourceId = null, ?bool $ignoreLanguage = false): array|object
    {
        return $this->retryClient->request('GET', $resourcePath, [
            'headers' => ['X-Requested-Alias' => $operationType],
        ]);
    }
}
