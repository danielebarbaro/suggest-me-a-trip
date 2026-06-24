<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Libsql\Database;
use Exception;
use App\Trips\CustomTripSubscriber;
use InvalidArgumentException;

class TursoEmailService
{
    private ?Database $database = null;
    private string $tursoUrl;
    private string $tursoAuthToken;

    public function __construct(string $tursoUrl, string $tursoAuthToken)
    {
        $this->tursoUrl = $tursoUrl;
        $this->tursoAuthToken = $tursoAuthToken;

        if ($this->isConfigured()) {
            try {
                $this->database = @new Database(
                    url: $tursoUrl,
                    authToken: $tursoAuthToken
                );
            } catch (Exception $e) {
                error_log('TursoEmailService constructor error: '.$e->getMessage());
                $this->database = null;
            }
        }
    }

    /**
     * Returns active recipients as a list of associative arrays:
     * ['email' => string, 'unsubscribe_token' => ?string].
     *
     * @param string|null $requiredFlag Optional whitelisted column to filter on (must equal 1).
     *                                  Allowed values: 'sub_daily_trips', 'sub_daily_itineraries', 'sub_custom_trip'.
     *
     * @throws InvalidArgumentException when $requiredFlag is non-null and not in the whitelist
     *
     * @return array<int, array{email: string, unsubscribe_token: ?string}>
     */
    public function getActiveEmails(?string $requiredFlag = null): array
    {
        $allowedFlags = ['sub_daily_trips', 'sub_daily_itineraries', 'sub_custom_trip'];

        if ($requiredFlag !== null && !in_array($requiredFlag, $allowedFlags, true)) {
            throw new InvalidArgumentException(sprintf('Invalid flag "%s". Allowed flags: %s.', $requiredFlag, implode(', ', $allowedFlags)));
        }

        if (!$this->database) {
            return [];
        }

        $where = 'deleted_at IS NULL';
        if ($requiredFlag !== null) {
            $where .= " AND {$requiredFlag} = 1";
        }

        try {
            $conn = $this->database->connect();
            $result = $conn->query(
                "SELECT email, unsubscribe_token FROM emails WHERE {$where} ORDER BY created_at DESC"
            );

            $emails = [];
            $rows = $result->fetchArray();

            foreach ($rows as $row) {
                if (isset($row['email'])) {
                    $emails[] = [
                        'email' => $row['email'],
                        'unsubscribe_token' => $row['unsubscribe_token'] ?? null,
                    ];
                }
            }

            return $emails;
        } catch (Exception $e) {
            error_log('TursoEmailService::getActiveEmails() - '.$e->getMessage());

            return [];
        }
    }

    /**
     * Returns subscribers opted into the custom trip channel.
     *
     * @return CustomTripSubscriber[]
     */
    public function getCustomTripSubscribers(): array
    {
        if (!$this->database) {
            return [];
        }

        try {
            $conn = $this->database->connect();
            $result = $conn->query(
                'SELECT email, unsubscribe_token, custom_country, custom_direction, '
                .'custom_max_km, custom_date_from, custom_date_to '
                .'FROM emails '
                .'WHERE deleted_at IS NULL AND sub_custom_trip = 1 '
                .'ORDER BY created_at DESC'
            );

            $subscribers = [];
            foreach ($result->fetchArray() as $row) {
                $subscriber = CustomTripSubscriber::fromRow($row);
                if ($subscriber !== null) {
                    $subscribers[] = $subscriber;
                }
            }

            return $subscribers;
        } catch (Exception $e) {
            error_log('TursoEmailService::getCustomTripSubscribers() - '.$e->getMessage());

            return [];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->tursoUrl) && !empty($this->tursoAuthToken);
    }
}
