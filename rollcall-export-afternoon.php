<?php
// rollcall-export-afternoon.php - FIXED to show ALL your students
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');

// Get ALL Day Scholars (active only)
$stmt = $pdo->prepare("
    SELECT s.id, s.full_name, s.student_type, s.dormitory_number,
           a.afternoon_status, a.afternoon_departure_time,
           a.evening_prayer_attended, a.evening_prep_attended
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
    WHERE s.status = 'Active' AND s.student_type = 'Day Scholar'
    ORDER BY s.full_name
");
$stmt->execute([$date]);
$day_scholars = $stmt->fetchAll();

// Get ALL Boarders (active only)
$stmt2 = $pdo->prepare("
    SELECT s.id, s.full_name, s.student_type, s.dormitory_number,
           a.afternoon_status, a.evening_prayer_attended, a.evening_prep_attended
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
    WHERE s.status = 'Active' AND s.student_type = 'Boarder'
    ORDER BY s.full_name
");
$stmt2->execute([$date]);
$boarders = $stmt2->fetchAll();

// Calculate stats
$day_departed = 0;
$day_present = 0;
$day_not_taken = 0;
$boarders_present = 0;
$boarders_not_taken = 0;
$prayer_count = 0;
$prep_count = 0;

foreach ($day_scholars as $d) {
    if (!isset($d['afternoon_status']) || $d['afternoon_status'] === null) {
        $day_not_taken++;
    } elseif ($d['afternoon_status'] == 'Departed') {
        $day_departed++;
    } elseif ($d['afternoon_status'] == 'Present') {
        $day_present++;
    }
}

foreach ($boarders as $b) {
    if (!isset($b['afternoon_status']) || $b['afternoon_status'] === null) {
        $boarders_not_taken++;
    } elseif ($b['afternoon_status'] == 'Present') {
        $boarders_present++;
    }
    if (!empty($b['evening_prayer_attended']) && $b['evening_prayer_attended']) $prayer_count++;
    if (!empty($b['evening_prep_attended']) && $b['evening_prep_attended']) $prep_count++;
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=afternoon_rollcall_' . $date . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header
fputcsv($output, ['AFTERNOON ROLL CALL REPORT - P.5 PURPLE']);
fputcsv($output, ['Date: ' . date('l, F j, Y', strtotime($date))]);
fputcsv($output, []);

// Summary
fputcsv($output, ['SUMMARY']);
fputcsv($output, ['Total Day Scholars', count($day_scholars)]);
fputcsv($output, ['Day Scholars Departed', $day_departed]);
fputcsv($output, ['Day Scholars Present', $day_present]);
fputcsv($output, ['Day Scholars Not Taken', $day_not_taken]);
fputcsv($output, ['Total Boarders', count($boarders)]);
fputcsv($output, ['Boarders Present', $boarders_present]);
fputcsv($output, ['Boarders Not Taken', $boarders_not_taken]);
fputcsv($output, ['Attended Prayers', $prayer_count]);
fputcsv($output, ['Attended Prep', $prep_count]);
fputcsv($output, []);

// Day Scholars Section
fputcsv($output, ['DAY SCHOLARS']);
fputcsv($output, [
    'Student Name',
    'Afternoon Status',
    'Departure Time'
]);

foreach ($day_scholars as $d) {
    $departure = !empty($d['afternoon_departure_time']) ? date('g:i A', strtotime($d['afternoon_departure_time'])) : '';
    fputcsv($output, [
        $d['full_name'],
        $d['afternoon_status'] ?? 'Not Taken',
        $departure
    ]);
}

fputcsv($output, []);

// Boarders Section
fputcsv($output, ['BOARDERS - EVENING ACTIVITIES']);
fputcsv($output, [
    'Student Name',
    'Dormitory',
    'Status',
    'Prayers',
    'Prep'
]);

foreach ($boarders as $b) {
    fputcsv($output, [
        $b['full_name'],
        $b['dormitory_number'] ?? 'N/A',
        $b['afternoon_status'] ?? 'Not Taken',
        !empty($b['evening_prayer_attended']) && $b['evening_prayer_attended'] ? 'Yes' : 'No',
        !empty($b['evening_prep_attended']) && $b['evening_prep_attended'] ? 'Yes' : 'No'
    ]);
}

fclose($output);
exit;
?>