<?php
// students.php - Complete Student Management with Search & Sort
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle form submissions (ADD, EDIT, DELETE - same as before)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ADD STUDENT
    if (isset($_POST['add_student'])) {
        $admission = sanitize($_POST['admission_number']);
        $name = sanitize($_POST['full_name']);
        $gender = $_POST['gender'];
        $type = $_POST['student_type'];
        $parent = sanitize($_POST['parent_name']);
        $phone = sanitize($_POST['parent_phone']);
        $soccer = isset($_POST['soccer_academy']) ? 1 : 0;
        $dormitory = $_POST['dormitory_number'] ?? null;
        $bed = $_POST['bed_number'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO students 
            (admission_number, full_name, gender, student_type, parent_name, parent_phone, soccer_academy, dormitory_number, bed_number, joined_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$admission, $name, $gender, $type, $parent, $phone, $soccer, $dormitory, $bed, date('Y-m-d')])) {
            $message = "Student added successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding student.";
            $message_type = "error";
        }
    }
    
    // EDIT STUDENT
    if (isset($_POST['edit_student'])) {
        $id = $_POST['student_id'];
        $name = sanitize($_POST['full_name']);
        $gender = $_POST['gender'];
        $type = $_POST['student_type'];
        $parent = sanitize($_POST['parent_name']);
        $phone = sanitize($_POST['parent_phone']);
        $soccer = isset($_POST['soccer_academy']) ? 1 : 0;
        $dormitory = $_POST['dormitory_number'] ?? null;
        $bed = $_POST['bed_number'] ?? null;
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE students SET 
            full_name=?, gender=?, student_type=?, parent_name=?, parent_phone=?, 
            soccer_academy=?, dormitory_number=?, bed_number=?, status=? 
            WHERE id=?");
        if ($stmt->execute([$name, $gender, $type, $parent, $phone, $soccer, $dormitory, $bed, $status, $id])) {
            $message = "Student updated successfully!";
            $message_type = "success";
        }
    }
    
    // DELETE STUDENT
    if (isset($_POST['delete_student'])) {
        $id = $_POST['student_id'];
        
        // Get photo path to delete
        $stmt = $pdo->prepare("SELECT photo_path FROM students WHERE id=?");
        $stmt->execute([$id]);
        $photo = $stmt->fetchColumn();
        
        if ($photo && file_exists($photo)) {
            unlink($photo);
        }
        
        // Delete student (or set status to Inactive)
        $stmt = $pdo->prepare("UPDATE students SET status='Inactive' WHERE id=?");
        if ($stmt->execute([$id])) {
            $message = "Student deactivated successfully!";
            $message_type = "success";
        }
    }
}

// ===== SEARCH AND SORT PARAMETERS =====
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'full_name';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'all';
$filter_soccer = isset($_GET['filter_soccer']) ? $_GET['filter_soccer'] : 'all';

// Validate sort parameters
$allowed_sort = ['full_name', 'admission_number', 'student_type', 'parent_name', 'joined_date'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'full_name';
}
$sort_order = ($sort_order == 'DESC') ? 'DESC' : 'ASC';

// Build the query with search and filters
$query = "SELECT * FROM students WHERE status = 'Active'";
$params = [];

// Add search condition
if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR admission_number LIKE ? OR parent_name LIKE ? OR parent_phone LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

// Add student type filter
if ($filter_type != 'all') {
    $query .= " AND student_type = ?";
    $params[] = $filter_type;
}

// Add soccer academy filter
if ($filter_soccer != 'all') {
    $query .= " AND soccer_academy = ?";
    $params[] = ($filter_soccer == 'yes') ? 1 : 0;
}

// Add sorting
$query .= " ORDER BY $sort_by $sort_order";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get counts for stats
$total_students = count($students);
$day_scholars_count = count(array_filter($students, function($s) { return $s['student_type'] == 'Day Scholar'; }));
$boarders_count = count(array_filter($students, function($s) { return $s['student_type'] == 'Boarder'; }));
$soccer_count = count(array_filter($students, function($s) { return $s['soccer_academy'] == 1; }));

