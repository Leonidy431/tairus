<?php
/**
 * Email Mailer Service
 *
 * Provides secure email sending with proper headers and validation.
 */

namespace App\Mail;

class Mailer
{
    private string $fromAddress;
    private string $fromName;
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];
    private string $subject = '';
    private string $body = '';
    private string $htmlBody = '';
    private array $attachments = [];
    private array $headers = [];

    public function __construct(string $fromAddress, string $fromName)
    {
        $this->fromAddress = filter_var($fromAddress, FILTER_VALIDATE_EMAIL) ?: 'noreply@localhost';
        $this->fromName = $this->sanitizeName($fromName);
    }

    public function to(string $email, string $name = ''): self
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->to[] = [
                'email' => $email,
                'name' => $this->sanitizeName($name),
            ];
        }

        return $this;
    }

    public function cc(string $email, string $name = ''): self
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->cc[] = [
                'email' => $email,
                'name' => $this->sanitizeName($name),
            ];
        }

        return $this;
    }

    public function bcc(string $email): self
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->bcc[] = ['email' => $email];
        }

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $this->sanitizeSubject($subject);
        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function htmlBody(string $html): self
    {
        $this->htmlBody = $html;
        return $this;
    }

    public function attach(string $filePath, string $name = ''): self
    {
        if (file_exists($filePath) && is_readable($filePath)) {
            $this->attachments[] = [
                'path' => $filePath,
                'name' => $name ?: basename($filePath),
            ];
        }

        return $this;
    }

    public function send(): bool
    {
        if (empty($this->to) || empty($this->subject)) {
            return false;
        }

        $toAddresses = $this->formatAddresses($this->to);
        $headers = $this->buildHeaders();

        $body = $this->buildMessageBody();

        return mail($toAddresses, $this->subject, $body, $headers);
    }

    private function buildHeaders(): string
    {
        $headers = [];

        // From header
        if (!empty($this->fromName)) {
            $headers[] = sprintf('From: %s <%s>', $this->fromName, $this->fromAddress);
        } else {
            $headers[] = sprintf('From: %s', $this->fromAddress);
        }

        // CC header
        if (!empty($this->cc)) {
            $headers[] = 'Cc: ' . $this->formatAddresses($this->cc);
        }

        // BCC header
        if (!empty($this->bcc)) {
            $headers[] = 'Bcc: ' . $this->formatAddresses($this->bcc);
        }

        // Content headers
        $headers[] = 'MIME-Version: 1.0';

        if (!empty($this->htmlBody)) {
            $boundary = 'boundary_' . md5(microtime());
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
            $headers[] = 'Content-Transfer-Encoding: 8bit';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: 8bit';
        }

        // Security headers
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headers[] = 'X-Priority: 3';

        return implode("\r\n", $headers);
    }

    private function buildMessageBody(): string
    {
        if (empty($this->htmlBody)) {
            return $this->body;
        }

        $boundary = 'boundary_' . md5(microtime());
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $this->body . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $this->htmlBody . "\r\n";
        $body .= "--{$boundary}--";

        return $body;
    }

    private function formatAddresses(array $addresses): string
    {
        return implode(', ', array_map(function ($addr) {
            if (isset($addr['name']) && !empty($addr['name'])) {
                return sprintf('"%s" <%s>', $addr['name'], $addr['email']);
            }

            return $addr['email'];
        }, $addresses));
    }

    private function sanitizeName(string $name): string
    {
        return trim(preg_replace('/[^a-zA-Z0-9\s\-\.,]/', '', $name));
    }

    private function sanitizeSubject(string $subject): string
    {
        // Remove newlines to prevent header injection
        $subject = str_replace(["\r", "\n"], '', $subject);
        // Limit length
        return substr($subject, 0, 255);
    }
}
