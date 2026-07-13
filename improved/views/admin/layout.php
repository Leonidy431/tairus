<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title'] ?? 'Admin Panel'; ?> - <?php echo $data['app_name']; ?></title>
    <link rel="stylesheet" href="/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.0.0/dist/chart.min.js"></script>
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <h2><?php echo $data['app_name']; ?></h2>
                <p class="sidebar-subtitle">Admin Panel</p>
            </div>

            <nav class="admin-menu">
                <h3 class="menu-title">Main Menu</h3>

                <!-- Dashboard -->
                <?php if (hasPermission('view_dashboard')): ?>
                    <a href="?action=admin&view=dashboard" class="menu-item <?php echo $data['current_view'] === 'dashboard' ? 'active' : ''; ?>">
                        <span class="menu-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                <?php endif; ?>

                <!-- Products Management -->
                <?php if (hasPermission('manage_products')): ?>
                    <a href="?action=admin&view=products" class="menu-item <?php echo $data['current_view'] === 'products' ? 'active' : ''; ?>">
                        <span class="menu-icon">🎨</span>
                        <span>Products</span>
                    </a>
                <?php endif; ?>

                <!-- Orders Management -->
                <?php if (hasPermission('manage_orders')): ?>
                    <a href="?action=admin&view=orders" class="menu-item <?php echo $data['current_view'] === 'orders' ? 'active' : ''; ?>">
                        <span class="menu-icon">📦</span>
                        <span>Orders</span>
                    </a>
                <?php endif; ?>

                <!-- Users Management -->
                <?php if (hasPermission('manage_users')): ?>
                    <a href="?action=admin&view=users" class="menu-item <?php echo $data['current_view'] === 'users' ? 'active' : ''; ?>">
                        <span class="menu-icon">👥</span>
                        <span>Users</span>
                    </a>
                <?php endif; ?>

                <!-- Payments Management -->
                <?php if (hasPermission('manage_payments')): ?>
                    <a href="?action=admin&view=payments" class="menu-item <?php echo $data['current_view'] === 'payments' ? 'active' : ''; ?>">
                        <span class="menu-icon">💳</span>
                        <span>Payments</span>
                    </a>
                <?php endif; ?>

                <!-- Analytics -->
                <?php if (hasPermission('view_analytics')): ?>
                    <a href="?action=admin&view=analytics" class="menu-item <?php echo $data['current_view'] === 'analytics' ? 'active' : ''; ?>">
                        <span class="menu-icon">📈</span>
                        <span>Analytics</span>
                    </a>
                <?php endif; ?>

                <!-- Settings -->
                <?php if (hasPermission('manage_settings')): ?>
                    <a href="?action=admin&view=settings" class="menu-item <?php echo $data['current_view'] === 'settings' ? 'active' : ''; ?>">
                        <span class="menu-icon">⚙️</span>
                        <span>Settings</span>
                    </a>
                <?php endif; ?>

                <!-- Logs -->
                <?php if (hasPermission('view_logs')): ?>
                    <a href="?action=admin&view=logs" class="menu-item <?php echo $data['current_view'] === 'logs' ? 'active' : ''; ?>">
                        <span class="menu-icon">📋</span>
                        <span>Activity Logs</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="user-section">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($data['user_name'] ?? 'Admin'); ?></strong>
                    <small><?php echo implode(', ', $data['user_roles'] ?? []); ?></small>
                </div>
                <a href="?action=logout" class="btn btn-logout">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?php echo $data['title'] ?? 'Admin Panel'; ?></h1>
                </div>
                <div class="header-right">
                    <span class="current-time" id="currentTime"></span>
                </div>
            </header>

            <div class="admin-content">
                <?php if (isset($data['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($data['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($data['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($data['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Content will be rendered here -->
                <?php echo $data['content'] ?? ''; ?>
            </div>
        </main>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString();
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeStr;
            }
        }

        // Update time every second
        setInterval(updateTime, 1000);
        updateTime();

        // Toggle dark mode
        function toggleDarkMode() {
            document.documentElement.setAttribute(
                'data-theme',
                document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'
            );
        }
    </script>
</body>
</html>
