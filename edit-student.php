<?php
// edit-student.php - Complete Student Edit Form with ALL Fields
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';
$student_id = $_GET['id'] ?? 0;

// Get student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: students.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    try {
        // Personal Information
        $full_name = sanitize($_POST['full_name']);
        $gender = $_POST['gender'];
        $date_of_birth = $_POST['date_of_birth'] ?: null;
        $place_of_birth = sanitize($_POST['place_of_birth']);
        $nationality = sanitize($_POST['nationality']);
        $religion = sanitize($_POST['religion']);
        $languages_spoken = sanitize($_POST['languages_spoken']);
        
        // Residence Information
        $home_district = sanitize($_POST['home_district']);
        $home_county = sanitize($_POST['home_county']);
        $home_subcounty = sanitize($_POST['home_subcounty']);
        $home_parish = sanitize($_POST['home_parish']);
        $home_village = sanitize($_POST['home_village']);
        $distance_from_school = sanitize($_POST['distance_from_school']);
        $mode_of_transport = $_POST['mode_of_transport'] ?: null;
        $travel_time_minutes = $_POST['travel_time_minutes'] ?: null;
        
        // Parent Information
        $father_name = sanitize($_POST['father_name']);
        $father_phone = sanitize($_POST['father_phone']);
        $father_occupation = sanitize($_POST['father_occupation']);
        $mother_name = sanitize($_POST['mother_name']);
        $mother_phone = sanitize($_POST['mother_phone']);
        $mother_occupation = sanitize($_POST['mother_occupation']);
        $guardian_name = sanitize($_POST['guardian_name']);
        $guardian_phone = sanitize($_POST['guardian_phone']);
        $guardian_relationship = sanitize($_POST['guardian_relationship']);
        $parent_email = sanitize($_POST['parent_email']);
        
        // Medical Information
        $blood_group = $_POST['blood_group'] ?: null;
        $allergies = sanitize($_POST['allergies']);
        $medical_conditions = sanitize($_POST['medical_conditions']);
        $doctor_name = sanitize($_POST['doctor_name']);
        $doctor_phone = sanitize($_POST['doctor_phone']);
        $medications = sanitize($_POST['medications']);
        
        // Academic History
        $previous_school = sanitize($_POST['previous_school']);
        $previous_class = sanitize($_POST['previous_class']);
        $last_report_score = sanitize($_POST['last_report_score']);
        $special_needs = sanitize($_POST['special_needs']);
        
        // Documents
        $birth_certificate_number = sanitize($_POST['birth_certificate_number']);
        $medical_form_submitted = isset($_POST['medical_form_submitted']) ? 1 : 0;
        $previous_report_submitted = isset($_POST['previous_report_submitted']) ? 1 : 0;
        
        // Siblings
        $sibling_names = sanitize($_POST['sibling_names']);
        $sibling_classes = sanitize($_POST['sibling_classes']);
        
        // School Information
        $admission_number = sanitize($_POST['admission_number']);
        $student_type = $_POST['student_type'];
        $joined_date = $_POST['joined_date'] ?: null;
        $dormitory_number = sanitize($_POST['dormitory_number']);
        $bed_number = sanitize($_POST['bed_number']);
        $status = $_POST['status'];
        
        // Extracurricular
        $soccer_academy = isset($_POST['soccer_academy']) ? 1 : 0;
        $other_activities = sanitize($_POST['other_activities']);
        $sports_interests = sanitize($_POST['sports_interests']);
        $talents = sanitize($_POST['talents']);
        
        // Academic Notes
        $strengths = sanitize($_POST['strengths']);
        $weaknesses = sanitize($_POST['weaknesses']);
        $teacher_notes = sanitize($_POST['teacher_notes']);
        $counselor_notes = sanitize($_POST['counselor_notes']);
        
        // Fee Information
        $fee_status = $_POST['fee_status'];
        $last_fee_payment_date = $_POST['last_fee_payment_date'] ?: null;
        $fee_balance = $_POST['fee_balance'] ?: 0;

        $sql = "UPDATE students SET 
            full_name = ?, gender = ?, date_of_birth = ?, place_of_birth = ?,
            nationality = ?, religion = ?, languages_spoken = ?,
            home_district = ?, home_county = ?, home_subcounty = ?,
            home_parish = ?, home_village = ?, distance_from_school = ?,
            mode_of_transport = ?, travel_time_minutes = ?,
            father_name = ?, father_phone = ?, father_occupation = ?,
            mother_name = ?, mother_phone = ?, mother_occupation = ?,
            guardian_name = ?, guardian_phone = ?, guardian_relationship = ?,
            parent_email = ?,
            blood_group = ?, allergies = ?, medical_conditions = ?,
            doctor_name = ?, doctor_phone = ?, medications = ?,
            previous_school = ?, previous_class = ?, last_report_score = ?,
            special_needs = ?,
            birth_certificate_number = ?, medical_form_submitted = ?,
            previous_report_submitted = ?,
            sibling_names = ?, sibling_classes = ?,
            admission_number = ?, student_type = ?, joined_date = ?,
            dormitory_number = ?, bed_number = ?, status = ?,
            soccer_academy = ?, other_activities = ?, sports_interests = ?,
            talents = ?,
            strengths = ?, weaknesses = ?, teacher_notes = ?, counselor_notes = ?,
            fee_status = ?, last_fee_payment_date = ?, fee_balance = ?
            WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $full_name, $gender, $date_of_birth, $place_of_birth,
            $nationality, $religion, $languages_spoken,
            $home_district, $home_county, $home_subcounty,
            $home_parish, $home_village, $distance_from_school,
            $mode_of_transport, $travel_time_minutes,
            $father_name, $father_phone, $father_occupation,
            $mother_name, $mother_phone, $mother_occupation,
            $guardian_name, $guardian_phone, $guardian_relationship,
            $parent_email,
            $blood_group, $allergies, $medical_conditions,
            $doctor_name, $doctor_phone, $medications,
            $previous_school, $previous_class, $last_report_score,
            $special_needs,
            $birth_certificate_number, $medical_form_submitted,
            $previous_report_submitted,
            $sibling_names, $sibling_classes,
            $admission_number, $student_type, $joined_date,
            $dormitory_number, $bed_number, $status,
            $soccer_academy, $other_activities, $sports_interests,
            $talents,
            $strengths, $weaknesses, $teacher_notes, $counselor_notes,
            $fee_status, $last_fee_payment_date, $fee_balance,
            $student_id
        ]);

        $message = "Student profile updated successfully!";
        $message_type = "success";
        
        // Refresh student data
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
    } catch (PDOException $e) {
        $message = "Error updating profile: " . $e->getMessage();
        $message_type = "error";
    }
}

