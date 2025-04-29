<?php

declare(strict_types=1);

namespace App\Shared\Services;

interface EmailServiceInterface
{
    public function send(string $from, string $to, string $subject, string $htmlContent): void;
} 