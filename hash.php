<?php
// hash.php - DELETE AFTER USE!
echo "Password 'admin123' hash: " . password_hash('admin123', PASSWORD_DEFAULT) . "<br>";
echo "Password 'teacher123' hash: " . password_hash('teacher123', PASSWORD_DEFAULT);
?>