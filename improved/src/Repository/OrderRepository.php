<?php
/**
 * Order Repository
 *
 * Handles all order-related database operations including order creation,
 * order item management, and order retrieval.
 */

namespace App\Repository;

use App\Database\Database;

class OrderRepository extends Repository
{
    protected string $table = 'orders';

    /**
     * Create a new order with items
     *
     * @param array $orderData Order information
     * @param array $items Order items with product_id, quantity, price, subtotal
     * @return int Order ID
     */
    public function createOrder(array $orderData, array $items): int
    {
        return $this->db->transaction(function (Database $db) use ($orderData, $items) {
            // Prepare order data
            $order = [
                'user_id' => $orderData['user_id'],
                'order_number' => $orderData['order_number'],
                'total' => $orderData['total'],
                'subtotal' => $orderData['subtotal'],
                'shipping_cost' => $orderData['shipping_cost'] ?? 0,
                'tax' => $orderData['tax'] ?? 0,
                'discount' => $orderData['discount'] ?? 0,
                'status' => $orderData['status'] ?? 'pending',
                'billing_address' => $orderData['billing_address'] ?? null,
                'shipping_address' => $orderData['shipping_address'] ?? null,
                'notes' => $orderData['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Insert order
            $db->insert('orders', $order);
            $orderId = (int)$db->getConnection()->lastInsertId();

            // Insert order items
            foreach ($items as $item) {
                $orderItem = [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ];

                $db->insert('order_items', $orderItem);
            }

            return $orderId;
        });
    }

    /**
     * Get order with all items
     */
    public function getOrderWithItems(int $orderId): ?array
    {
        $order = $this->find($orderId);

        if (!$order) {
            return null;
        }

        $order['items'] = $this->getOrderItems($orderId);
        return $order;
    }

    /**
     * Get order items
     */
    public function getOrderItems(int $orderId): array
    {
        return $this->db->select(
            "SELECT oi.*, p.name as product_name, p.description as product_description
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC",
            [$orderId]
        );
    }

    /**
     * Get orders by user
     */
    public function getOrdersByUserId(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        return $this->db->select(
            "SELECT * FROM {$this->table}
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );
    }

    /**
     * Get order count by user
     */
    public function getOrderCountByUserId(int $userId): int
    {
        return $this->count(['user_id' => $userId]);
    }

    /**
     * Get order by order number
     */
    public function getByOrderNumber(string $orderNumber): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE order_number = ?",
            [$orderNumber]
        );
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(int $orderId, string $status, ?string $notes = null): bool
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($notes)) {
            $data['notes'] = $notes;
        }

        return $this->db->update('orders', $data, ['id' => $orderId]);
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus(string $status, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        return $this->db->select(
            "SELECT * FROM {$this->table}
             WHERE status = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$status, $perPage, $offset]
        );
    }

    /**
     * Get order count by status
     */
    public function getOrderCountByStatus(string $status): int
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?",
            [$status]
        );

        return $result['count'] ?? 0;
    }

    /**
     * Generate unique order number
     */
    public function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        } while ($this->getByOrderNumber($orderNumber) !== null);

        return $orderNumber;
    }

    /**
     * Get order summary statistics
     */
    public function getOrderStatistics(): array
    {
        return $this->db->selectOne(
            "SELECT
                COUNT(*) as total_orders,
                SUM(total) as total_revenue,
                AVG(total) as average_order_value,
                MAX(total) as highest_order,
                MIN(total) as lowest_order
            FROM {$this->table}
            WHERE status != 'cancelled'"
        ) ?: [];
    }

    /**
     * Get orders by date range
     */
    public function getOrdersByDateRange(string $startDate, string $endDate, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        return $this->db->select(
            "SELECT * FROM {$this->table}
             WHERE DATE(created_at) BETWEEN ? AND ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$startDate, $endDate, $perPage, $offset]
        );
    }

    /**
     * Calculate shipping cost based on address
     * For now, returns a flat rate; customize based on business logic
     */
    public static function calculateShippingCost(array $address): float
    {
        $country = $address['country'] ?? 'US';

        // Simple shipping cost logic
        $shippingRates = [
            'US' => 10.00,
            'CA' => 15.00,
            'MX' => 20.00,
            'GB' => 25.00,
            'DE' => 25.00,
            'FR' => 25.00,
            'IT' => 25.00,
            'ES' => 25.00,
            'AU' => 35.00,
        ];

        return $shippingRates[$country] ?? 30.00; // Default international rate
    }

    /**
     * Calculate tax based on address and subtotal
     * For now, returns a simplified tax; customize based on jurisdiction
     */
    public static function calculateTax(array $address, float $subtotal): float
    {
        $country = $address['country'] ?? 'US';
        $state = $address['state'] ?? '';

        // Simplified tax rates by country/state
        $taxRates = [
            'US' => [
                'CA' => 0.0725,
                'NY' => 0.08,
                'TX' => 0.0625,
                'default' => 0.07,
            ],
            'CA' => 0.13,
            'UK' => 0.20,
            'DE' => 0.19,
            'FR' => 0.20,
            'default' => 0.10,
        ];

        $rate = 0;

        if ($country === 'US' && isset($taxRates['US'])) {
            $rate = $taxRates['US'][$state] ?? $taxRates['US']['default'];
        } else {
            $rate = $taxRates[$country] ?? $taxRates['default'];
        }

        return round($subtotal * $rate, 2);
    }
}
