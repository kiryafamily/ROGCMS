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

// Get teacher profile (for header)
$stmt = $pdo->query("SELECT * FROM teacher_profile WHERE id = 1");
$teacher = $stmt->fetch();
if (!$teacher) {
    $pdo->exec("INSERT INTO teacher_profile (teacher_name, teacher_title) VALUES ('Mr. Kirya Amos', 'Class Teacher P.5 Purple')");
    $stmt = $pdo->query("SELECT * FROM teacher_profile WHERE id = 1");
    $teacher = $stmt->fetch();
}

// For navbar, we need recent students (optional) – provide fallback
$recent_students = $pdo->query("SELECT id FROM students WHERE status = 'Active' ORDER BY id DESC LIMIT 1")->fetchAll();

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

        /* ========== MOBILE TOP NAVIGATION - STICKY ========== */
        .mobile-top-nav {
            display: none;
            background-color: #4B1C3C;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(75,28,60,0.2);
            width: 100%;
            position: sticky;
            top: 10px;
            z-index: 1000;
            transition: box-shadow 0.3s ease;
        }

        .mobile-top-nav.scrolled {
            box-shadow: 0 6px 16px rgba(75,28,60,0.3);
        }

        .mobile-nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            color: white;
            cursor: pointer;
        }

        .mobile-nav-header .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .mobile-nav-header .logo i {
            color: #FFB800;
            font-size: 1.4rem;
        }

        .mobile-nav-header .menu-icon {
            color: #FFB800;
            font-size: 1.8rem;
            transition: transform 0.3s;
        }

        .mobile-nav-header.active .menu-icon {
            transform: rotate(90deg);
        }

        .mobile-nav-dropdown {
            display: none;
            background-color: white;
            border-radius: 0 0 15px 15px;
            padding: 15px;
            border-top: 3px solid #FFB800;
            max-height: 70vh;
            overflow-y: auto;
        }

        .mobile-nav-dropdown.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mobile-menu-item {
            border-bottom: 1px solid #f0e0f0;
        }

        .mobile-menu-item:last-child {
            border-bottom: none;
        }

        .mobile-menu-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 12px;
            color: #4B1C3C;
            font-weight: 500;
            cursor: pointer;
            background-color: #faf5fa;
            border-radius: 10px;
            margin: 8px 0;
        }

        .mobile-menu-header i:first-child {
            color: #FFB800;
            width: 24px;
            font-size: 1.1rem;
        }

        .mobile-menu-header span {
            flex: 1;
            margin-left: 8px;
        }

        .mobile-menu-header .fa-chevron-down {
            color: #4B1C3C;
            transition: transform 0.3s;
        }

        .mobile-submenu {
            display: none;
            background-color: white;
            padding: 5px 0 10px 0;
        }

        .mobile-submenu.show {
            display: block;
        }

        .mobile-submenu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 12px 12px 44px;
            color: #4B1C3C;
            text-decoration: none;
            font-size: 0.9rem;
            border-radius: 8px;
            margin: 2px 0;
        }

        .mobile-submenu a i {
            color: #FFB800;
            width: 20px;
        }

        .mobile-submenu a:hover {
            background-color: #f5eaf5;
        }

        .mobile-quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #FFB800;
        }

        .mobile-quick-action {
            background-color: #4B1C3C;
            color: white;
            text-decoration: none;
            padding: 12px 8px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            text-align: center;
        }

        .mobile-quick-action i {
            color: #FFB800;
            font-size: 1.2rem;
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
        @media (max-width: 1024px) {
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

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .premium-container {
                padding: 10px;
                padding-top: 5px;
            }
            
            /* Hide desktop navigation on mobile */
            .main-nav {
                display: none;
            }
            
            /* Show mobile navigation at the top - sticky */
            .mobile-top-nav {
                display: block;
                margin-bottom: 15px;
                position: sticky;
                top: 10px;
                z-index: 1000;
            }
            
            /* Header appears below mobile navigation */
            .premium-header {
                margin-bottom: 15px;
                padding: 20px;
            }

            .class-title h1 {
                font-size: 1.8rem;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .class-badge {
                justify-content: center;
            }

            .teacher-legend {
                padding: 15px;
            }

            .legend-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .timetable-container {
                padding: 10px;
            }

            .time-cell {
                position: static;
            }

            .timetable th {
                padding: 12px 4px;
                font-size: 0.9rem;
            }

            .timetable td {
                padding: 8px 4px;
            }

            .subject-code {
                font-size: 1rem;
            }

            .teacher-name {
                font-size: 0.7rem;
            }

            .note-section {
                padding: 15px;
            }

            .note-section p {
                font-size: 1rem;
            }

            .mobile-quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .class-title h1 {
                font-size: 1.5rem;
            }

            .btn-premium {
                padding: 8px 16px;
                font-size: 0.85rem;
            }

            .timetable th {
                font-size: 0.8rem;
                padding: 8px 2px;
            }

            .timetable td {
                padding: 6px 2px;
            }

            .subject-code {
                font-size: 0.9rem;
            }

            .time-range {
                font-size: 0.8rem;
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

        /* Desktop Navigation (for consistency) */
        .main-nav {
            background-color: #ffffff;
            border-radius: 50px;
            padding: 8px 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(75,28,60,0.1);
            border: 1px solid #e0d0e0;
        }

        .nav-menu {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            list-style: none;
            justify-content: center;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            color: #4B1C3C;
            text-decoration: none;
            font-weight: 500;
            border-radius: 30px;
            transition: all 0.2s;
        }

        .nav-link i {
            color: #FFB800;
            font-size: 1rem;
        }

        .nav-link:hover {
            background-color: #4B1C3C;
            color: white;
        }

        .nav-link:hover i {
            color: #FFB800;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: white;
            min-width: 200px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(75,28,60,0.15);
            border: 1px solid #FFB800;
            z-index: 100;
            padding: 8px 0;
        }

        .nav-item:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            color: #4B1C3C;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .dropdown-content a i {
            color: #FFB800;
            width: 20px;
        }

        .dropdown-content a:hover {
            background-color: #f5eaf5;
        }

        .dropdown-content hr {
            border: none;
            border-top: 1px dashed #FFB800;
            margin: 5px 0;
        }

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
</head>
<body>
    <div class="premium-container">
        <!-- MOBILE TOP NAVIGATION - STICKY AT THE VERY TOP ON MOBILE -->
        <div class="mobile-top-nav">
            <div class="mobile-nav-header" id="mobileNavHeader">
                <div class="logo">
                    <i class="fas fa-crown"></i>
                    <span>Navigation Menu</span>
                </div>
                <i class="fas fa-bars menu-icon" id="menuIcon"></i>
            </div>
            
            <div class="mobile-nav-dropdown" id="mobileNavDropdown">
                <!-- Attendance -->
                <div class="mobile-menu-item">
                    <div class="mobile-menu-header" onclick="toggleMobileSubmenu(this)">
                        <i class="fas fa-check-circle"></i>
                        <span>Attendance</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="mobile-submenu">
                        <a href="attendance.php"><i class="fas fa-sun"></i> Morning Roll Call</a>
                        <a href="attendance.php?type=afternoon"><i class="fas fa-cloud-sun"></i> Afternoon Roll Call</a>
                        <a href="attendance.php?type=evening"><i class="fas fa-moon"></i> Evening Roll Call</a>
                        <a href="attendance-reports.php"><i class="fas fa-chart-line"></i> Reports & Export</a>
                    </div>
                </div>
                
                <!-- Students -->
                <div class="mobile-menu-item">
                    <div class="mobile-menu-header" onclick="toggleMobileSubmenu(this)">
                        <i class="fas fa-users"></i>
                        <span>Students</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="mobile-submenu">
                        <a href="students.php"><i class="fas fa-list"></i> View All Students</a>
                        <a href="add-student.php"><i class="fas fa-user-plus"></i> Add New Student</a>
                        <a href="upload-student-photo.php"><i class="fas fa-camera"></i> Upload Photos</a>
                        <a href="student-profile.php?id=<?php echo $recent_students[0]['id'] ?? ''; ?>"><i class="fas fa-id-card"></i> View Profile</a>
                    </div>
                </div>
                
                <!-- Marks -->
                <div class="mobile-menu-item">
                    <div class="mobile-menu-header" onclick="toggleMobileSubmenu(this)">
                        <i class="fas fa-pencil-alt"></i>
                        <span>Marks</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="mobile-submenu">
                        <a href="assessments.php"><i class="fas fa-table"></i> Marksheet</a>
                        <a href="report-selector.php"><i class="fas fa-file-pdf"></i> Report Cards</a>
                    </div>
                </div>
                
                <!-- Communication -->
                <div class="mobile-menu-item">
                    <div class="mobile-menu-header" onclick="toggleMobileSubmenu(this)">
                        <i class="fas fa-comments"></i>
                        <span>Communication</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="mobile-submenu">
                        <a href="communication.php"><i class="fas fa-comments"></i> Parent Hub</a>
                        <a href="sms-broadcast.php"><i class="fas fa-bullhorn"></i> Bulk SMS</a>
                    </div>
                </div>
                
                <!-- Reports -->
                <div class="mobile-menu-item">
                    <div class="mobile-menu-header" onclick="toggleMobileSubmenu(this)">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="mobile-submenu">
                        <a href="attendance-reports.php"><i class="fas fa-calendar-check"></i> Attendance Reports</a>
                        <a href="assessment-reports.php"><i class="fas fa-chart-line"></i> Performance Analysis</a>
                        <a href="behavior-reports.php"><i class="fas fa-smile"></i> Behavior Logs</a>
                    </div>
                </div>
                
                <!-- Settings -->
                <div class="mobile-menu-item">
                    <div class="mobile-menu-header" onclick="toggleMobileSubmenu(this)">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="mobile-submenu">
                        <a href="timetable.php"><i class="fas fa-clock"></i> Timetable</a>
                        <a href="public-holidays.php"><i class="fas fa-calendar-day"></i> Public Holidays</a>
                        <a href="teacher-profile.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Profile</a>
                        <a href="school-info.php"><i class="fas fa-school"></i> School Info</a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mobile-quick-actions">
                    <a href="attendance.php" class="mobile-quick-action">
                        <i class="fas fa-check-circle"></i>
                        <span>Take Attendance</span>
                    </a>
                    <a href="add-student.php" class="mobile-quick-action">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Student</span>
                    </a>
                    <a href="assessments.php" class="mobile-quick-action">
                        <i class="fas fa-pencil-alt"></i>
                        <span>Enter Marks</span>
                    </a>
                    <a href="logout.php" class="mobile-quick-action">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

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

        <!-- DESKTOP NAVIGATION (hidden on mobile) -->
        <nav class="main-nav">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="return false;">
                        <i class="fas fa-check-circle"></i> Attendance <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="attendance.php"><i class="fas fa-sun"></i> Morning Roll Call</a>
                        <a href="attendance.php?type=afternoon"><i class="fas fa-cloud-sun"></i> Afternoon Roll Call</a>
                        <a href="attendance.php?type=evening"><i class="fas fa-moon"></i> Evening Roll Call</a>
                        <hr>
                        <a href="attendance-reports.php"><i class="fas fa-chart-line"></i> Reports & Export</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="return false;">
                        <i class="fas fa-users"></i> Students <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="students.php"><i class="fas fa-list"></i> View All Students</a>
                        <a href="add-student.php"><i class="fas fa-user-plus"></i> Add New Student</a>
                        <a href="upload-student-photo.php"><i class="fas fa-camera"></i> Upload Photos</a>
                        <a href="student-profile.php?id=<?php echo $recent_students[0]['id'] ?? ''; ?>"><i class="fas fa-id-card"></i> View Profile</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="return false;">
                        <i class="fas fa-pencil-alt"></i> Marks <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="assessments.php"><i class="fas fa-table"></i> Marksheet</a>
                        <a href="report-selector.php"><i class="fas fa-file-pdf"></i> Report Cards</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="return false;">
                        <i class="fas fa-comments"></i> Communication <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="communication.php"><i class="fas fa-comments"></i> Parent Hub</a>
                        <a href="sms-broadcast.php"><i class="fas fa-bullhorn"></i> Bulk SMS</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="return false;">
                        <i class="fas fa-chart-bar"></i> Reports <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="attendance-reports.php"><i class="fas fa-calendar-check"></i> Attendance Reports</a>
                        <a href="assessment-reports.php"><i class="fas fa-chart-line"></i> Performance Analysis</a>
                        <a href="behavior-reports.php"><i class="fas fa-smile"></i> Behavior Logs</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="return false;">
                        <i class="fas fa-cog"></i> Settings <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="timetable.php"><i class="fas fa-clock"></i> Timetable</a>
                        <a href="public-holidays.php"><i class="fas fa-calendar-day"></i> Public Holidays</a>
                        <a href="teacher-profile.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Profile</a>
                        <a href="school-info.php"><i class="fas fa-school"></i> School Info</a>
                    </div>
                </li>
            </ul>
        </nav>

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

    <script>
        // ========== MOBILE TOP NAVIGATION ==========
        const mobileNavHeader = document.getElementById('mobileNavHeader');
        const mobileNavDropdown = document.getElementById('mobileNavDropdown');
        const menuIcon = document.getElementById('menuIcon');

        if (mobileNavHeader && mobileNavDropdown && menuIcon) {
            mobileNavHeader.addEventListener('click', function(e) {
                e.stopPropagation();
                mobileNavDropdown.classList.toggle('show');
                this.classList.toggle('active');
                
                if (mobileNavDropdown.classList.contains('show')) {
                    menuIcon.classList.remove('fa-bars');
                    menuIcon.classList.add('fa-times');
                } else {
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                }
            });
        }

        // Mobile submenu toggle
        function toggleMobileSubmenu(header) {
            const submenu = header.nextElementSibling;
            const chevron = header.querySelector('.fa-chevron-down');
            
            if (submenu && chevron) {
                submenu.classList.toggle('show');
                
                if (submenu.classList.contains('show')) {
                    chevron.style.transform = 'rotate(180deg)';
                } else {
                    chevron.style.transform = '';
                }
            }
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (mobileNavHeader && mobileNavDropdown && menuIcon) {
                if (!mobileNavHeader.contains(event.target) && !mobileNavDropdown.contains(event.target)) {
                    mobileNavDropdown.classList.remove('show');
                    mobileNavHeader.classList.remove('active');
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                }
            }
        });

        // Prevent closing when clicking inside dropdown
        if (mobileNavDropdown) {
            mobileNavDropdown.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        }

        // Add scroll effect to sticky nav
        window.addEventListener('scroll', function() {
            const mobileNav = document.querySelector('.mobile-top-nav');
            if (mobileNav) {
                if (window.scrollY > 10) {
                    mobileNav.classList.add('scrolled');
                } else {
                    mobileNav.classList.remove('scrolled');
                }
            }
        });
    </script>

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
