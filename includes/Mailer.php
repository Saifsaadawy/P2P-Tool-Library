<?php

/**
 * Simple email wrapper using PHP's built-in mail().
 * For production: swap send() body to use PHPMailer/SMTP.
 */
class Mailer
{
    private string $fromName;
    private string $fromEmail;

    public function __construct(string $fromEmail = '', string $fromName = 'Tool Library')
    {
        // Falls back to .env values if not passed directly
        $this->fromEmail = $fromEmail ?: ($_ENV['MAIL_FROM'] ?? 'no-reply@toollibrary.com');
        $this->fromName  = $fromName;
    }

    /**
     * Send a plain-text email.
     *
     * @param string $to      Recipient email
     * @param string $subject Email subject
     * @param string $body    Plain text body
     * @return bool
     */
    public function send(string $to, string $subject, string $body): bool
    {
        if (empty($to)) return false;

        $headers  = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . PHP_VERSION;

        $sent = mail($to, $subject, $body, $headers);

        // Log result (helpful during development)
        $status = $sent ? 'SENT' : 'FAILED';
        $logLine = "[" . date('Y-m-d H:i:s') . "] [$status] To: $to | Subject: $subject\n";
        file_put_contents(__DIR__ . '/../logs/mail.log', $logLine, FILE_APPEND | LOCK_EX);

        return $sent;
    }
}
