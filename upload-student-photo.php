<?php
// upload-student-photo.php - Handle student photo uploads
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

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    $student_id = $_POST['student_id'];
    
    // Check if student exists
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $error = "Student not found.";
    } elseif (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/students/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['student_photo']['name']);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is actual image
        $check = getimagesize($_FILES['student_photo']['tmp_name']);
        if ($check === false) {
            $error = "File is not an image.";
        }
        // Check file size (max 2MB)
        elseif ($_FILES['student_photo']['size'] > 2000000) {
            $error = "File is too large. Max size is 2MB.";
        }
        // Allow certain file formats
        elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
        else {
            // Delete old photo if exists
            if (!empty($student['photo_path']) && file_exists($student['photo_path'])) {
                unlink($student['photo_path']);
            }
            
            if (move_uploaded_file($_FILES['student_photo']['tmp_name'], $target_file)) {
                // Update database
                $stmt = $pdo->prepare("UPDATE students SET photo_path = ? WHERE id = ?");
                if ($stmt->execute([$target_file, $student_id])) {
                    $message = "Photo uploaded successfully!";
                } else {
                    $error = "Database update failed.";
                }
            } else {
                $error = "Error uploading file.";
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Get student list for dropdown
$students = $pdo->query("SELECT id, full_name, photo_path FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Student Photo - P.5 Purple</title>
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

        /* Main Container */
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            padding: 40px;
            border: 1px solid rgba(74, 26, 58, 0.1);
        }

        h1 {
            color: #4B1C3C;
            margin-bottom: 30px;
            font-size: 2.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        h1 i {
            color: #FFB800;
        }
        
        .upload-form {
            background: linear-gradient(135deg, #f8f4f8, #fcf9fc);
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(75,28,60,0.1);
        }
        
        .upload-form h2 {
            color: #4B1C3C;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .upload-form h2 i {
            color: #FFB800;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #4B1C3C;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        label i {
            color: #FFB800;
            margin-right: 5px;
        }
        
        select, input[type="file"] {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-300);
            border-radius: 12px;
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            background: white;
        }
        
        select:focus, input[type="file"]:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(239,91,43,0.1);
        }
        
        .btn-upload {
            background: linear-gradient(135deg, #4B1C3C, #2F1224);
            color: white;
            padding: 14px 32px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }
        
        .btn-upload:hover {
            background: linear-gradient(135deg, #6a2b52, #4B1C3C);
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }
        
        .btn-upload i {
            color: #FFB800;
        }
        
        .message {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.success {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-left-color: #4CAF50;
            color: #2E7D32;
        }
        
        .message.error {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            border-left-color: #f44336;
            color: #C62828;
        }
        
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .student-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: 1px solid rgba(74, 26, 58, 0.1);
        }
        
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            border-color: #FFB800;
        }
        
        .student-photo {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #4B1C3C, #2F1224);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFB800;
            font-size: 4rem;
            border-bottom: 3px solid #FFB800;
        }
        
        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .student-info {
            padding: 20px;
            background: white;
        }
        
        .student-name {
            font-weight: 600;
            color: #4B1C3C;
            font-size: 1.1rem;
            margin-bottom: 12px;
        }
        
        .btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        
        .btn-small {
            background: #f8f4f8;
            color: #4B1C3C;
            padding: 8px 12px;
            border: 1px solid #e0d0e0;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: var(--transition);
        }
        
        .btn-small:hover {
            background: #4B1C3C;
            color: white;
            border-color: #4B1C3C;
        }
        
        .btn-small i {
            color: #FFB800;
        }
        
        .btn-small:hover i {
            color: #FFB800;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #666;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            transition: var(--transition);
            margin-top: 30px;
        }
        
        .btn-back:hover {
            background: #444;
            transform: translateX(-5px);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-container {
                padding: 30px;
            }
            
            h1 {
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
            
            .main-container {
                padding: 20px;
            }
            
            h1 {
                font-size: 1.8rem;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .upload-form {
                padding: 20px;
            }
            
            .upload-form h2 {
                font-size: 1.3rem;
            }
            
            .student-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .student-photo {
                height: 150px;
                font-size: 3rem;
            }
            
            .btn-group {
                grid-template-columns: 1fr;
            }
            
            .btn-upload {
                width: 100%;
                justify-content: center;
            }
            
            .btn-back {
                width: 100%;
                justify-content: center;
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
            
            .main-container {
                padding: 15px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .upload-form h2 {
                font-size: 1.2rem;
            }
            
            .student-grid {
                grid-template-columns: 1fr;
            }
            
            select, input[type="file"] {
                padding: 12px;
                font-size: 0.95rem;
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

        <!-- Premium Header with Teacher Info -->
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1>
                        <i class="fas fa-camera"></i> 
                        Photo Management
                    </h1>
                    <div class="class-slogan">
                        <i class="fas fa-star"></i>
                        Upload and manage student photos
                    </div>
                </div>
                <div class="class-badge">
                    <a href="students.php" class="btn-premium">
                        <i class="fas fa-arrow-left"></i> Back to Students
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

        <!-- Main Content -->
        <div class="main-container">
            <h1>
                <i class="fas fa-camera"></i> 
                Student Photo Management
            </h1>
            
            <?php if ($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="upload-form">
                <h2>
                    <i class="fas fa-upload"></i> 
                    Upload New Photo
                </h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-user-graduate"></i> 
                            Select Student
                        </label>
                        <select name="student_id" required>
                            <option value="">Choose student...</option>
                            <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['full_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-image"></i> 
                            Choose Photo (Max 2MB)
                        </label>
                        <input type="file" name="student_photo" accept="image/*" required>
                    </div>
                    
                    <button type="submit" name="upload_photo" class="btn-upload">
                        <i class="fas fa-upload"></i> Upload Photo
                    </button>
                </form>
            </div>
            
            <h2 style="color: #4B1C3C; margin-bottom: 20px; font-size: 1.5rem;">
                <i class="fas fa-images" style="color: #FFB800; margin-right: 10px;"></i>
                Student Photos
            </h2>
            
            <div class="student-grid">
                <?php foreach ($students as $s): ?>
                <div class="student-card">
                    <div class="student-photo">
                        <?php if (!empty($s['photo_path']) && file_exists($s['photo_path'])): ?>
                            <img src="<?php echo $s['photo_path']; ?>" alt="<?php echo $s['full_name']; ?>">
                        <?php else: ?>
                            <i class="fas fa-user-graduate"></i>
                        <?php endif; ?>
                    </div>
                    <div class="student-info">
                        <div class="student-name"><?php echo $s['full_name']; ?></div>
                        <div class="btn-group">
                            <a href="student-profile.php?id=<?php echo $s['id']; ?>" class="btn-small">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="edit-student.php?id=<?php echo $s['id']; ?>" class="btn-small">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <a href="students.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
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

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.message');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>