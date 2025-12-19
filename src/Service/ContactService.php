<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;
use Symfony\Component\Mime\Email;

class ContactService
{
    public function __construct(private MailerInterface $mailer, private Environment $twig) {}

    public function sendContactEmail(array $data): bool
    {
        // Validasyon
        if (empty($data['firstName']) || empty($data['lastName']) || empty($data['email']) || empty($data['subject']) || empty($data['message'])) {
            throw new \InvalidArgumentException('Lütfen tüm zorunlu alanları doldurun.');
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Geçerli bir e-posta adresi girin.');
        }
        if (!$data['privacy']) {
            throw new \InvalidArgumentException('Gizlilik politikasını kabul etmeniz gerekir.');
        }

        try {
            // E-posta gönderme
            $emailMessage = (new Email())
                ->from($data['email'])
                ->to('ali.kose@guzelteknoloji.com')
                ->subject('Lumina İletişim Formu: ' . $data['subject'])
                ->html($this->twig->render('emails/contact.html.twig', $data));

            $this->mailer->send($emailMessage);
            return true;
        } catch (\Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            throw new \RuntimeException('E-posta gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
        }
    }
}
