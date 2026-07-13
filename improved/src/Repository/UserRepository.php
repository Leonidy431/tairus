<?php
/**
 * User Repository
 *
 * Handles database operations for user registration, authentication, and email verification.
 */

namespace App\Repository;

use App\Database\Database;

class UserRepository extends Repository
{
    protected string $table = 'users';
    protected string $tokenTable = 'email_verification_tokens';

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Register a new user with email verification
     */
    public function register(string $email, string $password, string $name, ?string $phone = null): int|false
    {
        // Hash password with bcrypt
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Generate email verification token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        try {
            // Use transaction to ensure consistency
            $userId = $this->db->transaction(function (Database $db) use ($email, $hashedPassword, $name, $phone, $token, $expiresAt) {
                // Insert user
                $data = [
                    'email' => $email,
                    'password' => $hashedPassword,
                    'name' => $name,
                    'phone' => $phone,
                    'email_verification_token' => $token,
                    'token_expires_at' => $expiresAt,
                ];

                $db->insert('users', $data);
                $userId = (int)$db->getConnection()->lastInsertId();

                // Also store token in email_verification_tokens table for better tracking
                $tokenData = [
                    'user_id' => $userId,
                    'token' => $token,
                    'expires_at' => $expiresAt,
                ];
                $db->insert('email_verification_tokens', $tokenData);

                return $userId;
            });

            return $userId;
        } catch (\Exception $e) {
            error_log('User registration failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by email address
     */
    public function getByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1",
            [$email]
        );
    }

    /**
     * Get user by ID
     */
    public function getById(int $id): ?array
    {
        return $this->find($id);
    }

    /**
     * Verify user email with verification token
     */
    public function verifyEmail(string $token): bool
    {
        try {
            // Find token in email_verification_tokens table
            $tokenRecord = $this->db->selectOne(
                "SELECT * FROM {$this->tokenTable} WHERE token = ? AND expires_at > NOW() LIMIT 1",
                [$token]
            );

            if (!$tokenRecord) {
                return false;
            }

            $userId = $tokenRecord['user_id'];

            // Update user with verification timestamp
            $this->db->update(
                $this->table,
                [
                    'verified_at' => date('Y-m-d H:i:s'),
                    'email_verification_token' => null,
                    'token_expires_at' => null,
                ],
                ['id' => $userId]
            );

            // Delete the token record
            $this->db->delete($this->tokenTable, ['id' => $tokenRecord['id']]);

            return true;
        } catch (\Exception $e) {
            error_log('Email verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email is already registered
     */
    public function emailExists(string $email): bool
    {
        $result = $this->db->selectOne(
            "SELECT id FROM {$this->table} WHERE email = ? LIMIT 1",
            [$email]
        );

        return $result !== null;
    }

    /**
     * Check if user email is verified
     */
    public function isEmailVerified(int $userId): bool
    {
        $user = $this->find($userId);
        return $user !== null && $user['verified_at'] !== null;
    }

    /**
     * Get user by verification token
     */
    public function getUserByToken(string $token): ?array
    {
        $tokenRecord = $this->db->selectOne(
            "SELECT * FROM {$this->tokenTable} WHERE token = ? AND expires_at > NOW() LIMIT 1",
            [$token]
        );

        if (!$tokenRecord) {
            return null;
        }

        return $this->getById($tokenRecord['user_id']);
    }

    /**
     * Resend verification email (generate new token)
     */
    public function regenerateVerificationToken(int $userId): string|false
    {
        try {
            $user = $this->getById($userId);
            if (!$user) {
                return false;
            }

            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Delete old tokens
            $this->db->delete($this->tokenTable, ['user_id' => $userId]);

            // Update user with new token
            $this->db->update(
                $this->table,
                [
                    'email_verification_token' => $token,
                    'token_expires_at' => $expiresAt,
                ],
                ['id' => $userId]
            );

            // Insert new token record
            $this->db->insert($this->tokenTable, [
                'user_id' => $userId,
                'token' => $token,
                'expires_at' => $expiresAt,
            ]);

            return $token;
        } catch (\Exception $e) {
            error_log('Token regeneration failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all unverified users (for admin purposes)
     */
    public function getUnverifiedUsers(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM {$this->table} WHERE verified_at IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->select($query, [$perPage, $offset]);
    }
}
