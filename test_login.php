<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

// Check if users table exists and has data
$users = $pdo->query("SELECT * FROM users")->fetchAll();
echo "<h2>Users in database:</h2>";
echo "<pre>";
print_r($users);
echo "</pre>";

// Test password verification for 'admin'
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    echo "<h3>Admin user found:</h3>";
    echo "Username: " . $admin['username'] . "<br>";
    echo "Password hash: " . $admin['password'] . "<br>";
    
    // Test with password 'admin123'
    if (password_verify('admin123', $admin['password'])) {
        echo "<p style='color:green'>✓ Password 'admin123' works!</p>";
    } else {
        echo "<p style='color:red'>✗ Password 'admin123' does NOT match</p>";
    }
} else {
    echo "<p style='color:red'>No admin user found!</p>";
}
?>