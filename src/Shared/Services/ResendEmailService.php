<?php

declare(strict_types=1);

namespace App\Shared\Services;

use InvalidArgumentException;
use Resend;
use Resend\Client;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Exception;

class ResendEmailService implements EmailServiceInterface
{
    private Client $resend;
    private Environment $twig;

    public function __construct(string $apiKey)
    {
        $this->resend = Resend::client($apiKey);
        $loader = new FilesystemLoader(__DIR__.'/../../../templates');
        $this->twig = new Environment($loader);
    }

    public function send(
        string $template,
        string $from,
        string $to,
        string $subject,
        string $htmlContent
    ): void {
        $data = json_decode($htmlContent, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Email content payload must be a valid JSON object.');
        }

        $html = $this->twig->render($template, $data);

        $maxAttempts = 5;
        $delay = 500_000; // 500ms

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $this->resend->emails->send([
                    'from' => $from,
                    'to' => $to,
                    'subject' => $subject,
                    'html' => $html,
                ]);

                return;
            } catch (Exception $e) {
                if ($attempt === $maxAttempts) {
                    throw $e;
                }
                usleep($delay);
                $delay *= 2;
            }
        }
    }
}
