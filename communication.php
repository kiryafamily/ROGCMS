<?php
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE
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

$message = '';
$message_type = '';

// Get current term
$current_term = $_GET['term'] ?? CURRENT_TERM;

// Get all active students with parent contact info
$students = $pdo->query("
    SELECT id, full_name, parent_name, parent_phone, parent_email 
    FROM students 
    WHERE status = 'Active' 
    ORDER BY full_name
")->fetchAll();

// ============================================
// SEND WHATSAPP MESSAGE FUNCTION (UltraMsg) - IMPROVED
// ============================================
function sendWhatsAppMessage($phone, $message) {
    // UltraMsg API Configuration - YOUR CREDENTIALS
    $token = 'u1r28xma3d0ir71a';
    $instance_id = '165224';
    
    // Clean phone number - remove any non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Ensure phone has country code (256 for Uganda)
    if (substr($phone, 0, 1) == '0') {
        $phone = '256' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) != '256') {
        $phone = '256' . $phone;
    }
    
    // UltraMsg API endpoint
    $url = "https://api.ultramsg.com/" . $instance_id . "/messages/chat";
    
    $data = [
        'token' => $token,
        'to' => $phone,
        'body' => $message,
        'priority' => 10
    ];
    
    // Initialize cURL with better error handling
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Add timeout
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log for debugging (remove in production)
    error_log("WhatsApp API - Phone: $phone, HTTP Code: $http_code");
    error_log("WhatsApp API - Response: $response");
    if ($curl_error) {
        error_log("WhatsApp API - cURL Error: $curl_error");
    }
    
    // Check if successful
    if ($http_code == 200) {
        $result = json_decode($response, true);
        if (isset($result['sent']) && $result['sent'] == 'true') {
            return true;
        }
    }
    
    return false;
}

// ============================================
// SEND EMAIL FUNCTION (PHP mail)
// ============================================
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Rays of Grace Junior School <reports@raysofgrace.com>' . "\r\n";
    
    $html_message = nl2br($message);
    
    return mail($to, $subject, $html_message, $headers);
}

// ============================================
// SEND SMS FUNCTION (Africa's Talking)
// ============================================
function sendSMS($phone, $message) {
    // Africa's Talking API - REPLACE WITH YOUR CREDENTIALS
    $username = 'sandbox';
    $api_key = 'your_api_key';
    
    // Clean phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Ensure phone has country code
    if (substr($phone, 0, 1) == '0') {
        $phone = '256' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) != '256') {
        $phone = '256' . $phone;
    }
    
    $url = "https://api.africastalking.com/version1/messaging";
    
    $data = [
        'username' => $username,
        'to' => $phone,
        'message' => $message,
        'from' => 'RAYSGRACE'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'apiKey: ' . $api_key
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code == 201;
}

// ============================================
// HANDLE FORM SUBMISSIONS
// ============================================

// Send WhatsApp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_whatsapp'])) {
    $student_id = $_POST['student_id'];
    $phone = $_POST['phone'];
    $message_text = $_POST['message_text'];
    $parent_name = $_POST['parent_name'];
    
    $sent = sendWhatsAppMessage($phone, $message_text);
    
    // Log the communication - REMOVED status column
    $stmt = $pdo->prepare("
        INSERT INTO parent_communication 
        (student_id, communication_date, term, year, parent_name, contact_method, purpose, notes)
        VALUES (?, CURDATE(), ?, ?, ?, 'WhatsApp', 'General', ?)
    ");
    $stmt->execute([$student_id, $current_term, ACADEMIC_YEAR, $parent_name, $message_text]);
    
    if ($sent) {
        $message = "✅ WhatsApp message sent successfully!";
        $message_type = "success";
    } else {
        $message = "❌ Failed to send WhatsApp. Please check your API settings.";
        $message_type = "error";
    }
}

// Send Email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $student_id = $_POST['student_id'];
    $email = $_POST['email'];
    $subject = $_POST['email_subject'];
    $body = $_POST['email_body'];
    $parent_name = $_POST['parent_name'];
    
    $sent = sendEmail($email, $subject, $body);
    
    // Log the communication - REMOVED status column
    $stmt = $pdo->prepare("
        INSERT INTO parent_communication 
        (student_id, communication_date, term, year, parent_name, contact_method, purpose, notes)
        VALUES (?, CURDATE(), ?, ?, ?, 'Email', 'General', ?)
    ");
    $stmt->execute([$student_id, $current_term, ACADEMIC_YEAR, $parent_name, $subject . "\n\n" . $body]);
    
    if ($sent) {
        $message = "✅ Email sent successfully!";
        $message_type = "success";
    } else {
        $message = "❌ Failed to send email. Please check your mail settings.";
        $message_type = "error";
    }
}