// Arrays for dropdowns
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
$transport_modes = ['Walking', 'Boda Boda', 'Taxi', 'Private Car', 'School Bus', 'Other'];
$fee_statuses = ['Paid', 'Partial', 'Unpaid', 'Scholarship'];
$religions = ['Christian', 'Muslim', 'Other'];
$nationalities = ['Ugandan', 'Kenyan', 'Tanzanian', 'Rwandan', 'South Sudanese', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - <?php echo $student['full_name']; ?></title>
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
            --success-dark: #219a52;
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
            padding: 30px 20px;
            color: var(--gray-800);
        }

        .premium-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .edit-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 30px;
            box-shadow: var(--shadow-lg);
            padding: 40px;
            border: 1px solid rgba(74, 26, 58, 0.1);
            animation: slideIn 0.5s ease;
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

        .edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg, var(--orange), var(--purple)) 1;
            position: relative;
        }

        .edit-header::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, var(--orange), var(--purple));
            border-radius: 3px;
        }

        .edit-header h1 {
            color: var(--purple-dark);
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .edit-header h1 i {
            color: var(--orange);
            margin-right: 15px;
            filter: drop-shadow(0 2px 4px rgba(239,91,43,0.3));
        }

        /* Alert Styles */
        .alert {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
            border-left: 4px solid transparent;
        }

        @keyframes slideDown {
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

        /* Form Sections */
        .form-section {
            background: var(--gray-50);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(74, 26, 58, 0.1);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .form-section:hover {
            border-color: var(--orange);
            box-shadow: var(--shadow-md);
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--orange), var(--purple));
            opacity: 0;
            transition: var(--transition);
        }

        .form-section:hover::before {
            opacity: 1;
        }

        .form-section h2 {
            color: var(--purple-dark);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--gray-300);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-section h2 i {
            color: var(--orange);
            font-size: 1.8rem;
            background: rgba(239, 91, 43, 0.1);
            padding: 10px;
            border-radius: 12px;
        }

        .form-section h3 {
            color: var(--purple);
            font-size: 1.2rem;
            font-weight: 600;
            margin: 20px 0 15px;
            padding-left: 10px;
            border-left: 3px solid var(--orange);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--purple-dark);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group label i {
            color: var(--orange);
            width: 20px;
            margin-right: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-300);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(239, 91, 43, 0.1);
            transform: translateY(-1px);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .readonly-field {
            background: var(--gray-100);
            border-color: var(--gray-400);
            color: var(--gray-700);
            cursor: not-allowed;
        }

        .readonly-field:focus {
            border-color: var(--gray-400);
            box-shadow: none;
            transform: none;
        }

        /* Checkbox Group */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            background: white;
            border: 2px solid var(--gray-300);
            border-radius: 12px;
            transition: var(--transition);
            cursor: pointer;
        }

        .checkbox-group:hover {
            border-color: var(--orange);
            background: rgba(239, 91, 43, 0.02);
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--orange);
        }

        .checkbox-group label {
            color: var(--purple-dark);
            font-weight: 500;
            cursor: pointer;
            flex: 1;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: var(--transition);
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
        }

        .btn i {
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .btn:hover i {
            transform: scale(1.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--purple-dark), #1a0d14);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--purple-dark);
            border: 2px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
            border-color: var(--orange);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, var(--orange-dark), #af2f0a);
        }

        /* Small Hint */
        .small-hint {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .small-hint i {
            color: var(--orange);
            font-size: 0.7rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .edit-container {
                padding: 30px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .edit-container {
                padding: 20px;
            }

            .edit-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .edit-header::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .edit-header h1 {
                font-size: 1.8rem;
            }

            .form-section {
                padding: 20px;
            }

            .form-section h2 {
                font-size: 1.3rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Spinner */
        .fa-spinner {
            color: var(--orange);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
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

        /* Focus Visible */
        :focus-visible {
            outline: 2px solid var(--orange);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="premium-container">
        <div class="edit-container">
            <div class="edit-header">
                <h1>
                    <i class="fas fa-user-edit"></i> 
                    Edit Student Profile
                </h1>
                <a href="student-profile.php?id=<?php echo $student['id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="editForm">
                <!-- SECTION 1: PERSONAL INFORMATION -->
                <div class="form-section">
                    <h2><i class="fas fa-user-circle"></i> Personal Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Admission Number</label>
                            <input type="text" name="admission_number" class="form-control readonly-field" 
                                   value="<?php echo $student['admission_number']; ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name *</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?php echo $student['full_name']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-venus-mars"></i> Gender *</label>
                            <select name="gender" class="form-control" required>
                                <option value="Male" <?php echo $student['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $student['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" 
                                   value="<?php echo $student['date_of_birth']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-pin"></i> Place of Birth</label>
                            <input type="text" name="place_of_birth" class="form-control" 
                                   value="<?php echo $student['place_of_birth']; ?>" placeholder="e.g., Kampala">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-flag"></i> Nationality</label>
                            <select name="nationality" class="form-control">
                                <option value="">Select Nationality</option>
                                <?php foreach ($nationalities as $nat): ?>
                                <option value="<?php echo $nat; ?>" <?php echo ($student['nationality'] ?? 'Ugandan') == $nat ? 'selected' : ''; ?>>
                                    <?php echo $nat; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-church"></i> Religion</label>
                            <select name="religion" class="form-control">
                                <option value="">Select Religion</option>
                                <?php foreach ($religions as $rel): ?>
                                <option value="<?php echo $rel; ?>" <?php echo ($student['religion'] ?? '') == $rel ? 'selected' : ''; ?>>
                                    <?php echo $rel; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-language"></i> Languages Spoken</label>
                            <input type="text" name="languages_spoken" class="form-control" 
                                   value="<?php echo $student['languages_spoken']; ?>" placeholder="e.g., Luganda, English">
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 2: RESIDENCE INFORMATION -->
                <div class="form-section">
                    <h2><i class="fas fa-home"></i> Residence Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-map"></i> District</label>
                            <input type="text" name="home_district" class="form-control" 
                                   value="<?php echo $student['home_district']; ?>" placeholder="e.g., Wakiso">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map"></i> County</label>
                            <input type="text" name="home_county" class="form-control" 
                                   value="<?php echo $student['home_county']; ?>" placeholder="e.g., Busiro">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map"></i> Sub-county</label>
                            <input type="text" name="home_subcounty" class="form-control" 
                                   value="<?php echo $student['home_subcounty']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map"></i> Parish</label>
                            <input type="text" name="home_parish" class="form-control" 
                                   value="<?php echo $student['home_parish']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Village</label>
                            <input type="text" name="home_village" class="form-control" 
                                   value="<?php echo $student['home_village']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-road"></i> Distance from School</label>
                            <input type="text" name="distance_from_school" class="form-control" 
                                   value="<?php echo $student['distance_from_school']; ?>" placeholder="e.g., 5km">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-bus"></i> Mode of Transport</label>
                            <select name="mode_of_transport" class="form-control">
                                <option value="">Select Mode</option>
                                <?php foreach ($transport_modes as $mode): ?>
                                <option value="<?php echo $mode; ?>" <?php echo ($student['mode_of_transport'] ?? '') == $mode ? 'selected' : ''; ?>>
                                    <?php echo $mode; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Travel Time (minutes)</label>
                            <input type="number" name="travel_time_minutes" class="form-control" 
                                   value="<?php echo $student['travel_time_minutes']; ?>" min="0">
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 3: PARENT/GUARDIAN INFORMATION -->
                <div class="form-section">
                    <h2><i class="fas fa-users"></i> Parent/Guardian Information</h2>
                    
                    <h3><i class="fas fa-male"></i> Father's Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_name" class="form-control" 
                                   value="<?php echo $student['father_name']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Father's Phone</label>
                            <input type="text" name="father_phone" class="form-control" 
                                   value="<?php echo $student['father_phone']; ?>" placeholder="256...">
                        </div>
                        
                        <div class="form-group">
                            <label>Father's Occupation</label>
                            <input type="text" name="father_occupation" class="form-control" 
                                   value="<?php echo $student['father_occupation']; ?>">
                        </div>
                    </div>
                    
                    <h3><i class="fas fa-female"></i> Mother's Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control" 
                                   value="<?php echo $student['mother_name']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Mother's Phone</label>
                            <input type="text" name="mother_phone" class="form-control" 
                                   value="<?php echo $student['mother_phone']; ?>" placeholder="256...">
                        </div>
                        
                        <div class="form-group">
                            <label>Mother's Occupation</label>
                            <input type="text" name="mother_occupation" class="form-control" 
                                   value="<?php echo $student['mother_occupation']; ?>">
                        </div>
                    </div>
                    
                    <h3><i class="fas fa-user-tie"></i> Guardian/Emergency Contact</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Guardian Name</label>
                            <input type="text" name="guardian_name" class="form-control" 
                                   value="<?php echo $student['guardian_name']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Guardian Phone</label>
                            <input type="text" name="guardian_phone" class="form-control" 
                                   value="<?php echo $student['guardian_phone']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Relationship</label>
                            <input type="text" name="guardian_relationship" class="form-control" 
                                   value="<?php echo $student['guardian_relationship']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Parent Email</label>
                            <input type="email" name="parent_email" class="form-control" 
                                   value="<?php echo $student['parent_email']; ?>">
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 4: MEDICAL INFORMATION -->
                <div class="form-section">
                    <h2><i class="fas fa-heartbeat"></i> Medical Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Blood Group</label>
                            <select name="blood_group" class="form-control">
                                <option value="">Select Blood Group</option>
                                <?php foreach ($blood_groups as $bg): ?>
                                <option value="<?php echo $bg; ?>" <?php echo ($student['blood_group'] ?? '') == $bg ? 'selected' : ''; ?>>
                                    <?php echo $bg; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Allergies</label>
                            <textarea name="allergies" class="form-control" placeholder="List any allergies"><?php echo $student['allergies']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Medical Conditions</label>
                            <textarea name="medical_conditions" class="form-control" placeholder="Any chronic conditions?"><?php echo $student['medical_conditions']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Medications</label>
                            <textarea name="medications" class="form-control" placeholder="Regular medications"><?php echo $student['medications']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Doctor's Name</label>
                            <input type="text" name="doctor_name" class="form-control" 
                                   value="<?php echo $student['doctor_name']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Doctor's Phone</label>
                            <input type="text" name="doctor_phone" class="form-control" 
                                   value="<?php echo $student['doctor_phone']; ?>">
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 5: ACADEMIC HISTORY -->
                <div class="form-section">
                    <h2><i class="fas fa-graduation-cap"></i> Academic History</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Previous School</label>
                            <input type="text" name="previous_school" class="form-control" 
                                   value="<?php echo $student['previous_school']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Previous Class</label>
                            <input type="text" name="previous_class" class="form-control" 
                                   value="<?php echo $student['previous_class']; ?>" placeholder="e.g., P.4">
                        </div>
                        
                        <div class="form-group">
                            <label>Last Report Score</label>
                            <input type="text" name="last_report_score" class="form-control" 
                                   value="<?php echo $student['last_report_score']; ?>" placeholder="e.g., 80% or Division 1">
                        </div>
                        
                        <div class="form-group">
                            <label>Special Needs</label>
                            <textarea name="special_needs" class="form-control" placeholder="Any special educational needs?"><?php echo $student['special_needs']; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 6: DOCUMENTS -->
                <div class="form-section">
                    <h2><i class="fas fa-file-alt"></i> Documents</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Birth Certificate Number</label>
                            <input type="text" name="birth_certificate_number" class="form-control" 
                                   value="<?php echo $student['birth_certificate_number']; ?>">
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="medical_form_submitted" id="medical_form" value="1" 
                                   <?php echo $student['medical_form_submitted'] ? 'checked' : ''; ?>>
                            <label for="medical_form">Medical Form Submitted</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="previous_report_submitted" id="previous_report" value="1" 
                                   <?php echo $student['previous_report_submitted'] ? 'checked' : ''; ?>>
                            <label for="previous_report">Previous School Report Submitted</label>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 7: SIBLINGS -->
                <div class="form-section">
                    <h2><i class="fas fa-child"></i> Siblings at School</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Sibling Names (one per line)</label>
                            <textarea name="sibling_names" class="form-control" placeholder="e.g., John Okello&#10;Mary Akello"><?php echo $student['sibling_names']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Their Classes (one per line)</label>
                            <textarea name="sibling_classes" class="form-control" placeholder="e.g., P.6&#10;P.3"><?php echo $student['sibling_classes']; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 8: SCHOOL INFORMATION -->
                <div class="form-section">
                    <h2><i class="fas fa-school"></i> School Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Student Type *</label>
                            <select name="student_type" class="form-control" id="studentType" required>
                                <option value="Day Scholar" <?php echo $student['student_type'] == 'Day Scholar' ? 'selected' : ''; ?>>Day Scholar</option>
                                <option value="Boarder" <?php echo $student['student_type'] == 'Boarder' ? 'selected' : ''; ?>>Boarder</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Joined Date</label>
                            <input type="date" name="joined_date" class="form-control" 
                                   value="<?php echo $student['joined_date']; ?>">
                        </div>
                        
                        <div class="form-group" id="dormitoryGroup" style="<?php echo $student['student_type'] == 'Boarder' ? '' : 'display: none;'; ?>">
                            <label>Dormitory Number</label>
                            <input type="text" name="dormitory_number" class="form-control" 
                                   value="<?php echo $student['dormitory_number']; ?>">
                        </div>
                        
                        <div class="form-group" id="bedGroup" style="<?php echo $student['student_type'] == 'Boarder' ? '' : 'display: none;'; ?>">
                            <label>Bed Number</label>
                            <input type="text" name="bed_number" class="form-control" 
                                   value="<?php echo $student['bed_number']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="Active" <?php echo $student['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $student['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Transferred" <?php echo $student['status'] == 'Transferred' ? 'selected' : ''; ?>>Transferred</option>
                                <option value="Graduated" <?php echo $student['status'] == 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 9: EXTRACURRICULAR -->
                <div class="form-section">
                    <h2><i class="fas fa-running"></i> Extracurricular Activities</h2>
                    <div class="form-grid">
                        <div class="checkbox-group">
                            <input type="checkbox" name="soccer_academy" id="soccer_academy" value="1" 
                                   <?php echo $student['soccer_academy'] ? 'checked' : ''; ?>>
                            <label for="soccer_academy">⚽ Soccer Academy</label>
                        </div>
                        
                        <div class="form-group">
                            <label>Other Activities</label>
                            <textarea name="other_activities" class="form-control" placeholder="e.g., Choir, Drama, Debate"><?php echo $student['other_activities']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Sports Interests</label>
                            <input type="text" name="sports_interests" class="form-control" 
                                   value="<?php echo $student['sports_interests']; ?>" placeholder="e.g., Football, Netball">
                        </div>
                        
                        <div class="form-group">
                            <label>Talents</label>
                            <input type="text" name="talents" class="form-control" 
                                   value="<?php echo $student['talents']; ?>" placeholder="e.g., Singing, Drawing">
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 10: ACADEMIC NOTES -->
                <div class="form-section">
                    <h2><i class="fas fa-sticky-note"></i> Academic Notes</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Strengths</label>
                            <textarea name="strengths" class="form-control" placeholder="What is this student good at?"><?php echo $student['strengths']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Areas for Improvement</label>
                            <textarea name="weaknesses" class="form-control" placeholder="What needs work?"><?php echo $student['weaknesses']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Teacher Notes</label>
                            <textarea name="teacher_notes" class="form-control" placeholder="General observations"><?php echo $student['teacher_notes']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Counselor Notes</label>
                            <textarea name="counselor_notes" class="form-control" placeholder="Any counseling notes"><?php echo $student['counselor_notes']; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 11: FEE INFORMATION -->
                <div class="form-section">
                    <h2><i class="fas fa-money-bill"></i> Fee Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Fee Status</label>
                            <select name="fee_status" class="form-control">
                                <option value="">Select Status</option>
                                <?php foreach ($fee_statuses as $fs): ?>
                                <option value="<?php echo $fs; ?>" <?php echo ($student['fee_status'] ?? '') == $fs ? 'selected' : ''; ?>>
                                    <?php echo $fs; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Last Payment Date</label>
                            <input type="date" name="last_fee_payment_date" class="form-control" 
                                   value="<?php echo $student['last_fee_payment_date']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Fee Balance (UGX)</label>
                            <input type="number" name="fee_balance" class="form-control" 
                                   value="<?php echo $student['fee_balance'] ?? 0; ?>" min="0" step="1000">
                            <div class="small-hint">
                                <i class="fas fa-info-circle"></i> Enter amount in Uganda Shillings
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ACTION BUTTONS -->
                <div class="action-buttons">
                    <a href="student-profile.php?id=<?php echo $student['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="update_student" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="upload-student-photo.php?student_id=<?php echo $student['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-camera"></i> Update Photo
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle boarding fields
        document.getElementById('studentType').addEventListener('change', function() {
            const dormGroup = document.getElementById('dormitoryGroup');
            const bedGroup = document.getElementById('bedGroup');
            
            if (this.value === 'Boarder') {
                dormGroup.style.display = 'block';
                bedGroup.style.display = 'block';
            } else {
                dormGroup.style.display = 'none';
                bedGroup.style.display = 'none';
            }
        });

        // Confirm before leaving with unsaved changes
        let formChanged = false;
        document.getElementById('editForm').addEventListener('input', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        document.getElementById('editForm').addEventListener('submit', function() {
            formChanged = false;
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Add smooth scroll to sections
        document.querySelectorAll('.form-section h2').forEach(heading => {
            heading.addEventListener('click', function() {
                this.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
    </script>
</body>
</html>