// Group by type for display (after filtering)
$day_scholars = array_filter($students, function($s) { return $s['student_type'] == 'Day Scholar'; });
$boarders = array_filter($students, function($s) { return $s['student_type'] == 'Boarder'; });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - P.5 Purple</title>
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
        }

        .premium-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
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

        .btn-add {
            background: var(--orange);
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgba(239, 91, 43, 0.4);
        }

        .btn-add:hover {
            background: var(--orange-dark);
        }

        /* Alert */
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

        .alert-success {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-left-color: var(--success);
            color: var(--success-dark);
        }

        .alert-error {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            border-left-color: var(--danger);
            color: #c62828;
        }

        /* Search and Filter Bar */
        .search-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(74, 26, 58, 0.1);
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .search-group {
            flex: 2;
            min-width: 250px;
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .search-group label, .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--purple-dark);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .search-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-300);
            border-radius: 12px;
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(239,91,43,0.1);
        }

        .filter-select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-300);
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--orange);
        }

        .search-btn {
            background: var(--purple);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            height: 54px;
        }

        .search-btn:hover {
            background: var(--purple-dark);
            transform: translateY(-2px);
        }

        .reset-btn {
            background: var(--gray-200);
            color: var(--gray-700);
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            text-decoration: none;
            height: 54px;
        }

        .reset-btn:hover {
            background: var(--gray-300);
        }

        /* Results Stats */
        .results-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .stats-info {
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        .stats-info strong {
            color: var(--purple-dark);
            font-size: 1.2rem;
        }

        .sort-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .sort-label {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .sort-link {
            padding: 8px 16px;
            background: var(--gray-100);
            border-radius: 30px;
            color: var(--gray-700);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
            border: 1px solid var(--gray-300);
        }

        .sort-link:hover {
            background: var(--orange);
            color: white;
            border-color: var(--orange);
        }

        .sort-link.active {
            background: var(--purple);
            color: white;
            border-color: var(--purple);
        }

        .sort-link i {
            font-size: 0.8rem;
        }

        /* Section Title */
        .section-title {
            color: var(--purple-dark);
            margin: 40px 0 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            position: relative;
            padding-bottom: 12px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--orange), var(--purple));
            border-radius: 4px;
        }

        .section-title i {
            color: var(--orange);
            font-size: 2rem;
        }

        /* Student Gallery */
        .student-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }

        .student-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: 1px solid rgba(74, 26, 58, 0.1);
            position: relative;
        }

        .student-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
            border-color: var(--orange);
        }

        .student-photo {
            height: 180px;
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .student-card:hover .student-photo img {
            transform: scale(1.05);
        }

        .student-photo-placeholder {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: white;
            border: 4px solid rgba(255,255,255,0.3);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 1;
        }

        .student-type-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: rgba(255,255,255,0.95);
            color: var(--purple-dark);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(239,91,43,0.3);
            z-index: 2;
        }

        .student-info {
            padding: 20px;
            background: white;
        }

        .student-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--purple-dark);
            margin-bottom: 4px;
        }

        .student-admission {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .student-admission i {
            color: var(--orange);
        }

        .student-detail {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            color: var(--gray-700);
            font-size: 0.95rem;
            border-bottom: 1px dashed var(--gray-200);
        }

        .student-detail i {
            color: var(--orange);
            width: 20px;
        }

        .soccer-badge {
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
            color: white;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
            margin-top: 8px;
            box-shadow: 0 2px 8px rgba(239,91,43,0.3);
        }

        .student-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid var(--gray-200);
        }

        .action-btn {
            padding: 8px 6px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: var(--transition);
            text-decoration: none;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-btn i {
            font-size: 0.9rem;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            filter: brightness(110%);
        }

        .btn-view {
            background: linear-gradient(135deg, var(--info), #2980b9);
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--success), #219a52);
        }

        .btn-photo {
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger), #c0392b);
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
        }

        .no-results i {
            font-size: 4rem;
            color: var(--gray-400);
            margin-bottom: 15px;
        }

        .no-results h3 {
            color: var(--gray-600);
            margin-bottom: 10px;
        }

        .no-results p {
            color: var(--gray-500);
        }

        /* Modal Styles (keep existing) */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 24px;
            padding: 30px;
            max-width: 550px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: modalSlideIn 0.3s ease;
            border: 1px solid var(--orange);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-200);
        }

        .modal-header h2 {
            color: var(--purple-dark);
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 i {
            color: var(--orange);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: var(--gray-500);
            transition: var(--transition);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-btn:hover {
            background: var(--gray-200);
            color: var(--danger);
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--purple-dark);
            font-weight: 600;
        }

        .form-group label i {
            color: var(--orange);
            margin-right: 6px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-300);
            border-radius: 12px;
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(239,91,43,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px 12px;
            background: var(--gray-100);
            border-radius: 10px;
        }

        .checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--orange);
        }

        @media (max-width: 768px) {
            .student-gallery {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-group, .filter-group {
                width: 100%;
            }
            
            .results-stats {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="premium-container">
        <!-- Header -->
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1><i class="fas fa-users"></i> Student Management</h1>
                    <div class="class-slogan">
                        <i class="fas fa-star"></i>
                        <?php echo $total_students; ?> Active Students • P.5 Purple
                    </div>
                </div>
                <div class="class-badge">
                    <button class="btn-premium btn-add" onclick="openAddModal()">
                        <i class="fas fa-user-plus"></i> Add Student
                    </button>
                    <a href="upload-student-photo.php" class="btn-premium">
                        <i class="fas fa-camera"></i> Photos
                    </a>
                    <a href="index.php" class="btn-premium">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Search and Filter Section -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <div class="search-group">
                    <label><i class="fas fa-search"></i> Search Students</label>
                    <input type="text" name="search" class="search-input" 
                           placeholder="Search by name, admission number, parent name or phone..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Student Type</label>
                    <select name="filter_type" class="filter-select">
                        <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="Day Scholar" <?php echo $filter_type == 'Day Scholar' ? 'selected' : ''; ?>>Day Scholars</option>
                        <option value="Boarder" <?php echo $filter_type == 'Boarder' ? 'selected' : ''; ?>>Boarders</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-futbol"></i> Soccer Academy</label>
                    <select name="filter_soccer" class="filter-select">
                        <option value="all" <?php echo $filter_soccer == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="yes" <?php echo $filter_soccer == 'yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="no" <?php echo $filter_soccer == 'no' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
                
                <input type="hidden" name="sort_by" value="<?php echo $sort_by; ?>">
                <input type="hidden" name="sort_order" value="<?php echo $sort_order; ?>">
                
                <button type="submit" class="search-btn">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                
                <a href="students.php" class="reset-btn">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </form>
        </div>

        <!-- Results Stats and Sort Controls -->
        <div class="results-stats">
            <div class="stats-info">
                <strong><?php echo $total_students; ?></strong> students found 
                <?php if (!empty($search)): ?>
                    matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                <?php endif; ?>
                <span style="margin-left: 10px; color: var(--gray-500);">
                    (<?php echo $day_scholars_count; ?> Day • <?php echo $boarders_count; ?> Boarders • <?php echo $soccer_count; ?> ⚽)
                </span>
            </div>
            
            <div class="sort-controls">
                <span class="sort-label">Sort by:</span>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'full_name', 'sort_order' => ($sort_by == 'full_name' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                   class="sort-link <?php echo $sort_by == 'full_name' ? 'active' : ''; ?>">
                    Name <?php if($sort_by == 'full_name') echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'student_type', 'sort_order' => ($sort_by == 'student_type' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                   class="sort-link <?php echo $sort_by == 'student_type' ? 'active' : ''; ?>">
                    Type <?php if($sort_by == 'student_type') echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'admission_number', 'sort_order' => ($sort_by == 'admission_number' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                   class="sort-link <?php echo $sort_by == 'admission_number' ? 'active' : ''; ?>">
                    Admission <?php if($sort_by == 'admission_number') echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'parent_name', 'sort_order' => ($sort_by == 'parent_name' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                   class="sort-link <?php echo $sort_by == 'parent_name' ? 'active' : ''; ?>">
                    Parent <?php if($sort_by == 'parent_name') echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                </a>
            </div>
        </div>

        <?php if (empty($students)): ?>
            <!-- No Results -->
            <div class="no-results">
                <i class="fas fa-user-slash"></i>
                <h3>No students found</h3>
                <p>Try adjusting your search or filter criteria</p>
            </div>
        <?php else: ?>
            <!-- Day Scholars Section -->
            <?php if (!empty($day_scholars)): ?>
            <h2 class="section-title">
                <i class="fas fa-sun"></i> Day Scholars (<?php echo count($day_scholars); ?>)
            </h2>
            
            <div class="student-gallery">
                <?php foreach ($day_scholars as $student): ?>
                <div class="student-card">
                    <div class="student-photo">
                        <?php if (!empty($student['photo_path']) && file_exists($student['photo_path'])): ?>
                            <img src="<?php echo $student['photo_path']; ?>" alt="<?php echo $student['full_name']; ?>">
                        <?php else: ?>
                            <div class="student-photo-placeholder">
                                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <span class="student-type-badge">
                            <i class="fas fa-sun"></i> Day
                        </span>
                    </div>
                    
                    <div class="student-info">
                        <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                        <div class="student-admission">
                            <i class="fas fa-id-card"></i>
                            <?php echo htmlspecialchars($student['admission_number']); ?>
                        </div>
                        
                        <div class="student-detail">
                            <i class="fas fa-venus-mars"></i>
                            <?php echo $student['gender']; ?>
                        </div>
                        
                        <div class="student-detail">
                            <i class="fas fa-user-tie"></i>
                            <?php echo htmlspecialchars($student['parent_name'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="student-detail">
                            <i class="fas fa-phone"></i>
                            <?php echo htmlspecialchars($student['parent_phone'] ?? 'N/A'); ?>
                        </div>
                        
                        <?php if ($student['soccer_academy']): ?>
                            <span class="soccer-badge">
                                <i class="fas fa-futbol"></i> Soccer Academy
                            </span>
                        <?php endif; ?>
                        
                        <div class="student-actions">
                            <a href="student-profile.php?id=<?php echo $student['id']; ?>" class="action-btn btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="edit-student.php?id=<?php echo $student['id']; ?>" class="action-btn btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="upload-student-photo.php?student_id=<?php echo $student['id']; ?>" class="action-btn btn-photo">
                                <i class="fas fa-camera"></i> Photo
                            </a>
                            <button class="action-btn btn-delete" onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo addslashes($student['full_name']); ?>')">
                                <i class="fas fa-trash"></i> Del
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Boarders Section -->
            <?php if (!empty($boarders)): ?>
            <h2 class="section-title">
                <i class="fas fa-moon"></i> Boarders (<?php echo count($boarders); ?>)
            </h2>
            
            <div class="student-gallery">
                <?php foreach ($boarders as $student): ?>
                <div class="student-card">
                    <div class="student-photo">
                        <?php if (!empty($student['photo_path']) && file_exists($student['photo_path'])): ?>
                            <img src="<?php echo $student['photo_path']; ?>" alt="<?php echo $student['full_name']; ?>">
                        <?php else: ?>
                            <div class="student-photo-placeholder">
                                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <span class="student-type-badge">
                            <i class="fas fa-moon"></i> Boarder
                        </span>
                    </div>
                    
                    <div class="student-info">
                        <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                        <div class="student-admission">
                            <i class="fas fa-id-card"></i>
                            <?php echo htmlspecialchars($student['admission_number']); ?>
                        </div>
                        
                        <div class="student-detail">
                            <i class="fas fa-venus-mars"></i>
                            <?php echo $student['gender']; ?>
                        </div>
                        
                        <div class="student-detail">
                            <i class="fas fa-user-tie"></i>
                            <?php echo htmlspecialchars($student['parent_name'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="student-detail">
                            <i class="fas fa-phone"></i>
                            <?php echo htmlspecialchars($student['parent_phone'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="student-detail">
                            <i class="fas fa-bed"></i>
                            Dorm: <?php echo $student['dormitory_number'] ?: 'Not assigned'; ?> | Bed: <?php echo $student['bed_number'] ?: 'N/A'; ?>
                        </div>
                        
                        <?php if ($student['soccer_academy']): ?>
                            <span class="soccer-badge">
                                <i class="fas fa-futbol"></i> Soccer Academy
                            </span>
                        <?php endif; ?>
                        
                        <div class="student-actions">
                            <a href="student-profile.php?id=<?php echo $student['id']; ?>" class="action-btn btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="edit-student.php?id=<?php echo $student['id']; ?>" class="action-btn btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="upload-student-photo.php?student_id=<?php echo $student['id']; ?>" class="action-btn btn-photo">
                                <i class="fas fa-camera"></i> Photo
                            </a>
                            <button class="action-btn btn-delete" onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo addslashes($student['full_name']); ?>')">
                                <i class="fas fa-trash"></i> Del
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ADD STUDENT MODAL (keep existing) -->
    <div id="addModal" class="modal">
        <!-- ... existing add modal content ... -->
    </div>

    <!-- EDIT STUDENT MODAL (keep existing) -->
    <div id="editModal" class="modal">
        <!-- ... existing edit modal content ... -->
    </div>

    <!-- VIEW STUDENT MODAL (keep existing) -->
    <div id="viewModal" class="modal">
        <!-- ... existing view modal content ... -->
    </div>

    <!-- DELETE CONFIRMATION MODAL (keep existing) -->
    <div id="deleteModal" class="modal">
        <!-- ... existing delete modal content ... -->
    </div>

    <script>
        // Keep all existing JavaScript functions
        
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function editStudent(id) {
            window.location.href = 'edit-student.php?id=' + id;
        }

        function viewStudent(id) {
            window.location.href = 'student-profile.php?id=' + id;
        }

        function uploadPhoto(id) {
            window.location.href = 'upload-student-photo.php?student_id=' + id;
        }

        function deleteStudent(id, name) {
            document.getElementById('deleteStudentId').value = id;
            document.getElementById('deleteStudentName').textContent = name;
            document.getElementById('deleteModal').classList.add('active');
        }

        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.classList.remove('active');
                }
            });
        }

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>