<?php
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Teacher name mappings based on your document
$teacher_names = [
    'Mr. Rogers' => 'Mr. Kiibi Rogers',
    'Mr. Amos' => 'Mr. Kirya Amos',
    'Miss Grace' => 'Miss Ageo Grace',
    'Mr. Opio' => 'Mr. Opio Emmanuel',
    'Mr. Musa' => 'Mr. Musa',
    'Mr. Ronald' => 'Mr. Ronald',
    'Mr. Dean' => 'Mr. Dean',
    'Mr. Nelson' => 'Mr. Nelson'
];

// Complete timetable data from your document
$timetable_data = [
    'Monday' => [
        '6:00-7:20' => ['activity' => 'Morning Prep', 'subject' => 'MTC', 'teacher' => 'Mr. Musa'],
        '7:20-7:50' => ['activity' => 'Morning Tea', 'subject' => 'TEA', 'teacher' => ''],
        '7:50-8:30' => ['activity' => 'Assembly', 'subject' => 'ASS', 'teacher' => ''],
        '8:30-9:30' => ['activity' => 'Period 1', 'subject' => 'MTC', 'teacher' => 'Mr. Rogers'],
        '9:30-10:30' => ['activity' => 'Period 2', 'subject' => 'SCI', 'teacher' => 'Mr. Amos'],
        '10:30-11:00' => ['activity' => 'Break Time', 'subject' => 'BREAK', 'teacher' => ''],
        '11:00-12:00' => ['activity' => 'Period 3', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '12:00-1:00' => ['activity' => 'Period 4', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '1:00-1:40' => ['activity' => 'Lunch Break', 'subject' => 'LUNCH', 'teacher' => ''],
        '1:40-2:50' => ['activity' => 'Period 5', 'subject' => 'COMP', 'teacher' => 'Mr. Nelson'],
        '2:50-3:40' => ['activity' => 'Period 6', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '3:40-4:00' => ['activity' => 'Homework', 'subject' => 'MTC', 'teacher' => 'Mr. Rogers'],
        '4:00-4:20' => ['activity' => 'Cleaning', 'subject' => 'CLEAN', 'teacher' => ''],
        '4:20-5:20' => ['activity' => 'Music, Dance & Drama / Games', 'subject' => 'MDD', 'teacher' => ''],
        '5:20-6:00' => ['activity' => 'Personal Admin (Washing)', 'subject' => 'ADMIN', 'teacher' => ''],
        '6:00-6:30' => ['activity' => 'Prayers', 'subject' => 'PRAY', 'teacher' => ''],
        '6:30-7:30' => ['activity' => 'Evening Prep', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '7:30-8:00' => ['activity' => 'Supper', 'subject' => 'SUPPER', 'teacher' => ''],
        '8:00-9:00' => ['activity' => 'Night Prep', 'subject' => 'PREP', 'teacher' => ''],
        '9:30' => ['activity' => 'Lights Out', 'subject' => 'LIGHTS', 'teacher' => '']
    ],
    'Tuesday' => [
        '6:00-7:20' => ['activity' => 'Morning Prep', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '7:20-7:50' => ['activity' => 'Morning Tea', 'subject' => 'TEA', 'teacher' => ''],
        '7:50-8:30' => ['activity' => 'Period', 'subject' => 'MTC', 'teacher' => 'Mr. Rogers'],
        '8:30-9:30' => ['activity' => 'Period 1', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '9:30-10:30' => ['activity' => 'Period 2', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '10:30-11:00' => ['activity' => 'Break Time', 'subject' => 'BREAK', 'teacher' => ''],
        '11:00-12:00' => ['activity' => 'Period 3', 'subject' => 'SCI', 'teacher' => 'Mr. Amos'],
        '12:00-1:00' => ['activity' => 'Period 4', 'subject' => 'R.E', 'teacher' => 'Mr. Opio'],
        '1:00-1:40' => ['activity' => 'Lunch Break', 'subject' => 'LUNCH', 'teacher' => ''],
        '1:40-2:50' => ['activity' => 'Period 5', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '2:50-3:40' => ['activity' => 'Period 6', 'subject' => 'MTC', 'teacher' => 'Mr. Rogers'],
        '3:40-4:00' => ['activity' => 'Homework', 'subject' => 'SCI', 'teacher' => 'Mr. Amos'],
        '4:00-4:20' => ['activity' => 'Cleaning', 'subject' => 'CLEAN', 'teacher' => ''],
        '4:20-5:20' => ['activity' => 'Music, Dance & Drama / Games', 'subject' => 'MDD', 'teacher' => ''],
        '5:20-6:00' => ['activity' => 'Personal Admin (Washing)', 'subject' => 'ADMIN', 'teacher' => ''],
        '6:00-6:30' => ['activity' => 'Prayers', 'subject' => 'PRAY', 'teacher' => ''],
        '6:30-7:30' => ['activity' => 'Evening Prep', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '7:30-8:00' => ['activity' => 'Supper', 'subject' => 'SUPPER', 'teacher' => ''],
        '8:00-9:00' => ['activity' => 'Night Prep', 'subject' => 'PREP', 'teacher' => ''],
        '9:30' => ['activity' => 'Lights Out', 'subject' => 'LIGHTS', 'teacher' => '']
    ],
    'Wednesday' => [
        '6:00-7:20' => ['activity' => 'Morning Prep', 'subject' => 'MTC', 'teacher' => 'Mr. Rogers'],
        '7:20-7:50' => ['activity' => 'Morning Tea', 'subject' => 'TEA', 'teacher' => ''],
        '7:50-8:30' => ['activity' => 'Assembly', 'subject' => 'ASS', 'teacher' => ''],
        '8:30-9:30' => ['activity' => 'Period 1', 'subject' => 'SCI', 'teacher' => 'Mr. Amos'],
        '9:30-10:30' => ['activity' => 'Period 2', 'subject' => 'MTC', 'teacher' => 'Mr. Rogers'],
        '10:30-11:00' => ['activity' => 'Break Time', 'subject' => 'BREAK', 'teacher' => ''],
        '11:00-12:00' => ['activity' => 'Period 3', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '12:00-1:00' => ['activity' => 'Period 4', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '1:00-1:40' => ['activity' => 'Lunch Break', 'subject' => 'LUNCH', 'teacher' => ''],
        '1:40-2:50' => ['activity' => 'Period 5', 'subject' => 'SCI', 'teacher' => 'Mr. Amos'],
        '2:50-3:40' => ['activity' => 'Period 6', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '3:40-4:00' => ['activity' => 'Homework', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '4:00-4:20' => ['activity' => 'Cleaning', 'subject' => 'CLEAN', 'teacher' => ''],
        '4:20-5:20' => ['activity' => 'Music, Dance & Drama / Games', 'subject' => 'MDD', 'teacher' => ''],
        '5:20-6:00' => ['activity' => 'Personal Admin (Washing)', 'subject' => 'ADMIN', 'teacher' => ''],
        '6:00-6:30' => ['activity' => 'Prayers', 'subject' => 'PRAY', 'teacher' => ''],
        '6:30-7:30' => ['activity' => 'Evening Prep', 'subject' => 'SCI', 'teacher' => 'Mr. Amos'],
        '7:30-8:00' => ['activity' => 'Supper', 'subject' => 'SUPPER', 'teacher' => ''],
        '8:00-9:00' => ['activity' => 'Night Prep', 'subject' => 'PREP', 'teacher' => ''],
        '9:30' => ['activity' => 'Lights Out', 'subject' => 'LIGHTS', 'teacher' => '']
    ],
    'Thursday' => [
        '6:00-7:20' => ['activity' => 'Morning Prep', 'subject' => 'SCI', 'teacher' => 'Mr. Ronald'],
        '7:20-7:50' => ['activity' => 'Morning Tea', 'subject' => 'TEA', 'teacher' => ''],
        '7:50-8:30' => ['activity' => 'Period', 'subject' => 'P.E', 'teacher' => ''],
        '8:30-9:30' => ['activity' => 'Period 1', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '9:30-10:30' => ['activity' => 'Period 2', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '10:30-11:00' => ['activity' => 'Break Time', 'subject' => 'BREAK', 'teacher' => ''],
        '11:00-12:00' => ['activity' => 'Period 3', 'subject' => 'MTC', 'teacher' => 'Mr. Rogers'],
        '12:00-1:00' => ['activity' => 'Period 4', 'subject' => 'MUSIC', 'teacher' => 'Mr. Dean'],
        '1:00-1:40' => ['activity' => 'Lunch Break', 'subject' => 'LUNCH', 'teacher' => ''],
        '1:40-2:50' => ['activity' => 'Period 5', 'subject' => 'SCI', 'teacher' => 'Mr. Amos'],
        '2:50-3:40' => ['activity' => 'Period 6', 'subject' => 'R.E', 'teacher' => 'Mr. Opio'],
        '3:40-4:00' => ['activity' => 'Homework', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '4:00-4:20' => ['activity' => 'Cleaning', 'subject' => 'CLEAN', 'teacher' => ''],
        '4:20-5:20' => ['activity' => 'Music, Dance & Drama / Games', 'subject' => 'MDD', 'teacher' => ''],
        '5:20-6:00' => ['activity' => 'Personal Admin (Washing)', 'subject' => 'ADMIN', 'teacher' => ''],
        '6:00-6:30' => ['activity' => 'Prayers', 'subject' => 'PRAY', 'teacher' => ''],
        '6:30-7:30' => ['activity' => 'Evening Prep', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '7:30-8:00' => ['activity' => 'Supper', 'subject' => 'SUPPER', 'teacher' => ''],
        '8:00-9:00' => ['activity' => 'Night Prep', 'subject' => 'PREP', 'teacher' => ''],
        '9:30' => ['activity' => 'Lights Out', 'subject' => 'LIGHTS', 'teacher' => '']
    ],
    'Friday' => [
        '6:00-7:20' => ['activity' => 'Morning Prep', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '7:20-7:50' => ['activity' => 'Morning Tea', 'subject' => 'TEA', 'teacher' => ''],
        '7:50-8:30' => ['activity' => 'Period', 'subject' => 'SCI', 'teacher' => 'Mr. Amos'],
        '8:30-9:30' => ['activity' => 'Period 1', 'subject' => 'MTC', 'teacher' => 'Mr. Rogers'],
        '9:30-10:30' => ['activity' => 'Period 2', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '10:30-11:00' => ['activity' => 'Break Time', 'subject' => 'BREAK', 'teacher' => ''],
        '11:00-12:00' => ['activity' => 'Period 3', 'subject' => 'MUSIC', 'teacher' => 'Mr. Dean'],
        '12:00-1:00' => ['activity' => 'Period 4', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '1:00-1:40' => ['activity' => 'Lunch Break', 'subject' => 'LUNCH', 'teacher' => ''],
        '1:40-3:40' => ['activity' => 'Period 5/6', 'subject' => 'DEBATE/QUIZ', 'teacher' => ''],
        '3:40-4:00' => ['activity' => 'Homework', 'subject' => 'R.E', 'teacher' => 'Mr. Opio'],
        '4:00-4:20' => ['activity' => 'Cleaning', 'subject' => 'CLEAN', 'teacher' => ''],
        '4:20-5:20' => ['activity' => 'Music, Dance & Drama / Games', 'subject' => 'MDD', 'teacher' => ''],
        '5:20-6:00' => ['activity' => 'Personal Admin (Washing)', 'subject' => 'ADMIN', 'teacher' => ''],
        '6:00-6:30' => ['activity' => 'Prayers', 'subject' => 'PRAY', 'teacher' => ''],
        '6:30-7:30' => ['activity' => 'Evening Prep', 'subject' => 'MTC', 'teacher' => 'Mr. Rogers'],
        '7:30-8:00' => ['activity' => 'Supper', 'subject' => 'SUPPER', 'teacher' => ''],
        '8:00-9:00' => ['activity' => 'Night Prep', 'subject' => 'PREP', 'teacher' => ''],
        '9:30' => ['activity' => 'Lights Out', 'subject' => 'LIGHTS', 'teacher' => '']
    ],
    'Saturday' => [
        '6:00-7:20' => ['activity' => 'Morning Prep', 'subject' => 'WASHING', 'teacher' => ''],
        '7:20-7:50' => ['activity' => 'Morning Tea', 'subject' => 'TEA', 'teacher' => ''],
        '7:50-8:30' => ['activity' => 'Period', 'subject' => 'CLEANING', 'teacher' => ''],
        '8:30-9:30' => ['activity' => 'Period 1', 'subject' => 'SST', 'teacher' => 'Mr. Opio'],
        '9:30-10:30' => ['activity' => 'Period 2', 'subject' => 'SCI', 'teacher' => 'Mr. Amos'],
        '10:30-11:00' => ['activity' => 'Break Time', 'subject' => 'BREAK', 'teacher' => ''],
        '11:00-12:00' => ['activity' => 'Period 3', 'subject' => 'MTC', 'teacher' => 'Mr. Rogers'],
        '12:00-1:00' => ['activity' => 'Period 4', 'subject' => 'ENG', 'teacher' => 'Miss Grace'],
        '1:00-1:40' => ['activity' => 'Lunch Break', 'subject' => 'LUNCH', 'teacher' => ''],
        '1:40-5:20' => ['activity' => 'Free Activity', 'subject' => 'FREE', 'teacher' => ''],
        '5:20-6:00' => ['activity' => 'Personal Admin (Washing)', 'subject' => 'ADMIN', 'teacher' => ''],
        '6:00-6:30' => ['activity' => 'Prayers', 'subject' => 'PRAY', 'teacher' => ''],
        '6:30-7:30' => ['activity' => 'Evening Prep', 'subject' => 'PREP', 'teacher' => ''],
        '7:30-8:00' => ['activity' => 'Supper', 'subject' => 'SUPPER', 'teacher' => ''],
        '8:00-9:00' => ['activity' => 'Night Prep', 'subject' => 'PREP', 'teacher' => ''],
        '9:30' => ['activity' => 'Lights Out', 'subject' => 'LIGHTS', 'teacher' => '']
    ]
];

// Define all time slots in order
$time_slots = [
    '6:00-7:20', '7:20-7:50', '7:50-8:30', '8:30-9:30', '9:30-10:30',
    '10:30-11:00', '11:00-12:00', '12:00-1:00', '1:00-1:40', '1:40-2:50',
    '2:50-3:40', '3:40-4:00', '4:00-4:20', '4:20-5:20', '5:20-6:00',
    '6:00-6:30', '6:30-7:30', '7:30-8:00', '8:00-9:00', '9:30'
];

// Subject full names for tooltips
$subject_names = [
    'MTC' => 'Mathematics',
    'ENG' => 'English',
    'SCI' => 'Science',
    'SST' => 'Social Studies',
    'R.E' => 'Religious Education',
    'COMP' => 'Computer',
    'P.E' => 'Physical Education',
    'MUSIC' => 'Music',
    'ASS' => 'Assembly',
    'BREAK' => 'Break Time',
    'LUNCH' => 'Lunch Break',
    'CLEAN' => 'Cleaning',
    'MDD' => 'Music, Dance & Drama',
    'ADMIN' => 'Personal Administration',
    'PRAY' => 'Prayers',
    'PREP' => 'Prep Time',
    'SUPPER' => 'Supper',
    'LIGHTS' => 'Lights Out',
    'DEBATE/QUIZ' => 'Debate & Quiz',
    'FREE' => 'Free Activity',
    'WASHING' => 'Washing Time',
    'TEA' => 'Morning Tea'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P.5 Purple Timetable 2026 - Rays of Grace Junior School</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --purple: #4a1a3a;
            --purple-dark: #2f1224;
            --purple-light: #6a2b52;
            --orange: #ef5b2b;
            --orange-dark: #cf3b0b;
            --orange-light: #ff7b4b;
            --off-white: #f8f8f6;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #eeeeee;
            --gray-300: #e0e0e0;
            --gray-400: #bdbdbd;
            --gray-500: #9e9e9e;
            --gray-600: #757575;
            --gray-700: #616161;
            --gray-800: #424242;
            --gray-900: #212121;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
            --shadow-sm: 0 2px 4px rgba(74, 26, 58, 0.08);
            --shadow-md: 0 4px 8px rgba(74, 26, 58, 0.12);
            --shadow-lg: 0 8px 16px rgba(74, 26, 58, 0.16);
            --shadow-hover: 0 12px 24px rgba(239, 91, 43, 0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: linear-gradient(135deg, var(--off-white) 0%, #ffffff 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .premium-container {
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Premium Header */
        .premium-header {
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            border-radius: 20px;
            padding: 30px 40px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .premium-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .class-title h1 {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .class-title i {
            color: var(--orange);
            margin-right: 12px;
        }

        .class-slogan {
            color: var(--orange-light);
            font-size: 1rem;
            font-weight: 500;
            background: rgba(0,0,0,0.2);
            padding: 6px 12px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            width: fit-content;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .class-badge {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-premium {
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 12px 24px;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .btn-premium:hover {
            background: rgba(239, 91, 43, 0.3);
            border-color: var(--orange);
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .btn-premium i {
            color: var(--orange);
        }

        /* Teacher Legend */
        .teacher-legend {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(74, 26, 58, 0.1);
        }

        .legend-title {
            color: var(--purple-dark);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .legend-title i {
            color: var(--orange);
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: var(--gray-50);
            border-radius: 8px;
            border-left: 3px solid var(--orange);
        }

        .legend-name {
            color: var(--gray-700);
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Timetable Container */
        .timetable-container {
            overflow-x: auto;
            margin: 20px 0 40px;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            background: white;
            padding: 20px;
        }

        .timetable {
            width: 100%;
            border-collapse: collapse;
            min-width: 1400px;
        }

        .timetable th {
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            color: white;
            padding: 16px 8px;
            font-weight: 600;
            font-size: 1rem;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .timetable th:first-child {
            border-top-left-radius: 12px;
        }

        .timetable th:last-child {
            border-top-right-radius: 12px;
        }

        .timetable td {
            border: 1px solid var(--gray-200);
            padding: 12px 6px;
            text-align: center;
            vertical-align: middle;
            transition: var(--transition);
        }

        .timetable tr:hover td {
            background: var(--gray-50);
        }

        .time-cell {
            background: var(--gray-100);
            font-weight: 600;
            color: var(--purple-dark);
            width: 100px;
            position: sticky;
            left: 0;
            z-index: 5;
        }

        .time-range {
            font-size: 0.9rem;
            font-weight: 700;
        }

        .activity-label {
            font-size: 0.7rem;
            color: var(--gray-500);
            margin-top: 2px;
        }

        .subject-code {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--purple-dark);
        }

        .teacher-name {
            font-size: 0.8rem;
            color: var(--orange);
            font-weight: 600;
            margin-top: 2px;
        }

        /* Color-coded cells */
        .break-cell {
            background: #fff3e0;
        }
        .lunch-cell {
            background: #e8f5e9;
        }
        .assembly-cell {
            background: #f3e5f5;
        }
        .cleaning-cell {
            background: #e1f5fe;
        }
        .prayer-cell {
            background: #e0f2f1;
        }
        .prep-cell {
            background: #fff8e1;
        }
        .mdd-cell {
            background: #fce4ec;
        }
        .admin-cell {
            background: #e8eaf6;
        }
        .supper-cell {
            background: #f1f8e9;
        }
        .lights-cell {
            background: #212121;
            color: white;
        }
        .weekend-cell {
            background: #f5f5f5;
        }

        /* Note Section */
        .note-section {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            border-radius: 16px;
            padding: 20px;
            margin: 30px 0;
            border-left: 6px solid var(--orange);
            box-shadow: var(--shadow-md);
        }

        .note-section i {
            color: var(--orange);
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .note-section p {
            color: var(--purple-dark);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .premium-header {
                padding: 20px;
            }

            .class-title h1 {
                font-size: 2rem;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .class-slogan {
                margin: 0 auto;
            }

            .legend-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-200);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--purple), var(--orange));
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--purple-dark), var(--orange-dark));
        }

        /* Tooltip */
        [data-tooltip] {
            position: relative;
            cursor: help;
        }

        [data-tooltip]:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 4px 8px;
            background: var(--purple-dark);
            color: white;
            font-size: 0.75rem;
            border-radius: 4px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 1000;
        }

        [data-tooltip]:hover:before {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="premium-container">
        <!-- Header -->
        <div class="premium-header no-print">
            <div class="header-content">
                <div class="class-title">
                    <h1>
                        <i class="fas fa-calendar-alt"></i> 
                        P.5 PURPLE TIMETABLE 2026
                    </h1>
                    <div class="class-slogan">
                        <i class="fas fa-star"></i>
                        Rays of Grace Junior School
                    </div>
                </div>
                <div class="class-badge">
                    <button class="btn-premium" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Timetable
                    </button>
                    <a href="index.php" class="btn-premium">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Teacher Legend -->
        <div class="teacher-legend no-print">
            <div class="legend-title">
                <i class="fas fa-chalkboard-teacher"></i>
                Subject Teachers
            </div>
            <div class="legend-grid">
                <div class="legend-item">
                    <span class="legend-name">👨‍🏫 Mr. Kirya Amos (K.A)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-name">👨‍🏫 Mr. Kiibi Rogers (K.R)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-name">👩‍🏫 Miss Ageo Grace (A.G)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-name">👨‍🏫 Mr. Opio Emmanuel (O.E)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-name">👨‍🏫 Mr. Musa</span>
                </div>
                <div class="legend-item">
                    <span class="legend-name">👨‍🏫 Mr. Ronald</span>
                </div>
                <div class="legend-item">
                    <span class="legend-name">👨‍🏫 Mr. Dean</span>
                </div>
                <div class="legend-item">
                    <span class="legend-name">👨‍🏫 Mr. Nelson</span>
                </div>
            </div>
        </div>

        <!-- Timetable -->
        <div class="timetable-container">
            <table class="timetable">
                <thead>
                    <tr>
                        <th>TIME</th>
                        <th>MONDAY</th>
                        <th>TUESDAY</th>
                        <th>WEDNESDAY</th>
                        <th>THURSDAY</th>
                        <th>FRIDAY</th>
                        <th>SATURDAY</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $time): 
                        // Determine cell class based on activity
                        $getCellClass = function($subject) {
                            if (strpos($subject, 'BREAK') !== false) return 'break-cell';
                            if (strpos($subject, 'LUNCH') !== false) return 'lunch-cell';
                            if (strpos($subject, 'ASS') !== false) return 'assembly-cell';
                            if (strpos($subject, 'CLEAN') !== false) return 'cleaning-cell';
                            if (strpos($subject, 'PRAY') !== false) return 'prayer-cell';
                            if (strpos($subject, 'PREP') !== false) return 'prep-cell';
                            if (strpos($subject, 'MDD') !== false) return 'mdd-cell';
                            if (strpos($subject, 'ADMIN') !== false || strpos($subject, 'WASHING') !== false) return 'admin-cell';
                            if (strpos($subject, 'SUPPER') !== false) return 'supper-cell';
                            if (strpos($subject, 'LIGHTS') !== false) return 'lights-cell';
                            return '';
                        };
                    ?>
                    <tr>
                        <td class="time-cell">
                            <div class="time-range"><?php echo $time; ?></div>
                            <?php if ($time == '6:00-7:20'): ?>
                                <div class="activity-label">Morning Prep</div>
                            <?php elseif ($time == '7:20-7:50'): ?>
                                <div class="activity-label">Morning Tea</div>
                            <?php elseif ($time == '10:30-11:00'): ?>
                                <div class="activity-label">Break</div>
                            <?php elseif ($time == '1:00-1:40'): ?>
                                <div class="activity-label">Lunch</div>
                            <?php elseif ($time == '3:40-4:00'): ?>
                                <div class="activity-label">Homework</div>
                            <?php elseif ($time == '4:00-4:20'): ?>
                                <div class="activity-label">Cleaning</div>
                            <?php elseif ($time == '4:20-5:20'): ?>
                                <div class="activity-label">MDD/Games</div>
                            <?php elseif ($time == '5:20-6:00'): ?>
                                <div class="activity-label">Personal Admin</div>
                            <?php elseif ($time == '6:00-6:30'): ?>
                                <div class="activity-label">Prayers</div>
                            <?php elseif ($time == '6:30-7:30'): ?>
                                <div class="activity-label">Evening Prep</div>
                            <?php elseif ($time == '7:30-8:00'): ?>
                                <div class="activity-label">Supper</div>
                            <?php elseif ($time == '8:00-9:00'): ?>
                                <div class="activity-label">Night Prep</div>
                            <?php elseif ($time == '9:30'): ?>
                                <div class="activity-label">Lights Out</div>
                            <?php endif; ?>
                        </td>
                        
                        <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day): 
                            $slot = $timetable_data[$day][$time] ?? ['subject' => '—', 'teacher' => ''];
                            $subject = $slot['subject'];
                            $teacher = $slot['teacher'];
                            $full_subject = $subject_names[$subject] ?? $subject;
                            $cell_class = $getCellClass($subject);
                        ?>
                        <td class="<?php echo $cell_class; ?>" data-tooltip="<?php echo $full_subject; ?>">
                            <div class="subject-code"><?php echo $subject; ?></div>
                            <?php if ($teacher): ?>
                                <div class="teacher-name"><?php echo $teacher; ?></div>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Important Note -->
        <div class="note-section no-print">
            <p>
                <i class="fas fa-exclamation-circle"></i>
                PLEASE MANAGE TIME PROMPTLY FOR ALL YOUR LESSONS.
            </p>
        </div>

        <!-- Print Version Note -->
        <div class="print-only" style="display: none; text-align: center; margin-top: 30px; font-weight: 600; color: var(--purple-dark);">
            <p>PLEASE MANAGE TIME PROMPTLY FOR ALL YOUR LESSONS.</p>
        </div>

        <!-- Daily Schedule Summary -->
        <div style="margin-top: 40px; padding: 25px; background: white; border-radius: 16px; box-shadow: var(--shadow-md); border: 1px solid rgba(74,26,58,0.1);">
            <h3 style="color: var(--purple-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-clock" style="color: var(--orange);"></i>
                Daily Routine Summary
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <div style="padding: 12px; background: var(--gray-50); border-radius: 8px; border-left: 3px solid var(--orange);">
                    <strong>🌅 Morning (6:00am - 7:50am):</strong> Prep → Tea → Assembly
                </div>
                <div style="padding: 12px; background: var(--gray-50); border-radius: 8px; border-left: 3px solid var(--orange);">
                    <strong>📚 Lessons (8:30am - 1:00pm):</strong> 4 Periods + Break
                </div>
                <div style="padding: 12px; background: var(--gray-50); border-radius: 8px; border-left: 3px solid var(--orange);">
                    <strong>🍽️ Afternoon (1:00pm - 4:00pm):</strong> Lunch → 2 Periods → Homework
                </div>
                <div style="padding: 12px; background: var(--gray-50); border-radius: 8px; border-left: 3px solid var(--orange);">
                    <strong>🧹 Evening (4:00pm - 6:30pm):</strong> Cleaning → MDD/Games → Admin → Prayers
                </div>
                <div style="padding: 12px; background: var(--gray-50); border-radius: 8px; border-left: 3px solid var(--orange);">
                    <strong>🌙 Night (6:30pm - 9:30pm):</strong> Evening Prep → Supper → Night Prep → Lights Out
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            body {
                background: white;
                padding: 10px;
            }
            .timetable-container {
                box-shadow: none;
                padding: 0;
            }
            .timetable th {
                background: #4a1a3a !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .break-cell, .lunch-cell, .assembly-cell, .cleaning-cell, 
            .prayer-cell, .prep-cell, .mdd-cell, .admin-cell, .supper-cell {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</body>
</html> 