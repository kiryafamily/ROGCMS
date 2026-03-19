<?php
// rollcall-export.php - Simple Roll Call Export
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');

// Get ALL active students with attendance
$stmt = $pdo->prepare("
    SELECT s.full_name, s.student_type, s.dormitory_number,
           a.morning_status, a.morning_arrival_time,
           a.afternoon_status, a.afternoon_departure_time,
           a.final_roll_call_status, a.evening_prayer_attended,
           a.evening_prep_attended, a.lights_out_check
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
    WHERE s.status = 'Active'
    ORDER BY s.student_type, s.full_name
");
$stmt->execute([$date]);
$records = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=rollcall_' . $date . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Student Name',
    'Type',
    'Dormitory',
    'Morning Status',
    'Arrival Time',
    'Afternoon Status',
    'Departure Time',
    'Evening Status',
    'Prayers',
    'Prep',
    'Lights Out'
]);

// Add data rows - ALL 52 students
foreach ($records as $row) {
    fputcsv($output, [
        $row['full_name'],
        $row['student_type'],
        $row['dormitory_number'] ?? 'N/A',
        $row['morning_status'] ?? 'Not Taken',
        !empty($row['morning_arrival_time']) ? date('g:i A', strtotime($row['morning_arrival_time'])) : '',
        $row['afternoon_status'] ?? '',
        !empty($row['afternoon_departure_time']) ? date('g:i A', strtotime($row['afternoon_departure_time'])) : '',
        $row['final_roll_call_status'] ?? '',
        !empty($row['evening_prayer_attended']) && $row['evening_prayer_attended'] ? 'Yes' : 'No',
        !empty($row['evening_prep_attended']) && $row['evening_prep_attended'] ? 'Yes' : 'No',
        !empty($row['lights_out_check']) && $row['lights_out_check'] ? 'Yes' : 'No'
    ]);
}

fclose($output);
exit;   