// Send SMS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_sms'])) {
    $student_id = $_POST['student_id'];
    $phone = $_POST['phone'];
    $message_text = $_POST['sms_text'];
    $parent_name = $_POST['parent_name'];
    
    $sent = sendSMS($phone, $message_text);
    
    // Log the communication - REMOVED status column
    $stmt = $pdo->prepare("
        INSERT INTO parent_communication 
        (student_id, communication_date, term, year, parent_name, contact_method, purpose, notes)
        VALUES (?, CURDATE(), ?, ?, ?, 'SMS', 'General', ?)
    ");
    $stmt->execute([$student_id, $current_term, ACADEMIC_YEAR, $parent_name, $message_text]);
    
    if ($sent) {
        $message = "✅ SMS sent successfully!";
        $message_type = "success";
    } else {
        $message = "❌ Failed to send SMS. Please check your Africa's Talking settings.";
        $message_type = "error";
    }
}

// Handle manual communication log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_communication'])) {
    $student_id = $_POST['student_id'];
    $parent_name = $_POST['parent_name'] ?? '';
    $contact_method = $_POST['contact_method'];
    $purpose = $_POST['purpose'];
    $notes = $_POST['notes'];
    $follow_up = isset($_POST['follow_up_required']) ? 1 : 0;
    $follow_up_date = $_POST['follow_up_date'] ?? null;
    
    $stmt = $pdo->prepare("
        INSERT INTO parent_communication 
        (student_id, communication_date, term, year, parent_name, contact_method, purpose, notes, follow_up_required, follow_up_date)
        VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$student_id, $current_term, ACADEMIC_YEAR, $parent_name, $contact_method, $purpose, $notes, $follow_up, $follow_up_date])) {
        $message = "Communication logged successfully!";
        $message_type = "success";
    }
}

