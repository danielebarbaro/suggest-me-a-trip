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
        $uri = $this->client->buildUri($resourcePath, $resourceId, $ignoreLanguage);

        $response = $this->retryClient->request('GET', $uri, [
            'headers' => ['X-Requested-Alias' => $operationType],
        ]);

        return $this->client->parseResponse($response);
    }
}
