<?php
/**
 * Admin Dashboard Controller
 *
 * Handles admin dashboard display with key metrics and overview.
 * Displays total_sales, total_users, total_products, active_orders, conversion_rate, revenue_today.
 */

namespace App\Controllers;

use App\Database\Database;
use App\Middleware\AdminMiddleware;

class AdminDashboardController
{
    private Database $db;
    private AdminMiddleware $auth;

    public function __construct(Database $db, AdminMiddleware $auth)
    {
        $this->db = $db;
        $this->auth = $auth;

        if (!$this->auth->hasPermission('view_dashboard')) {
            AdminMiddleware::deny('Access denied: insufficient permissions');
        }
    }

    /**
     * Display admin dashboard with key metrics
     */
    public function index(): array
    {
        return [
            'total_sales' => $this->getTotalSales(),
            'total_users' => $this->getTotalUsers(),
            'total_products' => $this->getTotalProducts(),
            'active_orders' => $this->getActiveOrders(),
            'conversion_rate' => $this->getConversionRate(),
            'revenue_today' => $this->getRevenueToday(),
            'recent_orders' => $this->getRecentOrders(),
            'top_products' => $this->getTopProducts(),
            'user_roles' => $this->auth->getRoles(),
            'user_permissions' => $this->auth->getPermissions(),
        ];
    }

    /**
     * Get total sales amount
     */
    private function getTotalSales(): float
    {
        $result = $this->db->selectOne(
            "SELECT SUM(amount) as total FROM transactions WHERE status = 'completed'"
        );

        return (float)($result['total'] ?? 0);
    }

    /**
     * Get total active users
     */
    private function getTotalUsers(): int
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(id) as count FROM users WHERE is_active = 1"
        );

        return (int)($result['count'] ?? 0);
    }

    /**
     * Get total products
     */
    private function getTotalProducts(): int
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(id) as count FROM products"
        );

        return (int)($result['count'] ?? 0);
    }

    /**
     * Get active orders count
     */
    private function getActiveOrders(): int
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(id) as count FROM transactions
             WHERE status IN ('pending', 'processing')"
        );

        return (int)($result['count'] ?? 0);
    }

    /**
     * Calculate conversion rate (users with orders / total users)
     */
    private function getConversionRate(): float
    {
        $totalUsers = $this->getTotalUsers();
        if ($totalUsers === 0) {
            return 0;
        }

        $result = $this->db->selectOne(
            "SELECT COUNT(DISTINCT user_id) as count FROM transactions"
        );

        $usersWithOrders = (int)($result['count'] ?? 0);
        return ($usersWithOrders / $totalUsers) * 100;
    }

    /**
     * Get revenue for today
     */
    private function getRevenueToday(): float
    {
        $result = $this->db->selectOne(
            "SELECT SUM(amount) as total FROM transactions
             WHERE status = 'completed' AND DATE(created_at) = CURDATE()"
        );

        return (float)($result['total'] ?? 0);
    }

    /**
     * Get recent orders
     */
    private function getRecentOrders(int $limit = 5): array
    {
        return $this->db->select(
            "SELECT t.*, u.name, u.email FROM transactions t
             JOIN users u ON t.user_id = u.id
             ORDER BY t.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get top products by views
     */
    private function getTopProducts(int $limit = 5): array
    {
        return $this->db->select(
            "SELECT id, title, viewed_count, price FROM products
             ORDER BY viewed_count DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get dashboard data for chart/analytics
     */
    public function getAnalyticsData(): array
    {
        if (!$this->auth->hasPermission('view_analytics')) {
            AdminMiddleware::deny('Access denied');
        }

        return [
            'sales_by_month' => $this->getSalesByMonth(),
            'user_registration_trend' => $this->getUserRegistrationTrend(),
            'product_views' => $this->getProductViews(),
        ];
    }

    /**
     * Get sales data grouped by month
     */
    private function getSalesByMonth(): array
    {
        return $this->db->select(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                    SUM(amount) as total, COUNT(id) as count
             FROM transactions
             WHERE status = 'completed'
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month DESC
             LIMIT 12"
        );
    }

    /**
     * Get user registration trend
     */
    private function getUserRegistrationTrend(): array
    {
        return $this->db->select(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(id) as count
             FROM users
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month DESC
             LIMIT 12"
        );
    }

    /**
     * Get product view statistics
     */
    private function getProductViews(): array
    {
        return $this->db->select(
            "SELECT id, title, viewed_count
             FROM products
             ORDER BY viewed_count DESC
             LIMIT 10"
        );
    }
}
