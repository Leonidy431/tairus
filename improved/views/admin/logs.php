<!-- Admin Activity Logs View -->
<div class="logs-section">
    <div class="logs-header">
        <h2>Activity Logs</h2>
        <p class="subtitle">Audit trail of all administrative actions</p>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <h3>Filter Logs</h3>
        <form method="GET" action="?action=admin&view=logs" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="admin_filter">Administrator:</label>
                    <select id="admin_filter" name="admin_id" class="form-control">
                        <option value="">All Admins</option>
                        <!-- Options populated from $data['admins'] -->
                        <?php if (!empty($data['admins'])): ?>
                            <?php foreach ($data['admins'] as $admin): ?>
                                <option value="<?php echo $admin['id']; ?>" <?php echo (isset($_GET['admin_id']) && $_GET['admin_id'] == $admin['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="action_filter">Action:</label>
                    <select id="action_filter" name="action" class="form-control">
                        <option value="">All Actions</option>
                        <option value="created" <?php echo (isset($_GET['action']) && $_GET['action'] === 'created') ? 'selected' : ''; ?>>Created</option>
                        <option value="updated" <?php echo (isset($_GET['action']) && $_GET['action'] === 'updated') ? 'selected' : ''; ?>>Updated</option>
                        <option value="deleted" <?php echo (isset($_GET['action']) && $_GET['action'] === 'deleted') ? 'selected' : ''; ?>>Deleted</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="entity_filter">Entity Type:</label>
                    <select id="entity_filter" name="entity_type" class="form-control">
                        <option value="">All Types</option>
                        <option value="product" <?php echo (isset($_GET['entity_type']) && $_GET['entity_type'] === 'product') ? 'selected' : ''; ?>>Product</option>
                        <option value="order" <?php echo (isset($_GET['entity_type']) && $_GET['entity_type'] === 'order') ? 'selected' : ''; ?>>Order</option>
                        <option value="user" <?php echo (isset($_GET['entity_type']) && $_GET['entity_type'] === 'user') ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
            </div>

            <div class="filter-row">
                <div class="filter-group">
                    <label for="date_from">Date From:</label>
                    <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                </div>

                <div class="filter-group">
                    <label for="date_to">Date To:</label>
                    <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                </div>

                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="?action=admin&view=logs" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="logs-table-card">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date & Time</th>
                    <th>Administrator</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data['logs'])): ?>
                    <?php foreach ($data['logs'] as $log): ?>
                        <tr class="log-row">
                            <td>#<?php echo htmlspecialchars($log['id']); ?></td>
                            <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['admin_name']); ?></strong>
                                <br><small><?php echo htmlspecialchars($log['admin_email']); ?></small>
                            </td>
                            <td>
                                <span class="badge badge-action badge-<?php echo htmlspecialchars($log['action']); ?>">
                                    <?php echo ucfirst($log['action']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($log['entity_type']); ?> #<?php echo htmlspecialchars($log['entity_id'] ?? 'N/A'); ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code>
                            </td>
                            <td>
                                <button class="btn-expand" data-log-id="<?php echo $log['id']; ?>" onclick="toggleDetails(<?php echo $log['id']; ?>)">
                                    Show
                                </button>
                            </td>
                        </tr>
                        <!-- Expandable Details Row -->
                        <tr id="details-<?php echo $log['id']; ?>" class="log-details" style="display: none;">
                            <td colspan="7">
                                <div class="details-content">
                                    <?php if (!empty($log['changes'])): ?>
                                        <h4>Changes:</h4>
                                        <pre><?php echo json_encode($log['changes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
                                    <?php else: ?>
                                        <p>No changes recorded</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No activity logs found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (!empty($data['pagination'])): ?>
        <div class="pagination">
            <?php if ($data['pagination']['current_page'] > 1): ?>
                <a href="?action=admin&view=logs&page=1" class="btn btn-small">First</a>
                <a href="?action=admin&view=logs&page=<?php echo $data['pagination']['current_page'] - 1; ?>" class="btn btn-small">Previous</a>
            <?php endif; ?>

            <span class="pagination-info">
                Page <?php echo $data['pagination']['current_page']; ?> of <?php echo $data['pagination']['total_pages']; ?>
            </span>

            <?php if ($data['pagination']['current_page'] < $data['pagination']['total_pages']): ?>
                <a href="?action=admin&view=logs&page=<?php echo $data['pagination']['current_page'] + 1; ?>" class="btn btn-small">Next</a>
                <a href="?action=admin&view=logs&page=<?php echo $data['pagination']['total_pages']; ?>" class="btn btn-small">Last</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .logs-section {
        padding: 20px;
    }

    .logs-header {
        margin-bottom: 30px;
    }

    .logs-header h2 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 1.8em;
    }

    .subtitle {
        color: #666;
        margin: 0;
    }

    .filters-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .filters-card h3 {
        margin-top: 0;
        color: #333;
        font-size: 1.1em;
    }

    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
        font-size: 0.9em;
    }

    .form-control {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9em;
    }

    .logs-table-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .logs-table {
        width: 100%;
        border-collapse: collapse;
    }

    .logs-table th {
        background: #f5f5f5;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #ddd;
        font-size: 0.9em;
    }

    .logs-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 0.9em;
        color: #666;
    }

    .logs-table tr:hover {
        background: #f9f9f9;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8em;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-created {
        background: #d4edda;
        color: #155724;
    }

    .badge-updated {
        background: #cfe2ff;
        color: #084298;
    }

    .badge-deleted {
        background: #f8d7da;
        color: #721c24;
    }

    code {
        background: #f4f4f4;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: monospace;
        font-size: 0.85em;
    }

    .btn-expand {
        background: #007bff;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.8em;
        transition: background 0.2s;
    }

    .btn-expand:hover {
        background: #0056b3;
    }

    .log-details {
        background: #f9f9f9;
    }

    .details-content {
        padding: 15px;
    }

    .details-content h4 {
        margin: 0 0 10px 0;
        color: #333;
    }

    .details-content pre {
        background: white;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 4px;
        overflow-x: auto;
        font-size: 0.8em;
        margin: 0;
    }

    .text-center {
        text-align: center;
        color: #999;
        padding: 30px;
    }

    .btn {
        display: inline-block;
        padding: 8px 16px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.2s;
    }

    .btn-primary {
        background: #007bff;
    }

    .btn-primary:hover {
        background: #0056b3;
    }

    .btn-secondary {
        background: #6c757d;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .btn-small {
        padding: 5px 10px;
        font-size: 0.8em;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
    }

    .pagination-info {
        padding: 8px 16px;
        color: #666;
        font-size: 0.9em;
    }

    @media (prefers-color-scheme: dark) {
        .filters-card,
        .logs-table-card {
            background: #2d2d2d;
        }

        .logs-header h2,
        .filters-card h3,
        .filter-group label,
        .logs-table th {
            color: #fff;
        }

        .subtitle,
        .logs-table td,
        .pagination-info {
            color: #aaa;
        }

        .logs-table tr:hover {
            background: #333;
        }

        .log-details {
            background: #333;
        }

        code {
            background: #333;
            color: #aaa;
        }

        .details-content pre {
            background: #2d2d2d;
            border-color: #444;
            color: #aaa;
        }
    }
</style>

<script>
    function toggleDetails(logId) {
        const detailsRow = document.getElementById(`details-${logId}`);
        const button = document.querySelector(`[data-log-id="${logId}"]`);

        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = 'table-row';
            button.textContent = 'Hide';
        } else {
            detailsRow.style.display = 'none';
            button.textContent = 'Show';
        }
    }
</script>
