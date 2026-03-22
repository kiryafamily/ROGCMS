<?php
session_start();
require_once 'includes/config.php';

// Include the communication functions
require_once 'includes/communication_functions.php';

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

// Define all roll call sessions
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

// Determine active session
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

// Handle form submission with parent notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $session_key = $_POST['session_key'];
    $student_ids = $_POST['student_id'] ?? [];
    $statuses = $_POST['status'] ?? [];
    $times = $_POST['time'] ?? [];
    $notes = $_POST['notes'] ?? [];
    
    $notification_results = [];
    $status_changes = [];

    foreach ($student_ids as $index => $student_id) {
        $status = $statuses[$index] ?? null;
        if (!$status) continue;

        $time = $times[$index] ?? null;
        $note = $notes[$index] ?? '';

        // Get previous status to check if there's a change
        $stmt = $pdo->prepare("SELECT status FROM attendance_records WHERE student_id = ? AND date = ? AND session = ?");
        $stmt->execute([$student_id, $today, $session_key]);
        $previous = $stmt->fetch();
        $previous_status = $previous['status'] ?? null;
        
        // Check if status changed
        $status_changed = ($previous_status !== $status && $previous_status !== null);
        
        $stmt = $pdo->prepare("SELECT id FROM attendance_records WHERE student_id = ? AND date = ? AND session = ?");
        $stmt->execute([$student_id, $today, $session_key]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE attendance_records SET status = ?, time = ?, notes = ?, taken_by = ? WHERE id = ?");
            $stmt->execute([$status, $time, $note, $_SESSION['user_id'], $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance_records (student_id, date, session, status, time, notes, taken_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $today, $session_key, $status, $time, $note, $_SESSION['user_id']]);
            $status_changed = true; // New record is a change
        }
        
        // Send notification if status changed or is important (Absent/Late)
        $important_statuses = ['Absent', 'Late'];
        if ($status_changed || in_array($status, $important_statuses)) {
            // Get student details with parent contact info - CORRECTED
            $stmt = $pdo->prepare("SELECT id, full_name, parent_name, parent_phone, parent_email, student_type, soccer_academy 
                                    FROM students 
                                    WHERE id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
            
            if ($student && ($student['parent_phone'] || $student['parent_email'])) {
                $status_changes[] = [
                    'student_id' => $student_id,
                    'student_name' => $student['full_name'],
                    'status' => $status,
                    'session_name' => $sessions[$session_key]['name'],
                    'session_time' => $sessions[$session_key]['time'],
                    'parent_phone' => $student['parent_phone'],
                    'parent_email' => $student['parent_email'],
                    'parent_name' => $student['parent_name']
                ];
            }
        }
    }
    
    // Send notifications for status changes
    if (!empty($status_changes)) {
        foreach ($status_changes as $change) {
            $notification_sent = false;
            $notification_method = ''; // Always initialize as empty string
            
            // Prepare message
            $attendance_message = "🏫 *Rays of Grace Junior School*\n\n";
            $attendance_message .= "Dear parent of " . $change['student_name'] . ",\n\n";
            $attendance_message .= "Attendance Update for " . $change['session_name'] . " (" . $change['session_time'] . "):\n";
            $attendance_message .= "Status: *" . $change['status'] . "*\n\n";
            
            if ($change['status'] == 'Absent') {
                $attendance_message .= "Please contact the school for more information.\n\n";
            } elseif ($change['status'] == 'Late') {
                $attendance_message .= "Please ensure timely arrival to school.\n\n";
            } elseif ($change['status'] == 'Present') {
                $attendance_message .= "Student is present and accounted for.\n\n";
            }
            
            $attendance_message .= "Regards,\n";
            $attendance_message .= "Class Teacher - P.5 Purple\n";
            $attendance_message .= "Rays of Grace Junior School";
            
            $methods_sent = []; // Use an array to track methods
            
            // Try WhatsApp first if phone number exists
            if (!empty($change['parent_phone']) && $change['parent_phone'] != '00000000000') {
                $result = sendWhatsAppMessage($change['parent_phone'], $attendance_message);
                if ($result['success']) {
                    $notification_sent = true;
                    $methods_sent[] = 'WhatsApp';
                }
            }
            
            // If WhatsApp failed or no phone, try SMS
            if (!$notification_sent && !empty($change['parent_phone']) && $change['parent_phone'] != '00000000000') {
                $sms_message = "Rays of Grace: " . $change['student_name'] . " - " . $change['session_name'] . ": " . $change['status'];
                $result = sendSMS($change['parent_phone'], $sms_message);
                if ($result['success']) {
                    $notification_sent = true;
                    $methods_sent[] = 'SMS';
                }
            }
            
            // Try email if available
            if (!empty($change['parent_email'])) {
                $email_subject = "Attendance Update - " . $change['student_name'] . " - " . $change['session_name'];
                $email_body = "Dear " . ($change['parent_name'] ?: 'Parent') . ",\n\n";
                $email_body .= "This is an attendance update for your child, " . $change['student_name'] . ".\n\n";
                $email_body .= "Session: " . $change['session_name'] . " (" . $change['session_time'] . ")\n";
                $email_body .= "Status: " . $change['status'] . "\n\n";
                
                if ($change['status'] == 'Absent') {
                    $email_body .= "Please contact the school for more information.\n\n";
                } elseif ($change['status'] == 'Late') {
                    $email_body .= "Please ensure timely arrival to school.\n\n";
                }
                
                $email_body .= "Regards,\n";
                $email_body .= "Class Teacher - P.5 Purple\n";
                $email_body .= "Rays of Grace Junior School";
                
                $result = sendEmail($change['parent_email'], $email_subject, $email_body);
                if ($result['success']) {
                    $notification_sent = true;
                    $methods_sent[] = 'Email';
                }
            }
            
            // Convert methods array to string
            $method_string = !empty($methods_sent) ? implode(' + ', $methods_sent) : '';
            
            // Log the notification
            if ($notification_sent) {
                $stmt = $pdo->prepare("
                    INSERT INTO parent_communication 
                    (student_id, communication_date, term, year, parent_name, contact_method, purpose, notes)
                    VALUES (?, CURDATE(), ?, ?, ?, ?, 'Attendance Alert', ?)
                ");
                $stmt->execute([
                    $change['student_id'], 
                    $current_term, 
                    ACADEMIC_YEAR, 
                    $change['parent_name'] ?? '', 
                    $method_string,
                    "Attendance: " . $change['session_name'] . " - " . $change['status']
                ]);
            }
        }
        
        $notification_count = count($status_changes);
        if ($notification_count > 0) {
            $message = "Attendance saved! " . $notification_count . " parent notification(s) sent.";
        } else {
            $message = "Attendance saved successfully!";
        }
    } else {
        $message = "Attendance saved successfully!";
    }
    $message_type = 'success';  
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
        /* All your existing CSS styles remain exactly the same */
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
            
            .mobile-quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
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

        <!-- Session Panels -->
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
        // ========== MOBILE TOP NAVIGATION ==========
        const mobileNavHeader = document.getElementById('mobileNavHeader');
        const mobileNavDropdown = document.getElementById('mobileNavDropdown');
        const menuIcon = document.getElementById('menuIcon');

        // Make sure elements exist before adding event listeners
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

        // Mobile submenu toggle - make this function globally available
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

        // ========== OPTIONAL: Live Clock (only if the element exists) ==========
        const liveTimeElement = document.getElementById('liveTime');
        if (liveTimeElement) {
            function updateClock() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    second: '2-digit',
                    hour12: true 
                });
                liveTimeElement.textContent = timeString;
            }
            setInterval(updateClock, 1000);
            updateClock();
        }

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
            
            // Find the clicked button and add active class
            event.target.classList.add('active');
            document.getElementById(tabKey + '-panel').classList.add('active');
        }
    </script>
</body>
</html>