<?php
// test-export.php - Run this to see what students are in your database
session_start();
require_once 'includes/config.php';

echo "<h2>🔍 DEBUG: Your Students in Database</h2>";

// Count total students
$total = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn();
echo "<p><strong>Total Active Students:</strong> " . $total . "</p>";

// Get all students
$students = $pdo->query("SELECT id, full_name, student_type, status FROM students ORDER BY full_name")->fetchAll();

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Full Name</th><th>Type</th><th>Status</th></tr>";

foreach ($students as $s) {
    echo "<tr>";
    echo "<td>" . $s['id'] . "</td>";
    echo "<td>" . $s['full_name'] . "</td>";
    echo "<td>" . $s['student_type'] . "</td>";
    echo "<td>" . $s['status'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check if attendance table has records for today
$today = date('Y-m-d');
$attendance = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date = ?");
$attendance->execute([$today]);
$att_count = $attendance->fetchColumn();

echo "<p><strong>Attendance records for today ($today):</strong> " . $att_count . "</p>";

// Show the SQL query the export files are using
echo "<h3>SQL Query being used in exports:</h3>";
echo "<pre style='background: #f4f4f4; padding: 10px;'>
SELECT s.full_name, s.student_type, s.dormitory_number,
       a.morning_status, a.morning_arrival_time,
       a.afternoon_status, a.afternoon_departure_time,
       a.final_roll_call_status, a.evening_prayer_attended,
       a.evening_prep_attended, a.lights_out_check
FROM students s
LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
WHERE s.status = 'Active'
ORDER BY s.student_type, s.full_name
</pre>";
?>
<br>
<a href="rollcall-selector.php">⬅ Go back to Roll Call Export</a>