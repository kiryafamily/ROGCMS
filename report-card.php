<?php
// report-card.php - Complete Fixed Version with Full Subject Names
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get parameters
$student_id = $_GET['student_id'] ?? 0;
$term = $_GET['term'] ?? CURRENT_TERM;
$exam_type = $_GET['type'] ?? 'BOT'; // BOT, MID, END

// Map exam type to database exam_type
$exam_type_map = [
    'BOT' => 'Beginning',
    'MID' => 'Mid-term',
    'END' => 'End of Term'
];
$db_exam_type = $exam_type_map[$exam_type];

// Get school information with logo
$stmt = $pdo->query("SELECT * FROM school_info WHERE id = 1");
$school = $stmt->fetch();
if (!$school) {
    // Initialize if not exists
    $pdo->exec("INSERT INTO school_info (id) VALUES (1)");
    $stmt = $pdo->query("SELECT * FROM school_info WHERE id = 1");
    $school = $stmt->fetch();
}

// Get student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND status = 'Active'");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: report-selector.php?error=Student not found');
    exit;
}

// Get student photo
$photo_path = '';
if (!empty($student['photo_path']) && file_exists($student['photo_path'])) {
    $photo_path = $student['photo_path'];
}

// Get assessment scores for this student
$stmt = $pdo->prepare("
    SELECT subject, score, subject_teacher_initials 
    FROM assessments 
    WHERE student_id = ? AND year = ? AND term = ? AND exam_type = ?
");
$stmt->execute([$student_id, ACADEMIC_YEAR, $term, $db_exam_type]);
$assessments = $stmt->fetchAll();

// Initialize all subjects with null values
$all_subjects = [
    'English' => null,
    'Mathematics' => null,
    'Integrated Science' => null,
    'Social Studies' => null,
    'Kiswahili' => null,
    'Religious Education' => null
];

$initials = [];
$total_score = 0;
$subject_count = 0;
$core_agg_total = 0;

$core_subjects = ['Mathematics', 'English', 'Integrated Science', 'Social Studies'];

// Fill in actual data from assessments
foreach ($assessments as $a) {
    $all_subjects[$a['subject']] = $a['score'];
    $initials[$a['subject']] = $a['subject_teacher_initials'] ?? '';
    $total_score += $a['score'];
    $subject_count++;
}

// Helper function for aggregates
function getAggregate($score) {
    if ($score >= 90) return 1;
    if ($score >= 80) return 2;
    if ($score >= 70) return 3;
    if ($score >= 60) return 4;
    if ($score >= 50) return 5;
    if ($score >= 45) return 6;
    if ($score >= 40) return 7;
    if ($score >= 35) return 8;
    return 9;
}

function getDivision($total_agg) {
    if ($total_agg >= 4 && $total_agg <= 12) return 'I';
    if ($total_agg >= 13 && $total_agg <= 24) return 'II';
    if ($total_agg >= 25 && $total_agg <= 29) return 'III';
    if ($total_agg >= 30 && $total_agg <= 33) return 'IV';
    if ($total_agg >= 34 && $total_agg <= 36) return 'U';
    return '-';
}

// Calculate aggregates and core total
$aggregates = [];
$core_total = 0;

foreach ($all_subjects as $subject => $score) {
    if ($score) {
        $agg = getAggregate($score);
        $aggregates[$subject] = $agg;
        if (in_array($subject, $core_subjects)) {
            $core_total += $agg;
        }
    }
}

$average = $subject_count > 0 ? round($total_score / $subject_count, 2) : 0;
$division = getDivision($core_total);

// Get teacher's comment
$teacher_comment = "Excellent start to the term.";

// Get term name
$term_names = ['1' => 'I', '2' => 'II', '3' => 'III'];
$term_roman = $term_names[$term] ?? 'I';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - <?php echo $student['full_name']; ?> - Rays of Grace Junior School</title>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #4B1C3C 0%, #36152B 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* Export buttons container */
        .export-panel {
            margin-bottom: 25px;
            width: 100%;
            max-width: 1100px;
        }

        .export-panel h3 {
            color: #FFB800;
            text-align: center;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .export-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 25px;
            border-radius: 50px;
            box-shadow: 0 15px 30px rgba(75, 28, 60, 0.3);
            border: 3px solid #FFB800;
        }

        .export-btn {
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 40px;
            cursor: pointer;
            box-shadow: 0 5px 12px rgba(75, 28, 60, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .export-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(75, 28, 60, 0.3);
            border-color: #FFB800;
        }

        .btn-pdf { background: #4B1C3C; color: #FFB800; }
        .btn-png { background: #5C234A; color: #FFB800; }
        .btn-jpg { background: #6D2A58; color: #FFB800; }
        .btn-doc { background: #3A152E; color: #FFB800; }
        .btn-print { background: #FFB800; color: #4B1C3C; }

        /* A4 exact size - 210mm x 297mm */
        .report-card {
            width: 210mm;
            height: 297mm;
            background: white;
            box-shadow: 0 30px 60px rgba(75, 28, 60, 0.3);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid #FFB80060;
            margin: 0 auto;
            padding: 15px 22px;
        }

        /* School header with logo */
        .school-header {
            background: linear-gradient(135deg, #4B1C3C 0%, #5C234A 100%);
            margin: -15px -22px 10px -22px;
            padding: 15px 25px 12px 25px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .school-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FFB800, #ffffff, #FFB800);
        }

        /* Dynamic Logo Container */
        .logo-container {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            border: 3px solid #FFB800;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            flex-shrink: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logo-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #4B1C3C, #5C234A);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #FFB800;
            text-align: center;
        }

        .logo-placeholder .logo-top {
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 1px;
            line-height: 1;
        }

        .logo-placeholder .logo-middle {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 2px;
            line-height: 1;
            margin: 2px 0;
        }

        .logo-placeholder .logo-bottom {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .school-title {
            flex-grow: 1;
        }

        .school-title h1 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .school-title .motto {
            color: #FFB800;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-style: italic;
            font-weight: 600;
        }

        .school-title .contact {
            color: rgba(255,255,255,0.9);
            font-size: 9px;
            margin-top: 3px;
        }

        .term-badge {
            background: rgba(75, 28, 60, 0.4);
            backdrop-filter: blur(5px);
            padding: 6px 15px;
            border-radius: 30px;
            border: 2px solid #FFB800;
            text-align: center;
            flex-shrink: 0;
        }

        .term-badge .term {
            color: #FFB800;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .term-badge .year {
            color: white;
            font-size: 20px;
            font-weight: 800;
            line-height: 1;
        }

        /* Student info card */
        .student-info {
            background: #FDF5F9;
            border-radius: 12px;
            padding: 15px 20px;
            display: grid;
            grid-template-columns: 1.5fr 0.8fr 1.5fr;
            gap: 15px;
            border: 2px solid #FFB80080;
            margin-bottom: 20px;
        }

        .info-item .label {
            font-size: 10px;
            color: #4B1C3C;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .info-item .value {
            font-size: 16px;
            font-weight: 600;
            color: #1e1e2f;
        }

        .info-item .value small {
            font-size: 12px;
            color: #666;
            margin-left: 5px;
        }

        .photo-placeholder {
            width: 80px;
            height: 90px;
            border-radius: 10px;
            margin: 0 auto;
            overflow: hidden;
            border: 2px solid #4B1C3C;
        }

        .photo-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-placeholder .no-photo {
            width: 100%;
            height: 100%;
            background: #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #4B1C3C;
        }

        /* Section title */
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .section-icon {
            width: 30px;
            height: 30px;
            background: #4B1C3C;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFB800;
            font-size: 16px;
        }

        .section-title h2 {
            font-size: 18px;
            font-weight: 700;
            color: #4B1C3C;
            text-transform: uppercase;
        }

        .section-title span {
            background: #FFB80020;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: #4B1C3C;
            margin-left: 10px;
            border: 1px solid #FFB80040;
        }

        /* FIXED: Table with proper spacing for all 6 subjects */
        .table-container {
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #FFB80080;
            margin-bottom: 20px;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th {
            background: #4B1C3C;
            color: white;
            font-size: 11px;
            padding: 10px 3px;
            font-weight: 600;
            text-transform: uppercase;
            text-align: center;
        }

/* FIXED: Table with optimal column widths for A4 */
.table-container {
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #FFB80080;
    margin-bottom: 20px;
    width: 100%;
}

table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

th {
    background: #4B1C3C;
    color: white;
    font-size: 10px; /* Smaller font to fit more */
    padding: 8px 2px;
    font-weight: 600;
    text-transform: uppercase;
    text-align: center;
}

/* OPTIMIZED column widths for A4 */
th:nth-child(1) { width: 18%; }  /* SUBJECT - English, Mathematics, etc */
th:nth-child(2) { width: 8%; }   /* SCORE - 80 */
th:nth-child(3) { width: 6%; }   /* AGG - 1,2,3 */
th:nth-child(4) { width: 40%; }  /* REMARKS - "Outstanding performance!" */
th:nth-child(5) { width: 8%; }   /* INIT - A.G, K.R, etc */

td {
    padding: 6px 2px;
    border-bottom: 1px solid #FFB80030;
    font-size: 10px; /* Smaller font */
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
}

.subject {
    font-weight: 600;
    color: #4B1C3C;
    text-align: left;
    padding-left: 4px;
    white-space: normal;
    font-size: 9.5px; /* Even smaller for long names */
    line-height: 1.2;
}

.score {
    font-weight: 700;
    color: #FFB800;
    text-align: center;
    font-size: 10px;
}

.agg {
    background: #FFB80020;
    padding: 2px 4px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 9px;
    display: inline-block;
    width: 25px;
    text-align: center;
    color: #4B1C3C;
    border: 1px solid #FFB80040;
}

.remarks {
    color: #555;
    font-size: 9.5px;
    text-align: center;
    padding-left: 2px;
    padding-right: 2px;
    white-space: normal;
    word-break: break-word;
    line-height: 1.2;
}

.init {
    text-align: center;
    font-weight: 600;
    color: #4B1C3C;
    font-size: 10px;
}

.total-row {
    background: #FDF5F9;
}

.total-row td {
    padding: 8px 2px;
    border-top: 3px solid #4B1C3C;
    font-weight: 700;
    color: #4B1C3C;
    text-align: center;
    font-size: 10px;
}
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #FDF5F9;
            border-radius: 12px;
            padding: 15px;
            border: 2px solid #FFB80080;
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 2px solid #FFB800;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .stat-header i {
            width: 25px;
            height: 25px;
            background: #4B1C3C;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFB800;
            font-size: 14px;
        }

        .stat-header h3 {
            font-size: 14px;
            font-weight: 700;
            color: #4B1C3C;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .stat-label {
            color: #555;
        }

        .stat-value {
            font-weight: 700;
            color: #4B1C3C;
            background: white;
            padding: 2px 15px;
            border-radius: 20px;
            border: 1px solid #FFB80080;
        }

        .division-badge {
            background: #4B1C3C;
            color: #FFB800;
            padding: 8px;
            border-radius: 8px;
            text-align: center;
            font-weight: 700;
            font-size: 12px;
            margin-top: 10px;
        }

        .grade-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .grade-item {
            background: #FFB80020;
            padding: 4px 10px;
            border-radius: 20px;
            color: #4B1C3C;
            border: 1px solid #FFB80040;
            width: 48%;
            text-align: center;
        }

        /* Comment section */
        .comment-section {
            background: #FDF5F9;
            border-radius: 12px;
            padding: 15px 20px;
            border-left: 6px solid #4B1C3C;
            margin-bottom: 20px;
        }

        .comment-text {
            font-size: 14px;
            font-style: italic;
            color: #1e1e2f;
        }

        .comment-author {
            text-align: right;
            font-weight: 700;
            color: #4B1C3C;
            font-size: 13px;
            margin-top: 5px;
        }

        /* Signature section */
        .signature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .signature-item {
            background: white;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            border: 2px dashed #FFB800;
        }

        .signature-label {
            font-size: 10px;
            color: #4B1C3C;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .signature-name {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .signature-line {
            border-bottom: 3px solid #4B1C3C;
            width: 80%;
            margin: 0 auto;
            height: 20px;
        }

        /* Footer */
        .footer {
            margin-top: auto;
            border-top: 3px solid #FFB80030;
            padding-top: 12px;
        }

        .keys {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
            margin-bottom: 8px;
        }

        .key {
            background: #FDF5F9;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 9px;
            color: #4B1C3C;
            border: 1px solid #FFB80080;
        }

        .division-info {
            text-align: center;
            font-size: 9px;
            color: #555;
            background: #FDF5F9;
            padding: 5px;
            border-radius: 20px;
            margin-bottom: 5px;
            border: 1px solid #FFB80040;
        }

        .paycode {
            text-align: right;
            font-weight: 700;
            color: #4B1C3C;
            font-size: 11px;
        }

        .back-link {
            margin-bottom: 15px;
            width: 100%;
            max-width: 1100px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .back-link a {
            color: #FFB800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }

        .logo-upload-link {
            color: white;
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 12px;
            border: 1px solid #FFB800;
        }

        @media print {
            body { background: white; }
            .export-panel, .back-link { display: none; }
            .report-card { box-shadow: none; }
        }
    </style>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
    <div class="back-link">
        <a href="report-selector.php?term=<?php echo $term; ?>">
            <i class="fas fa-arrow-left"></i> Back to Report Selector
        </a>
        <a href="upload-logo.php" class="logo-upload-link" target="_blank">
            <i class="fas fa-image"></i> Change School Logo
        </a>
    </div>

    <div class="export-panel">
        <h3><i class="fas fa-download"></i> EXPORT REPORT CARD</h3>
        <div class="export-buttons">
            <button class="export-btn btn-pdf" onclick="exportAsPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
            <button class="export-btn btn-png" onclick="exportAsPNG()"><i class="fas fa-image"></i> PNG</button>
            <button class="export-btn btn-jpg" onclick="exportAsJPG()"><i class="fas fa-camera"></i> JPG</button>
            <button class="export-btn btn-doc" onclick="exportAsDOC()"><i class="fas fa-file-word"></i> DOC</button>
            <button class="export-btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> PRINT</button>
        </div>
    </div>

    <div class="report-card" id="reportCard">
        <!-- School Header with Dynamic Logo -->
        <div class="school-header">
            <div class="logo-container">
                <?php 
                // Fixed logo path handling
                $logo_display = false;
                $logo_path = '';
                
                if (!empty($school['logo_path'])) {
                    $logo_path = $school['logo_path'];
                    
                    // Check if file exists
                    if (file_exists($logo_path)) {
                        $logo_display = true;
                    } 
                    // Try with './' prefix
                    elseif (file_exists('./' . $logo_path)) {
                        $logo_display = true;
                        $logo_path = './' . $logo_path;
                    }
                    // Try without any prefix
                    elseif (file_exists(str_replace('./', '', $logo_path))) {
                        $logo_display = true;
                        $logo_path = str_replace('./', '', $logo_path);
                    }
                }
                
                if ($logo_display): 
                ?>
                    <img src="<?php echo $logo_path; ?>" alt="School Logo" style="width:100%; height:100%; object-fit: cover;">
                <?php else: ?>
                    <div class="logo-placeholder">
                        <span class="logo-top">OF</span>
                        <span class="logo-middle">CEI</span>
                        <span class="logo-bottom">KNOWLEDGE</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="school-title">
                <h1><?php echo $school['school_name'] ?? 'RAYS OF GRACE JUNIOR SCHOOL - KIRUGU'; ?></h1>
                <div class="motto">"<?php echo $school['motto'] ?? 'KNOWLEDGE CHANGING LIVES FOREVER'; ?>"</div>
                <div class="contact"><?php echo $school['address'] ?? 'P.O BOX 200 KAMPALA'; ?> | TEL: <?php echo $school['phone'] ?? '0741344783 / 0776189712'; ?></div>
            </div>
            <div class="term-badge">
                <div class="term">Term <?php echo $term_roman; ?></div>
                <div class="year"><?php echo ACADEMIC_YEAR; ?></div>
            </div>
        </div>

        <!-- Student Information -->
        <div class="student-info">
            <div class="info-item">
                <div class="label">STUDENT</div>
                <div class="value"><?php echo $student['full_name']; ?></div>
            </div>
            <div class="photo-placeholder">
                <?php if ($photo_path): ?>
                    <img src="<?php echo $photo_path; ?>" alt="Student Photo">
                <?php else: ?>
                    <div class="no-photo">
                        <i class="fas fa-camera" style="font-size: 20px; margin-bottom: 3px;"></i>
                        <span>NO PHOTO</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="info-item">
                <div class="label">ADMISSION / TYPE / DATE</div>
                <div class="value"><?php echo $student['admission_number']; ?> <small><?php echo $student['student_type']; ?></small></div>
                <div style="font-size: 12px;"><?php echo date('F j, Y', strtotime($student['joined_date'] ?: '2026-03-08')); ?></div>
            </div>
        </div>

        <!-- Academic Performance Title -->
        <div class="section-title">
            <div class="section-icon"><i class="fas fa-chart-line"></i></div>
            <h2>ACADEMIC PERFORMANCE <span>DIVISION <?php echo $division ?: 'I'; ?></span></h2>
        </div>

        <!-- Results Table - FIXED with full subject names, whole numbers, and all 6 subjects -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>SUBJECT</th>
                        <th>SCORE</th>
                        <th>AGG</th>
                        <th>REMARKS</th>
                        <th>INIT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // All 6 subjects with FULL NAMES and default initials
                    $subject_list = [
                        'English' => ['display' => 'English', 'default_init' => 'A.G'],
                        'Mathematics' => ['display' => 'Mathematics', 'default_init' => 'K.R'],
                        'Integrated Science' => ['display' => 'Integrated Science', 'default_init' => 'K.A'],
                        'Social Studies' => ['display' => 'Social Studies', 'default_init' => 'O.E'],
                        'Kiswahili' => ['display' => 'Kiswahili', 'default_init' => 'K.A'],
                        'Religious Education' => ['display' => 'Religious Education', 'default_init' => 'O.E']
                    ];
                    
                    foreach ($subject_list as $subject => $data): 
                        $score = $all_subjects[$subject] ?? null;
                        $agg = $score ? getAggregate($score) : '';
                        
                        // FIXED: Score-based remarks
                        if ($score) {
                            if ($score >= 90) {
                                $remarks = 'Outstanding performance!';
                            } elseif ($score >= 80) {
                                $remarks = 'Excellent performance!';
                            } elseif ($score >= 70) {
                                $remarks = 'Very good work.';
                            } elseif ($score >= 60) {
                                $remarks = 'Good work.';
                            } elseif ($score >= 50) {
                                $remarks = 'Fair work.';
                            } else {
                                $remarks = 'Needs improvement.';
                            }
                        } else {
                            $remarks = '-';
                        }
                        
                        // Get initials from database or use default
                        $initial = !empty($initials[$subject]) ? $initials[$subject] : $data['default_init'];
                    ?>
                    <tr>
                        <td class="subject"><?php echo $data['display']; ?></td>
                        <td class="score"><?php echo $score ? round($score) : '-'; ?></td>
                        <td><?php if ($agg): ?><span class="agg"><?php echo $agg; ?></span><?php else: ?>-<?php endif; ?></td>
                        <td class="remarks"><?php echo $remarks; ?></td>
                        <td class="init"><?php echo $initial; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- Total Row -->
                    <tr class="total-row">
                        <td colspan="5">
                            <strong>TOTAL: <?php echo $total_score ?: '0'; ?></strong> | 
                            <strong>DIVISION <?php echo $division ?: 'I'; ?></strong> | 
                            Core Agg: <?php echo $core_total ?: '0'; ?> | <?php echo $subject_count; ?>/6
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Score Summary -->
            <div class="stat-box">
                <div class="stat-header">
                    <i class="fas fa-calculator"></i>
                    <h3>Score Summary</h3>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Total Score:</span>
                    <span class="stat-value"><?php echo $total_score ?: '0'; ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Average:</span>
                    <span class="stat-value"><?php echo $average ?: '0'; ?>%</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Core Aggregate:</span>
                    <span class="stat-value"><?php echo $core_total ?: '0'; ?></span>
                </div>
                <div class="division-badge">
                    <i class="fas fa-crown"></i> Division <?php echo $division ?: 'I'; ?> (<?php 
                        echo $division == 'I' ? 'Distinction' : 
                            ($division == 'II' ? 'Credit' : 
                            ($division == 'III' ? 'Merit' : 
                            ($division == 'IV' ? 'Pass' : 'Ungraded'))); ?>)
                </div>
            </div>

            <!-- Grade Key -->
            <div class="stat-box">
                <div class="stat-header">
                    <i class="fas fa-key"></i>
                    <h3>Grade Key</h3>
                </div>
                <div class="grade-row">
                    <span class="grade-item">1/90-100%</span>
                    <span class="grade-item">2/80-89%</span>
                </div>
                <div class="grade-row">
                    <span class="grade-item">3/70-79%</span>
                    <span class="grade-item">4/60-69%</span>
                </div>
                <div class="grade-row">
                    <span class="grade-item">5/50-59%</span>
                    <span class="grade-item">6/45-49%</span>
                </div>
                <div class="grade-row">
                    <span class="grade-item">7/40-44%</span>
                    <span class="grade-item">8/35-39%</span>
                </div>
                <div class="grade-row">
                    <span class="grade-item">9/0-34%</span>
                </div>
            </div>
        </div>

        <!-- Teacher's Comment -->
        <div class="comment-section">
            <div class="comment-text">"<?php echo $teacher_comment; ?>"</div>
            <div class="comment-author">— <?php echo CLASS_TEACHER; ?>, Class Teacher</div>
        </div>

        <!-- Signatures -->
        <div class="signature-grid">
            <div class="signature-item">
                <div class="signature-label">CLASS TEACHER</div>
                <div class="signature-name"><?php echo CLASS_TEACHER; ?></div>
                <div class="signature-line"></div>
            </div>
            <div class="signature-item">
                <div class="signature-label">HEAD TEACHER</div>
                <div class="signature-name">Mr. Sanga'yi Gerald</div>
                <div class="signature-line"></div>
            </div>
            <div class="signature-item">
                <div class="signature-label">PARENT</div>
                <div class="signature-name"></div>
                <div class="signature-line"></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="keys">
                <span class="key">1/90-100%</span>
                <span class="key">2/80-89%</span>
                <span class="key">3/70-79%</span>
                <span class="key">4/60-69%</span>
                <span class="key">5/50-59%</span>
                <span class="key">6/45-49%</span>
                <span class="key">7/40-44%</span>
                <span class="key">8/35-39%</span>
                <span class="key">9/0-34%</span>
            </div>
            <div class="division-info">
                Division (4 Core): 1/4-12, 1/13-24, 1/25-29, 1/30-33, 1/34-36
            </div>
            <div class="paycode">
                <i class="fas fa-barcode"></i> PAYCODE: <?php echo $student['admission_number']; ?>-<?php echo date('Ymd'); ?>
            </div>
        </div>
    </div>

    <script>
        const reportCard = document.getElementById('reportCard');

        function exportAsPDF() {
            html2canvas(reportCard, { scale: 2, backgroundColor: '#ffffff' }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save('RAYS_OF_GRACE_REPORT_CARD_<?php echo $student['admission_number']; ?>.pdf');
            });
        }

        function exportAsPNG() {
            html2canvas(reportCard, { scale: 2, backgroundColor: '#ffffff' }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'RAYS_OF_GRACE_REPORT_CARD_<?php echo $student['admission_number']; ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }

        function exportAsJPG() {
            html2canvas(reportCard, { scale: 2, backgroundColor: '#ffffff' }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'RAYS_OF_GRACE_REPORT_CARD_<?php echo $student['admission_number']; ?>.jpg';
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
            });
        }

        function exportAsDOC() {
            const styles = document.querySelectorAll('style');
            let stylesString = '';
            styles.forEach(style => stylesString += style.innerHTML);
            
            const fullHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Rays of Grace - Report Card - <?php echo $student['full_name']; ?></title>
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
                    <style>${stylesString}</style>
                </head>
                <body style="background: white; padding: 20px; display: flex; justify-content: center;">
                    ${reportCard.outerHTML}
                </body>
                </html>
            `;
            
            const blob = new Blob([fullHTML], { type: 'application/msword' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'RAYS_OF_GRACE_REPORT_CARD_<?php echo $student['admission_number']; ?>.doc';
            link.click();
        }
    </script>
</body>
</html>