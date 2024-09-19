<?php

namespace App\Utils;

class HaversineService
{
    /**
     * Calculate the great-circle distance between two points
     * on the Earth's surface given their latitude and longitude coordinates.
     *
     * This function uses the Haversine formula to calculate the shortest distance
     * over the Earth's surface (assuming a spherical Earth), known as the great-circle distance.
     *
     * The Haversine formula is more accurate for short distances compared to
     * the spherical law of cosines, especially when the two points are close together.
     *
     * @param array $firstPoint  An array containing the latitude and longitude of the first point.
     *                           Example: [latitude1, longitude1] in degrees.
     * @param array $secondPoint An array containing the latitude and longitude of the second point.
     *                           Example: [latitude2, longitude2] in degrees.
     *
     * @return float The distance between the two points in kilometers.
     *
     * Formula Explanation:
     * 1. Convert latitude and longitude from degrees to radians for both points.
     * 2. Compute the differences in latitudes and longitudes in radians.
     * 3. Apply the Haversine formula to calculate the central angle 'c' between the two points:
     *      a = sin²(Δlat / 2) + cos(latFrom) * cos(latTo) * sin²(Δlon / 2)
     *      c = 2 * atan2(√a, √(1 - a))
     * 4. Multiply 'c' by the Earth's radius (6371 km) to get the distance in kilometers.
     *
     * Note: This method assumes the Earth is a perfect sphere, which may introduce slight inaccuracies
     * for long distances, but it is accurate enough for most applications.
     */
    public function execute(array $firstPoint, array $secondPoint): float
    {
        $earthRadius = 6371;

        $latFrom = deg2rad($firstPoint[0]);
        $lonFrom = deg2rad($firstPoint[1]);

        $latTo = deg2rad($secondPoint[0]);
        $lonTo = deg2rad($secondPoint[1]);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
