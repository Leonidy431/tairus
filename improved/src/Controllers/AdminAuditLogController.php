<?php
/**
 * Admin Audit Log Controller
 *
 * Handles viewing and filtering activity logs for admin actions.
 * Provides audit trail for all administrative operations.
 */

namespace App\Controllers;

use App\Database\Database;
use App\Middleware\AdminMiddleware;
use App\Security\Sanitizer;

class AdminAuditLogController
{
    private Database $db;
    private AdminMiddleware $auth;

    public function __construct(Database $db, AdminMiddleware $auth)
    {
        $this->db = $db;
        $this->auth = $auth;

        if (!$this->auth->hasPermission('view_logs')) {
            AdminMiddleware::deny('Access denied: insufficient permissions');
        }
    }

    /**
     * Get paginated activity logs
     */
    public function getLogs(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT aal.*, u.name as admin_name, u.email as admin_email
                  FROM admin_activity_logs aal
                  JOIN users u ON aal.admin_id = u.id
                  WHERE 1=1";
        $bindings = [];

        if (!empty($filters['admin_id'])) {
            $query .= " AND aal.admin_id = ?";
            $bindings[] = $filters['admin_id'];
        }

        if (!empty($filters['action'])) {
            $query .= " AND aal.action = ?";
            $bindings[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $query .= " AND aal.entity_type = ?";
            $bindings[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(aal.created_at) >= ?";
            $bindings[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(aal.created_at) <= ?";
            $bindings[] = $filters['date_to'];
        }

        $query .= " ORDER BY aal.created_at DESC LIMIT ? OFFSET ?";
        $bindings[] = $perPage;
        $bindings[] = $offset;

        $logs = $this->db->select($query, $bindings);

        foreach ($logs as &$log) {
            if (!empty($log['changes'])) {
                $log['changes'] = json_decode($log['changes'], true);
            }
        }

        return $logs;
    }

    /**
     * Get total count of logs with filters
     */
    public function getLogsCount(array $filters = []): int
    {
        $query = "SELECT COUNT(id) as count FROM admin_activity_logs WHERE 1=1";
        $bindings = [];

        if (!empty($filters['admin_id'])) {
            $query .= " AND admin_id = ?";
            $bindings[] = $filters['admin_id'];
        }

        if (!empty($filters['action'])) {
            $query .= " AND action = ?";
            $bindings[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $query .= " AND entity_type = ?";
            $bindings[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(created_at) >= ?";
            $bindings[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(created_at) <= ?";
            $bindings[] = $filters['date_to'];
        }

        $result = $this->db->selectOne($query, $bindings);
        return $result['count'] ?? 0;
    }

    /**
     * Get single log entry
     */
    public function getLog(int $logId): ?array
    {
        $log = $this->db->selectOne(
            "SELECT aal.*, u.name as admin_name, u.email as admin_email
             FROM admin_activity_logs aal
             JOIN users u ON aal.admin_id = u.id
             WHERE aal.id = ?",
            [$logId]
        );

        if ($log && !empty($log['changes'])) {
            $log['changes'] = json_decode($log['changes'], true);
        }

        return $log;
    }

    /**
     * Get logs by user
     */
    public function getUserLogs(int $userId, int $limit = 10): array
    {
        return $this->db->select(
            "SELECT * FROM admin_activity_logs
             WHERE admin_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Get logs by entity type
     */
    public function getEntityLogs(string $entityType, int $entityId = null, int $limit = 10): array
    {
        if ($entityId === null) {
            return $this->db->select(
                "SELECT * FROM admin_activity_logs
                 WHERE entity_type = ?
                 ORDER BY created_at DESC
                 LIMIT ?",
                [$entityType, $limit]
            );
        }

        return $this->db->select(
            "SELECT * FROM admin_activity_logs
             WHERE entity_type = ? AND entity_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$entityType, $entityId, $limit]
        );
    }

    /**
     * Get available actions for filter dropdown
     */
    public function getActions(): array
    {
        $actions = $this->db->select(
            "SELECT DISTINCT action FROM admin_activity_logs ORDER BY action"
        );

        return array_map(fn($a) => $a['action'], $actions);
    }

    /**
     * Get available entity types for filter dropdown
     */
    public function getEntityTypes(): array
    {
        $types = $this->db->select(
            "SELECT DISTINCT entity_type FROM admin_activity_logs
             WHERE entity_type IS NOT NULL
             ORDER BY entity_type"
        );

        return array_map(fn($t) => $t['entity_type'], $types);
    }

    /**
     * Export logs as CSV
     */
    public function exportAsCSV(array $filters = []): string
    {
        $logs = $this->db->select(
            "SELECT aal.*, u.name as admin_name FROM admin_activity_logs aal
             JOIN users u ON aal.admin_id = u.id
             WHERE 1=1 ORDER BY aal.created_at DESC"
        );

        $csv = "ID,Admin,Action,Entity Type,Entity ID,IP Address,Date\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s\n",
                $log['id'],
                $log['admin_name'],
                $log['action'],
                $log['entity_type'] ?? '',
                $log['entity_id'] ?? '',
                $log['ip_address'],
                $log['created_at']
            );
        }

        return $csv;
    }

    /**
     * Delete old logs (older than specified days)
     */
    public function cleanupOldLogs(int $olderThanDays = 90): int
    {
        $date = date('Y-m-d', strtotime("-{$olderThanDays} days"));

        $result = $this->db->delete(
            'admin_activity_logs',
            "created_at < '{$date}'"
        );

        return $result ? 1 : 0;
    }
}
