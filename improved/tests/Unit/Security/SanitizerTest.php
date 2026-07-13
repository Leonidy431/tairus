<?php

namespace Tests\Unit\Security;

use App\Security\Sanitizer;
use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
    public function testSanitizeString(): void
    {
        $this->assertEquals('hello world', Sanitizer::string('  hello world  '));
        $this->assertEquals('hello', Sanitizer::string('<script>hello</script>', true));
        $this->assertEquals('hello', Sanitizer::string('hello'));
    }

    public function testSanitizeEmail(): void
    {
        $this->assertEquals('test@example.com', Sanitizer::email('test@example.com'));
        $this->assertEquals('test@example.com', Sanitizer::email('  TEST@EXAMPLE.COM  '));
        $this->assertNull(Sanitizer::email('invalid-email'));
        $this->assertNull(Sanitizer::email(''));
        $this->assertNull(Sanitizer::email('<script>test@example.com</script>'));
    }

    public function testSanitizeInteger(): void
    {
        $this->assertEquals(123, Sanitizer::integer('123'));
        $this->assertEquals(0, Sanitizer::integer('0'));
        $this->assertEquals(-456, Sanitizer::integer('-456'));
        $this->assertNull(Sanitizer::integer('abc'));
        $this->assertNull(Sanitizer::integer('12.34'));
    }

    public function testSanitizeFloat(): void
    {
        $this->assertEquals(12.34, Sanitizer::float('12.34'));
        $this->assertEquals(0.5, Sanitizer::float('0.5'));
        $this->assertEquals(-3.14, Sanitizer::float('-3.14'));
        $this->assertNull(Sanitizer::float('abc'));
    }

    public function testSanitizeUrl(): void
    {
        $this->assertEquals('http://example.com', Sanitizer::url('http://example.com'));
        $this->assertEquals('https://example.com/path', Sanitizer::url('https://example.com/path'));
        $this->assertNull(Sanitizer::url('not a url'));
        $this->assertNull(Sanitizer::url('javascript:alert("xss")'));
    }

    public function testSanitizeFileName(): void
    {
        $this->assertEquals('test.jpg', Sanitizer::fileName('test.jpg'));
        $this->assertEquals('my_file.txt', Sanitizer::fileName('my-file.txt'));
        $this->assertEquals('file_with_spaces.pdf', Sanitizer::fileName('file with spaces.pdf'));
        $this->assertNotContains('/', Sanitizer::fileName('../../../etc/passwd'));
        $this->assertNotContains('\\', Sanitizer::fileName('..\\..\\windows\\system32'));
    }

    public function testHtmlEscape(): void
    {
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            Sanitizer::html('<script>alert("xss")</script>'));
        $this->assertEquals('&amp; &lt; &gt;', Sanitizer::html('& < >'));
        $this->assertEquals('Hello World', Sanitizer::html('Hello World'));
    }

    public function testJavaScriptEscape(): void
    {
        $escaped = Sanitizer::javascript('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<', $escaped);
        $this->assertStringNotContainsString('script', $escaped);
    }

    public function testSqlLike(): void
    {
        $this->assertEquals('test%', Sanitizer::sqlLike('test%'));
        $this->assertEquals('\\%test\\%', Sanitizer::sqlLike('%test%'));
        $this->assertEquals('_test', Sanitizer::sqlLike('_test'));
    }

    public function testValidateDate(): void
    {
        $this->assertEquals('2026-07-13', Sanitizer::date('2026-07-13', 'Y-m-d'));
        $this->assertNull(Sanitizer::date('invalid-date', 'Y-m-d'));
        $this->assertNull(Sanitizer::date('2026-13-45', 'Y-m-d'));
    }

    public function testValidatePhone(): void
    {
        $this->assertNotNull(Sanitizer::phone('+7 (495) 123-45-67'));
        $this->assertNotNull(Sanitizer::phone('8-800-555-35-35'));
        $this->assertNull(Sanitizer::phone('123'));
        $this->assertNull(Sanitizer::phone('abc'));
    }

    public function testXssVulnerability(): void
    {
        $xssAttempts = [
            '<script>alert("xss")</script>',
            '<img src=x onerror="alert(1)">',
            'javascript:alert("xss")',
            '<svg/onload=alert("xss")>',
        ];

        foreach ($xssAttempts as $attempt) {
            $sanitized = Sanitizer::string($attempt, true);
            $this->assertStringNotContainsString('<', $sanitized);
            $this->assertStringNotContainsString('>', $sanitized);
        }
    }
}
