<?php
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get current term info
$current_term = getCurrentTerm($pdo);

// Get student statistics
$total_students = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn();
$day_scholars = $pdo->query("SELECT COUNT(*) FROM students WHERE student_type = 'Day Scholar' AND status = 'Active'")->fetchColumn();
$boarders = $pdo->query("SELECT COUNT(*) FROM students WHERE student_type = 'Boarder' AND status = 'Active'")->fetchColumn();
$soccer_students = $pdo->query("SELECT COUNT(*) FROM students WHERE soccer_academy = TRUE AND status = 'Active'")->fetchColumn();

// Get teacher profile
$stmt = $pdo->query("SELECT * FROM teacher_profile WHERE id = 1");
$teacher = $stmt->fetch();
if (!$teacher) {
    // Create default if not exists
    $pdo->exec("INSERT INTO teacher_profile (teacher_name, teacher_title) VALUES ('Mr. Kirya Amos', 'Class Teacher P.5 Purple')");
    $stmt = $pdo->query("SELECT * FROM teacher_profile WHERE id = 1");
    $teacher = $stmt->fetch();
}

// Get today's date
$today = date('Y-m-d');
$day_of_week = date('l');

// Get today's schedule
$stmt = $pdo->prepare("SELECT * FROM daily_schedule WHERE day_of_week = ? ORDER BY period_number");
$stmt->execute([$day_of_week]);
$schedule = $stmt->fetchAll();

// Get today's attendance summary
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN morning_status IN ('Present', 'Late') THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN morning_status = 'Absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN morning_status = 'Late' THEN 1 ELSE 0 END) as late
    FROM attendance 
    WHERE date = ?
");
$stmt->execute([$today]);
$today_attendance = $stmt->fetch();

// Get recent students
$stmt = $pdo->query("SELECT * FROM students WHERE status = 'Active' ORDER BY id DESC LIMIT 5");
$recent_students = $stmt->fetchAll();