// Mark follow-up as completed
if (isset($_POST['mark_completed'])) {
    $id = $_POST['comm_id'];
    $stmt = $pdo->prepare("UPDATE parent_communication SET follow_up_required = FALSE WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Follow-up marked as completed!";
    $message_type = "success";
}

// Get communication records
$stmt = $pdo->prepare("
    SELECT c.*, s.full_name, s.parent_phone, s.parent_email
    FROM parent_communication c
    JOIN students s ON c.student_id = s.id
    WHERE c.year = ? AND c.term = ?
    ORDER BY c.communication_date DESC, c.id DESC
");
$stmt->execute([ACADEMIC_YEAR, $current_term]);
$communications = $stmt->fetchAll();

// Group by follow-up needed
$follow_ups = array_filter($communications, function($c) { return $c['follow_up_required']; });
$history = array_filter($communications, function($c) { return !$c['follow_up_required']; });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Communication - Rays of Grace Junior School</title>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --purple: #4B1C3C;
            --purple-dark: #36152B;
            --purple-light: #6D2A58;
            --gold: #FFB800;
            --gold-dark: #D99B00;
            --gold-light: #FFE08C;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --shadow-sm: 0 2px 4px rgba(75,28,60,0.1);
            --shadow-md: 0 4px 8px rgba(75,28,60,0.15);
            --shadow-lg: 0 8px 16px rgba(75,28,60,0.2);
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .premium-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 30px;
            box-shadow: var(--shadow-lg);
            padding: 30px;
            border: 1px solid var(--gold);
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
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            border-bottom: 4px solid var(--gold);
        }

        .premium-header::after {
            content: "\f086";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: 30px;
            top: 20px;
            font-size: 60px;
            color: rgba(255, 184, 0, 0.2);
            transform: rotate(15deg);
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
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .class-title h1 i {
            color: var(--gold);
            margin-right: 10px;
        }

        .class-slogan {
            color: var(--gold);
            font-size: 1rem;
        }

        .school-badge {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 12px 25px;
            border-radius: 50px;
            border: 1px solid var(--gold);
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .school-badge i {
            color: var(--gold);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid var(--gold);
        }

        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid var(--danger);
        }

        .term-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .term-btn {
            padding: 10px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            background: var(--gray-200);
            color: var(--purple);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .term-btn i {
            color: var(--gold);
        }

        .term-btn.active {
            background: var(--purple);
            color: white;
            border: 2px solid var(--gold);
        }

        .term-btn.active i {
            color: var(--gold);
        }

        .term-btn:hover {
            background: var(--gold);
            color: var(--purple);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #FDF5F9, white);
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 2px solid var(--gold);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--purple);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 2rem;
        }

        .stat-content h3 {
            font-size: 2rem;
            color: var(--purple);
            line-height: 1;
        }

        .stat-content p {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .quick-send {
            background: linear-gradient(135deg, #FDF5F9, white);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid var(--gold);
        }

        .quick-send h3 {
            color: var(--purple);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }

        .quick-send h3 i {
            color: var(--gold);
        }

        .student-selector {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 15px;
            align-items: end;
        }

        .student-select {
            padding: 12px 15px;
            border: 2px solid var(--gray-300);
            border-radius: 10px;
            width: 100%;
            font-family: 'Poppins', sans-serif;
        }

        .btn-whatsapp {
            background: #25D366;
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
            width: 100%;
        }

        .btn-whatsapp:hover:not(:disabled) {
            background: #128C7E;
            transform: translateY(-2px);
        }

        .btn-whatsapp:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }

        .btn-email {
            background: #2196F3;
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
            width: 100%;
        }

        .btn-email:hover:not(:disabled) {
            background: #1976D2;
            transform: translateY(-2px);
        }

        .btn-email:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }

        .btn-sms {
            background: var(--purple);
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
            width: 100%;
        }

        .btn-sms:hover:not(:disabled) {
            background: var(--purple-dark);
            transform: translateY(-2px);
        }

        .btn-sms:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }

        .btn-sms i {
            color: var(--gold);
        }

        .follow-up-section {
            background: linear-gradient(135deg, #FFF8E7, white);
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            border: 2px solid var(--gold);
        }

        .follow-up-section h3 {
            color: var(--purple);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }

        .follow-up-section h3 i {
            color: var(--gold);
        }

        .follow-up-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border: 1px solid var(--gray-300);
        }

        .btn-mark {
            background: var(--purple);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-mark:hover {
            background: var(--purple-dark);
        }

        .btn-mark i {
            color: var(--gold);
        }

        .comm-grid {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }

        .comm-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            border: 2px solid var(--gray-300);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .comm-card:hover {
            border-color: var(--gold);
            transform: translateX(5px);
        }

        .comm-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .student-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--purple);
        }

        .student-name i {
            color: var(--gold);
            margin-right: 8px;
        }

        .comm-date {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .comm-date i {
            margin-right: 5px;
            color: var(--gold);
        }

        .method-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 8px;
        }

        .method-whatsapp {
            background: #25D366;
            color: white;
        }

        .method-email {
            background: #2196F3;
            color: white;
        }

        .method-sms {
            background: var(--purple);
            color: white;
        }

        .purpose-badge {
            background: var(--purple);
            color: white;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .comm-message {
            background: var(--gray-100);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid var(--gold);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(75, 28, 60, 0.9);
            backdrop-filter: blur(5px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            border-top: 4px solid var(--gold);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            color: var(--purple);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 i {
            color: var(--gold);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: var(--gray-500);
            transition: var(--transition);
        }

        .close-btn:hover {
            color: var(--danger);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--purple);
            font-weight: 500;
        }

        .form-group label i {
            color: var(--gold);
            margin-right: 5px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--gray-300);
            border-radius: 10px;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            border-color: var(--gold);
            outline: none;
        }

        .btn-premium {
            background: var(--purple);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-premium:hover {
            background: var(--purple-dark);
        }

        .btn-premium i {
            color: var(--gold);
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--gold);
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: var(--gray-500);
            background: var(--gray-100);
            border-radius: 15px;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gold);
            margin-bottom: 15px;
        }

        #smsCounter {
            display: block;
            text-align: right;
            margin-top: 5px;
            color: var(--gray-600);
            font-size: 0.8rem;
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
            
            .premium-container {
                padding: 20px;
            }
            
            .premium-header {
                padding: 20px;
            }
            
            .premium-header::after {
                display: none;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .class-title h1 {
                font-size: 1.8rem;
            }
            
            .school-badge {
                justify-content: center;
                width: 100%;
            }
            
            .student-selector {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .stat-card {
                padding: 15px;
                gap: 15px;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .stat-content h3 {
                font-size: 1.5rem;
            }
            
            .term-selector {
                justify-content: center;
            }
            
            .follow-up-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .comm-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .mobile-quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .premium-container {
                padding: 15px;
            }
            
            .premium-header {
                padding: 15px;
            }
            
            .class-title h1 {
                font-size: 1.5rem;
            }
            
            .class-slogan {
                font-size: 0.9rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                width: 100%;
            }
            
            .term-btn {
                width: 100%;
                justify-content: center;
            }
            
            .quick-send h3 {
                font-size: 1.2rem;
            }
            
            .modal-content {
                padding: 20px;
            }
            
            .modal-header h2 {
                font-size: 1.3rem;
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
            background: linear-gradient(135deg, var(--purple), var(--gold));
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--purple-dark), var(--gold-dark));
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
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1>
                        <i class="fas fa-comments"></i> 
                        Parent Communication Hub
                    </h1>
                    <div class="class-slogan">
                        <i class="fas fa-quote-left"></i> Building Strong Home-School Partnerships <i class="fas fa-quote-right"></i>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <div class="school-badge">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-graduation-cap"></i>
                        RAYS OF GRACE
                    </div>
                    <a href="index.php" class="btn-premium" style="width: auto; padding: 12px 20px;">
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

        <!-- Alert -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Term Selector -->
        <div class="term-selector">
            <a href="?term=1" class="term-btn <?php echo $current_term == '1' ? 'active' : ''; ?>">
                <i class="fas fa-play"></i> Term 1
            </a>
            <a href="?term=2" class="term-btn <?php echo $current_term == '2' ? 'active' : ''; ?>">
                <i class="fas fa-pause"></i> Term 2
            </a>
            <a href="?term=3" class="term-btn <?php echo $current_term == '3' ? 'active' : ''; ?>">
                <i class="fas fa-flag-checkered"></i> Term 3
            </a>
            <div style="flex: 1;"></div>
            <button class="btn-premium" onclick="openAddModal()" style="width: auto;">
                <i class="fas fa-plus-circle"></i> Log Communication
            </button>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-phone-alt"></i></div>
                <div class="stat-content">
                    <h3><?php echo count($communications); ?></h3>
                    <p>Total Interactions</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fab fa-whatsapp"></i></div>
                <div class="stat-content">
                    <h3><?php echo count(array_filter($communications, function($c) { return $c['contact_method'] == 'WhatsApp'; })); ?></h3>
                    <p>WhatsApp</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                <div class="stat-content">
                    <h3><?php echo count(array_filter($communications, function($c) { return $c['contact_method'] == 'Email'; })); ?></h3>
                    <p>Emails</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-comment-sms"></i></div>
                <div class="stat-content">
                    <h3><?php echo count(array_filter($communications, function($c) { return $c['contact_method'] == 'SMS'; })); ?></h3>
                    <p>SMS</p>
                </div>
            </div>
        </div>

        <!-- Quick Send -->
        <div class="quick-send">
            <h3><i class="fas fa-bolt"></i> Quick Send</h3>
            <div class="student-selector">
                <select class="student-select" id="quickStudent">
                    <option value="">Select Student...</option>
                    <?php foreach ($students as $s): ?>
                    <option value="<?php echo $s['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($s['parent_name']); ?>"
                            data-phone="<?php echo $s['parent_phone']; ?>"
                            data-email="<?php echo $s['parent_email']; ?>">
                        <?php echo htmlspecialchars($s['full_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <button class="btn-whatsapp" onclick="openModal('whatsapp')" id="quickWhatsAppBtn" disabled>
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </button>
                
                <button class="btn-email" onclick="openModal('email')" id="quickEmailBtn" disabled>
                    <i class="fas fa-envelope"></i> Email
                </button>
                
                <button class="btn-sms" onclick="openModal('sms')" id="quickSMSBtn" disabled>
                    <i class="fas fa-comment-sms"></i> SMS
                </button>
            </div>
        </div>

        <!-- Follow-ups -->
        <?php if (!empty($follow_ups)): ?>
        <div class="follow-up-section">
            <h3><i class="fas fa-bell"></i> Follow-ups Required</h3>
            <?php foreach ($follow_ups as $comm): ?>
            <div class="follow-up-item">
                <div>
                    <strong><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($comm['full_name']); ?></strong> - <?php echo htmlspecialchars($comm['purpose']); ?>
                    <br>
                    <small><i class="far fa-calendar-alt"></i> Due: <?php echo date('M d, Y', strtotime($comm['follow_up_date'])); ?></small>
                </div>
                <form method="POST">
                    <input type="hidden" name="comm_id" value="<?php echo $comm['id']; ?>">
                    <button type="submit" name="mark_completed" class="btn-mark">
                        <i class="fas fa-check-circle"></i> Mark Done
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- History -->
        <h2 style="color: var(--purple); margin: 30px 0 20px;">
            <i class="fas fa-history" style="color: var(--gold);"></i> Communication History
        </h2>

        <?php if (empty($history)): ?>
        <div class="empty-state">
            <i class="fas fa-comments"></i>
            <h3>No communication history</h3>
            <p>Start connecting with parents via WhatsApp, Email, or SMS</p>
        </div>
        <?php else: ?>
        <div class="comm-grid">
            <?php foreach ($history as $comm): ?>
            <div class="comm-card">
                <div class="comm-header">
                    <div>
                        <span class="student-name">
                            <i class="fas fa-user-graduate"></i> 
                            <?php echo htmlspecialchars($comm['full_name']); ?>
                        </span>
                        <?php if ($comm['parent_name']): ?>
                            <span style="color: var(--gray-600);">(<?php echo htmlspecialchars($comm['parent_name']); ?>)</span>
                        <?php endif; ?>
                    </div>
                    <span class="comm-date">
                        <i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($comm['communication_date'])); ?>
                    </span>
                </div>
                
                <div>
                    <span class="method-badge method-<?php echo strtolower($comm['contact_method']); ?>">
                        <i class="<?php 
                            echo $comm['contact_method'] == 'WhatsApp' ? 'fab fa-whatsapp' : 
                                ($comm['contact_method'] == 'Email' ? 'fas fa-envelope' : 
                                ($comm['contact_method'] == 'SMS' ? 'fas fa-comment-sms' : 'fas fa-phone')); 
                        ?>"></i>
                        <?php echo htmlspecialchars($comm['contact_method']); ?>
                    </span>
                    <span class="purpose-badge">
                        <i class="fas fa-tag"></i> 
                        <?php echo htmlspecialchars($comm['purpose']); ?>
                    </span>
                </div>
                
                <div class="comm-message">
                    <i class="fas fa-quote-left" style="color: var(--gold); opacity: 0.5; margin-right: 5px;"></i>
                    <?php echo nl2br(htmlspecialchars(substr($comm['notes'], 0, 200) . (strlen($comm['notes']) > 200 ? '...' : ''))); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- WhatsApp Modal -->
    <div id="whatsappModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fab fa-whatsapp"></i> Send WhatsApp</h2>
                <button class="close-btn" onclick="closeModal('whatsappModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="student_id" id="wa_student_id">
                <input type="hidden" name="parent_name" id="wa_parent_name">
                <input type="hidden" name="phone" id="wa_phone">
                
                <div class="form-group">
                    <label><i class="fas fa-user-graduate"></i> Student</label>
                    <input type="text" id="wa_student_name" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone-alt"></i> Phone</label>
                    <input type="text" id="wa_phone_display" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Message</label>
                    <textarea name="message_text" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" name="send_whatsapp" class="btn-whatsapp">Send WhatsApp</button>
            </form>
        </div>
    </div>

    <!-- Email Modal -->
    <div id="emailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-envelope"></i> Send Email</h2>
                <button class="close-btn" onclick="closeModal('emailModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="student_id" id="email_student_id">
                <input type="hidden" name="parent_name" id="email_parent_name">
                <input type="hidden" name="email" id="email_address">
                
                <div class="form-group">
                    <label><i class="fas fa-user-graduate"></i> Student</label>
                    <input type="text" id="email_student_name" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="text" id="email_display" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Subject</label>
                    <input type="text" name="email_subject" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Message</label>
                    <textarea name="email_body" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" name="send_email" class="btn-email">Send Email</button>
            </form>
        </div>
    </div>

    <!-- SMS Modal -->
    <div id="smsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-comment-sms"></i> Send SMS</h2>
                <button class="close-btn" onclick="closeModal('smsModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="student_id" id="sms_student_id">
                <input type="hidden" name="parent_name" id="sms_parent_name">
                <input type="hidden" name="phone" id="sms_phone">
                
                <div class="form-group">
                    <label><i class="fas fa-user-graduate"></i> Student</label>
                    <input type="text" id="sms_student_name" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone-alt"></i> Phone</label>
                    <input type="text" id="sms_phone_display" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Message (160 char max)</label>
                    <textarea name="sms_text" class="form-control" rows="3" maxlength="160" oninput="updateCounter(this)"></textarea>
                    <span id="smsCounter">0/160</span>
                </div>
                <button type="submit" name="send_sms" class="btn-sms">Send SMS</button>
            </form>
        </div>
    </div>

    <!-- Add Communication Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Log Communication</h2>
                <button class="close-btn" onclick="closeModal('addModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Parent Name</label>
                    <input type="text" name="parent_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Contact Method</label>
                    <select name="contact_method" class="form-control" required>
                        <option value="Phone Call">Phone Call</option>
                        <option value="WhatsApp">WhatsApp</option>
                        <option value="Email">Email</option>
                        <option value="SMS">SMS</option>
                        <option value="In Person">In Person</option>
                        <option value="Visitation Day">Visitation Day</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Purpose</label>
                    <select name="purpose" class="form-control" required>
                        <option value="Academic">Academic</option>
                        <option value="Behavior">Behavior</option>
                        <option value="Attendance">Attendance</option>
                        <option value="Health">Health</option>
                        <option value="Fees">Fees</option>
                        <option value="General">General</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox">
                        <input type="checkbox" name="follow_up_required" value="1" id="followUpCheckbox">
                        Follow-up Required
                    </label>
                </div>
                <div class="form-group" id="followUpDate" style="display: none;">
                    <label>Follow-up Date</label>
                    <input type="date" name="follow_up_date" class="form-control">
                </div>
                <button type="submit" name="add_communication" class="btn-premium">Save Communication</button>
            </form>
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

        // Student selector
        document.getElementById('quickStudent').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const hasPhone = selected.dataset.phone;
            const hasEmail = selected.dataset.email;
            
            document.getElementById('quickWhatsAppBtn').disabled = !hasPhone;
            document.getElementById('quickEmailBtn').disabled = !hasEmail;
            document.getElementById('quickSMSBtn').disabled = !hasPhone;
        });

        // Open modals
        function openModal(type) {
            const select = document.getElementById('quickStudent');
            const selected = select.options[select.selectedIndex];
            
            if (type === 'whatsapp') {
                document.getElementById('wa_student_id').value = selected.value;
                document.getElementById('wa_parent_name').value = selected.dataset.name;
                document.getElementById('wa_student_name').value = selected.text;
                document.getElementById('wa_phone').value = selected.dataset.phone;
                document.getElementById('wa_phone_display').value = selected.dataset.phone;
                document.getElementById('whatsappModal').style.display = 'flex';
            } else if (type === 'email') {
                document.getElementById('email_student_id').value = selected.value;
                document.getElementById('email_parent_name').value = selected.dataset.name;
                document.getElementById('email_student_name').value = selected.text;
                document.getElementById('email_address').value = selected.dataset.email;
                document.getElementById('email_display').value = selected.dataset.email;
                document.getElementById('emailModal').style.display = 'flex';
            } else if (type === 'sms') {
                document.getElementById('sms_student_id').value = selected.value;
                document.getElementById('sms_parent_name').value = selected.dataset.name;
                document.getElementById('sms_student_name').value = selected.text;
                document.getElementById('sms_phone').value = selected.dataset.phone;
                document.getElementById('sms_phone_display').value = selected.dataset.phone;
                document.getElementById('smsModal').style.display = 'flex';
            }
        }

        // Close modals
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // SMS counter
        function updateCounter(textarea) {
            const counter = document.getElementById('smsCounter');
            if (counter) {
                counter.innerText = textarea.value.length + '/160';
            }
        }

        // Follow-up date
        document.getElementById('followUpCheckbox')?.addEventListener('change', function() {
            const followUpDate = document.getElementById('followUpDate');
            if (followUpDate) {
                followUpDate.style.display = this.checked ? 'block' : 'none';
            }
        });

        // Click outside modal to close
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Open add modal
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
    </script>
</body>
</html>