<?php

namespace Library\RoadSurfer\HttpClient;

use Library\RoadSurfer\DTO\StationDTO;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class ClientDecorator implements ClientInterface
{
    protected ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function getStations(): array
    {
        return $this->client->getStations();
    }

    public function getRallyStations(): array
    {
        return $this->client->getRallyStations();
    }

    public function getStationById(?string $resourceId = null): StationDTO
    {
        return $this->client->getStationById($resourceId);
    }

    public function getStationTimeFramesByStationIds(?string $resourceId = null): array
    {
        return $this->client->getStationTimeFramesByStationIds($resourceId);
    }

    public function fetch(string $resourcePath, string $operationType, ?string $resourceId = null, ?bool $ignoreLanguage = false): array|object
    {
        $uri = $this->client->buildUri($resourcePath, $resourceId, $ignoreLanguage);

        return $this->client->fetch($uri, $operationType, $resourceId, $ignoreLanguage);
    }

    public function buildUri(
        string $resourcePath,
        ?string $resourceId,
        ?bool $ignoreLanguage
    ): string {
        return $this->client->buildUri($resourcePath, $resourceId, $ignoreLanguage);
    }

    public function parseResponse(ResponseInterface $response): array|object
    {
        return $this->client->parseResponse($response);
    }
}
