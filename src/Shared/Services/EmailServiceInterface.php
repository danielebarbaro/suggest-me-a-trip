<?php

declare(strict_types=1);

namespace App\Shared\Services;

interface EmailServiceInterface
{
    /**
     * @param array<string, string> $headers Optional custom email headers (e.g. List-Unsubscribe).
     */
    public function send(
        string $template,
        string $from,
        string $to,
        string $subject,
        string $htmlContent,
        array $headers = []
    ): void;
}
