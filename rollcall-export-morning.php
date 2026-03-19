<?php
// rollcall-export-morning.php - FIXED to show ALL your 52 students
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');

// FIXED: Get ALL active students with LEFT JOIN to include those without attendance
$stmt = $pdo->prepare("
    SELECT s.id, s.full_name, s.student_type, s.dormitory_number,
           a.morning_status, a.morning_arrival_time, a.morning_notes
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
    WHERE s.status = 'Active'
    ORDER BY s.student_type, s.full_name
");
$stmt->execute([$date]);
$records = $stmt->fetchAll();

// Calculate stats from actual records
$present = 0;
$absent = 0;
$late = 0;
$not_taken = 0;

foreach ($records as $r) {
    if (!isset($r['morning_status']) || $r['morning_status'] === null) {
        $not_taken++;
    } elseif ($r['morning_status'] == 'Present') {
        $present++;
    } elseif ($r['morning_status'] == 'Absent') {
        $absent++;
    } elseif ($r['morning_status'] == 'Late') {
        $late++;
    }
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=morning_rollcall_' . $date . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add report header
fputcsv($output, ['MORNING ROLL CALL REPORT - P.5 PURPLE']);
fputcsv($output, ['Date: ' . date('l, F j, Y', strtotime($date))]);
fputcsv($output, []);

// Add summary with YOUR ACTUAL student counts (52 students)
fputcsv($output, ['SUMMARY']);
fputcsv($output, ['Total Students', count($records)]);
fputcsv($output, ['Present', $present]);
fputcsv($output, ['Late', $late]);
fputcsv($output, ['Absent', $absent]);
fputcsv($output, ['Not Taken', $not_taken]);
fputcsv($output, []);

// Add headers
fputcsv($output, [
    'Student Name',
    'Type',
    'Dormitory',
    'Morning Status',
    'Arrival Time',
    'Notes'
]);

// Add data rows - this will include ALL 52 students
foreach ($records as $row) {
    $status = $row['morning_status'] ?? 'Not Taken';
    $arrival = !empty($row['morning_arrival_time']) ? date('g:i A', strtotime($row['morning_arrival_time'])) : '';
    
    fputcsv($output, [
        $row['full_name'],
        $row['student_type'],
        $row['dormitory_number'] ?? 'N/A',
        $status,
        $arrival,
        $row['morning_notes'] ?? ''
    ]);
}

fclose($output);
exit;
?>