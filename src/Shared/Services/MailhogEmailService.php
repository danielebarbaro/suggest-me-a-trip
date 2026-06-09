<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class MailhogEmailService implements EmailServiceInterface
{
    private Mailer $mailer;
    private Environment $twig;

    public function __construct()
    {
        $transport = Transport::fromDsn('smtp://localhost:1025');
        $this->mailer = new Mailer($transport);

        $loader = new FilesystemLoader(__DIR__.'/../../../templates');
        $this->twig = new Environment($loader);
    }

    public function send(
        string $template,
        string $from,
        string $to,
        string $subject,
        string $htmlContent,
        array $headers = []
    ): void {
        $html = $this->twig->render(
            $template,
            json_decode($htmlContent, true)
        );

        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($html);

        foreach ($headers as $name => $value) {
            $email->getHeaders()->addTextHeader($name, $value);
        }

        $this->mailer->send($email);
    }
}
