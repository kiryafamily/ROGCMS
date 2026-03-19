<?php
// report-selector.php - Report Card Type Selector
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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

$current_term = $_GET['term'] ?? CURRENT_TERM;
$students = $pdo->query("SELECT id, full_name FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report Card - P.5 Purple</title>
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
            max-width: 1400px;
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

        /* Desktop Navigation */
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

        /* Selector Container */
        .selector-container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(74, 26, 58, 0.1);
            animation: slideIn 0.5s ease;
            margin: 0 auto;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: var(--purple-dark);
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h1 i {
            color: var(--orange);
        }

        .subtitle {
            color: var(--gray-600);
            margin-bottom: 30px;
            font-size: 1rem;
            border-left: 3px solid var(--orange);
            padding-left: 15px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: var(--purple-dark);
            font-weight: 600;
            font-size: 0.95rem;
        }

        label i {
            color: var(--orange);
            margin-right: 8px;
        }

        select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-300);
            border-radius: 14px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: white;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(239, 91, 43, 0.1);
        }

        .radio-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }

        .radio-option {
            background: var(--gray-50);
            border: 2px solid var(--gray-300);
            border-radius: 16px;
            padding: 20px 15px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .radio-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--orange);
            transform: translateY(-100%);
            transition: var(--transition);
        }

        .radio-option:hover {
            border-color: var(--orange);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .radio-option:hover::before {
            transform: translateY(0);
        }

        .radio-option.selected {
            background: var(--purple);
            border-color: var(--purple);
        }

        .radio-option.selected i {
            color: var(--orange);
        }

        .radio-option.selected h3 {
            color: white;
        }

        .radio-option.selected p {
            color: var(--gray-300);
        }

        .radio-option i {
            font-size: 2.5rem;
            color: var(--purple);
            margin-bottom: 10px;
            transition: var(--transition);
        }

        .radio-option h3 {
            margin: 5px 0;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--purple-dark);
        }

        .radio-option p {
            font-size: 0.8rem;
            color: var(--gray-600);
            line-height: 1.4;
        }

        input[type="radio"] {
            display: none;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-generate {
            flex: 1;
            padding: 16px;
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(239, 91, 43, 0.3);
        }

        .btn-generate:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(239, 91, 43, 0.4);
        }

        .btn-generate i {
            font-size: 1.2rem;
        }

        .btn-generate-all {
            flex: 1;
            padding: 16px;
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(74, 26, 58, 0.3);
        }

        .btn-generate-all:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(74, 26, 58, 0.4);
        }

        .btn-generate-all i {
            color: var(--orange);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            color: var(--purple);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            padding: 10px 20px;
            border-radius: 50px;
        }

        .btn-back:hover {
            background: var(--gray-100);
            color: var(--orange);
            transform: translateX(-5px);
        }

        .btn-back i {
            font-size: 0.9rem;
        }

        .footer-note {
            text-align: center;
            margin-top: 25px;
            color: var(--gray-500);
            font-size: 0.8rem;
        }

        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 0.9rem;
            color: #0b5e8e;
        }

        .info-box i {
            color: #2196F3;
            margin-right: 10px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .premium-header {
                padding: 20px;
            }

            .class-title h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
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
            
            .premium-header {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .class-title h1 {
                font-size: 1.8rem;
            }
            
            .class-badge {
                justify-content: center;
            }

            .selector-container {
                padding: 25px;
            }

            h1 {
                font-size: 1.8rem;
                flex-direction: column;
                text-align: center;
                gap: 5px;
            }

            .radio-group {
                grid-template-columns: 1fr;
            }

            .radio-option {
                padding: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .mobile-quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .premium-header {
                padding: 15px;
            }
            
            .class-title h1 {
                font-size: 1.5rem;
            }
            
            .class-slogan {
                font-size: 0.9rem;
            }
            
            .btn-premium {
                padding: 8px 16px;
                font-size: 0.85rem;
            }
            
            .selector-container {
                padding: 20px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .subtitle {
                font-size: 0.9rem;
                text-align: center;
            }
            
            select {
                padding: 12px;
                font-size: 0.95rem;
            }
            
            .radio-option h3 {
                font-size: 1.2rem;
            }
            
            .btn-generate, .btn-generate-all {
                padding: 14px;
                font-size: 1rem;
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

        <!-- Premium Header -->
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1>
                        <i class="fas fa-file-alt"></i> 
                        Report Card Generator
                    </h1>
                    <div class="class-slogan">
                        <i class="fas fa-star"></i>
                        P.5 Purple - Academic Year <?php echo ACADEMIC_YEAR; ?>
                    </div>
                </div>
                <div class="class-badge">
                    <a href="assessments.php?term=<?php echo $current_term; ?>" class="btn-premium">
                        <i class="fas fa-arrow-left"></i> Back to Marksheet
                    </a>
                    <a href="index.php" class="btn-premium">
                        <i class="fas fa-home"></i> Dashboard
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

        <!-- Main Selector Container -->
        <div class="selector-container">
            <h1>
                <i class="fas fa-file-alt"></i> 
                Generate Report Cards
            </h1>
            <p class="subtitle">Choose options to generate individual or bulk report cards</p>
            
            <form action="report-card.php" method="GET" id="reportForm">
                <div class="form-group">
                    <label><i class="fas fa-user-graduate"></i> Select Student</label>
                    <select name="student_id" id="studentSelect">
                        <option value="" selected>-- All Students (Bulk Generation) --</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--gray-500); display: block; margin-top: 5px;">
                        <i class="fas fa-info-circle"></i> Select a specific student or leave as "All Students" to generate reports for everyone
                    </small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Academic Term</label>
                    <select name="term">
                        <option value="1" <?php echo $current_term == '1' ? 'selected' : ''; ?>>Term I (February - May)</option>
                        <option value="2" <?php echo $current_term == '2' ? 'selected' : ''; ?>>Term II (May - August)</option>
                        <option value="3" <?php echo $current_term == '3' ? 'selected' : ''; ?>>Term III (September - December)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-file-signature"></i> Report Type</label>
                    <div class="radio-group">
                        <label class="radio-option" id="opt-bot">
                            <input type="radio" name="type" value="BOT" checked>
                            <i class="fas fa-play"></i>
                            <h3>BOT</h3>
                            <p>Beginning of Term<br>Only BOT results</p>
                        </label>
                        
                        <label class="radio-option" id="opt-mid">
                            <input type="radio" name="type" value="MID">
                            <i class="fas fa-pause"></i>
                            <h3>MID</h3>
                            <p>Mid-Term<br>BOT + MID results</p>
                        </label>
                        
                        <label class="radio-option" id="opt-end">
                            <input type="radio" name="type" value="END">
                            <i class="fas fa-flag-checkered"></i>
                            <h3>END</h3>
                            <p>End of Term<br>BOT + MID + END results</p>
                        </label>
                    </div>
                </div>
                
                <div class="info-box">
                    <i class="fas fa-lightbulb"></i>
                    <strong>Bulk Generation:</strong> Select "All Students" to generate a single PDF containing report cards for every student in the class.
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn-generate" id="generateBtn">
                        <i class="fas fa-file-pdf"></i> Generate Report
                    </button>
                    <button type="button" class="btn-generate-all" id="generateAllBtn" onclick="generateAllReports()">
                        <i class="fas fa-layer-group"></i> Generate All Reports
                    </button>
                </div>
            </form>
            
            <div style="text-align: center;">
                <a href="assessments.php?term=<?php echo $current_term; ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Marksheet
                </a>
            </div>
            
            <div class="footer-note">
                <i class="fas fa-info-circle"></i> Report cards are generated in A4 format, ready for printing
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

        // Highlight selected radio option
        document.querySelectorAll('.radio-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.radio-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Set initial selected state
        document.addEventListener('DOMContentLoaded', function() {
            const checkedRadio = document.querySelector('input[type="radio"]:checked');
            if (checkedRadio) {
                checkedRadio.closest('.radio-option').classList.add('selected');
            }
        });

        // Form validation for single report
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const studentSelect = document.getElementById('studentSelect');
            const generateBtn = document.getElementById('generateBtn');
            
            if (!studentSelect.value) {
                e.preventDefault();
                alert('Please select a student or use "Generate All Reports" for bulk generation');
                studentSelect.focus();
            } else {
                generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
                generateBtn.disabled = true;
            }
        });

        // Generate all reports function
        function generateAllReports() {
            const term = document.querySelector('select[name="term"]').value;
            const type = document.querySelector('input[name="type"]:checked').value;
            const generateAllBtn = document.getElementById('generateAllBtn');
            
            // Confirm action
            if (!confirm('Generate report cards for ALL active students? This will create a single PDF with all reports.')) {
                return;
            }
            
            // Show loading state
            generateAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating All Reports...';
            generateAllBtn.disabled = true;
            
            // Redirect to bulk report generator
            window.location.href = `bulk-reports.php?term=${term}&type=${type}`;
        }

        // Handle student selection change
        document.getElementById('studentSelect').addEventListener('change', function() {
            const generateBtn = document.getElementById('generateBtn');
            const generateAllBtn = document.getElementById('generateAllBtn');
            
            if (this.value) {
                // Single student selected
                generateBtn.style.display = 'flex';
                generateAllBtn.style.display = 'none';
            } else {
                // All students selected
                generateBtn.style.display = 'none';
                generateAllBtn.style.display = 'flex';
            }
        });

        // Trigger change on page load
        document.addEventListener('DOMContentLoaded', function() {
            const event = new Event('change');
            document.getElementById('studentSelect').dispatchEvent(event);
        });
    </script>
</body>
</html>