<?php
// rollcall-export-full.php - Complete Day Report with ALL students
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');

// Get ALL active students with their attendance
$stmt = $pdo->prepare("
    SELECT s.id, s.full_name, s.student_type, s.dormitory_number, s.bed_number, s.soccer_academy,
           a.morning_status, a.morning_arrival_time, a.morning_notes,
           a.afternoon_status, a.afternoon_departure_time,
           a.final_roll_call_status, a.evening_prayer_attended,
           a.evening_prep_attended, a.lights_out_check,
           a.evening_prayer_notes, a.evening_prep_notes, a.dormitory_notes
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
    WHERE s.status = 'Active'
    ORDER BY s.student_type, s.full_name
");
$stmt->execute([$date]);
$records = $stmt->fetchAll();

// Calculate stats
$total = count($records);
$day_scholars = 0;
$boarders = 0;
$morning_present = 0;
$morning_absent = 0;
$morning_late = 0;
$morning_not_taken = 0;
$departed = 0;
$evening_present = 0;

foreach ($records as $r) {
    if ($r['student_type'] == 'Day Scholar') $day_scholars++;
    else $boarders++;
    
    if (!isset($r['morning_status']) || $r['morning_status'] === null) {
        $morning_not_taken++;
    } elseif ($r['morning_status'] == 'Present') {
        $morning_present++;
    } elseif ($r['morning_status'] == 'Absent') {
        $morning_absent++;
    } elseif ($r['morning_status'] == 'Late') {
        $morning_late++;
    }
    
    if (!empty($r['afternoon_status']) && $r['afternoon_status'] == 'Departed') $departed++;
    if (!empty($r['final_roll_call_status']) && $r['final_roll_call_status'] == 'Present') $evening_present++;
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=full_rollcall_' . $date . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header
fputcsv($output, ['COMPLETE DAY ROLL CALL REPORT - P.5 PURPLE']);
fputcsv($output, ['Date: ' . date('l, F j, Y', strtotime($date))]);
fputcsv($output, []);

// Summary
fputcsv($output, ['SUMMARY STATISTICS']);
fputcsv($output, ['Total Students', $total]);
fputcsv($output, ['Day Scholars', $day_scholars]);
fputcsv($output, ['Boarders', $boarders]);
fputcsv($output, ['Morning Present', $morning_present]);
fputcsv($output, ['Morning Late', $morning_late]);
fputcsv($output, ['Morning Absent', $morning_absent]);
fputcsv($output, ['Morning Not Taken', $morning_not_taken]);
fputcsv($output, ['Departed (Day Scholars)', $departed]);
fputcsv($output, ['Boarders in Dorm (Evening)', $evening_present]);
fputcsv($output, []);

// Detailed Table
fputcsv($output, [
    'Student Name',
    'Type',
    'Dorm/Bed',
    'Morning',
    'Arrival',
    'Afternoon',
    'Departure',
    'Evening',
    'Prayers',
    'Prep',
    'Lights',
    'Notes'
]);

foreach ($records as $r) {
    $type = $r['student_type'] == 'Day Scholar' ? 'Day' : 'Boarder';
    $dorm = $r['student_type'] == 'Boarder' ? ($r['dormitory_number'] . '/' . ($r['bed_number'] ?? '')) : '-';
    
    fputcsv($output, [
        $r['full_name'],
        $type,
        $dorm,
        $r['morning_status'] ?? 'Not Taken',
        !empty($r['morning_arrival_time']) ? date('g:i A', strtotime($r['morning_arrival_time'])) : '-',
        $r['afternoon_status'] ?? '-',
        !empty($r['afternoon_departure_time']) ? date('g:i A', strtotime($r['afternoon_departure_time'])) : '-',
        $r['final_roll_call_status'] ?? '-',
        !empty($r['evening_prayer_attended']) && $r['evening_prayer_attended'] ? 'Yes' : 'No',
        !empty($r['evening_prep_attended']) && $r['evening_prep_attended'] ? 'Yes' : 'No',
        !empty($r['lights_out_check']) && $r['lights_out_check'] ? 'Yes' : 'No',
        $r['morning_notes'] ?? '-'
    ]);
}

fclose($output);
exit;
?>  