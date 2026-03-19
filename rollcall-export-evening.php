<?php
// rollcall-export-evening.php - FIXED for Boarders only
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');

// Get ALL Boarders (active only)
$stmt = $pdo->prepare("
    SELECT s.id, s.full_name, s.dormitory_number, s.bed_number,
           a.final_roll_call_status, a.evening_prayer_attended,
           a.evening_prep_attended, a.lights_out_check,
           a.evening_prayer_notes, a.evening_prep_notes, a.dormitory_notes
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
    WHERE s.status = 'Active' AND s.student_type = 'Boarder'
    ORDER BY s.dormitory_number, s.full_name
");
$stmt->execute([$date]);
$boarders = $stmt->fetchAll();

// Calculate stats
$total_boarders = count($boarders);
$in_dorm = 0;
$not_taken = 0;
$prayer_count = 0;
$prep_count = 0;
$lights_out = 0;

foreach ($boarders as $b) {
    if (!isset($b['final_roll_call_status']) || $b['final_roll_call_status'] === null) {
        $not_taken++;
    } elseif ($b['final_roll_call_status'] == 'Present') {
        $in_dorm++;
    }
    if (!empty($b['evening_prayer_attended']) && $b['evening_prayer_attended']) $prayer_count++;
    if (!empty($b['evening_prep_attended']) && $b['evening_prep_attended']) $prep_count++;
    if (!empty($b['lights_out_check']) && $b['lights_out_check']) $lights_out++;
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=evening_rollcall_' . $date . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header
fputcsv($output, ['EVENING PREP ROLL CALL REPORT - P.5 PURPLE']);
fputcsv($output, ['Date: ' . date('l, F j, Y', strtotime($date))]);
fputcsv($output, []);

// Summary
fputcsv($output, ['SUMMARY']);
fputcsv($output, ['Total Boarders', $total_boarders]);
fputcsv($output, ['In Dormitory', $in_dorm]);
fputcsv($output, ['Not Taken', $not_taken]);
fputcsv($output, ['Attended Prayers', $prayer_count]);
fputcsv($output, ['Attended Prep', $prep_count]);
fputcsv($output, ['Lights Out Checked', $lights_out]);
fputcsv($output, []);

// Main Table
fputcsv($output, [
    'Student Name',
    'Dormitory',
    'Bed',
    'Final Status',
    'Prayers',
    'Prep',
    'Lights Out',
    'Notes'
]);

foreach ($boarders as $b) {
    $notes = trim(($b['evening_prayer_notes'] ?? '') . ' ' . ($b['evening_prep_notes'] ?? '') . ' ' . ($b['dormitory_notes'] ?? ''));
    
    fputcsv($output, [
        $b['full_name'],
        $b['dormitory_number'] ?? 'N/A',
        $b['bed_number'] ?? 'N/A',
        $b['final_roll_call_status'] ?? 'Not Taken',
        !empty($b['evening_prayer_attended']) && $b['evening_prayer_attended'] ? 'Yes' : 'No',
        !empty($b['evening_prep_attended']) && $b['evening_prep_attended'] ? 'Yes' : 'No',
        !empty($b['lights_out_check']) && $b['lights_out_check'] ? 'Yes' : 'No',
        $notes ?: '-'
    ]);
}

fclose($output);
exit;
?>