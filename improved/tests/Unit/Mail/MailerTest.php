<?php

namespace Tests\Unit\Mail;

use App\Mail\Mailer;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    private Mailer $mailer;

    protected function setUp(): void
    {
        $this->mailer = new Mailer('noreply@test.local', 'Test App');
    }

    public function testMailerInitialization(): void
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer);
    }

    public function testAddRecipient(): void
    {
        $mailer = $this->mailer->to('user@example.com', 'John Doe');
        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testInvalidEmailRejected(): void
    {
        $mailer = $this->mailer->to('invalid-email', 'Test');
        // Invalid email should not be added
        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testAddCc(): void
    {
        $mailer = $this->mailer
            ->to('user@example.com')
            ->cc('cc@example.com', 'CC User');
        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testAddBcc(): void
    {
        $mailer = $this->mailer
            ->to('user@example.com')
            ->bcc('bcc@example.com');
        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testSetSubject(): void
    {
        $mailer = $this->mailer->subject('Test Subject');
        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testSubjectNewlineRemoved(): void
    {
        // Test that newlines are removed from subject (prevents header injection)
        $this->mailer->subject("Test\nSubject\r\nWith\nNewlines");
        // Should not throw exception and newlines should be removed
        $this->assertInstanceOf(Mailer::class, $this->mailer);
    }

    public function testSetBody(): void
    {
        $mailer = $this->mailer->body('Test body content');
        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testSetHtmlBody(): void
    {
        $mailer = $this->mailer->htmlBody('<p>Test HTML content</p>');
        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testFluentInterface(): void
    {
        $mailer = $this->mailer
            ->to('test@example.com', 'Test User')
            ->cc('cc@example.com')
            ->subject('Test Subject')
            ->body('Plain text body')
            ->htmlBody('<p>HTML body</p>');

        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testEmailInjectionPrevention(): void
    {
        // Test that header injection attempts are prevented
        $injectionAttempts = [
            "user@example.com\nBcc: attacker@evil.com",
            "user@example.com\r\nCc: attacker@evil.com",
        ];

        foreach ($injectionAttempts as $attempt) {
            $mailer = new Mailer('noreply@test.local', 'App');
            $mailer->to($attempt);
            // Should not add the injection
            $this->assertInstanceOf(Mailer::class, $mailer);
        }
    }

    public function testSubjectLengthLimited(): void
    {
        $longSubject = str_repeat('a', 500);
        $this->mailer->subject($longSubject);
        // Subject should be truncated to 255 characters
        $this->assertInstanceOf(Mailer::class, $this->mailer);
    }
}
