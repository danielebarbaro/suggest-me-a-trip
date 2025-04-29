<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Resend;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ResendEmailService implements EmailServiceInterface
{
    private $resend;
    private Environment $twig;

    public function __construct(string $apiKey)
    {
        $this->resend = Resend::client($apiKey);
        $loader = new FilesystemLoader(__DIR__.'/../../../templates');
        $this->twig = new Environment($loader);
    }

    public function send(string $from, string $to, string $subject, string $htmlContent): void
    {
        $html = $this->twig->render(
            'emails/daily_trips.html.twig',
            json_decode($htmlContent, true)
        );

        $this->resend->emails->send([
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
        ]);
    }
}
