<?php
/**
 * Database Initialization Script
 * Creates SQLite database with all required tables
 */

$dbPath = __DIR__ . '/storage/database.sqlite';

// Create/connect to database
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔧 Initializing database at: $dbPath\n\n";

    // Users table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        name TEXT NOT NULL,
        phone TEXT,
        verified_at TIMESTAMP,
        email_verification_token TEXT,
        token_expires_at TIMESTAMP,
        address TEXT,
        city TEXT,
        country TEXT,
        postal_code TEXT,
        avatar_url TEXT,
        bio TEXT,
        phone_verified INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        last_login TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ users table created\n";

    // Roles table
    $db->exec("CREATE TABLE IF NOT EXISTS roles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ roles table created\n";

    // Insert default roles
    try {
        $db->exec("INSERT INTO roles (name, description) VALUES
            ('admin', 'Administrator with full access'),
            ('moderator', 'Moderator with limited access'),
            ('user', 'Regular user')");
        echo "✅ Default roles inserted\n";
    } catch (Exception $e) {
        echo "⚠️  Roles already exist\n";
    }

    // Permissions table
    $db->exec("CREATE TABLE IF NOT EXISTS permissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ permissions table created\n";

    // Role permissions junction
    $db->exec("CREATE TABLE IF NOT EXISTS role_permissions (
        role_id INTEGER NOT NULL,
        permission_id INTEGER NOT NULL,
        PRIMARY KEY (role_id, permission_id),
        FOREIGN KEY (role_id) REFERENCES roles(id),
        FOREIGN KEY (permission_id) REFERENCES permissions(id)
    )");
    echo "✅ role_permissions table created\n";

    // User roles junction
    $db->exec("CREATE TABLE IF NOT EXISTS user_roles (
        user_id INTEGER NOT NULL,
        role_id INTEGER NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (role_id) REFERENCES roles(id)
    )");
    echo "✅ user_roles table created\n";

    // Login attempts table
    $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        ip_address TEXT,
        success INTEGER DEFAULT 0,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    echo "✅ login_attempts table created\n";

    // Remember me tokens
    $db->exec("CREATE TABLE IF NOT EXISTS remember_me_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT UNIQUE NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    echo "✅ remember_me_tokens table created\n";

    // Login history
    $db->exec("CREATE TABLE IF NOT EXISTS login_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        ip_address TEXT,
        user_agent TEXT,
        login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    echo "✅ login_history table created\n";

    // Email verification tokens
    $db->exec("CREATE TABLE IF NOT EXISTS email_verification_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT UNIQUE NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    echo "✅ email_verification_tokens table created\n";

    // Payment methods
    $db->exec("CREATE TABLE IF NOT EXISTS payment_methods (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        stripe_payment_method_id TEXT UNIQUE NOT NULL,
        type TEXT,
        last4 TEXT,
        exp_month INTEGER,
        exp_year INTEGER,
        is_default INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    echo "✅ payment_methods table created\n";

    // Transactions
    $db->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        order_id INTEGER,
        amount DECIMAL(10, 2) NOT NULL,
        currency TEXT DEFAULT 'USD',
        stripe_transaction_id TEXT UNIQUE,
        status TEXT DEFAULT 'pending',
        payment_method_id INTEGER,
        error_message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
    )");
    echo "✅ transactions table created\n";

    // Payment logs
    $db->exec("CREATE TABLE IF NOT EXISTS payment_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        transaction_id INTEGER NOT NULL,
        event TEXT,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (transaction_id) REFERENCES transactions(id)
    )");
    echo "✅ payment_logs table created\n";

    // Activity logs
    $db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        admin_id INTEGER NOT NULL,
        action TEXT,
        entity_type TEXT,
        entity_id INTEGER,
        changes TEXT,
        ip_address TEXT,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id)
    )");
    echo "✅ activity_logs table created\n";

    // Products table
    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        price DECIMAL(10, 2),
        category TEXT,
        subcategory TEXT,
        is_public INTEGER DEFAULT 1,
        is_featured INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ products table created\n";

    // News table
    $db->exec("CREATE TABLE IF NOT EXISTS news (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT,
        is_public INTEGER DEFAULT 1,
        is_subscribed INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ news table created\n";

    // Subscriptions
    $db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        is_subscribed INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ subscriptions table created\n";

    // Shopping Cart Table
    $db->exec("CREATE TABLE IF NOT EXISTS carts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        product_id INTEGER NOT NULL,
        quantity INTEGER DEFAULT 1,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
    echo "✅ carts table created\n";

    // Cart Sessions Table (for guest users)
    $db->exec("CREATE TABLE IF NOT EXISTS cart_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT UNIQUE NOT NULL,
        cart_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ cart_sessions table created\n";

    // Product Uploads/Images Table
    $db->exec("CREATE TABLE IF NOT EXISTS uploads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        original_filename TEXT NOT NULL,
        file_path TEXT NOT NULL,
        thumbnail_path TEXT,
        medium_path TEXT,
        large_path TEXT,
        webp_path TEXT,
        webp_medium_path TEXT,
        file_size INTEGER,
        mime_type TEXT,
        is_primary INTEGER DEFAULT 0,
        alt_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
    echo "✅ uploads table created\n";

    echo "\n✨ Database initialized successfully!\n";
    echo "📁 Database file: $dbPath\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
