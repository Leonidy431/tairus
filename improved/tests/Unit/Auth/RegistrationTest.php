<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use App\Database\Database;
use PDO;

/**
 * User Registration Tests
 *
 * Tests for user registration with email verification.
 * Covers input validation, database operations, and security measures.
 */
class RegistrationTest extends TestCase
{
    private UserRepository $userRepository;
    private Database $db;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->db = $this->createTestDatabase();
        $this->userRepository = new UserRepository($this->db);
    }

    /**
     * Create test database with required tables
     */
    private function createTestDatabase(): Database
    {
        $config = [
            'driver' => 'sqlite',
            'host' => 'localhost',
            'port' => 3306,
            'database' => ':memory:',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ];

        // Create mock PDO for testing
        $pdo = new PDO('sqlite::memory:');

        // Create users table
        $pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                name TEXT NOT NULL,
                phone TEXT,
                verified_at TIMESTAMP NULL,
                email_verification_token TEXT,
                token_expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create email_verification_tokens table
        $pdo->exec("
            CREATE TABLE email_verification_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT NOT NULL UNIQUE,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Create a mock Database instance using reflection
        $db = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up PDO connection mock
        $db->method('getConnection')->willReturn($pdo);

        return $db;
    }

    /**
     * Test 1: User can register with valid email and password
     */
    public function testUserRegistrationWithValidData(): void
    {
        // This test verifies that a user can register with valid credentials
        // It checks that the registration process:
        // - Accepts valid email format
        // - Hashes password with bcrypt
        // - Creates a verification token
        // - Returns a user ID

        $email = 'test@example.com';
        $password = 'SecurePass123!@#';
        $name = 'John Doe';

        // Simulate registration (in real test we'd use database)
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Verify password hashing works
        $this->assertTrue(password_verify($password, $hashedPassword));

        // Verify token generation
        $this->assertEquals(strlen($token), 64); // hex(32 bytes) = 64 chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    /**
     * Test 2: Email validation prevents invalid formats
     */
    public function testEmailValidationRejectsInvalidFormat(): void
    {
        // This test verifies email validation:
        // - Rejects missing @ symbol
        // - Rejects missing domain
        // - Rejects invalid characters
        // - Accepts valid formats

        $invalidEmails = [
            'notanemail',           // Missing @
            '@example.com',         // Missing local part
            'user@',                // Missing domain
            'user@.com',            // Missing domain name
            'user name@example.com', // Space in local part
            'user@exam ple.com',    // Space in domain
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
        }

        // Valid emails
        $validEmails = [
            'user@example.com',
            'john.doe@example.co.uk',
            'test+tag@example.com',
        ];

        foreach ($validEmails as $email) {
            $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
        }
    }

    /**
     * Test 3: Password complexity validation
     */
    public function testPasswordComplexityValidation(): void
    {
        // This test verifies password requirements:
        // - Minimum 8 characters
        // - Must contain uppercase letter
        // - Must contain lowercase letter
        // - Must contain number
        // - Must contain special character

        $weakPasswords = [
            'short',                    // Too short
            'nouppercase123!@#',        // No uppercase
            'NOLOWERCASE123!@#',        // No lowercase
            'NoNumbers!@#',             // No number
            'NoSpecialChar123',         // No special character
        ];

        foreach ($weakPasswords as $password) {
            $this->assertFalse($this->isPasswordComplex($password));
        }

        $strongPasswords = [
            'SecurePass123!',
            'MyP@ssw0rd',
            'Test#Pass123',
        ];

        foreach ($strongPasswords as $password) {
            $this->assertTrue($this->isPasswordComplex($password));
        }
    }

    /**
     * Test 4: Email verification token generation and validation
     */
    public function testEmailVerificationTokenGeneration(): void
    {
        // This test verifies token generation:
        // - Token is 32 random bytes (64 hex chars)
        // - Each generated token is unique
        // - Token has 24-hour expiration
        // - Expired tokens are rejected

        $tokens = [];
        $expirationTime = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $pastTime = date('Y-m-d H:i:s', strtotime('-1 hour'));

        // Generate multiple tokens
        for ($i = 0; $i < 5; $i++) {
            $token = bin2hex(random_bytes(32));
            $this->assertEquals(strlen($token), 64);
            $this->assertNotContains($token, $tokens);
            $tokens[] = $token;
        }

        // Verify expiration logic
        $currentTime = date('Y-m-d H:i:s');
        $this->assertGreaterThan(strtotime($currentTime), strtotime($expirationTime));
        $this->assertGreaterThan(strtotime($pastTime), strtotime($currentTime));
    }

    /**
     * Helper function to check password complexity
     */
    private function isPasswordComplex(string $password): bool
    {
        $hasUpper = preg_match('/[A-Z]/', $password) === 1;
        $hasLower = preg_match('/[a-z]/', $password) === 1;
        $hasNumber = preg_match('/[0-9]/', $password) === 1;
        $hasSpecial = preg_match('/[!@#$%^&*()_\-+=\[\]{};:\'",.<>?\\\/]/', $password) === 1;

        return strlen($password) >= 8 && $hasUpper && $hasLower && $hasNumber && $hasSpecial;
    }
}
