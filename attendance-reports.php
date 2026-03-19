<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Define all roll call sessions (must match the keys used in attendance.php)
$sessions = [
    'morning_prep'       => 'Morning Prep (7:20 AM)',
    'after_break'        => 'After Break (11:00 AM)',
    'after_lunch'        => 'After Lunch (1:40 PM)',
    'day_departure'      => 'Day Scholar Departure (4:00 PM)',
    'boarding_departure' => 'Boarding Departure (4:20 PM)',
    'evening_prep'       => 'Evening Prep (7:00 PM)'
];

// Get term start and end dates from config (if defined)
$term_start = defined('TERM_START_DATE') ? TERM_START_DATE : date('Y-m-01');
$term_end = defined('TERM_END_DATE') ? TERM_END_DATE : date('Y-m-t');

// Get filter values from GET
$from_date = $_GET['from'] ?? date('Y-m-d');
$to_date = $_GET['to'] ?? date('Y-m-d');
$period = $_GET['period'] ?? 'custom';
$selected_session = $_GET['session'] ?? 'all';

// Presets
if ($period == 'today') {
    $from_date = $to_date = date('Y-m-d');
} elseif ($period == 'week') {
    $from_date = date('Y-m-d', strtotime('monday this week'));
    $to_date = date('Y-m-d', strtotime('sunday this week'));
} elseif ($period == 'month') {
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-t');
} elseif ($period == 'term') {
    $from_date = $term_start;
    $to_date = $term_end;
}

// Build SQL query with optional session filter
$sql = "SELECT ar.*, s.full_name, s.admission_number, s.student_type, s.dormitory_number
        FROM attendance_records ar
        JOIN students s ON ar.student_id = s.id
        WHERE ar.date BETWEEN ? AND ?";
$params = [$from_date, $to_date];

if ($selected_session !== 'all') {
    $sql .= " AND ar.session = ?";
    $params[] = $selected_session;
}

