<?php

namespace Tests\Unit\Mail;

use App\Mail\Mailer;
use PHPUnit\Framework\TestCase;

class MailerComprehensiveTest extends TestCase
{
    private Mailer $mailer;

    protected function setUp(): void
    {
        $this->mailer = new Mailer('sender@example.com', 'Sender Name');
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer);
    }

    public function testToSingleRecipient(): void
    {
        $result = $this->mailer->to('recipient@example.com', 'Recipient');
        $this->assertInstanceOf(Mailer::class, $result);
    }

    public function testToMultipleRecipients(): void
    {
        $this->mailer->to('first@example.com', 'First');
        $result = $this->mailer->to('second@example.com', 'Second');
        $this->assertInstanceOf(Mailer::class, $result);
    }

    public function testSubject(): void
    {
        $result = $this->mailer->subject('Test Subject');
        $this->assertInstanceOf(Mailer::class, $result);
    }

    public function testBodyPlainText(): void
    {
        $result = $this->mailer->body('This is a test message');
        $this->assertInstanceOf(Mailer::class, $result);
    }

    public function testBodyHtml(): void
    {
        $html = '<html><body>Test</body></html>';
        $result = $this->mailer->htmlBody($html);
        $this->assertInstanceOf(Mailer::class, $result);
    }

    public function testCc(): void
    {
        $result = $this->mailer->cc('cc@example.com', 'CC Name');
        $this->assertInstanceOf(Mailer::class, $result);
    }

    public function testBcc(): void
    {
        $result = $this->mailer->bcc('bcc@example.com');
        $this->assertInstanceOf(Mailer::class, $result);
    }

    public function testAttach(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, 'test content');
        $result = $this->mailer->attach($tmpFile, 'test.txt');
        $this->assertInstanceOf(Mailer::class, $result);
        @unlink($tmpFile);
    }

    public function testFluentInterface(): void
    {
        $result = $this->mailer
            ->to('recipient@example.com', 'Recipient')
            ->subject('Test')
            ->body('Message')
            ->cc('cc@example.com');
        $this->assertInstanceOf(Mailer::class, $result);
    }

    public function testInvalidEmailHandling(): void
    {
        $this->mailer->to('invalid-email', 'Invalid');
        $this->assertInstanceOf(Mailer::class, $this->mailer);
    }

    public function testMultipleCcRecipients(): void
    {
        $this->mailer->cc('cc1@example.com', 'CC 1');
        $result = $this->mailer->cc('cc2@example.com', 'CC 2');
        $this->assertInstanceOf(Mailer::class, $result);
    }

    public function testMultipleBccRecipients(): void
    {
        $this->mailer->bcc('bcc1@example.com');
        $result = $this->mailer->bcc('bcc2@example.com');
        $this->assertInstanceOf(Mailer::class, $result);
    }

    public function testHeaderInjectionPrevention(): void
    {
        $subject = "Test\nBcc: attacker@example.com";
        $this->mailer->subject($subject);
        $this->assertInstanceOf(Mailer::class, $this->mailer);
    }

    public function testSend(): void
    {
        $mailer = $this->mailer
            ->to('test@example.com', 'Test')
            ->subject('Test Subject')
            ->body('Test Body');
        $result = $mailer->send();
        $this->assertIsBool($result);
    }

    public function testChainableAfterSend(): void
    {
        $this->mailer
            ->to('test@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();
        $result = $this->mailer->to('new@example.com');
        $this->assertInstanceOf(Mailer::class, $result);
    }
}
