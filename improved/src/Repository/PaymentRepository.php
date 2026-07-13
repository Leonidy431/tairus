<?php
/**
 * Payment Repository
 *
 * Handles all payment-related database operations with PCI DSS compliance.
 * Never stores raw card data - uses Stripe tokenization only.
 */

namespace App\Repository;

use App\Database\Database;

class PaymentRepository extends Repository
{
    protected string $table = 'transactions';

    /**
     * Create a new payment intent
     */
    public function createPaymentIntent(array $data): int
    {
        $paymentData = [
            'user_id' => $data['user_id'],
            'order_id' => $data['order_id'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'stripe_transaction_id' => $data['stripe_transaction_id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->save($paymentData);
    }

    /**
     * Get transaction by Stripe transaction ID
     */
    public function getByStripeTransactionId(string $stripeTransactionId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE stripe_transaction_id = ?",
            [$stripeTransactionId]
        );
    }

    /**
     * Get transaction by user and status
     */
    public function getByUserIdAndStatus(int $userId, string $status, int $limit = 10): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE user_id = ? AND status = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $status, $limit]
        );
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus(int $transactionId, string $status, ?string $errorMessage = null): bool
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($errorMessage)) {
            $data['error_message'] = $errorMessage;
        }

        $this->db->update('transactions', $data, ['id' => $transactionId]);
        return true;
    }

    /**
     * Save payment method (tokenized, PCI compliant)
     */
    public function savePaymentMethod(array $data): int
    {
        $methodData = [
            'user_id' => $data['user_id'],
            'stripe_payment_method_id' => $data['stripe_payment_method_id'],
            'type' => $data['type'] ?? 'card',
            'last4' => $data['last4'] ?? null,
            'brand' => $data['brand'] ?? null,
            'exp_month' => $data['exp_month'] ?? null,
            'exp_year' => $data['exp_year'] ?? null,
            'is_default' => $data['is_default'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->save($methodData);
    }

    /**
     * Get payment methods for user
     */
    public function getPaymentMethodsByUserId(int $userId): array
    {
        return $this->db->select(
            "SELECT * FROM payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC",
            [$userId]
        );
    }

    /**
     * Get default payment method for user
     */
    public function getDefaultPaymentMethod(int $userId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM payment_methods WHERE user_id = ? AND is_default = 1",
            [$userId]
        );
    }

    /**
     * Set payment method as default
     */
    public function setDefaultPaymentMethod(int $userId, int $paymentMethodId): bool
    {
        // Unset all other default methods
        $this->db->update('payment_methods', ['is_default' => 0], ['user_id' => $userId]);

        // Set this one as default
        $this->db->update('payment_methods', ['is_default' => 1], ['id' => $paymentMethodId, 'user_id' => $userId]);

        return true;
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod(int $paymentMethodId, int $userId): bool
    {
        return $this->db->delete('payment_methods', ['id' => $paymentMethodId, 'user_id' => $userId]);
    }

    /**
     * Get payment method by ID
     */
    public function getPaymentMethodById(int $paymentMethodId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM payment_methods WHERE id = ?",
            [$paymentMethodId]
        );
    }

    /**
     * Log payment event for audit trail
     */
    public function logPaymentEvent(int $transactionId, string $event, array $details): int
    {
        $logData = [
            'transaction_id' => $transactionId,
            'event' => $event,
            'details' => json_encode($details),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('payment_logs', $logData);
        return (int)$this->db->getConnection()->lastInsertId();
    }

    /**
     * Get payment logs for transaction
     */
    public function getPaymentLogs(int $transactionId): array
    {
        return $this->db->select(
            "SELECT * FROM payment_logs WHERE transaction_id = ? ORDER BY created_at ASC",
            [$transactionId]
        );
    }

    /**
     * Get transaction audit trail with detailed logs
     */
    public function getTransactionWithLogs(int $transactionId): ?array
    {
        $transaction = $this->find($transactionId);

        if (!$transaction) {
            return null;
        }

        $transaction['logs'] = $this->getPaymentLogs($transactionId);
        return $transaction;
    }

    /**
     * Get user transactions by status
     */
    public function getUserTransactions(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );
    }

    /**
     * Get transaction count by user and status
     */
    public function getTransactionCountByUserAndStatus(int $userId, string $status): int
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND status = ?",
            [$userId, $status]
        );

        return $result['count'] ?? 0;
    }

    /**
     * Get monthly transaction statistics
     */
    public function getMonthlyStatistics(int $year, int $month): array
    {
        return $this->db->select(
            "SELECT
                DATE(created_at) as date,
                COUNT(*) as transaction_count,
                SUM(amount) as total_amount,
                status
            FROM {$this->table}
            WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
            GROUP BY DATE(created_at), status
            ORDER BY created_at ASC",
            [$year, $month]
        );
    }

    /**
     * Check if user exists in the database
     * (Assumes users table exists)
     */
    public function userExists(int $userId): bool
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM users WHERE id = ?",
            [$userId]
        );

        return ($result['count'] ?? 0) > 0;
    }
}
