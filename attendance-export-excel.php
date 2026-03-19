<?php
// attendance-export-excel.php - FIXED to use correct column names
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');

// FIXED: Using correct column name 'date' not 'attendance_date'
$stmt = $pdo->prepare("
    SELECT s.full_name, s.student_type, 
           a.morning_status, a.morning_arrival_time,
           a.afternoon_status, a.afternoon_departure_time,
           a.evening_prayer_attended, a.evening_prep_attended,
           a.final_roll_call_status, a.morning_notes
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.date = ?
    ORDER BY s.student_type, s.full_name
");
$stmt->execute([$date]);
$attendance = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance_' . $date . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Student Name',
    'Type',
    'Morning Status',
    'Arrival Time',
    'Afternoon Status',
    'Departure Time',
    'Prayers',
    'Prep',
    'Final Status',
    'Notes'
]);

// Add data rows
foreach ($attendance as $row) {
    fputcsv($output, [
        $row['full_name'],
        $row['student_type'],
        $row['morning_status'] ?? 'Not Taken',
        $row['morning_arrival_time'] ?? '',
        $row['afternoon_status'] ?? '',
        $row['afternoon_departure_time'] ?? '',
        $row['evening_prayer_attended'] ? 'Yes' : 'No',
        $row['evening_prep_attended'] ? 'Yes' : 'No',
        $row['final_roll_call_status'] ?? '',
        $row['morning_notes'] ?? ''
    ]);
}

fclose($output);
exit;
?>