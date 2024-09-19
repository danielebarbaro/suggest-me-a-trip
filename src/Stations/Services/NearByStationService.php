<?php

namespace App\Stations\Services;

use App\Utils\HaversineService;

class NearByStationService
{
    public HaversineService $haversineService;

    public function __construct(HaversineService $haversineService)
    {
        $this->haversineService = $haversineService;
    }

    public function execute(array $cities, int $maxDistance = 200): array
    {
        $mergedCities = [];
        $visited = [];

        foreach ($cities as $firstCity => $firstPlaceCoordinates) {
            if (in_array($firstCity, $visited)) {
                continue;
            }

            $group = [$firstCity];

            foreach ($cities as $secondCity => $secondPlaceCoordinate) {
                if ($firstCity !== $secondCity && !in_array($secondCity, $visited)) {
                    $distance = $this->haversineService->execute($firstPlaceCoordinates, $secondPlaceCoordinate);
                    if ($distance <= $maxDistance) {
                        $group[] = $secondCity;
                        $visited[] = $secondCity;
                    }
                }
            }

            $mergedCities[] = $group;
            $visited[] = $firstCity;
        }

        return $mergedCities;
    }
}
