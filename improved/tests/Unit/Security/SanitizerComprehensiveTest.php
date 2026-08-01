<?php

namespace Tests\Unit\Security;

use App\Security\Sanitizer;
use PHPUnit\Framework\TestCase;

class SanitizerComprehensiveTest extends TestCase
{
    public function testStringWithValidString(): void
    {
        $result = Sanitizer::string('hello world');
        $this->assertEquals('hello world', $result);
    }

    public function testStringWithEmptyString(): void
    {
        $result = Sanitizer::string('');
        $this->assertEquals('', $result);
    }

    public function testStringWithNonString(): void
    {
        $result = Sanitizer::string(123);
        $this->assertEquals('123', $result);
    }

    public function testEmailWithValidEmail(): void
    {
        $result = Sanitizer::email('test@example.com');
        $this->assertEquals('test@example.com', $result);
    }

    public function testEmailWithInvalidEmail(): void
    {
        $result = Sanitizer::email('invalid-email');
        $this->assertNull($result);
    }

    public function testEmailWithEmptyString(): void
    {
        $result = Sanitizer::email('');
        $this->assertNull($result);
    }

    public function testIntegerWithValidInt(): void
    {
        $result = Sanitizer::integer(42);
        $this->assertEquals(42, $result);
    }

    public function testIntegerWithNumericString(): void
    {
        $result = Sanitizer::integer('123');
        $this->assertEquals(123, $result);
    }

    public function testIntegerWithNonNumericString(): void
    {
        $result = Sanitizer::integer('abc');
        $this->assertNull($result);
    }

    public function testIntegerWithZero(): void
    {
        $result = Sanitizer::integer(0);
        $this->assertEquals(0, $result);
    }

    public function testIntegerWithNegativeNumber(): void
    {
        $result = Sanitizer::integer(-42);
        $this->assertEquals(-42, $result);
    }

    public function testFloatWithValidFloat(): void
    {
        $result = Sanitizer::float(3.14);
        $this->assertEquals(3.14, $result);
    }

    public function testFloatWithNumericString(): void
    {
        $result = Sanitizer::float('2.71');
        $this->assertEquals(2.71, $result);
    }

    public function testFloatWithNonNumericString(): void
    {
        $result = Sanitizer::float('abc');
        $this->assertNull($result);
    }

    public function testPhoneWithValidPhone(): void
    {
        $result = Sanitizer::phone('+1234567890');
        $this->assertEquals('+1234567890', $result);
    }

    public function testPhoneWithLocalFormat(): void
    {
        $result = Sanitizer::phone('(555) 123-4567');
        $this->assertIsString($result);
    }

    public function testPhoneWithInvalidPhone(): void
    {
        $result = Sanitizer::phone('abc');
        $this->assertNull($result);
    }

    public function testHtmlWithHtmlContent(): void
    {
        $html = '<div>Test</div>';
        $result = Sanitizer::html($html);
        $this->assertStringContainsString('&lt;div&gt;', $result);
    }

    public function testHtmlWithPlainText(): void
    {
        $text = 'Plain text';
        $result = Sanitizer::html($text);
        $this->assertEquals('Plain text', $result);
    }

    public function testJavascriptWithValidJson(): void
    {
        $data = json_encode(['key' => 'value']);
        $result = Sanitizer::javascript($data);
        $this->assertIsString($result);
        $this->assertStringContainsString('key', $result);
    }

    public function testSqlLikeWithValidString(): void
    {
        $result = Sanitizer::sqlLike('test');
        $this->assertEquals('test', $result);
    }

    public function testSqlLikeWithWildcards(): void
    {
        $result = Sanitizer::sqlLike('%test%');
        $this->assertIsString($result);
    }

    public function testUrlWithValidUrl(): void
    {
        $result = Sanitizer::url('https://example.com');
        $this->assertEquals('https://example.com', $result);
    }

    public function testUrlWithInvalidUrl(): void
    {
        $result = Sanitizer::url('not a url');
        $this->assertNull($result);
    }

    public function testFileNameWithValidName(): void
    {
        $result = Sanitizer::fileName('document.pdf');
        $this->assertIsString($result);
    }

    public function testFileNameWithPathTraversal(): void
    {
        $result = Sanitizer::fileName('../../../etc/passwd');
        $this->assertStringNotContainsString('..', $result);
    }

    public function testDateWithValidDate(): void
    {
        $result = Sanitizer::date('2024-01-15');
        $this->assertEquals('2024-01-15', $result);
    }

    public function testDateWithInvalidDate(): void
    {
        $result = Sanitizer::date('invalid-date');
        $this->assertNull($result);
    }

    public function testChainableOperations(): void
    {
        $email = Sanitizer::email('TEST@EXAMPLE.COM');
        $this->assertIsString($email);

        $text = Sanitizer::string('  hello  ');
        $this->assertIsString($text);
    }
}
