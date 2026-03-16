<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing error reporting...<br>";

require_once 'includes/config.php';
echo "Config loaded successfully!<br>";

// Test database connection
$test = $pdo->query("SELECT 1");
echo "Database connected!<br>";

phpinfo();
?>