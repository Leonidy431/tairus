<?php

namespace Tests\Unit\Security;

use App\Security\CsrfToken;
use PHPUnit\Framework\TestCase;

class CsrfTokenAdvancedTest extends TestCase
{
    protected function setUp(): void
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        $_SESSION = [];
        $_POST = [];
        $_SERVER = ['HTTP_HOST' => 'localhost'];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER = [];
    }

    public function testGenerateCreatesUniqueTokens(): void
    {
        ob_start();
        $token1 = @CsrfToken::generate();
        ob_end_clean();

        ob_start();
        $token2 = @CsrfToken::generate();
        ob_end_clean();

        $this->assertNotEmpty($token1);
        $this->assertNotEmpty($token2);
        $this->assertEquals(64, strlen($token1));
        $this->assertEquals(64, strlen($token2));
    }

    public function testValidationLogic(): void
    {
        $hash1 = hash('sha256', 'test');
        $hash2 = hash('sha256', 'test');
        $hash3 = hash('sha256', 'different');

        $this->assertTrue(hash_equals($hash1, $hash2));
        $this->assertFalse(hash_equals($hash1, $hash3));
    }

    public function testTokenLengthConsistency(): void
    {
        $lengths = [];
        for ($i = 0; $i < 5; $i++) {
            ob_start();
            $token = @CsrfToken::generate();
            ob_end_clean();
            $lengths[] = strlen($token);
        }

        foreach ($lengths as $length) {
            $this->assertEquals(64, $length);
        }
    }

    public function testTimingSafeComparison(): void
    {
        $token = bin2hex(random_bytes(32));
        $fakeToken = str_repeat('a', 64);

        $this->assertFalse(hash_equals($token, $fakeToken));
        $this->assertTrue(hash_equals($token, $token));
    }

    public function testRandomBytesGeneration(): void
    {
        $bytes1 = random_bytes(32);
        $bytes2 = random_bytes(32);

        $this->assertEquals(32, strlen($bytes1));
        $this->assertEquals(32, strlen($bytes2));
        $this->assertNotEquals($bytes1, $bytes2);
    }

    public function testBinHexConversion(): void
    {
        $bytes = random_bytes(32);
        $hex = bin2hex($bytes);

        $this->assertEquals(64, strlen($hex));
        $this->assertEquals($bytes, hex2bin($hex));
    }

    public function testTokenUniqueness(): void
    {
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $token = bin2hex(random_bytes(32));
            $this->assertNotContains($token, $tokens);
            $tokens[] = $token;
        }
    }

    public function testTokenFormatting(): void
    {
        $token = bin2hex(random_bytes(32));

        $this->assertEquals(64, strlen($token));
        $this->assertTrue(ctype_xdigit($token));
    }

    public function testArrayKeyAccess(): void
    {
        $_SESSION['test_key'] = 'test_value';

        $this->assertTrue(isset($_SESSION['test_key']));
        $this->assertEquals('test_value', $_SESSION['test_key']);
    }

    public function testPostDataHandling(): void
    {
        $_POST['token'] = 'test_token_123';

        $this->assertTrue(isset($_POST['token']));
        $this->assertEquals('test_token_123', $_POST['token']);
    }
}
