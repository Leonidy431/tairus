<?php
/**
 * Create Test Admin User
 */

$dbPath = __DIR__ . '/storage/database.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create test admin user
    $email = 'admin@test.local';
    $password = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
    $name = 'Test Admin';

    $stmt = $db->prepare("INSERT INTO users (email, password, name, verified_at, is_active)
                         VALUES (?, ?, ?, datetime('now'), 1)");
    $stmt->execute([$email, $password, $name]);

    $userId = $db->lastInsertId();

    // Assign admin role
    $stmt = $db->prepare("SELECT id FROM roles WHERE name = 'admin'");
    $stmt->execute();
    $adminRole = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adminRole) {
        $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->execute([$userId, $adminRole['id']]);
    }

    echo "✅ Test Admin User Created:\n";
    echo "   Email: $email\n";
    echo "   Password: password123\n";
    echo "   ID: $userId\n";

    // Create test regular user
    $email2 = 'user@test.local';
    $password2 = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
    $name2 = 'Test User';

    $stmt = $db->prepare("INSERT INTO users (email, password, name, verified_at, is_active)
                         VALUES (?, ?, ?, datetime('now'), 1)");
    $stmt->execute([$email2, $password2, $name2]);

    $userId2 = $db->lastInsertId();

    // Assign user role
    $stmt = $db->prepare("SELECT id FROM roles WHERE name = 'user'");
    $stmt->execute();
    $userRole = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userRole) {
        $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->execute([$userId2, $userRole['id']]);
    }

    echo "\n✅ Test Regular User Created:\n";
    echo "   Email: $email2\n";
    echo "   Password: password123\n";
    echo "   ID: $userId2\n";

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
        echo "⚠️  Test users already exist\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>
