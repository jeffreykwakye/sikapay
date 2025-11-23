<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Config\AppConfig;

class EmailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        try {
            // Load mail config from AppConfig
            $mailConfig = AppConfig::get('mail');

            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $mailConfig['host'] ?? 'smtp.mailtrap.io';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $mailConfig['username'] ?? '';
            $this->mailer->Password = $mailConfig['password'] ?? '';
            $this->mailer->SMTPSecure = $mailConfig['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = (int)($mailConfig['port'] ?? 587);

            // Sender
            $this->mailer->setFrom($mailConfig['from_address'] ?? 'no-reply@sikapay.com', $mailConfig['from_name'] ?? 'SikaPay');

        } catch (Exception $e) {
            Log::critical('PHPMailer configuration failed: ' . $e->getMessage());
        }
    }

    public function send(string $to, string $subject, string $body, ?string $altBody = ''): bool
    {
        try {
            // Recipients
            $this->mailer->addAddress($to);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = $altBody ?: strip_tags($body);

            $this->mailer->send();
            Log::info("Email sent successfully to {$to} with subject '{$subject}'.");
            return true;
        } catch (Exception $e) {
            Log::error("Email could not be sent to {$to}. Mailer Error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
}
