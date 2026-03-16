<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing config.php...<br>";

require_once 'includes/config.php';

echo "✓ config.php loaded successfully<br>";
echo "✓ Database connection established<br>";

// Test a simple query
$test = $pdo->query("SELECT 1");
echo "✓ Database query working<br>";

echo "<h2>All good! Config.php is working</h2>";
?>