<?php
// communication_functions.php - All communication functions for the school system

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================
// SEND WHATSAPP MESSAGE FUNCTION (UltraMsg)
// ============================================
function sendWhatsAppMessage($phone, $message) {
    $token = 'YOUR_ULTRAMSG_TOKEN';
    $instance_id = 'YOUR_ULTRAMSG_INSTANCE_ID';
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (substr($phone, 0, 1) == '0') {
        $phone = '256' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) != '256' && strlen($phone) == 9) {
        $phone = '256' . $phone;
    }

    if (empty($phone) || strlen($phone) < 10 || $phone == '256' || $phone == '256000000000') {
        return ['success' => false, 'message' => 'Invalid phone number'];
    }
    
    // UltraMsg API endpoint
    $url = "https://api.ultramsg.com/" . $instance_id . "/messages/chat";
    
    $data = [
        'token' => $token,
        'to' => $phone,
        'body' => $message,
        'priority' => 10,
        'referenceId' => 'attendance_' . time()
    ];
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Check if successful
    if ($http_code == 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['sent']) && $result['sent'] == 'true') {
            return ['success' => true, 'message' => 'WhatsApp message sent successfully'];
        }
    }
    
    return ['success' => false, 'message' => 'WhatsApp failed'];
}

// ============================================
// SEND SMS FUNCTION (Africa's Talking)
// ============================================
function sendSMS($phone, $message) {
    $username = 'sandbox';
    $api_key = 'YOUR_API_KEY';

    $phone = preg_replace('/[^0-9]/', '', $phone);

    if (substr($phone, 0, 1) == '0') {
        $phone = '256' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) != '256' && strlen($phone) == 9) {
        $phone = '256' . $phone;
    }
 
    if (empty($phone) || strlen($phone) < 10 || $phone == '256' || $phone == '256000000000') {
        return ['success' => false, 'message' => 'Invalid phone number'];
    }
 
    if (strlen($message) > 160) {
        $message = substr($message, 0, 157) . '...';
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'ApiKey: ' . $api_key
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 || $http_code == 201) {
        return ['success' => true, 'message' => 'SMS sent successfully'];
    }
    
    return ['success' => false, 'message' => 'SMS failed'];
}

// ============================================
// SEND EMAIL FUNCTION (PHPMailer with SMTP)
// ============================================
function sendEmail($to, $subject, $message) {
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
   
    $log_file = 'email_log.txt';
    $log_entry = "[" . date('Y-m-d H:i:s') . "]\n";
    $log_entry .= "To: $to\n";
    $log_entry .= "Subject: $subject\n";
    $log_entry .= "Message:\n$message\n";
    $log_entry .= str_repeat("-", 50) . "\n\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // Return success for testing
    return ['success' => true, 'message' => 'Email logged to email_log.txt'];
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = 'mail.privateemail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@raysofgrace.ac.ug';
        $mail->Password   = 'your_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('info@raysofgrace.ac.ug', 'Rays of Grace Junior School');
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br(htmlspecialchars($message));
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email failed: ' . $mail->ErrorInfo];
    }
}

// ============================================
// FUNCTION TO NOTIFY PARENT ABOUT ATTENDANCE
// ============================================
function notifyParent($student_name, $parent_phone, $parent_email, $parent_name, $session_name, $session_time, $status) {
    $notifications = [];
    
    // Prepare message
    $attendance_message = "🏫 *Rays of Grace Junior School*\n\n";
    $attendance_message .= "Dear parent of " . $student_name . ",\n\n";
    $attendance_message .= "Attendance Update for " . $session_name . " (" . $session_time . "):\n";
    $attendance_message .= "Status: *" . $status . "*\n\n";
    
    if ($status == 'Absent') {
        $attendance_message .= "Please contact the school for more information.\n\n";
    } elseif ($status == 'Late') {
        $attendance_message .= "Please ensure timely arrival to school.\n\n";
    } elseif ($status == 'Present') {
        $attendance_message .= "Student is present and accounted for.\n\n";
    }
    
    $attendance_message .= "Regards,\n";
    $attendance_message .= "Class Teacher - P.5 Purple\n";
    $attendance_message .= "Rays of Grace Junior School";
    
    // Try WhatsApp first if phone exists
    if (!empty($parent_phone) && $parent_phone != '00000000000' && $parent_phone != '0') {
        $result = sendWhatsAppMessage($parent_phone, $attendance_message);
        if ($result['success']) {
            $notifications[] = 'WhatsApp';
        }
    }
    
    // Try SMS (if WhatsApp failed or no phone)
    if (empty($notifications) && !empty($parent_phone) && $parent_phone != '00000000000' && $parent_phone != '0') {
        $sms_message = "Rays of Grace: " . $student_name . " - " . $session_name . ": " . $status;
        $result = sendSMS($parent_phone, $sms_message);
        if ($result['success']) {
            $notifications[] = 'SMS';
        }
    }
    
    // Try Email if available
    if (!empty($parent_email)) {
        $email_subject = "Attendance Update - " . $student_name . " - " . $session_name;
        $email_body = "Dear " . ($parent_name ?: 'Parent') . ",\n\n";
        $email_body .= "This is an attendance update for your child, " . $student_name . ".\n\n";
        $email_body .= "Session: " . $session_name . " (" . $session_time . ")\n";
        $email_body .= "Status: " . $status . "\n\n";
        
        if ($status == 'Absent') {
            $email_body .= "Please contact the school for more information.\n\n";
        } elseif ($status == 'Late') {
            $email_body .= "Please ensure timely arrival to school.\n\n";
        }
        
        $email_body .= "Regards,\n";
        $email_body .= "Class Teacher - P.5 Purple\n";
        $email_body .= "Rays of Grace Junior School";
        
        $result = sendEmail($parent_email, $email_subject, $email_body);
        if ($result['success']) {
            $notifications[] = 'Email';
        }
    }
    
    if (!empty($notifications)) {
        return ['success' => true, 'methods' => $notifications, 'message' => 'Notifications sent via ' . implode(' + ', $notifications)];
    }
    
    return ['success' => false, 'message' => 'No contact method available'];
}
?>