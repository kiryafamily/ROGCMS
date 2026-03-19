<?php
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$today = date('Y-m-d');
$message = '';
$message_type = '';

// Check if today is a holiday
$stmt = $pdo->prepare("SELECT * FROM public_holidays WHERE holiday_date = ?");
$stmt->execute([$today]);
$holiday = $stmt->fetch();

// Define all roll call sessions (same as before)
$sessions = [
    'morning_prep' => [
        'name' => 'Morning Prep',
        'time' => '7:20 AM',
        'icon' => 'fa-sun',
        'student_type' => 'all',
        'statuses' => ['Present', 'Absent', 'Late', 'Excused']
    ],
    'after_break' => [
        'name' => 'After Break',
        'time' => '11:00 AM',
        'icon' => 'fa-coffee',
        'student_type' => 'all',
        'statuses' => ['Present', 'Absent', 'Late', 'Excused']
    ],
    'after_lunch' => [
        'name' => 'After Lunch',
        'time' => '1:40 PM',
        'icon' => 'fa-utensils',
        'student_type' => 'all',
        'statuses' => ['Present', 'Absent', 'Late', 'Excused']
    ],
    'day_departure' => [
        'name' => 'Day Scholar Departure',
        'time' => '4:00 PM',
        'icon' => 'fa-bus',
        'student_type' => 'Day Scholar',
        'statuses' => ['Departed', 'Present', 'Absent']
    ],
    'boarding_departure' => [
        'name' => 'Boarding Departure',
        'time' => '4:20 PM',
        'icon' => 'fa-moon',
        'student_type' => 'Boarder',
        'statuses' => ['Departed', 'Present', 'Absent']
    ],
    'evening_prep' => [
        'name' => 'Evening Prep',
        'time' => '7:00 PM',
        'icon' => 'fa-book',
        'student_type' => 'Boarder',
        'statuses' => ['Present', 'Absent', 'Late', 'Excused']
    ]
];

// Determine active session (same as before)
$current_hour = (int)date('H');
$current_minute = (int)date('i');
$current_time_decimal = $current_hour + $current_minute / 60;

$session_times = [
    'morning_prep' => 7 + 20/60,
    'after_break'  => 11,
    'after_lunch'  => 13 + 40/60,
    'day_departure' => 16,
    'boarding_departure' => 16 + 20/60,
    'evening_prep' => 19
];

$active_session = 'morning_prep';
foreach ($session_times as $key => $time) {
    if ($current_time_decimal >= $time) {
        $active_session = $key;
    } else {
        break;
    }
}

// Handle form submission (same as before)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $session_key = $_POST['session_key'];
    $student_ids = $_POST['student_id'] ?? [];
    $statuses = $_POST['status'] ?? [];
    $times = $_POST['time'] ?? [];
    $notes = $_POST['notes'] ?? [];

    foreach ($student_ids as $index => $student_id) {
        $status = $statuses[$index] ?? null;
        if (!$status) continue;

        $time = $times[$index] ?? null;
        $note = $notes[$index] ?? '';

        $stmt = $pdo->prepare("SELECT id FROM attendance_records WHERE student_id = ? AND date = ? AND session = ?");
        $stmt->execute([$student_id, $today, $session_key]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE attendance_records SET status = ?, time = ?, notes = ?, taken_by = ? WHERE id = ?");
            $stmt->execute([$status, $time, $note, $_SESSION['user_id'], $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance_records (student_id, date, session, status, time, notes, taken_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $today, $session_key, $status, $time, $note, $_SESSION['user_id']]);
        }
    }
    $message = "Attendance for " . $sessions[$session_key]['name'] . " saved successfully!";
    $message_type = "success";
}

// Get all active students
$students = $pdo->query("SELECT * FROM students WHERE status = 'Active' ORDER BY student_type, full_name")->fetchAll();
$day_scholars = array_filter($students, fn($s) => $s['student_type'] == 'Day Scholar');
$boarders = array_filter($students, fn($s) => $s['student_type'] == 'Boarder');

