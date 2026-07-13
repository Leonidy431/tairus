<!-- Admin Dashboard Content -->
<div class="dashboard-grid">
    <!-- Key Metrics Row 1 -->
    <div class="metric-card">
        <div class="metric-icon metric-sales">💰</div>
        <div class="metric-content">
            <h3>Total Sales</h3>
            <p class="metric-value">$<?php echo number_format($data['total_sales'] ?? 0, 2); ?></p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon metric-users">👥</div>
        <div class="metric-content">
            <h3>Total Users</h3>
            <p class="metric-value"><?php echo number_format($data['total_users'] ?? 0); ?></p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon metric-products">🎨</div>
        <div class="metric-content">
            <h3>Total Products</h3>
            <p class="metric-value"><?php echo number_format($data['total_products'] ?? 0); ?></p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon metric-orders">📦</div>
        <div class="metric-content">
            <h3>Active Orders</h3>
            <p class="metric-value"><?php echo number_format($data['active_orders'] ?? 0); ?></p>
        </div>
    </div>

    <!-- Key Metrics Row 2 -->
    <div class="metric-card">
        <div class="metric-icon metric-conversion">📊</div>
        <div class="metric-content">
            <h3>Conversion Rate</h3>
            <p class="metric-value"><?php echo number_format($data['conversion_rate'] ?? 0, 2); ?>%</p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon metric-revenue">💵</div>
        <div class="metric-content">
            <h3>Revenue Today</h3>
            <p class="metric-value">$<?php echo number_format($data['revenue_today'] ?? 0, 2); ?></p>
        </div>
    </div>
</div>

<!-- Charts and Tables Section -->
<div class="dashboard-section">
    <div class="section-grid">
        <!-- Recent Orders -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2>Recent Orders</h2>
                <a href="?action=admin&view=orders" class="link-text">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($data['recent_orders'])): ?>
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['recent_orders'] as $order): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['name']); ?></td>
                                    <td>$<?php echo number_format($order['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No recent orders</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Products -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2>Top Products</h2>
                <a href="?action=admin&view=products" class="link-text">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($data['top_products'])): ?>
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Views</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['top_products'] as $product): ?>
                                <tr>
                                    <td>
                                        <a href="?action=product&id=<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo number_format($product['viewed_count']); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No products yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="dashboard-section">
    <h2>Quick Actions</h2>
    <div class="action-buttons">
        <?php if (hasPermission('manage_products')): ?>
            <a href="?action=admin&view=products&create=1" class="btn btn-primary">Add Product</a>
        <?php endif; ?>

        <?php if (hasPermission('manage_users')): ?>
            <a href="?action=admin&view=users" class="btn btn-secondary">Manage Users</a>
        <?php endif; ?>

        <?php if (hasPermission('view_logs')): ?>
            <a href="?action=admin&view=logs" class="btn btn-secondary">View Logs</a>
        <?php endif; ?>

        <?php if (hasPermission('view_analytics')): ?>
            <a href="?action=admin&view=analytics" class="btn btn-secondary">View Analytics</a>
        <?php endif; ?>
    </div>
</div>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .metric-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .metric-icon {
        font-size: 2.5em;
        min-width: 60px;
        text-align: center;
    }

    .metric-content h3 {
        font-size: 0.9em;
        color: #666;
        margin: 0 0 5px 0;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .metric-value {
        font-size: 1.8em;
        font-weight: bold;
        color: #333;
        margin: 0;
    }

    .section-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .dashboard-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .card-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header h2 {
        margin: 0;
        font-size: 1.2em;
        color: #333;
    }

    .link-text {
        color: #007bff;
        text-decoration: none;
        font-size: 0.9em;
    }

    .link-text:hover {
        text-decoration: underline;
    }

    .card-body {
        padding: 20px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-condensed th,
    .table-condensed td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 0.9em;
    }

    .table-condensed th {
        font-weight: 600;
        color: #666;
        background: #f9f9f9;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85em;
        font-weight: 600;
    }

    .badge-completed {
        background: #d4edda;
        color: #155724;
    }

    .badge-pending {
        background: #fff3cd;
        color: #856404;
    }

    .badge-processing {
        background: #cfe2ff;
        color: #084298;
    }

    .text-center {
        text-align: center;
        color: #999;
        padding: 20px;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 4px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background: #0056b3;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    @media (prefers-color-scheme: dark) {
        .metric-card,
        .dashboard-card {
            background: #2d2d2d;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .metric-content h3 {
            color: #aaa;
        }

        .metric-value {
            color: #fff;
        }

        .card-header {
            border-bottom-color: #444;
        }

        .card-header h2 {
            color: #fff;
        }

        .table-condensed th {
            background: #333;
            color: #aaa;
        }

        .table-condensed td {
            border-bottom-color: #444;
            color: #ddd;
        }
    }
</style>
