<?php

namespace Library\RoadSurfer\HttpClient;

use Library\RoadSurfer\DTO\StationDTO;

interface ClientInterface
{
    public function fetch(
        string $resourcePath,
        string $operationType,
        ?string $resourceId = null,
        ?bool $ignoreLanguage = false
    ): array|object;

    public function getStations(): array;

    public function getRallyStations(): array;

    public function getStationById(
        ?string $resourceId = null,
    ): StationDTO;

    public function getStationTimeFramesByStationIds(
        ?string $resourceId = null,
    ): array;
}
