<?php
// add-student.php - Complete Add Student Form with ALL Fields
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
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
        $joined_date = $_POST['joined_date'] ?: date('Y-m-d');
        $dormitory_number = sanitize($_POST['dormitory_number']);
        $bed_number = sanitize($_POST['bed_number']);
        $status = 'Active'; // Default for new students
        
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

        $sql = "INSERT INTO students SET 
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
            fee_status = ?, last_fee_payment_date = ?, fee_balance = ?";

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
            $fee_status, $last_fee_payment_date, $fee_balance
        ]);

        $new_id = $pdo->lastInsertId();
        $message = "Student added successfully!";
        $message_type = "success";
        
        // Redirect to edit page after success
        header("Location: edit-student.php?id=$new_id&added=1");
        exit;
        
    } catch (PDOException $e) {
        $message = "Error adding student: " . $e->getMessage();
        $message_type = "error";
    }
}

// Arrays for dropdowns (same as in edit-student.php)
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
    <title>Add New Student</title>
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
        --primary: #4B1C3C;
        --primary-dark: #36152B;
        --primary-light: #6A2B52;
        --accent: #FFB800;
        --accent-dark: #D99B00;
        --accent-light: #FFD966;
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
        --danger: #e74c3c;
        --shadow-sm: 0 2px 4px rgba(75, 28, 60, 0.08);
        --shadow-md: 0 4px 8px rgba(75, 28, 60, 0.12);
        --shadow-lg: 0 8px 16px rgba(75, 28, 60, 0.16);
        --transition: all 0.2s ease;
    }

    body {
        background-color: #f5f0f5;  /* solid light background */
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
        border-radius: 24px;
        box-shadow: var(--shadow-lg);
        padding: 40px;
        border: 1px solid rgba(75, 28, 60, 0.1);
    }

    .edit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 3px solid var(--accent);
    }

    .edit-header h1 {
        color: var(--primary-dark);
        font-size: 2.2rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .edit-header h1 i {
        color: var(--accent);
        font-size: 2.2rem;
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
        border-left: 4px solid transparent;
        box-shadow: var(--shadow-sm);
    }

    .alert-success {
        background-color: #e8f5e9;
        border-left-color: var(--success);
        color: #2e7d32;
    }

    .alert-error {
        background-color: #ffebee;
        border-left-color: var(--danger);
        color: #c62828;
    }

    /* Form Sections */
    .form-section {
        background-color: var(--gray-50);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        border: 1px solid var(--gray-300);
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }

    .form-section:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-md);
    }

    .form-section h2 {
        color: var(--primary-dark);
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--accent);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .form-section h2 i {
        color: var(--accent);
        font-size: 1.8rem;
        background-color: rgba(255, 184, 0, 0.1);
        padding: 8px;
        border-radius: 10px;
    }

    .form-section h3 {
        color: var(--primary);
        font-size: 1.2rem;
        font-weight: 600;
        margin: 20px 0 15px;
        padding-left: 10px;
        border-left: 4px solid var(--accent);
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
        color: var(--primary-dark);
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-group label i {
        color: var(--accent);
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
        background-color: white;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(75, 28, 60, 0.1);
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 15px;
        background-color: white;
        border: 2px solid var(--gray-300);
        border-radius: 12px;
        transition: var(--transition);
        cursor: pointer;
    }

    .checkbox-group:hover {
        border-color: var(--primary);
        background-color: rgba(75, 28, 60, 0.02);
    }

    .checkbox-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: var(--primary);
    }

    .checkbox-group label {
        color: var(--primary-dark);
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
        box-shadow: var(--shadow-sm);
    }

    .btn i {
        font-size: 1.1rem;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .btn-primary {
        background-color: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
    }

    .btn-secondary {
        background-color: var(--gray-200);
        color: var(--primary-dark);
        border: 2px solid var(--gray-300);
    }

    .btn-secondary:hover {
        background-color: var(--gray-300);
        border-color: var(--accent);
    }

    .btn-warning {
        background-color: var(--accent);
        color: var(--primary-dark);
    }

    .btn-warning:hover {
        background-color: var(--accent-dark);
    }

    .small-hint {
        font-size: 0.8rem;
        color: var(--gray-600);
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .small-hint i {
        color: var(--accent);
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

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 10px;
    }

    ::-webkit-scrollbar-track {
        background: var(--gray-200);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }

    /* Focus Visible */
    :focus-visible {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
    }
</style>

</head>
<body>
    <div class="premium-container">
        <div class="edit-container">
            <div class="edit-header">
                <h1>
                    <i class="fas fa-user-plus"></i> 
                    Add New Student
                </h1>
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="addForm">
                <!-- SECTION 1: PERSONAL INFORMATION -->
                <div class="form-section">
                    <h2><i class="fas fa-user-circle"></i> Personal Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Admission Number *</label>
                            <input type="text" name="admission_number" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-venus-mars"></i> Gender *</label>
                            <select name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-pin"></i> Place of Birth</label>
                            <input type="text" name="place_of_birth" class="form-control" placeholder="e.g., Kampala">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-flag"></i> Nationality</label>
                            <select name="nationality" class="form-control">
                                <option value="">Select Nationality</option>
                                <?php foreach ($nationalities as $nat): ?>
                                <option value="<?php echo $nat; ?>"><?php echo $nat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-church"></i> Religion</label>
                            <select name="religion" class="form-control">
                                <option value="">Select Religion</option>
                                <?php foreach ($religions as $rel): ?>
                                <option value="<?php echo $rel; ?>"><?php echo $rel; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-language"></i> Languages Spoken</label>
                            <input type="text" name="languages_spoken" class="form-control" placeholder="e.g., Luganda, English">
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 2: RESIDENCE INFORMATION -->
                <div class="form-section">
                    <h2><i class="fas fa-home"></i> Residence Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-map"></i> District</label>
                            <input type="text" name="home_district" class="form-control" placeholder="e.g., Wakiso">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map"></i> County</label>
                            <input type="text" name="home_county" class="form-control" placeholder="e.g., Busiro">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map"></i> Sub-county</label>
                            <input type="text" name="home_subcounty" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map"></i> Parish</label>
                            <input type="text" name="home_parish" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Village</label>
                            <input type="text" name="home_village" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-road"></i> Distance from School</label>
                            <input type="text" name="distance_from_school" class="form-control" placeholder="e.g., 5km">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-bus"></i> Mode of Transport</label>
                            <select name="mode_of_transport" class="form-control">
                                <option value="">Select Mode</option>
                                <?php foreach ($transport_modes as $mode): ?>
                                <option value="<?php echo $mode; ?>"><?php echo $mode; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Travel Time (minutes)</label>
                            <input type="number" name="travel_time_minutes" class="form-control" min="0">
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
                            <input type="text" name="father_name" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Father's Phone</label>
                            <input type="text" name="father_phone" class="form-control" placeholder="256...">
                        </div>
                        
                        <div class="form-group">
                            <label>Father's Occupation</label>
                            <input type="text" name="father_occupation" class="form-control">
                        </div>
                    </div>
                    
                    <h3><i class="fas fa-female"></i> Mother's Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Mother's Phone</label>
                            <input type="text" name="mother_phone" class="form-control" placeholder="256...">
                        </div>
                        
                        <div class="form-group">
                            <label>Mother's Occupation</label>
                            <input type="text" name="mother_occupation" class="form-control">
                        </div>
                    </div>
                    
                    <h3><i class="fas fa-user-tie"></i> Guardian/Emergency Contact</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Guardian Name</label>
                            <input type="text" name="guardian_name" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Guardian Phone</label>
                            <input type="text" name="guardian_phone" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Relationship</label>
                            <input type="text" name="guardian_relationship" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Parent Email</label>
                            <input type="email" name="parent_email" class="form-control">
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
                                <option value="<?php echo $bg; ?>"><?php echo $bg; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Allergies</label>
                            <textarea name="allergies" class="form-control" placeholder="List any allergies"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Medical Conditions</label>
                            <textarea name="medical_conditions" class="form-control" placeholder="Any chronic conditions?"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Medications</label>
                            <textarea name="medications" class="form-control" placeholder="Regular medications"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Doctor's Name</label>
                            <input type="text" name="doctor_name" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Doctor's Phone</label>
                            <input type="text" name="doctor_phone" class="form-control">
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 5: ACADEMIC HISTORY -->
                <div class="form-section">
                    <h2><i class="fas fa-graduation-cap"></i> Academic History</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Previous School</label>
                            <input type="text" name="previous_school" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Previous Class</label>
                            <input type="text" name="previous_class" class="form-control" placeholder="e.g., P.4">
                        </div>
                        
                        <div class="form-group">
                            <label>Last Report Score</label>
                            <input type="text" name="last_report_score" class="form-control" placeholder="e.g., 80% or Division 1">
                        </div>
                        
                        <div class="form-group">
                            <label>Special Needs</label>
                            <textarea name="special_needs" class="form-control" placeholder="Any special educational needs?"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 6: DOCUMENTS -->
                <div class="form-section">
                    <h2><i class="fas fa-file-alt"></i> Documents</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Birth Certificate Number</label>
                            <input type="text" name="birth_certificate_number" class="form-control">
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="medical_form_submitted" id="medical_form" value="1">
                            <label for="medical_form">Medical Form Submitted</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="previous_report_submitted" id="previous_report" value="1">
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
                            <textarea name="sibling_names" class="form-control" placeholder="e.g., John Okello&#10;Mary Akello"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Their Classes (one per line)</label>
                            <textarea name="sibling_classes" class="form-control" placeholder="e.g., P.6&#10;P.3"></textarea>
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
                                <option value="Day Scholar">Day Scholar</option>
                                <option value="Boarder">Boarder</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Joined Date</label>
                            <input type="date" name="joined_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group" id="dormitoryGroup" style="display: none;">
                            <label>Dormitory Number</label>
                            <input type="text" name="dormitory_number" class="form-control">
                        </div>
                        
                        <div class="form-group" id="bedGroup" style="display: none;">
                            <label>Bed Number</label>
                            <input type="text" name="bed_number" class="form-control">
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 9: EXTRACURRICULAR -->
                <div class="form-section">
                    <h2><i class="fas fa-running"></i> Extracurricular Activities</h2>
                    <div class="form-grid">
                        <div class="checkbox-group">
                            <input type="checkbox" name="soccer_academy" id="soccer_academy" value="1">
                            <label for="soccer_academy">⚽ Soccer Academy</label>
                        </div>
                        
                        <div class="form-group">
                            <label>Other Activities</label>
                            <textarea name="other_activities" class="form-control" placeholder="e.g., Choir, Drama, Debate"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Sports Interests</label>
                            <input type="text" name="sports_interests" class="form-control" placeholder="e.g., Football, Netball">
                        </div>
                        
                        <div class="form-group">
                            <label>Talents</label>
                            <input type="text" name="talents" class="form-control" placeholder="e.g., Singing, Drawing">
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 10: ACADEMIC NOTES -->
                <div class="form-section">
                    <h2><i class="fas fa-sticky-note"></i> Academic Notes</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Strengths</label>
                            <textarea name="strengths" class="form-control" placeholder="What is this student good at?"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Areas for Improvement</label>
                            <textarea name="weaknesses" class="form-control" placeholder="What needs work?"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Teacher Notes</label>
                            <textarea name="teacher_notes" class="form-control" placeholder="General observations"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Counselor Notes</label>
                            <textarea name="counselor_notes" class="form-control" placeholder="Any counseling notes"></textarea>
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
                                <option value="<?php echo $fs; ?>"><?php echo $fs; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Last Payment Date</label>
                            <input type="date" name="last_fee_payment_date" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Fee Balance (UGX)</label>
                            <input type="number" name="fee_balance" class="form-control" value="0" min="0" step="1000">
                            <div class="small-hint">
                                <i class="fas fa-info-circle"></i> Enter amount in Uganda Shillings
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ACTION BUTTONS -->
                <div class="action-buttons">
                    <a href="students.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="add_student" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Student
                    </button>
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
        document.getElementById('addForm').addEventListener('input', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        document.getElementById('addForm').addEventListener('submit', function() {
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
    </script>
</body>
</html>