$sql .= " ORDER BY ar.date DESC, ar.session, s.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// For CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    $filename = 'attendance_' . ($selected_session !== 'all' ? $selected_session . '_' : '') . $from_date . '_to_' . $to_date . '.csv';
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Session', 'Student Name', 'Admission No', 'Type', 'Status', 'Time', 'Notes']);
    
    foreach ($records as $r) {
        fputcsv($output, [
            $r['date'],
            $sessions[$r['session']] ?? $r['session'],
            $r['full_name'],
            $r['admission_number'],
            $r['student_type'],
            $r['status'],
            $r['time'],
            $r['notes']
        ]);
    }
    fclose($output);
    exit;
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - P.5 Purple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- html2canvas & jsPDF for PDF/PNG export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        /* ========== GLOBAL STYLES ========== */
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

        .premium-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* HEADER */
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

        /* Report Container */
        .report-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(75,28,60,0.1);
            border: 1px solid #e0d0e0;
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1 1 200px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #4B1C3C;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0d0e0;
            border-radius: 8px;
            font-family: inherit;
        }
        .btn-filter {
            background: #4B1C3C;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-filter:hover {
            background: #2F1224;
        }
        .btn-premium {
            background: #4B1C3C;
            color: white;
            padding: 12px 24px;
            border: 1px solid #FFB800;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .btn-premium:hover {
            background: #36152B;
        }
        .btn-premium i {
            color: #FFB800;
        }
        .export-buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-export {
            background: #FFB800;
            color: #4B1C3C;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .btn-export:hover {
            background: #e6a600;
        }
        .btn-pdf {
            background: #4B1C3C;
            color: white;
        }
        .btn-png {
            background: #6A2B52;
            color: white;
        }
        .btn-print {
            background: #666;
            color: white;
        }
        .btn-print:hover {
            background: #444;
        }
        .summary-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(75,28,60,0.1);
            border-left: 4px solid #FFB800;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4B1C3C;
        }
        .stat-label {
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(75,28,60,0.1);
        }
        th {
            background: #4B1C3C;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0d0e0;
        }
        tr:hover {
            background: #f8f4f8;
        }
        .session-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #FFB800;
            color: #4B1C3C;
        }
        .status-present { color: #27ae60; font-weight: 600; }
        .status-absent { color: #e74c3c; font-weight: 600; }
        .status-late { color: #f39c12; font-weight: 600; }
        .status-departed { color: #3498db; font-weight: 600; }
        
        .alert-info {
            background-color: #e1f5fe;
            border-left: 4px solid #0288d1;
            color: #01579b;
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        /* Print styles for A4 */
        @media print {
            .filter-card, .export-buttons, .mobile-top-nav, .main-nav, .class-badge, .btn-premium {
                display: none;
            }
            body { background: white; }
            .report-container { padding: 0; }
            table { box-shadow: none; }
            .premium-header {
                background-color: #4B1C3C !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            th {
                background-color: #4B1C3C !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
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
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .export-buttons {
                flex-direction: column;
            }
            
            .btn-export {
                width: 100%;
                justify-content: center;
            }
            
            .summary-stats {
                flex-direction: column;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 8px 5px;
            }
            
            .mobile-quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.8rem;
            }
            
            th, td {
                padding: 5px 3px;
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
    <div class="report-container">
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
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1><i class="fas fa-chart-bar"></i> Attendance Reports</h1>
                    <div class="class-slogan"><?php echo CLASS_NAME; ?></div>
                </div>
                <div class="class-badge">
                    <a href="attendance.php" class="btn-premium"><i class="fas fa-arrow-left"></i> Back</a>
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

        <!-- Filter Card (excluded from PDF/PNG) -->
        <div class="filter-card" id="filterCard">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Period</label>
                    <select name="period" onchange="this.form.submit()">
                        <option value="today" <?php echo $period == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="term" <?php echo $period == 'term' ? 'selected' : ''; ?>>This Term</option>
                        <option value="custom" <?php echo $period == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="from" value="<?php echo $from_date; ?>" <?php echo $period != 'custom' ? 'disabled' : ''; ?>>
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="to" value="<?php echo $to_date; ?>" <?php echo $period != 'custom' ? 'disabled' : ''; ?>>
                </div>
                <div class="filter-group">
                    <label>Session</label>
                    <select name="session">
                        <option value="all" <?php echo $selected_session == 'all' ? 'selected' : ''; ?>>All Sessions</option>
                        <?php foreach ($sessions as $key => $name): ?>
                        <option value="<?php echo $key; ?>" <?php echo $selected_session == $key ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Apply</button>
                </div>
            </form>
        </div>

        <!-- Summary Stats (included in export) -->
        <div class="summary-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($records); ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_unique(array_column($records, 'date'))); ?></div>
                <div class="stat-label">Days</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_unique(array_column($records, 'student_id'))); ?></div>
                <div class="stat-label">Students</div>
            </div>
        </div>

        <!-- Export Buttons (excluded from PDF/PNG) -->
        <div class="export-buttons" id="exportButtons">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn-export">
                <i class="fas fa-file-csv"></i> CSV
            </a>
            <button class="btn-export btn-pdf" onclick="exportAsPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
            <button class="btn-export btn-png" onclick="exportAsPNG()"><i class="fas fa-image"></i> PNG</button>
            <button onclick="window.print()" class="btn-export btn-print"><i class="fas fa-print"></i> Print</button>
        </div>

        <!-- Attendance Table -->
        <?php if (empty($records)): ?>
            <div class="alert-info">No attendance records found for the selected period and session.</div>
        <?php else: ?>
            <table id="attendanceTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Session</th>
                        <th>Student</th>
                        <th>Admission</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                    <tr>
                        <td><?php echo date('D, M j, Y', strtotime($r['date'])); ?></td>
                        <td><span class="session-badge"><?php echo $sessions[$r['session']] ?? ucfirst(str_replace('_', ' ', $r['session'])); ?></span></td>
                        <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                        <td><?php echo $r['admission_number']; ?></td>
                        <td><?php echo $r['student_type']; ?></td>
                        <td class="status-<?php echo strtolower($r['status']); ?>"><?php echo $r['status']; ?></td>
                        <td><?php echo $r['time'] ? date('h:i A', strtotime($r['time'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($r['notes']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        // Enable/disable custom date inputs based on period selection
        document.querySelector('select[name="period"]').addEventListener('change', function() {
            const custom = this.value === 'custom';
            document.querySelectorAll('input[name="from"], input[name="to"]').forEach(input => {
                input.disabled = !custom;
            });
        });

        // PDF Export
        function exportAsPDF() {
            const element = document.getElementById('reportContainer');
            // Hide filter card and export buttons before capture
            const filterCard = document.getElementById('filterCard');
            const exportBtns = document.getElementById('exportButtons');
            if (filterCard) filterCard.style.visibility = 'hidden';
            if (exportBtns) exportBtns.style.visibility = 'hidden';
            
            html2canvas(element, {
                scale: 2,
                backgroundColor: '#ffffff',
                logging: false
            }).then(canvas => {
                // Restore visibility
                if (filterCard) filterCard.style.visibility = 'visible';
                if (exportBtns) exportBtns.style.visibility = 'visible';
                
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save('attendance_<?php echo $from_date; ?>_to_<?php echo $to_date; ?>.pdf');
            });
        }

        // PNG Export
        function exportAsPNG() {
            const element = document.getElementById('reportContainer');
            const filterCard = document.getElementById('filterCard');
            const exportBtns = document.getElementById('exportButtons');
            if (filterCard) filterCard.style.visibility = 'hidden';
            if (exportBtns) exportBtns.style.visibility = 'hidden';
            
            html2canvas(element, {
                scale: 2,
                backgroundColor: '#ffffff',
                logging: false
            }).then(canvas => {
                if (filterCard) filterCard.style.visibility = 'visible';
                if (exportBtns) exportBtns.style.visibility = 'visible';
                
                const link = document.createElement('a');
                link.download = 'attendance_<?php echo $from_date; ?>_to_<?php echo $to_date; ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
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