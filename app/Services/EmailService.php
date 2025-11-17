<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Jeffrey\Sikapay\Core\Log;

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
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $this->mailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = (int)($_ENV['MAIL_PORT'] ?? 587);

            // Sender
            $this->mailer->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@sikapay.com', $_ENV['MAIL_FROM_NAME'] ?? 'SikaPay');

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
