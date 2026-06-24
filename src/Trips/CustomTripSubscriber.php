<?php

declare(strict_types=1);

namespace App\Trips;

use Carbon\Carbon;
use Exception;

class CustomTripSubscriber
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $unsubscribeToken,
        public readonly string $country,
        public readonly string $direction,
        public readonly int $maxKm,
        public readonly Carbon $dateFrom,
        public readonly Carbon $dateTo,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): ?self
    {
        $email = $row['email'] ?? null;
        $country = $row['custom_country'] ?? null;
        $direction = $row['custom_direction'] ?? null;
        $maxKm = $row['custom_max_km'] ?? null;
        $from = $row['custom_date_from'] ?? null;
        $to = $row['custom_date_to'] ?? null;

        if (empty($email) || empty($country) || empty($from) || empty($to) || empty($maxKm) || !is_numeric($maxKm)) {
            return null;
        }

        if (!in_array($direction, ['departure', 'arrival'], true)) {
            return null;
        }

        try {
            $dateFrom = Carbon::parse($from)->startOfDay();
            $dateTo = Carbon::parse($to)->endOfDay();
        } catch (Exception) {
            return null;
        }

        return new self(
            (string) $email,
            isset($row['unsubscribe_token']) ? (string) $row['unsubscribe_token'] : null,
            strtolower((string) $country),
            (string) $direction,
            (int) $maxKm,
            $dateFrom,
            $dateTo,
        );
    }
}
