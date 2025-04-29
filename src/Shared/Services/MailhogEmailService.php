<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

class MailhogEmailService implements EmailServiceInterface
{
    private Mailer $mailer;

    public function __construct()
    {
        $transport = Transport::fromDsn('smtp://localhost:1025');
        $this->mailer = new Mailer($transport);
    }

    public function send(string $from, string $to, string $subject, string $htmlContent): void
    {
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        $this->mailer->send($email);
    }
}