// Fetch existing attendance records for today
$attendance_records = [];
$stmt = $pdo->prepare("SELECT * FROM attendance_records WHERE date = ?");
$stmt->execute([$today]);
foreach ($stmt->fetchAll() as $rec) {
    $attendance_records[$rec['student_id']][$rec['session']] = $rec;
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
    <title>Attendance - P.5 Purple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ========== GLOBAL STYLES (same as index.php) ========== */
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

        /* ========== NAVIGATION STYLES ========== */
        .main-nav {
            background-color: #ffffff;
            border-radius: 50px;
            padding: 8px 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(75,28,60,0.1);
            border: 1px solid #e0d0e0;
            position: relative;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .nav-toggle {
            display: none;
            background: none;
            border: none;
            color: #4B1C3C;
            font-size: 1.8rem;
            cursor: pointer;
            padding: 5px 10px;
        }

        .nav-menu {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            list-style: none;
            justify-content: center;
            transition: all 0.3s ease;
            width: 100%;
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

        /* Mobile overlay menu */
        .nav-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .nav-overlay.show {
            display: block;
            opacity: 1;
        }

        .mobile-menu {
            position: fixed;
            top: 0;
            right: -300px;
            width: 280px;
            height: 100%;
            background-color: white;
            z-index: 1000;
            box-shadow: -5px 0 20px rgba(75,28,60,0.3);
            transition: right 0.3s ease;
            padding: 20px 15px;
            overflow-y: auto;
        }

        .mobile-menu.show {
            right: 0;
        }

        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #FFB800;
        }

        .mobile-menu-header h3 {
            color: #4B1C3C;
            font-weight: 600;
        }

        .mobile-close {
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: #4B1C3C;
        }

        .mobile-close:hover {
            color: #FFB800;
        }

        .mobile-nav-item {
            margin-bottom: 5px;
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            background-color: #f8f4f8;
            border-radius: 10px;
            color: #4B1C3C;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }

        .mobile-nav-link i {
            color: #FFB800;
            width: 24px;
        }

        .mobile-nav-link:hover {
            background-color: #4B1C3C;
            color: white;
        }

        .mobile-nav-link:hover i {
            color: #FFB800;
        }

        .mobile-dropdown {
            display: none;
            margin-left: 20px;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        .mobile-dropdown.show {
            display: block;
        }

        .mobile-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            color: #4B1C3C;
            text-decoration: none;
            font-size: 0.9rem;
            border-left: 2px solid #FFB800;
            margin: 2px 0;
        }

        .mobile-dropdown a i {
            color: #FFB800;
            width: 20px;
        }

        .mobile-dropdown a:hover {
            background-color: #f0e8f0;
        }

        @media (max-width: 768px) {
            .main-nav {
                border-radius: 12px;
                padding: 8px 15px;
            }

            .nav-toggle {
                display: block;
            }

            .nav-menu {
                display: none;
            }

            .nav-overlay.show {
                display: block;
            }
        }

        /* ========== ATTENDANCE SPECIFIC STYLES ========== */
        .attendance-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 12px 25px;
            background: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(75,28,60,0.1);
        }
        
        .tab-btn.active {
            background: #4B1C3C;
            color: white;
        }
        
        .tab-btn.active i {
            color: #FFB800;
        }
        
        .tab-btn i {
            color: #FFB800;
        }
        
        .attendance-panel {
            display: none;
        }
        
        .attendance-panel.active {
            display: block;
        }
        
        .time-indicator {
            background: #FFB800;
            color: #4B1C3C;
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .student-row {
            display: flex;
            align-items: center;
            padding: 12px;
            background: white;
            border-radius: 10px;
            margin-bottom: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .student-info {
            width: 200px;
        }
        
        .student-name {
            font-weight: 600;
            color: #4B1C3C;
        }
        
        .student-type {
            font-size: 0.7rem;
            color: #999;
        }
        
        .attendance-controls {
            flex: 1;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .status-select {
            padding: 6px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }
        
        .time-input {
            width: 100px;
            padding: 6px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }
        
        .notes-input {
            width: 200px;
            padding: 6px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }
        
        .save-btn {
            margin-top: 20px;
            width: 100%;
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
        
        .alert {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .alert-info {
            background-color: #e1f5fe;
            border-left-color: #0288d1;
            color: #01579b;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            border-left-color: #27ae60;
            color: #2e7d32;
        }
        
        .alert-error {
            background-color: #ffebee;
            border-left-color: #e74c3c;
            color: #c62828;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .premium-container {
                padding: 10px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .teacher-profile {
                flex-direction: column;
                text-align: center;
            }
            
            .student-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .student-info {
                width: 100%;
            }
            
            .attendance-controls {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }
            
            .status-select, .time-input, .notes-input {
                width: 100% !important;
            }
            
            .attendance-tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="premium-container">
        <!-- Premium Header with Teacher Photo -->
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1><i class="fas fa-clipboard-list"></i> Daily Attendance</h1>
                    <div class="class-slogan"><?php echo CLASS_NAME; ?> - <?php echo date('l, F j, Y'); ?></div>
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

        <!-- Include the reusable navigation bar -->
        <?php include 'navbar.php'; ?>

        <!-- Holiday Alert -->
        <?php if ($holiday): ?>
        <div class="alert alert-info">
            <i class="fas fa-calendar-day"></i>
            <strong>Today is <?php echo $holiday['holiday_name']; ?> - No classes!</strong>
        </div>
        <?php endif; ?>

        <!-- Alert Message -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Attendance Tabs -->
        <div class="attendance-tabs">
            <?php foreach ($sessions as $key => $session): ?>
            <button class="tab-btn <?php echo $active_session == $key ? 'active' : ''; ?>" onclick="switchTab('<?php echo $key; ?>')">
                <i class="fas <?php echo $session['icon']; ?>"></i> <?php echo $session['name']; ?> (<?php echo $session['time']; ?>)
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Session Panels (rest of your attendance form) -->
        <?php foreach ($sessions as $key => $session): 
            if ($session['student_type'] == 'all') {
                $students_to_show = $students;
            } elseif ($session['student_type'] == 'Day Scholar') {
                $students_to_show = $day_scholars;
            } else {
                $students_to_show = $boarders;
            }
        ?>
        <div id="<?php echo $key; ?>-panel" class="attendance-panel <?php echo $active_session == $key ? 'active' : ''; ?>">
            <div class="time-indicator">
                <i class="fas <?php echo $session['icon']; ?>"></i> <?php echo $session['name']; ?> - <?php echo $session['time']; ?>
            </div>
            
            <form method="POST">
                <input type="hidden" name="session_key" value="<?php echo $key; ?>">
                <?php foreach ($students_to_show as $student): 
                    $record = $attendance_records[$student['id']][$key] ?? null;
                ?>
                <div class="student-row">
                    <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                    <div class="student-info">
                        <div class="student-name"><?php echo $student['full_name']; ?></div>
                        <div class="student-type">
                            <?php echo $student['student_type']; ?>
                            <?php if ($student['soccer_academy']): ?>⚽<?php endif; ?>
                        </div>
                    </div>
                    <div class="attendance-controls">
                        <select name="status[]" class="status-select">
                            <?php foreach ($session['statuses'] as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo ($record['status'] ?? '') == $status ? 'selected' : ''; ?>>
                                <?php echo $status; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="time" name="time[]" class="time-input" value="<?php echo $record['time'] ?? ''; ?>" placeholder="Time">
                        <input type="text" name="notes[]" class="notes-input" placeholder="Notes" value="<?php echo htmlspecialchars($record['notes'] ?? ''); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
                
                <button type="submit" name="save_attendance" class="btn-premium save-btn">
                    <i class="fas fa-save"></i> Save <?php echo $session['name']; ?> Attendance
                </button>
            </form>
        </div>
        <?php endforeach; ?>

        <!-- Export Button -->
        <div style="text-align: right; margin-bottom: 20px;">
            <a href="attendance-reports.php?period=today" class="btn-premium">
                <i class="fas fa-download"></i> Export Roll Call
            </a>
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
        // Live Clock (optional – remove if not needed)
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

        // Attendance tab switching
        function switchTab(tabKey) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.attendance-panel').forEach(panel => panel.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabKey + '-panel').classList.add('active');
        }
    </script>
</body>
</html>