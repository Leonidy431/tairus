<?php

namespace App\Repository;

use App\Database\Database;

class CartRepository extends Repository
{
    protected string $table = 'carts';
    private Database $db;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->db = $db;
    }

    /**
     * Add item to cart
     */
    public function addItem(?int $userId, int $productId, int $quantity = 1): bool
    {
        // Check if item already exists in cart
        $existing = $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE user_id = ? AND product_id = ?",
            [$userId, $productId]
        );

        if ($existing) {
            // Update quantity if item already exists
            return $this->db->update(
                $this->table,
                ['quantity' => $existing['quantity'] + $quantity],
                ['id' => $existing['id']]
            );
        }

        // Add new item
        return $this->db->insert($this->table, [
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Remove item from cart
     */
    public function removeItem(?int $userId, int $cartId): bool
    {
        $conditions = ['id' => $cartId];

        // If user is provided, verify ownership
        if ($userId !== null) {
            $conditions['user_id'] = $userId;
        }

        return $this->db->delete($this->table, $conditions);
    }

    /**
     * Update item quantity
     */
    public function updateQuantity(int $cartId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->db->delete($this->table, ['id' => $cartId]);
        }

        return $this->db->update(
            $this->table,
            ['quantity' => $quantity],
            ['id' => $cartId]
        );
    }

    /**
     * Get cart for user or null for guest
     */
    public function getCart(?int $userId): array
    {
        $query = "SELECT c.*, p.title, p.price, p.currency, p.image_url
                  FROM {$this->table} c
                  LEFT JOIN products p ON c.product_id = p.id
                  WHERE c.user_id = ?
                  ORDER BY c.added_at DESC";

        return $this->db->select($query, [$userId]);
    }

    /**
     * Clear all items from user's cart
     */
    public function clearCart(?int $userId): bool
    {
        return $this->db->delete($this->table, ['user_id' => $userId]);
    }

    /**
     * Get cart total for user
     */
    public function getTotal(?int $userId): float
    {
        $query = "SELECT SUM(c.quantity * p.price) as total
                  FROM {$this->table} c
                  LEFT JOIN products p ON c.product_id = p.id
                  WHERE c.user_id = ?";

        $result = $this->db->selectOne($query, [$userId]);
        return isset($result['total']) ? (float)$result['total'] : 0.0;
    }

    /**
     * Get cart item count for user
     */
    public function getItemCount(?int $userId): int
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?",
            [$userId]
        );
        return $result['count'] ?? 0;
    }

    /**
     * Check if product exists and has stock
     */
    public function validateProduct(int $productId): array|bool
    {
        return $this->db->selectOne(
            "SELECT id, title, price, is_public FROM products WHERE id = ? AND is_public = 1",
            [$productId]
        );
    }

    /**
     * Get cart item by id
     */
    public function getCartItem(int $cartId): ?array
    {
        return $this->db->selectOne(
            "SELECT c.*, p.title, p.price, p.currency, p.image_url
             FROM {$this->table} c
             LEFT JOIN products p ON c.product_id = p.id
             WHERE c.id = ?",
            [$cartId]
        );
    }

    /**
     * Delete expired guest cart sessions (older than 30 days)
     */
    public function cleanupExpiredSessions(int $daysOld = 30): bool
    {
        $expiryDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        return $this->db->delete(
            'cart_sessions',
            ['created_at' => $expiryDate]
        ) || true; // Consider success even if no rows deleted
    }

    /**
     * Save guest cart session
     */
    public function saveGuestCart(string $sessionId, array $cartData): bool
    {
        $data = [
            'session_id' => $sessionId,
            'cart_data' => json_encode($cartData),
        ];

        // Check if session exists
        $existing = $this->db->selectOne(
            "SELECT id FROM cart_sessions WHERE session_id = ?",
            [$sessionId]
        );

        if ($existing) {
            return $this->db->update(
                'cart_sessions',
                ['cart_data' => json_encode($cartData)],
                ['session_id' => $sessionId]
            );
        }

        return $this->db->insert('cart_sessions', $data);
    }

    /**
     * Get guest cart session
     */
    public function getGuestCart(string $sessionId): ?array
    {
        $result = $this->db->selectOne(
            "SELECT cart_data FROM cart_sessions WHERE session_id = ?",
            [$sessionId]
        );

        if (!$result || !$result['cart_data']) {
            return null;
        }

        return json_decode($result['cart_data'], true);
    }

    /**
     * Delete guest cart session
     */
    public function deleteGuestCart(string $sessionId): bool
    {
        return $this->db->delete('cart_sessions', ['session_id' => $sessionId]);
    }
}
