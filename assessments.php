<?php
// marksheet.php - Complete Ugandan Marksheet System with Total Aggregate (NO DECIMALS)
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get parameters
$term = $_GET['term'] ?? CURRENT_TERM;
$exam_type = $_GET['exam_type'] ?? 'Beginning'; // Beginning, Mid-term, End of Term
$export = $_GET['export'] ?? '';

// Define subjects
$subjects = [
    'Mathematics' => 'MATH',
    'English' => 'ENG',
    'Integrated Science' => 'SCI',
    'Social Studies' => 'SST',
    'Religious Education' => 'RE',
    'Kiswahili' => 'KISW'
];

// Core subjects for division
$core_subjects = ['Mathematics', 'English', 'Integrated Science', 'Social Studies'];

// Get all active students
$students = $pdo->query("SELECT id, full_name FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();

// Get teacher initials from database
$teacher_initials = [];
$stmt = $pdo->query("SELECT subject, teacher_initials FROM subject_teachers");
while ($row = $stmt->fetch()) {
    $teacher_initials[$row['subject']] = $row['teacher_initials'];
}

// Get existing marks
$marks = [];
$stmt = $pdo->prepare("SELECT student_id, subject, score FROM assessments WHERE year = ? AND term = ? AND exam_type = ?");
$stmt->execute([ACADEMIC_YEAR, $term, $exam_type]);
$results = $stmt->fetchAll();

foreach ($results as $row) {
    $marks[$row['student_id']][$row['subject']] = $row['score'];
}

// Helper functions
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

// Prepare data for export
function prepareExportData($students, $marks, $subjects, $core_subjects) {
    $export_data = [];
    $sn = 1;
    
    foreach ($students as $student) {
        $student_id = $student['id'];
        $row = [
            'sn' => $sn++,
            'name' => $student['full_name']
        ];
        
        $math = isset($marks[$student_id]['Mathematics']) ? round($marks[$student_id]['Mathematics']) : '';
        $eng = isset($marks[$student_id]['English']) ? round($marks[$student_id]['English']) : '';
        $sci = isset($marks[$student_id]['Integrated Science']) ? round($marks[$student_id]['Integrated Science']) : '';
        $sst = isset($marks[$student_id]['Social Studies']) ? round($marks[$student_id]['Social Studies']) : '';
        $re = isset($marks[$student_id]['Religious Education']) ? round($marks[$student_id]['Religious Education']) : '';
        $kisw = isset($marks[$student_id]['Kiswahili']) ? round($marks[$student_id]['Kiswahili']) : '';
        
        // Get aggregates
        $math_agg = $math ? getAggregate($math) : '';
        $eng_agg = $eng ? getAggregate($eng) : '';
        $sci_agg = $sci ? getAggregate($sci) : '';
        $sst_agg = $sst ? getAggregate($sst) : '';
        $re_agg = $re ? getAggregate($re) : '';
        $kisw_agg = $kisw ? getAggregate($kisw) : '';
        
        // Calculate total aggregate for core subjects
        $core_agg_total = 0;
        if ($math_agg) $core_agg_total += $math_agg;
        if ($eng_agg) $core_agg_total += $eng_agg;
        if ($sci_agg) $core_agg_total += $sci_agg;
        if ($sst_agg) $core_agg_total += $sst_agg;
        
        $row['math_score'] = $math;
        $row['math_agg'] = $math_agg;
        $row['eng_score'] = $eng;
        $row['eng_agg'] = $eng_agg;
        $row['sci_score'] = $sci;
        $row['sci_agg'] = $sci_agg;
        $row['sst_score'] = $sst;
        $row['sst_agg'] = $sst_agg;
        $row['re_score'] = $re;
        $row['re_agg'] = $re_agg;
        $row['kisw_score'] = $kisw;
        $row['kisw_agg'] = $kisw_agg;
        
        $total = array_sum(array_filter([$math, $eng, $sci, $sst, $re, $kisw]));
        $count = count(array_filter([$math, $eng, $sci, $sst, $re, $kisw]));
        $avg = $count > 0 ? round($total / $count, 1) : '';
        $division = getDivision($core_agg_total);
        
        $row['total'] = $total ?: '';
        $row['average'] = $avg;
        $row['total_agg'] = $core_agg_total ?: '';
        $row['division'] = $division;
        
        $export_data[] = $row;
    }
    
    return $export_data;
}

// ============================================
// HANDLE EXPORTS
// ============================================

// CSV EXPORT
if ($export == 'csv') {
    $export_data = prepareExportData($students, $marks, $subjects, $core_subjects);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . str_replace(' ', '_', $exam_type) . '_TERM_' . $term . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    // Header row
    $header = ['S/N', 'Student Name'];
    foreach ($subjects as $code) {
        $header[] = $code . ' Score';
        $header[] = $code . ' Agg';
        $header[] = $code . ' Init';
    }
    $header[] = 'TOTAL';
    $header[] = 'AVERAGE';
    $header[] = 'TOTAL AGG';
    $header[] = 'DIV';
    fputcsv($output, $header);
    
    // Data rows
    foreach ($export_data as $row) {
        $csv_row = [
            $row['sn'],
            $row['name'],
            $row['math_score'], $row['math_agg'], $GLOBALS['teacher_initials']['Mathematics'] ?? '',
            $row['eng_score'], $row['eng_agg'], $GLOBALS['teacher_initials']['English'] ?? '',
            $row['sci_score'], $row['sci_agg'], $GLOBALS['teacher_initials']['Integrated Science'] ?? '',
            $row['sst_score'], $row['sst_agg'], $GLOBALS['teacher_initials']['Social Studies'] ?? '',
            $row['re_score'], $row['re_agg'], $GLOBALS['teacher_initials']['Religious Education'] ?? '',
            $row['kisw_score'], $row['kisw_agg'], $GLOBALS['teacher_initials']['Kiswahili'] ?? '',
            $row['total'],
            $row['average'],
            $row['total_agg'],
            $row['division']
        ];
        fputcsv($output, $csv_row);
    }
    
    fclose($output);
    exit;
}

// EXCEL EXPORT
if ($export == 'excel') {
    $export_data = prepareExportData($students, $marks, $subjects, $core_subjects);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=' . str_replace(' ', '_', $exam_type) . '_TERM_' . $term . '_' . date('Y-m-d') . '.xls');
    
    echo '<html>';
    echo '<head>';
    echo '<style>';
    echo 'td { border: 1px solid #000; padding: 5px; text-align: center; }';
    echo 'th { background: #4a1a3a; color: white; padding: 8px; text-align: center; }';
    echo '.agg-1, .agg-2 { background: #4CAF50; color: white; }';
    echo '.agg-3, .agg-4 { background: #2196F3; color: white; }';
    echo '.agg-5, .agg-6 { background: #FF9800; color: white; }';
    echo '.agg-7, .agg-8 { background: #f44336; color: white; }';
    echo '.agg-9 { background: #9C27B0; color: white; }';
    echo '.init-cell { background: #f0e8f0; color: #4a1a3a; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<table border="1">';
    
    // Title
    echo '<tr><th colspan="' . (count($subjects) * 3 + 6) . '">';
    echo strtoupper($exam_type) . ' TERM ' . $term . ' MARKSHEET - P.5 PURPLE';
    echo '</th></tr>';
    
    // Main Header
    echo '<tr>';
    echo '<th rowspan="2">S/N</th>';
    echo '<th rowspan="2">Student Name</th>';
    foreach ($subjects as $code) {
        echo '<th colspan="3">' . $code . '</th>';
    }
    echo '<th rowspan="2">TOTAL</th>';
    echo '<th rowspan="2">AVG</th>';
    echo '<th rowspan="2">TOT AGG</th>';
    echo '<th rowspan="2">DIV</th>';
    echo '</tr>';
    
    // Sub-header
    echo '<tr>';
    foreach ($subjects as $code) {
        echo '<th>Score</th><th>Agg</th><th>Init</th>';
    }
    echo '</tr>';
    
    // Data rows
    foreach ($export_data as $row) {
        echo '<tr>';
        echo '<td>' . $row['sn'] . '</td>';
        echo '<td style="text-align: left;">' . $row['name'] . '</td>';
        
        // MATH
        echo '<td>' . $row['math_score'] . '</td>';
        echo '<td' . ($row['math_agg'] ? ' class="agg-' . $row['math_agg'] . '"' : '') . '>' . $row['math_agg'] . '</td>';
        echo '<td class="init-cell">' . ($teacher_initials['Mathematics'] ?? '-') . '</td>';
        
        // ENG
        echo '<td>' . $row['eng_score'] . '</td>';
        echo '<td' . ($row['eng_agg'] ? ' class="agg-' . $row['eng_agg'] . '"' : '') . '>' . $row['eng_agg'] . '</td>';
        echo '<td class="init-cell">' . ($teacher_initials['English'] ?? '-') . '</td>';
        
        // SCI
        echo '<td>' . $row['sci_score'] . '</td>';
        echo '<td' . ($row['sci_agg'] ? ' class="agg-' . $row['sci_agg'] . '"' : '') . '>' . $row['sci_agg'] . '</td>';
        echo '<td class="init-cell">' . ($teacher_initials['Integrated Science'] ?? '-') . '</td>';
        
        // SST
        echo '<td>' . $row['sst_score'] . '</td>';
        echo '<td' . ($row['sst_agg'] ? ' class="agg-' . $row['sst_agg'] . '"' : '') . '>' . $row['sst_agg'] . '</td>';
        echo '<td class="init-cell">' . ($teacher_initials['Social Studies'] ?? '-') . '</td>';
        
        // RE
        echo '<td>' . $row['re_score'] . '</td>';
        echo '<td' . ($row['re_agg'] ? ' class="agg-' . $row['re_agg'] . '"' : '') . '>' . $row['re_agg'] . '</td>';
        echo '<td class="init-cell">' . ($teacher_initials['Religious Education'] ?? '-') . '</td>';
        
        // KISW
        echo '<td>' . $row['kisw_score'] . '</td>';
        echo '<td' . ($row['kisw_agg'] ? ' class="agg-' . $row['kisw_agg'] . '"' : '') . '>' . $row['kisw_agg'] . '</td>';
        echo '<td class="init-cell">' . ($teacher_initials['Kiswahili'] ?? '-') . '</td>';
        
        echo '<td><strong>' . $row['total'] . '</strong></td>';
        echo '<td><strong>' . $row['average'] . '</strong></td>';
        echo '<td><strong>' . $row['total_agg'] . '</strong></td>';
        echo '<td><strong>' . $row['division'] . '</strong></td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

// PDF EXPORT - (kept as in original, not shown here for brevity – your existing PDF code remains unchanged)
// ... (your existing PDF export code) ...

// DOC EXPORT - (kept as in original)
// ... (your existing DOC export code) ...

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saved_count = 0;
    
    foreach ($_POST['scores'] as $student_id => $student_scores) {
        foreach ($student_scores as $subject => $score) {
            if ($score !== '') {
                // Check if record exists
                $check = $pdo->prepare("SELECT id FROM assessments WHERE student_id = ? AND year = ? AND term = ? AND exam_type = ? AND subject = ?");
                $check->execute([$student_id, ACADEMIC_YEAR, $term, $exam_type, $subject]);
                $existing = $check->fetch();
                
                if ($existing) {
                    // Update
                    $update = $pdo->prepare("UPDATE assessments SET score = ? WHERE id = ?");
                    $update->execute([$score, $existing['id']]);
                } else {
                    // Insert
                    $insert = $pdo->prepare("INSERT INTO assessments (student_id, year, term, exam_type, subject, score) VALUES (?, ?, ?, ?, ?, ?)");
                    $insert->execute([$student_id, ACADEMIC_YEAR, $term, $exam_type, $subject, $score]);
                }
                $saved_count++;
            }
        }
    }
    
    if ($saved_count > 0) {
        $message = "$saved_count scores saved successfully!";
        $message_type = "success";
        
        // Refresh marks
        $marks = [];
        $stmt = $pdo->prepare("SELECT student_id, subject, score FROM assessments WHERE year = ? AND term = ? AND exam_type = ?");
        $stmt->execute([ACADEMIC_YEAR, $term, $exam_type]);
        $results = $stmt->fetchAll();
        
        foreach ($results as $row) {
            $marks[$row['student_id']][$row['subject']] = $row['score'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marksheet - P.5 Purple</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            padding: 30px;
            border: 1px solid rgba(74, 26, 58, 0.1);
        }

        /* Premium Header */
        .premium-header {
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .class-title h1 {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .class-title h1 i {
            color: var(--orange);
            margin-right: 12px;
        }

        .class-slogan {
            color: var(--orange-light);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .class-badge {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-premium {
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 10px 20px;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 12px 25px;
            background: white;
            border: 2px solid var(--gray-300);
            border-radius: 12px;
            font-weight: 600;
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition);
        }

        .tab:hover {
            border-color: var(--orange);
            color: var(--orange);
        }

        .tab.active {
            background: var(--purple);
            border-color: var(--purple);
            color: white;
        }

        /* Export buttons */
        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-export {
            background: var(--orange);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .btn-export:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
        }

        /* Alert */
        .alert {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
            border-left: 4px solid transparent;
        }

        .alert.success {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-left-color: var(--success);
            color: #219a52;
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

        /* Table */
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 12px;
            border: 1px solid var(--gray-300);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1600px;
            font-size: 0.9rem;
        }

        th {
            background: var(--purple);
            color: white;
            padding: 12px 5px;
            font-weight: 600;
            text-align: center;
            border: 1px solid var(--purple-light);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            border: 1px solid var(--gray-300);
            padding: 8px 3px;
            text-align: center;
            vertical-align: middle;
        }

        tr:hover td {
            background: var(--gray-50);
        }

        .student-name {
            font-weight: 600;
            color: var(--purple-dark);
            text-align: left !important;
            padding-left: 10px !important;
        }

        input[type="number"] {
            width: 55px;
            padding: 5px;
            border: 2px solid var(--gray-300);
            border-radius: 6px;
            text-align: center;
            font-size: 0.85rem;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        input[type="number"]:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(239,91,43,0.1);
        }

        .agg-badge {
            display: inline-block;
            width: 25px;
            height: 25px;
            line-height: 25px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 0.8rem;
            color: white;
        }

        .agg-1, .agg-2 { background: #4CAF50; }
        .agg-3, .agg-4 { background: #2196F3; }
        .agg-5, .agg-6 { background: #FF9800; }
        .agg-7, .agg-8 { background: #f44336; }
        .agg-9 { background: #9C27B0; }

        .init-cell {
            font-weight: 600;
            color: var(--orange);
            background-color: rgba(239, 91, 43, 0.05);
        }

        .division-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .division-1 { background: #4CAF50; color: white; }
        .division-2 { background: #2196F3; color: white; }
        .division-3 { background: #FF9800; color: white; }
        .division-4 { background: #f44336; color: white; }
        .division-u { background: #9C27B0; color: white; }

        .total-cell, .avg-cell, .total-agg-cell {
            font-weight: 700;
            color: var(--purple-dark);
            background: var(--gray-100);
        }

        .btn-save {
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-300);
            font-size: 12px;
            color: var(--gray-500);
        }

        .grading-scale {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        .grade-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
        }

        .grade-sample {
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            .class-badge {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Premium Header with Back Button -->
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1><i class="fas fa-table"></i> Marksheet - P.5 Purple</h1>
                    <div class="class-slogan">Academic Year <?php echo ACADEMIC_YEAR; ?> • <?php echo $exam_type; ?> Term <?php echo $term; ?></div>
                </div>
                <div class="class-badge">
                    <a href="index.php" class="btn-premium"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>

        <!-- Term Tabs -->
        <div class="tabs">
            <a href="?term=1&exam_type=<?php echo urlencode($exam_type); ?>" class="tab <?php echo $term == '1' ? 'active' : ''; ?>">Term 1</a>
            <a href="?term=2&exam_type=<?php echo urlencode($exam_type); ?>" class="tab <?php echo $term == '2' ? 'active' : ''; ?>">Term 2</a>
            <a href="?term=3&exam_type=<?php echo urlencode($exam_type); ?>" class="tab <?php echo $term == '3' ? 'active' : ''; ?>">Term 3</a>
        </div>

        <!-- Exam Type Tabs -->
        <div class="tabs">
            <a href="?term=<?php echo $term; ?>&exam_type=Beginning" class="tab <?php echo $exam_type == 'Beginning' ? 'active' : ''; ?>">Beginning (BOT)</a>
            <a href="?term=<?php echo $term; ?>&exam_type=Mid-term" class="tab <?php echo $exam_type == 'Mid-term' ? 'active' : ''; ?>">Mid-Term (MID)</a>
            <a href="?term=<?php echo $term; ?>&exam_type=End of Term" class="tab <?php echo $exam_type == 'End of Term' ? 'active' : ''; ?>">End of Term (END)</a>
        </div>

        <!-- Export and Action Buttons -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <div class="export-buttons">
                <a href="?term=<?php echo $term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=csv" class="btn-export"><i class="fas fa-file-csv"></i> CSV</a>
                <a href="?term=<?php echo $term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=excel" class="btn-export"><i class="fas fa-file-excel"></i> Excel</a>
                <a href="?term=<?php echo $term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=pdf" class="btn-export"><i class="fas fa-file-pdf"></i> PDF</a>
                <a href="?term=<?php echo $term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=doc" class="btn-export"><i class="fas fa-file-word"></i> Word</a>
            </div>
            <a href="report-selector.php?term=<?php echo $term; ?>" class="btn-premium" style="background: #4a1a3a; color: white;">
                <i class="fas fa-file-alt"></i> Generate Report Cards
            </a>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Marksheet Form -->
        <form method="POST">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2">S/N</th>
                            <th rowspan="2">Student Name</th>
                            <th colspan="3">MATH</th>
                            <th colspan="3">ENG</th>
                            <th colspan="3">SCI</th>
                            <th colspan="3">SST</th>
                            <th colspan="3">RE</th>
                            <th colspan="3">KISW</th>
                            <th rowspan="2">TOTAL</th>
                            <th rowspan="2">AVG</th>
                            <th rowspan="2">TOT AGG</th>
                            <th rowspan="2">DIV</th>
                        </tr>
                        <tr>
                            <th>Score</th><th>Agg</th><th>Init</th>
                            <th>Score</th><th>Agg</th><th>Init</th>
                            <th>Score</th><th>Agg</th><th>Init</th>
                            <th>Score</th><th>Agg</th><th>Init</th>
                            <th>Score</th><th>Agg</th><th>Init</th>
                            <th>Score</th><th>Agg</th><th>Init</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; ?>
                        <?php foreach ($students as $student): 
                            $student_id = $student['id'];
                            
                            // Get scores and round to whole numbers
                            $math = isset($marks[$student_id]['Mathematics']) ? round($marks[$student_id]['Mathematics']) : '';
                            $eng = isset($marks[$student_id]['English']) ? round($marks[$student_id]['English']) : '';
                            $sci = isset($marks[$student_id]['Integrated Science']) ? round($marks[$student_id]['Integrated Science']) : '';
                            $sst = isset($marks[$student_id]['Social Studies']) ? round($marks[$student_id]['Social Studies']) : '';
                            $re = isset($marks[$student_id]['Religious Education']) ? round($marks[$student_id]['Religious Education']) : '';
                            $kisw = isset($marks[$student_id]['Kiswahili']) ? round($marks[$student_id]['Kiswahili']) : '';
                            
                            // Calculate aggregates
                            $math_agg = $math ? getAggregate($math) : '';
                            $eng_agg = $eng ? getAggregate($eng) : '';
                            $sci_agg = $sci ? getAggregate($sci) : '';
                            $sst_agg = $sst ? getAggregate($sst) : '';
                            $re_agg = $re ? getAggregate($re) : '';
                            $kisw_agg = $kisw ? getAggregate($kisw) : '';
                            
                            // Calculate total aggregate for core subjects
                            $core_agg_total = 0;
                            if ($math_agg) $core_agg_total += $math_agg;
                            if ($eng_agg) $core_agg_total += $eng_agg;
                            if ($sci_agg) $core_agg_total += $sci_agg;
                            if ($sst_agg) $core_agg_total += $sst_agg;
                            
                            // Calculate total and average
                            $total = array_sum(array_filter([$math, $eng, $sci, $sst, $re, $kisw]));
                            $count = count(array_filter([$math, $eng, $sci, $sst, $re, $kisw]));
                            $avg = $count > 0 ? round($total / $count, 1) : '';
                            
                            // Calculate division
                            $division = getDivision($core_agg_total);
                            $division_class = $division == 'I' ? 'division-1' : 
                                             ($division == 'II' ? 'division-2' : 
                                             ($division == 'III' ? 'division-3' : 
                                             ($division == 'IV' ? 'division-4' : 
                                             ($division == 'U' ? 'division-u' : ''))));
                        ?>
                        <tr>
                            <td><?php echo $sn++; ?></td>
                            <td class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></td>
                            
                            <!-- MATH -->
                            <td><input type="number" name="scores[<?php echo $student_id; ?>][Mathematics]" value="<?php echo $math; ?>" step="1" min="0" max="100"></td>
                            <td><?php echo $math_agg ? "<span class='agg-badge agg-$math_agg'>$math_agg</span>" : '-'; ?></td>
                            <td class="init-cell"><?php echo $teacher_initials['Mathematics'] ?? '-'; ?></td>
                            
                            <!-- ENG -->
                            <td><input type="number" name="scores[<?php echo $student_id; ?>][English]" value="<?php echo $eng; ?>" step="1" min="0" max="100"></td>
                            <td><?php echo $eng_agg ? "<span class='agg-badge agg-$eng_agg'>$eng_agg</span>" : '-'; ?></td>
                            <td class="init-cell"><?php echo $teacher_initials['English'] ?? '-'; ?></td>
                            
                            <!-- SCI -->
                            <td><input type="number" name="scores[<?php echo $student_id; ?>][Integrated Science]" value="<?php echo $sci; ?>" step="1" min="0" max="100"></td>
                            <td><?php echo $sci_agg ? "<span class='agg-badge agg-$sci_agg'>$sci_agg</span>" : '-'; ?></td>
                            <td class="init-cell"><?php echo $teacher_initials['Integrated Science'] ?? '-'; ?></td>
                            
                            <!-- SST -->
                            <td><input type="number" name="scores[<?php echo $student_id; ?>][Social Studies]" value="<?php echo $sst; ?>" step="1" min="0" max="100"></td>
                            <td><?php echo $sst_agg ? "<span class='agg-badge agg-$sst_agg'>$sst_agg</span>" : '-'; ?></td>
                            <td class="init-cell"><?php echo $teacher_initials['Social Studies'] ?? '-'; ?></td>
                            
                            <!-- RE -->
                            <td><input type="number" name="scores[<?php echo $student_id; ?>][Religious Education]" value="<?php echo $re; ?>" step="1" min="0" max="100"></td>
                            <td><?php echo $re_agg ? "<span class='agg-badge agg-$re_agg'>$re_agg</span>" : '-'; ?></td>
                            <td class="init-cell"><?php echo $teacher_initials['Religious Education'] ?? '-'; ?></td>
                            
                            <!-- KISW -->
                            <td><input type="number" name="scores[<?php echo $student_id; ?>][Kiswahili]" value="<?php echo $kisw; ?>" step="1" min="0" max="100"></td>
                            <td><?php echo $kisw_agg ? "<span class='agg-badge agg-$kisw_agg'>$kisw_agg</span>" : '-'; ?></td>
                            <td class="init-cell"><?php echo $teacher_initials['Kiswahili'] ?? '-'; ?></td>
                            
                            <!-- Total, Average, Total Aggregate, Division -->
                            <td class="total-cell"><strong><?php echo $total ?: '-'; ?></strong></td>
                            <td class="avg-cell"><strong><?php echo $avg ?: '-'; ?></strong></td>
                            <td class="total-agg-cell"><strong><?php echo $core_agg_total ?: '-'; ?></strong></td>
                            <td>
                                <?php if ($division != '-'): ?>
                                    <span class="division-badge <?php echo $division_class; ?>"><?php echo $division; ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save All Marks</button>
        </form>

        <div class="footer">
            <div class="grading-scale">
                <div class="grade-item"><span class="grade-sample" style="background: #4CAF50;"></span> 1-2 (90-100%)</div>
                <div class="grade-item"><span class="grade-sample" style="background: #2196F3;"></span> 3-4 (70-89%)</div>
                <div class="grade-item"><span class="grade-sample" style="background: #FF9800;"></span> 5-6 (50-69%)</div>
                <div class="grade-item"><span class="grade-sample" style="background: #f44336;"></span> 7-8 (35-49%)</div>
                <div class="grade-item"><span class="grade-sample" style="background: #9C27B0;"></span> 9 (0-34%)</div>
            </div>
            <p style="margin-top: 10px;">Division: I(4-12), II(13-24), III(25-29), IV(30-33), U(34-36)</p>
        </div>
    </div>
</body>
</html>