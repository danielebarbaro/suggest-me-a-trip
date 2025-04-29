<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Resend;

class ResendEmailService implements EmailServiceInterface
{
    private $resend;

    public function __construct(string $apiKey)
    {
        $this->resend = Resend::client($apiKey);
    }

    public function send(string $from, string $to, string $subject, string $htmlContent): void
    {
        $this->resend->emails->send([
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'html' => $htmlContent,
        ]);
    }
} 