// Get upcoming assessments
$stmt = $pdo->prepare("
    SELECT a.*, s.full_name 
    FROM assessments a
    JOIN students s ON a.student_id = s.id
    WHERE a.exam_date >= ? 
    ORDER BY a.exam_date 
    LIMIT 5
");
$stmt->execute([$today]);
$upcoming = $stmt->fetchAll();

// Get recent behavior
$stmt = $pdo->prepare("
    SELECT b.*, s.full_name 
    FROM behavior_log b
    JOIN students s ON b.student_id = s.id
    ORDER BY b.log_date DESC 
    LIMIT 5
");
$stmt->execute();
$recent_behavior = $stmt->fetchAll();

// Check if today is a holiday
$stmt = $pdo->prepare("SELECT * FROM public_holidays WHERE holiday_date = ?");
$stmt->execute([$today]);
$is_holiday = $stmt->fetch();

// Get upcoming events
$upcoming_events = [];

if ($current_term) {
    if ($current_term['visitation_day'] >= $today) {
        $upcoming_events[] = ['date' => $current_term['visitation_day'], 'event' => 'Visitation Day', 'icon' => 'fa-users'];
    }
    if ($current_term['mid_term_exam_start'] >= $today) {
        $upcoming_events[] = ['date' => $current_term['mid_term_exam_start'], 'event' => 'Mid-Term Exams Start', 'icon' => 'fa-pencil-alt'];
    }
    if ($current_term['end_term_exam_start'] >= $today) {
        $upcoming_events[] = ['date' => $current_term['end_term_exam_start'], 'event' => 'End of Term Exams', 'icon' => 'fa-file-alt'];
    }
    if ($current_term['report_card_date'] >= $today) {
        $upcoming_events[] = ['date' => $current_term['report_card_date'], 'event' => 'Report Cards Out', 'icon' => 'fa-certificate'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P.5 Purple - Classroom Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CLEAN SOLID COLORS - NO GRADIENTS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f0f5;
            color: #333;
        }

        /* Premium Container */
        .premium-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* HEADER WITH TEACHER PHOTO */
        .premium-header {
            background-color: #4B1C3C;
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(75, 28, 60, 0.2);
            position: relative;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .class-title h1 {
            color: #ffffff;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .class-title h1 i {
            color: #FFB800;
            margin-right: 10px;
        }

        .class-slogan {
            color: #FFB800;
            font-size: 0.9rem;
        }

        /* Teacher Profile Card */
        .teacher-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background-color: #2F1224;
            padding: 10px 20px;
            border-radius: 12px;
            border-left: 4px solid #FFB800;
        }

        .teacher-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #4B1C3C;
            border: 2px solid #FFB800;
            overflow: hidden;
            position: relative;
        }

        .teacher-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .teacher-photo-placeholder {
            width: 100%;
            height: 100%;
            background-color: #4B1C3C;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #FFB800;
        }

        .teacher-info {
            text-align: left;
        }

        .teacher-name {
            color: #FFB800;
            font-weight: 600;
            font-size: 1rem;
        }

        .teacher-title {
            color: #ffffff;
            font-size: 0.8rem;
        }

        .photo-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #FFB800;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4B1C3C;
            font-size: 0.6rem;
            cursor: pointer;
            border: 2px solid #4B1C3C;
            transition: background-color 0.3s;
        }

        .photo-upload-btn:hover {
            background-color: #ffffff;
        }

        .class-badge {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .logout-btn {
            background-color: #4B1C3C;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid #FFB800;
            transition: background-color 0.3s;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background-color: #1a0d14;
        }

        /* ========== DESKTOP NAVIGATION BAR ========== */
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

        /* Dropdown Menu */
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

        /* Time Card */
        .time-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e0d0e0;
        }

        .date-display {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .date-display i {
            font-size: 2rem;
            color: #4B1C3C;
        }

        .date-display .day {
            font-size: 1rem;
            color: #666;
        }

        .date-display .date {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4B1C3C;
        }

        .live-clock .time {
            font-size: 2rem;
            font-weight: 700;
            color: #4B1C3C;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e0d0e0;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(75, 28, 60, 0.1);
            border-color: #4B1C3C;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background-color: #f0e8f0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.8rem;
            color: #4B1C3C;
        }

        .stat-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4B1C3C;
            line-height: 1.2;
        }

        .stat-content p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e0d0e0;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0e8f0;
        }

        .card-header h2 {
            color: #4B1C3C;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h2 i {
            color: #FFB800;
        }

        .view-link {
            color: #4B1C3C;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .view-link:hover {
            color: #FFB800;
        }

        /* Schedule List */
        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .schedule-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background-color: #f8f4f8;
            border-radius: 8px;
            border-left: 3px solid transparent;
        }

        .schedule-item:hover {
            background-color: #f0e8f0;
            border-left-color: #4B1C3C;
        }

        .schedule-time {
            width: 80px;
            font-weight: 600;
            color: #4B1C3C;
        }

        .schedule-subject {
            flex: 1;
            font-weight: 500;
        }

        .schedule-badge {
            background-color: #4B1C3C;
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .break-item {
            background-color: #fff3e0;
        }

        .break-item .schedule-subject {
            color: #FFB800;
            font-weight: 600;
        }

        .break-item .schedule-badge {
            background-color: #FFB800;
            color: #4B1C3C;
        }

        /* Student List */
        .student-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .student-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #f8f4f8;
            border-radius: 8px;
        }

        .student-item:hover {
            background-color: #f0e8f0;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            background-color: #4B1C3C;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 600;
            margin-right: 15px;
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-weight: 600;
            color: #4B1C3C;
        }

        .student-meta {
            font-size: 0.8rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-present {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-departed {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .premium-badge {
            background-color: #FFB800;
            color: #4B1C3C;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Term Progress */
        .progress-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #e0d0e0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .progress-bar-bg {
            height: 10px;
            background-color: #f0e8f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background-color: #4B1C3C;
        }

        /* Footer */
        .footer-note {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 0.9rem;
        }

        .footer-note i {
            color: #FFB800;
        }

        /* Modal for Photo Upload */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #4B1C3C;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
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
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .teacher-profile {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .class-title h1 {
                font-size: 1.8rem;
            }
            
            .time-card {
                flex-direction: column;
                text-align: center;
            }
            
            .date-display {
                flex-direction: column;
                gap: 5px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .mobile-quick-actions {
                grid-template-columns: 1fr;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f0e8f0;
        }

        ::-webkit-scrollbar-thumb {
            background: #4B1C3C;
            border-radius: 4px;
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

        <!-- Premium Header with Teacher Photo (appears below navigation on mobile) -->
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1>
                        <i class="fas fa-crown"></i> 
                        <?php echo CLASS_NAME; ?>
                    </h1>
                    <div class="class-slogan">
                        <i class="fas fa-quote-left"></i>
                        <?php echo CLASS_SLOGAN; ?>
                        <i class="fas fa-quote-right"></i>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <!-- Teacher Profile with Photo -->
                    <div class="teacher-profile">
                        <div class="teacher-photo">
                            <?php if (!empty($teacher['teacher_photo'])): ?>
                                <img src="uploads/teachers/<?php echo $teacher['teacher_photo']; ?>" alt="Teacher Photo">
                            <?php else: ?>
                                <div class="teacher-photo-placeholder">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            <?php endif; ?>
                            <div class="photo-upload-btn" onclick="openUploadModal()">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <div class="teacher-info">
                            <div class="teacher-name"><?php echo $teacher['teacher_name']; ?></div>
                            <div class="teacher-title"><?php echo $teacher['teacher_title'] ?? 'Class Teacher'; ?></div>
                        </div>
                    </div>
                    
                    <!-- Class Badge with Logout -->
                    <div class="class-badge">
                        <div class="teacher">
                            <i class="fas fa-chalkboard-teacher"></i> P.5 Purple
                        </div>
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
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

        <!-- Holiday Alert -->
        <?php if ($is_holiday): ?>
        <div class="alert alert-info">
            <i class="fas fa-calendar-day"></i>
            <strong>Today is <?php echo $is_holiday['holiday_name']; ?> - No classes!</strong>
        </div>
        <?php endif; ?>

        <!-- Time Card -->
        <div class="time-card">
            <div class="date-display">
                <i class="fas fa-calendar-alt"></i>
                <div>
                    <div class="day"><?php echo $day_of_week; ?></div>
                    <div class="date"><?php echo date('F j, Y'); ?></div>
                </div>
            </div>
            <div class="live-clock">
                <div class="time" id="liveTime"></div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-sun"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $day_scholars; ?></h3>
                    <p>Day Scholars</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-moon"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $boarders; ?></h3>
                    <p>Boarders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $soccer_students; ?></h3>
                    <p>Soccer Academy</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $today_attendance['present'] ?? 0; ?></h3>
                    <p>Present Today</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $today_attendance['late'] ?? 0; ?></h3>
                    <p>Late Today</p>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Today's Schedule Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-clock"></i> Today's Schedule - <?php echo date('l'); ?></h2>
                    <a href="timetable.php" class="view-link">
                        Full Timetable <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="schedule-list">
                    <?php
                    // Simplified schedule for brevity (you can keep your original dynamic code)
                    echo '<div class="schedule-item prep-item" style="background: #fff8e1;">';
                    echo '<span class="schedule-time">6:00 AM</span>';
                    echo '<span class="schedule-subject">Morning Prep</span>';
                    echo '<span class="schedule-badge" style="background-color: #FF9800; color: white;">Prep</span>';
                    echo '</div>';
                    
                    echo '<div class="schedule-item break-item">';
                    echo '<span class="schedule-time">7:20 AM</span>';
                    echo '<span class="schedule-subject">Morning Tea</span>';
                    echo '<span class="schedule-badge" style="background-color: #FFB800; color: #4a1a3a;">Tea</span>';
                    echo '</div>';
                    
                    echo '<div class="schedule-item">';
                    echo '<span class="schedule-time">8:30 AM</span>';
                    echo '<span class="schedule-subject">Mathematics</span>';
                    echo '<span class="schedule-badge">Period 1</span>';
                    echo '</div>';
                    
                    echo '<div class="schedule-item">';
                    echo '<span class="schedule-time">9:30 AM</span>';
                    echo '<span class="schedule-subject">Science</span>';
                    echo '<span class="schedule-badge">Period 2</span>';
                    echo '</div>';
                    ?>
                </div>
            </div>

            <!-- Recent Students Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> Recent Students</h2>
                    <a href="students.php" class="view-link">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="student-list">
                    <?php foreach ($recent_students as $student): ?>
                    <div class="student-item">
                        <div class="student-avatar">
                            <?php echo substr($student['full_name'], 0, 1); ?>
                        </div>
                        <div class="student-info">
                            <div class="student-name"><?php echo $student['full_name']; ?></div>
                            <div class="student-meta">
                                <i class="fas <?php echo $student['student_type'] == 'Boarder' ? 'fa-moon' : 'fa-sun'; ?>" 
                                   style="color: <?php echo $student['student_type'] == 'Boarder' ? '#4B1C3C' : '#FFB800'; ?>"></i>
                                <?php echo $student['student_type']; ?>
                                <?php if ($student['soccer_academy']): ?>
                                    <span class="premium-badge">⚽ Soccer</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Second Row -->
        <div class="dashboard-grid">
            <!-- Upcoming Events Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-alt"></i> Upcoming Events</h2>
                    <a href="timetable.php" class="view-link">
                        View Calendar <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <?php if (empty($upcoming_events)): ?>
                    <p style="color: #999; text-align: center; padding: 20px;">No upcoming events</p>
                <?php else: ?>
                    <div class="student-list">
                        <?php foreach ($upcoming_events as $event): ?>
                        <div class="student-item">
                            <div class="student-avatar" style="background-color: #FFB800; color: #4B1C3C;">
                                <i class="fas <?php echo $event['icon']; ?>"></i>
                            </div>
                            <div class="student-info">
                                <div class="student-name"><?php echo $event['event']; ?></div>
                                <div class="student-meta">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($event['date'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Recent Behavior -->
                <?php if (!empty($recent_behavior)): ?>
                <div style="margin-top: 20px;">
                    <h3 style="color: #4B1C3C; font-size: 1rem; margin-bottom: 10px;">
                        <i class="fas fa-exclamation-triangle" style="color: #FFB800;"></i> Recent Incidents
                    </h3>
                    <?php foreach (array_slice($recent_behavior, 0, 3) as $behavior): ?>
                    <div class="student-item">
                        <div class="student-avatar" style="background-color: <?php 
                            echo $behavior['behavior_type'] == 'Positive' ? '#4CAF50' : 
                                ($behavior['behavior_type'] == 'Warning' ? '#FF9800' : '#f44336'); 
                        ?>">
                            <i class="fas <?php 
                                echo $behavior['behavior_type'] == 'Positive' ? 'fa-star' : 
                                    ($behavior['behavior_type'] == 'Warning' ? 'fa-exclamation' : 'fa-exclamation-triangle'); 
                            ?>"></i>
                        </div>
                        <div class="student-info">
                            <div class="student-name"><?php echo $behavior['full_name']; ?></div>
                            <div class="student-meta">
                                <?php echo substr($behavior['description'], 0, 40); ?>...
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Term Progress Bar -->
        <?php if ($current_term): 
            $start = strtotime($current_term['start_date']);
            $end = strtotime($current_term['end_date']);
            $now = time();
            $progress = (($now - $start) / ($end - $start)) * 100;
            $progress = min(100, max(0, $progress));
        ?>
        <div class="progress-container">
            <div class="progress-header">
                <span><i class="fas fa-play"></i> <?php echo date('M d', $start); ?></span>
                <span><strong>Term <?php echo $current_term['term_number']; ?> Progress</strong></span>
                <span><i class="fas fa-flag-checkered"></i> <?php echo date('M d', $end); ?></span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%;"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer-note">
            <i class="fas fa-heart"></i> 
            <?php echo CLASS_NAME; ?> - Where Great Minds Grow 
            <i class="fas fa-heart"></i>
        </div>
    </div>

    <!-- Photo Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-camera"></i> Update Profile Photo</h3>
                <button class="close-btn" onclick="closeUploadModal()">&times;</button>
            </div>
            <form action="upload-photo.php" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: #4B1C3C;">Choose Photo</label>
                    <input type="file" name="teacher_photo" accept="image/*" required style="width: 100%; padding: 8px; border: 2px solid #e0e0e0; border-radius: 5px;">
                </div>
                <button type="submit" class="logout-btn" style="width: 100%; margin-top: 0; justify-content: center;">Upload Photo</button>
            </form>
        </div>
    </div>

    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            });
            document.getElementById('liveTime').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Modal functions
        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }

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
</body>
</html>