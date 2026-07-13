<?php

namespace Tests\Unit\Security;

use App\Security\CsrfToken;
use PHPUnit\Framework\TestCase;

class CsrfTokenTest extends TestCase
{
    protected function setUp(): void
    {
        // Start session for each test
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        session_destroy();
    }

    public function testGenerateToken(): void
    {
        $token = CsrfToken::generate();
        $this->assertIsString($token);
        $this->assertGreaterThan(0, strlen($token));
    }

    public function testTokensAreConsistent(): void
    {
        $token1 = CsrfToken::generate();
        $token2 = CsrfToken::generate();

        // Same token should be returned on subsequent calls
        $this->assertEquals($token1, $token2);
    }

    public function testValidateToken(): void
    {
        $token = CsrfToken::generate();
        $this->assertTrue(CsrfToken::validate($token));
    }

    public function testInvalidTokenFails(): void
    {
        CsrfToken::generate();
        $this->assertFalse(CsrfToken::validate('invalid-token'));
    }

    public function testEmptyTokenFails(): void
    {
        $this->assertFalse(CsrfToken::validate(''));
    }

    public function testRegenerateToken(): void
    {
        $token1 = CsrfToken::generate();
        $token2 = CsrfToken::regenerate();

        $this->assertNotEquals($token1, $token2);
        $this->assertTrue(CsrfToken::validate($token2));
    }

    public function testTokenHashing(): void
    {
        $token = CsrfToken::generate();
        // Token should use secure random bytes
        $this->assertGreaterThanOrEqual(32, strlen($token));
    }
}
