<?php
// student-profile.php - Complete Student Profile View
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;

// Get student details with all new fields
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: students.php');
    exit;
}

// Get attendance summary
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN morning_status IN ('Present', 'Late') THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN morning_status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN morning_status = 'Late' THEN 1 ELSE 0 END) as late_days
    FROM attendance 
    WHERE student_id = ? AND year = ? AND term = ?
");
$stmt->execute([$id, ACADEMIC_YEAR, CURRENT_TERM]);
$attendance = $stmt->fetch();

// Get assessment summary
$stmt = $pdo->prepare("
    SELECT subject, 
           MAX(CASE WHEN exam_type = 'Beginning' THEN score END) as beginning,
           MAX(CASE WHEN exam_type = 'Mid-term' THEN score END) as midterm,
           MAX(CASE WHEN exam_type = 'End of Term' THEN score END) as endterm,
           AVG(score) as average
    FROM assessments 
    WHERE student_id = ? AND year = ? AND term = ?
    GROUP BY subject
");
$stmt->execute([$id, ACADEMIC_YEAR, CURRENT_TERM]);
$assessments = $stmt->fetchAll();

// Get behavior records
$stmt = $pdo->prepare("
    SELECT * FROM behavior_log 
    WHERE student_id = ? 
    ORDER BY log_date DESC 
    LIMIT 5
");
$stmt->execute([$id]);
$behaviors = $stmt->fetchAll();

// Get parent communication
$stmt = $pdo->prepare("
    SELECT * FROM parent_communication 
    WHERE student_id = ? 
    ORDER BY communication_date DESC 
    LIMIT 5
");
$stmt->execute([$id]);
$communications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - <?php echo $student['full_name']; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #4B1C3C, #2F1224);
            color: white;
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid #FFB800;
            overflow: hidden;
            background: white;
        }
        
        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-photo-placeholder {
            width: 100%;
            height: 100%;
            background: #FFB800;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #4B1C3C;
        }
        
        .profile-title h1 {
            font-size: 2.5rem;
            margin-bottom: 5px;
        }
        
        .profile-title .admission {
            color: #FFB800;
            font-size: 1.2rem;
        }
        
        .profile-stats {
            display: flex;
            gap: 20px;
            margin-left: auto;
        }
        
        .stat-badge {
            text-align: center;
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            min-width: 100px;
        }
        
        .stat-badge .number {
            font-size: 2rem;
            font-weight: 700;
            color: #FFB800;
        }
        
        .stat-badge .label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .profile-tabs {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            background: #f8f4f8;
            padding: 0 30px;
            gap: 5px;
        }
        
        .tab-btn {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            color: #4B1C3C;
        }
        
        .tab-btn.active {
            color: #4B1C3C;
            border-bottom-color: #FFB800;
        }
        
        .tab-panel {
            display: none;
            padding: 30px;
        }
        
        .tab-panel.active {
            display: block;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .info-card {
            background: #f8f4f8;
            border-radius: 15px;
            padding: 20px;
            border: 1px solid #e0d0e0;
        }
        
        .info-card h3 {
            color: #4B1C3C;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #FFB800;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-card h3 i {
            color: #FFB800;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px dashed #e0d0e0;
        }
        
        .info-label {
            width: 140px;
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            flex: 1;
            color: #4B1C3C;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #E8F5E9;
            color: #2E7D32;
        }
        
        .status-boarder {
            background: #4B1C3C;
            color: white;
        }
        
        .status-day {
            background: #FFB800;
            color: #4B1C3C;
        }
        
        .scores-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .scores-table th {
            background: #4B1C3C;
            color: white;
            padding: 10px;
            text-align: left;
        }
        
        .scores-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .grade-A { color: #4CAF50; font-weight: bold; }
        .grade-B { color: #2196F3; font-weight: bold; }
        .grade-C { color: #FF9800; font-weight: bold; }
        .grade-D { color: #f44336; font-weight: bold; }
        
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background: #4B1C3C;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2F1224;
        }
        
        .btn-warning {
            background: #FFB800;
            color: #4B1C3C;
        }
        
        .btn-warning:hover {
            background: #D99B00;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-stats {
                margin-left: 0;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 3px;
            }
        }
    </style>
</head>
<body>
    <div class="premium-container">
        <div class="profile-container">
            <!-- Header -->
            <div class="profile-header">
                <div class="profile-photo">
                    <?php if (!empty($student['photo_path']) && file_exists($student['photo_path'])): ?>
                        <img src="<?php echo $student['photo_path']; ?>" alt="<?php echo $student['full_name']; ?>">
                    <?php else: ?>
                        <div class="profile-photo-placeholder">
                            <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-title">
                    <h1><?php echo $student['full_name']; ?></h1>
                    <div class="admission">
                        <i class="fas fa-id-card"></i> 
                        <?php echo $student['admission_number']; ?> | 
                        <span class="status-badge <?php echo $student['status'] == 'Active' ? 'status-active' : ''; ?>">
                            <?php echo $student['status']; ?>
                        </span>
                    </div>
                    <div style="margin-top: 10px;">
                        <span class="status-badge <?php echo $student['student_type'] == 'Boarder' ? 'status-boarder' : 'status-day'; ?>">
                            <?php echo $student['student_type']; ?>
                        </span>
                        <?php if ($student['soccer_academy']): ?>
                            <span class="status-badge" style="background: #4CAF50; color: white;">
                                <i class="fas fa-futbol"></i> Soccer Academy
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-badge">
                        <div class="number"><?php echo $attendance['present_days'] ?? 0; ?></div>
                        <div class="label">Days Present</div>
                    </div>
                    <div class="stat-badge">
                        <div class="number"><?php echo $attendance['absent_days'] ?? 0; ?></div>
                        <div class="label">Days Absent</div>
                    </div>
                    <div class="stat-badge">
                        <div class="number"><?php echo count($behaviors); ?></div>
                        <div class="label">Behavior Logs</div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="profile-tabs">
                <button class="tab-btn active" onclick="switchTab('personal')">
                    <i class="fas fa-user"></i> Personal
                </button>
                <button class="tab-btn" onclick="switchTab('residence')">
                    <i class="fas fa-home"></i> Residence
                </button>
                <button class="tab-btn" onclick="switchTab('parents')">
                    <i class="fas fa-users"></i> Parents
                </button>
                <button class="tab-btn" onclick="switchTab('medical')">
                    <i class="fas fa-heartbeat"></i> Medical
                </button>
                <button class="tab-btn" onclick="switchTab('academic')">
                    <i class="fas fa-graduation-cap"></i> Academic
                </button>
                <button class="tab-btn" onclick="switchTab('documents')">
                    <i class="fas fa-file"></i> Documents
                </button>
                <button class="tab-btn" onclick="switchTab('activities')">
                    <i class="fas fa-running"></i> Activities
                </button>
                <button class="tab-btn" onclick="switchTab('notes')">
                    <i class="fas fa-sticky-note"></i> Notes
                </button>
            </div>
            
            <!-- PERSONAL TAB -->
            <div id="personal-panel" class="tab-panel active">
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-user-circle"></i> Basic Information</h3>
                        <div class="info-row">
                            <span class="info-label">Full Name:</span>
                            <span class="info-value"><?php echo $student['full_name']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Gender:</span>
                            <span class="info-value"><?php echo $student['gender']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date of Birth:</span>
                            <span class="info-value">
                                <?php echo $student['date_of_birth'] ? date('F j, Y', strtotime($student['date_of_birth'])) : 'Not set'; ?>
                                <?php if ($student['date_of_birth']): ?>
                                    (<?php echo floor((time() - strtotime($student['date_of_birth'])) / (365*60*60*24)); ?> years)
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Place of Birth:</span>
                            <span class="info-value"><?php echo $student['place_of_birth'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Nationality:</span>
                            <span class="info-value"><?php echo $student['nationality'] ?? 'Ugandan'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Religion:</span>
                            <span class="info-value"><?php echo $student['religion'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Languages:</span>
                            <span class="info-value"><?php echo $student['languages_spoken'] ?? 'Not set'; ?></span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-graduation-cap"></i> School Information</h3>
                        <div class="info-row">
                            <span class="info-label">Admission No:</span>
                            <span class="info-value"><?php echo $student['admission_number']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Joined Date:</span>
                            <span class="info-value"><?php echo $student['joined_date'] ? date('F j, Y', strtotime($student['joined_date'])) : 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Student Type:</span>
                            <span class="info-value"><?php echo $student['student_type']; ?></span>
                        </div>
                        <?php if ($student['student_type'] == 'Boarder'): ?>
                        <div class="info-row">
                            <span class="info-label">Dormitory:</span>
                            <span class="info-value"><?php echo $student['dormitory_number'] ?? 'Not assigned'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Bed Number:</span>
                            <span class="info-value"><?php echo $student['bed_number'] ?? 'Not assigned'; ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Previous School:</span>
                            <span class="info-value"><?php echo $student['previous_school'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Previous Class:</span>
                            <span class="info-value"><?php echo $student['previous_class'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Report Score:</span>
                            <span class="info-value"><?php echo $student['last_report_score'] ?? 'Not set'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- RESIDENCE TAB -->
            <div id="residence-panel" class="tab-panel">
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-map-marker-alt"></i> Home Location</h3>
                        <div class="info-row">
                            <span class="info-label">District:</span>
                            <span class="info-value"><?php echo $student['home_district'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">County:</span>
                            <span class="info-value"><?php echo $student['home_county'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Sub-county:</span>
                            <span class="info-value"><?php echo $student['home_subcounty'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Parish:</span>
                            <span class="info-value"><?php echo $student['home_parish'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Village:</span>
                            <span class="info-value"><?php echo $student['home_village'] ?? 'Not set'; ?></span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-bus"></i> Travel Information</h3>
                        <div class="info-row">
                            <span class="info-label">Distance from School:</span>
                            <span class="info-value"><?php echo $student['distance_from_school'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Mode of Transport:</span>
                            <span class="info-value"><?php echo $student['mode_of_transport'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Travel Time:</span>
                            <span class="info-value">
                                <?php echo $student['travel_time_minutes'] ? $student['travel_time_minutes'] . ' minutes' : 'Not set'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PARENTS TAB -->
            <div id="parents-panel" class="tab-panel">
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-male"></i> Father's Information</h3>
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo $student['father_name'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo $student['father_phone'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Occupation:</span>
                            <span class="info-value"><?php echo $student['father_occupation'] ?? 'Not set'; ?></span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-female"></i> Mother's Information</h3>
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo $student['mother_name'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo $student['mother_phone'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Occupation:</span>
                            <span class="info-value"><?php echo $student['mother_occupation'] ?? 'Not set'; ?></span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-user-tie"></i> Guardian/Emergency</h3>
                        <div class="info-row">
                            <span class="info-label">Guardian Name:</span>
                            <span class="info-value"><?php echo $student['guardian_name'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Guardian Phone:</span>
                            <span class="info-value"><?php echo $student['guardian_phone'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Relationship:</span>
                            <span class="info-value"><?php echo $student['guardian_relationship'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo $student['parent_email'] ?? 'Not set'; ?></span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-child"></i> Siblings at School</h3>
                        <div class="info-row">
                            <span class="info-label">Sibling Names:</span>
                            <span class="info-value"><?php echo nl2br($student['sibling_names'] ?? 'None'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Their Classes:</span>
                            <span class="info-value"><?php echo nl2br($student['sibling_classes'] ?? 'None'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- MEDICAL TAB -->
            <div id="medical-panel" class="tab-panel">
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-tint"></i> Medical Information</h3>
                        <div class="info-row">
                            <span class="info-label">Blood Group:</span>
                            <span class="info-value status-badge" style="background: #f44336; color: white;">
                                <?php echo $student['blood_group'] ?? 'Not set'; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Allergies:</span>
                            <span class="info-value"><?php echo nl2br($student['allergies'] ?? 'None'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Medical Conditions:</span>
                            <span class="info-value"><?php echo nl2br($student['medical_conditions'] ?? 'None'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Medications:</span>
                            <span class="info-value"><?php echo nl2br($student['medications'] ?? 'None'); ?></span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-user-md"></i> Doctor/Clinic Information</h3>
                        <div class="info-row">
                            <span class="info-label">Doctor's Name:</span>
                            <span class="info-value"><?php echo $student['doctor_name'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Doctor's Phone:</span>
                            <span class="info-value"><?php echo $student['doctor_phone'] ?? 'Not set'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ACADEMIC TAB -->
            <div id="academic-panel" class="tab-panel">
                <div class="info-card" style="margin-bottom: 20px;">
                    <h3><i class="fas fa-chart-line"></i> Current Term Performance (Term <?php echo CURRENT_TERM; ?>)</h3>
                    
                    <?php if (empty($assessments)): ?>
                        <p style="color: #999; text-align: center; padding: 20px;">No assessment records found.</p>
                    <?php else: ?>
                        <table class="scores-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Beginning</th>
                                    <th>Mid-Term</th>
                                    <th>End Term</th>
                                    <th>Average</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                $count = 0;
                                foreach ($assessments as $a): 
                                    $avg = $a['average'] ?? 0;
                                    $total += $avg;
                                    $count++;
                                    $grade = $avg >= 80 ? 'A' : ($avg >= 70 ? 'B' : ($avg >= 60 ? 'C' : ($avg >= 50 ? 'D' : 'E')));
                                ?>
                                <tr>
                                    <td><strong><?php echo $a['subject']; ?></strong></td>
                                    <td><?php echo $a['beginning'] ?? '-'; ?></td>
                                    <td><?php echo $a['midterm'] ?? '-'; ?></td>
                                    <td><?php echo $a['endterm'] ?? '-'; ?></td>
                                    <td><strong><?php echo round($avg, 1); ?>%</strong></td>
                                    <td class="grade-<?php echo $grade; ?>"><?php echo $grade; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr style="background: #f0e8f0; font-weight: bold;">
                                    <td colspan="4" style="text-align: right;">Overall Average:</td>
                                    <td><?php echo $count > 0 ? round($total/$count, 1) : 0; ?>%</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-brain"></i> Strengths</h3>
                        <p><?php echo nl2br($student['strengths'] ?? 'No strengths recorded.'); ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-exclamation-triangle"></i> Areas for Improvement</h3>
                        <p><?php echo nl2br($student['weaknesses'] ?? 'No weaknesses recorded.'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- DOCUMENTS TAB -->
            <div id="documents-panel" class="tab-panel">
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-file-alt"></i> Documents</h3>
                        <div class="info-row">
                            <span class="info-label">Birth Certificate No:</span>
                            <span class="info-value"><?php echo $student['birth_certificate_number'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Medical Form:</span>
                            <span class="info-value">
                                <?php echo $student['medical_form_submitted'] ? '✓ Submitted' : '✗ Pending'; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Previous Report:</span>
                            <span class="info-value">
                                <?php echo $student['previous_report_submitted'] ? '✓ Submitted' : '✗ Pending'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-money-bill"></i> Fee Information</h3>
                        <div class="info-row">
                            <span class="info-label">Fee Status:</span>
                            <span class="info-value status-badge" style="background: 
                                <?php echo $student['fee_status'] == 'Paid' ? '#4CAF50' : 
                                        ($student['fee_status'] == 'Partial' ? '#FF9800' : 
                                        ($student['fee_status'] == 'Scholarship' ? '#2196F3' : '#f44336')); ?>; color: white;">
                                <?php echo $student['fee_status'] ?? 'Unpaid'; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Payment:</span>
                            <span class="info-value"><?php echo $student['last_fee_payment_date'] ? date('M d, Y', strtotime($student['last_fee_payment_date'])) : 'Not recorded'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Balance:</span>
                            <span class="info-value">UGX <?php echo number_format($student['fee_balance'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ACTIVITIES TAB -->
            <div id="activities-panel" class="tab-panel">
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-running"></i> Extracurricular</h3>
                        <div class="info-row">
                            <span class="info-label">Soccer Academy:</span>
                            <span class="info-value"><?php echo $student['soccer_academy'] ? 'Yes ⚽' : 'No'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Other Activities:</span>
                            <span class="info-value"><?php echo nl2br($student['other_activities'] ?? 'None'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Sports Interests:</span>
                            <span class="info-value"><?php echo $student['sports_interests'] ?? 'None'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Talents:</span>
                            <span class="info-value"><?php echo $student['talents'] ?? 'None'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- NOTES TAB -->
            <div id="notes-panel" class="tab-panel">
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-sticky-note"></i> Teacher Notes</h3>
                        <p><?php echo nl2br($student['teacher_notes'] ?? 'No teacher notes.'); ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-comments"></i> Counselor Notes</h3>
                        <p><?php echo nl2br($student['counselor_notes'] ?? 'No counselor notes.'); ?></p>
                    </div>
                </div>
                
                <!-- Recent Behavior -->
                <?php if (!empty($behaviors)): ?>
                <div class="info-card" style="margin-top: 20px;">
                    <h3><i class="fas fa-exclamation-triangle"></i> Recent Behavior Records</h3>
                    <?php foreach ($behaviors as $b): ?>
                        <div style="padding: 10px; margin: 5px 0; background: #f8f4f8; border-left: 4px solid <?php 
                            echo $b['behavior_type'] == 'Positive' ? '#4CAF50' : 
                                ($b['behavior_type'] == 'Warning' ? '#FF9800' : 
                                ($b['behavior_type'] == 'Incident' ? '#f44336' : '#9C27B0')); ?>;">
                            <strong><?php echo date('M d, Y', strtotime($b['log_date'])); ?> - <?php echo $b['behavior_type']; ?></strong>
                            <p><?php echo $b['description']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Recent Parent Communication -->
                <?php if (!empty($communications)): ?>
                <div class="info-card" style="margin-top: 20px;">
                    <h3><i class="fas fa-phone"></i> Recent Parent Communication</h3>
                    <?php foreach ($communications as $c): ?>
                        <div style="padding: 10px; margin: 5px 0; background: #f8f4f8; border-left: 4px solid #FFB800;">
                            <strong><?php echo date('M d, Y', strtotime($c['communication_date'])); ?> - <?php echo $c['contact_method']; ?></strong>
                            <p><?php echo $c['notes']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="students.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
                <a href="edit-student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                <a href="report-card.php?student_id=<?php echo $student['id']; ?>&term=<?php echo CURRENT_TERM; ?>" class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> View Report Card
                </a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Remove active class from all tabs and panels
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding panel
            event.target.classList.add('active');
            document.getElementById(tab + '-panel').classList.add('active');
        }
    </script>
</body>
</html>