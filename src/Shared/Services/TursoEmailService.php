<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Libsql\Database;
use Exception;

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
                error_log("TursoEmailService constructor error: " . $e->getMessage());
                $this->database = null;
            }
        }
    }

    public function getActiveEmails(): array
    {
        if (!$this->database) {
            return [];
        }

        try {
            $conn = $this->database->connect();
            $result = $conn->query(
                'SELECT email FROM emails WHERE deleted_at IS NULL ORDER BY created_at DESC'
            );

            $emails = [];
            $rows = $result->fetchArray();
            
            foreach ($rows as $row) {
                if (isset($row['email'])) {
                    $emails[] = $row['email'];
                }
            }

            return $emails;

        } catch (Exception $e) {
            error_log("TursoEmailService::getActiveEmails() - " . $e->getMessage());
            return [];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->tursoUrl) && !empty($this->tursoAuthToken);
    }
} 