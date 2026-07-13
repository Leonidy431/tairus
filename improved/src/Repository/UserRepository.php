<?php
/**
 * User Repository
 *
 * Handles database operations for user management and authentication.
 */

namespace App\Repository;

use App\Database\Database;

class UserRepository extends Repository
{
    protected string $table = 'users';

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE username = ?",
            [$username]
        );
    }

    /**
     * Find user by username or email
     */
    public function findByUsernameOrEmail(string $identifier): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE username = ? OR email = ?",
            [$identifier, $identifier]
        );
    }

    /**
     * Verify password for user
     */
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }

    /**
     * Hash password for storage
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->db->update(
            $this->table,
            ['last_login_at' => date('Y-m-d H:i:s')],
            ['id' => $userId]
        );
    }

    /**
     * Record login attempt
     */
    public function recordLoginAttempt(
        ?int $userId,
        string $ipAddress,
        string $username,
        bool $success
    ): void {
        $this->db->insert('login_attempts', [
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'username' => $username,
            'success' => $success ? 1 : 0,
        ]);
    }

    /**
     * Get failed login attempts for IP in last 15 minutes
     */
    public function getFailedLoginAttempts(string $ipAddress, int $minutes = 15): int
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM login_attempts
             WHERE ip_address = ? AND success = 0
             AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$ipAddress, $minutes]
        );

        return (int)($result['count'] ?? 0);
    }

    /**
     * Create remember me token
     */
    public function createRememberToken(int $userId, int $daysValid = 7): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$daysValid} days"));

        $this->db->insert('remember_me_tokens', [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    /**
     * Verify remember me token
     */
    public function verifyRememberToken(string $token): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM remember_me_tokens
             WHERE token = ? AND expires_at > NOW()",
            [$token]
        );
    }

    /**
     * Revoke remember token
     */
    public function revokeRememberToken(string $token): bool
    {
        return $this->db->delete('remember_me_tokens', ['token' => $token]);
    }

    /**
     * Revoke all remember tokens for user
     */
    public function revokeAllRememberTokens(int $userId): bool
    {
        return $this->db->delete('remember_me_tokens', ['user_id' => $userId]);
    }

    /**
     * Clean expired remember tokens
     */
    public function cleanExpiredTokens(): void
    {
        $this->db->getConnection()->exec(
            "DELETE FROM remember_me_tokens WHERE expires_at < NOW()"
        );
    }

    /**
     * Get login attempt history for user
     */
    public function getLoginHistory(int $userId, int $limit = 10): array
    {
        return $this->db->select(
            "SELECT * FROM login_attempts
             WHERE user_id = ?
             ORDER BY attempted_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }
}
