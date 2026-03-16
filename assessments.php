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

// PDF EXPORT - OPTIMIZED HEADER FOR MORE TABLE SPACE
if ($export == 'pdf') {
    $fpdf_path = 'fpdf186/fpdf.php';
    
    if (!file_exists($fpdf_path)) {
        $message = "PDF library not found. Please download FPDF from http://www.fpdf.org/";
        $message_type = "warning";
    } else {
        require_once($fpdf_path);
        $export_data = prepareExportData($students, $marks, $subjects, $core_subjects);
        
        // Calculate performance statistics
        $subject_stats = [];
        $division_stats = ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, 'U' => 0];
        $aggregate_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0];
        
        foreach ($subjects as $subject_name => $code) {
            $subject_stats[$subject_name] = [
                'total_students' => 0,
                'avg_score' => 0,
                'total_score' => 0,
                'aggregates' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0],
                'highest' => 0,
                'lowest' => 100
            ];
        }
        
        foreach ($export_data as $row) {
            // Division stats
            $div = $row['division'];
            if ($div && isset($division_stats[$div])) {
                $division_stats[$div]++;
            }
            
            // Subject stats
            $subject_fields = [
                'Mathematics' => 'math_score',
                'English' => 'eng_score',
                'Integrated Science' => 'sci_score',
                'Social Studies' => 'sst_score',
                'Religious Education' => 're_score',
                'Kiswahili' => 'kisw_score'
            ];
            
            foreach ($subject_fields as $subject => $field) {
                $score = $row[$field];
                if ($score && is_numeric($score)) {
                    $subject_stats[$subject]['total_students']++;
                    $subject_stats[$subject]['total_score'] += $score;
                    $subject_stats[$subject]['highest'] = max($subject_stats[$subject]['highest'], $score);
                    $subject_stats[$subject]['lowest'] = min($subject_stats[$subject]['lowest'], $score);
                    
                    $agg = getAggregate($score);
                    if (isset($subject_stats[$subject]['aggregates'][$agg])) {
                        $subject_stats[$subject]['aggregates'][$agg]++;
                    }
                    $aggregate_counts[$agg]++;
                }
            }
        }
        
        // Calculate averages
        foreach ($subject_stats as $subject => &$stats) {
            if ($stats['total_students'] > 0) {
                $stats['avg_score'] = round($stats['total_score'] / $stats['total_students'], 1);
            } else {
                $stats['avg_score'] = 0;
                $stats['highest'] = 0;
                $stats['lowest'] = 0;
            }
        }
        
        class PDF extends FPDF {
    function Header() {
        // Only show full header on first page
        if ($this->PageNo() == 1) {
            // Background accent bar at top (thinner)
            $this->SetFillColor(74, 26, 58);
            $this->Rect(0, 0, 297, 5, 'F');
            
            // Logo on left (slightly smaller)
            $logo_path = 'images/school-logo.png';
            if (file_exists($logo_path)) {
                $this->Image($logo_path, 15, 8, 22);
            }
            
            // School name - compact
            $this->SetY(10);
            $this->SetX(42);
            $this->SetFont('Arial', 'B', 16);
            $this->SetTextColor(74, 26, 58);
            $this->Cell(0, 5, 'RAYS OF GRACE JUNIOR SCHOOL', 0, 1, 'L');
            
            // Contact line - one line only
            $this->SetX(42);
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(80, 80, 80);
            $this->Cell(0, 3, 'P.O. Box XXX, Kampala | Tel: +256 XXX XXX XXX | info@raysofgrace.ac.ug', 0, 1, 'L');
            
            // Motto - right below contact
            $this->SetX(42);
            $this->SetFont('Arial', 'I', 9);
            $this->SetTextColor(239, 91, 43);
            $this->Cell(0, 4, '"Knowledge Changing Lives Forever"', 0, 1, 'L');
            
            // Decorative bar - moved up, right below motto
            $this->Ln(2);
            $this->SetFillColor(239, 91, 43);
            $this->Rect(15, 32, 267, 1.5, 'F');
            
            // Title - right below decorative bar
            $this->SetY(36);
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(74, 26, 58);
            $this->Cell(0, 5, strtoupper($GLOBALS['exam_type']) . ' TERM ' . $GLOBALS['term'] . ' MARKSHEET', 0, 1, 'C');
            
            // Subtitle and date on same line to save space
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 4, 'P.5 Purple • Academic Year ' . ACADEMIC_YEAR . ' • Generated: ' . date('F j, Y'), 0, 1, 'C');
            
            // Minimal space before table
            $this->Ln(2);
            
        } else {
            // Minimal header for subsequent pages
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(74, 26, 58);
            $this->Cell(0, 8, strtoupper($GLOBALS['exam_type']) . ' TERM ' . $GLOBALS['term'] . ' MARKSHEET (cont.)', 0, 1, 'C');
            $this->Ln(2);
        }
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 4, 'RAYS OF GRACE JUNIOR SCHOOL', 0, 0, 'L');
        $this->Cell(0, 4, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }
    
    function ProfessionalTable($data, $subjects, $teacher_initials) {
        // Column widths optimized - NO INITIALS COLUMN
        $pageWidth = 267;
        $snW = 9;
        $nameW = 45;
        $scoreW = 14;
        $aggW = 11;
        $totalW = 16;
        
        $subjectWidth = $scoreW + $aggW; // No initials column
        
        // Store for later use
        $this->snW = $snW;
        $this->nameW = $nameW;
        $this->scoreW = $scoreW;
        $this->aggW = $aggW;
        $this->totalW = $totalW;
        $this->subjectWidth = $subjectWidth;
        $this->subjects = $subjects;
        
        $this->PrintTableHeader();
        
        // Data rows - with larger font
        $this->SetFillColor(248, 248, 248);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 7); // Larger font
        $fill = false;
        
        foreach ($data as $row) {
            if ($this->GetY() > 210) {
                $this->AddPage();
                $this->PrintTableHeader();
                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 7);
            }
            
            // Row number
            $this->Cell($snW, 7, $row['sn'], 1, 0, 'C', $fill);
            
            // Student name
            $name = strlen($row['name']) > 20 ? substr($row['name'], 0, 18) . '..' : $row['name'];
            $this->Cell($nameW, 7, $name, 1, 0, 'L', $fill);
            
            // MATH
            $this->Cell($scoreW, 7, $row['math_score'] ?: '-', 1, 0, 'C', $fill);
            $this->Cell($aggW, 7, $row['math_agg'] ?: '-', 1, 0, 'C', $fill);
            
            // ENG
            $this->Cell($scoreW, 7, $row['eng_score'] ?: '-', 1, 0, 'C', $fill);
            $this->Cell($aggW, 7, $row['eng_agg'] ?: '-', 1, 0, 'C', $fill);
            
            // SCI
            $this->Cell($scoreW, 7, $row['sci_score'] ?: '-', 1, 0, 'C', $fill);
            $this->Cell($aggW, 7, $row['sci_agg'] ?: '-', 1, 0, 'C', $fill);
            
            // SST
            $this->Cell($scoreW, 7, $row['sst_score'] ?: '-', 1, 0, 'C', $fill);
            $this->Cell($aggW, 7, $row['sst_agg'] ?: '-', 1, 0, 'C', $fill);
            
            // RE
            $this->Cell($scoreW, 7, $row['re_score'] ?: '-', 1, 0, 'C', $fill);
            $this->Cell($aggW, 7, $row['re_agg'] ?: '-', 1, 0, 'C', $fill);
            
            // KISW
            $this->Cell($scoreW, 7, $row['kisw_score'] ?: '-', 1, 0, 'C', $fill);
            $this->Cell($aggW, 7, $row['kisw_agg'] ?: '-', 1, 0, 'C', $fill);
            
            // Totals
            $this->Cell($totalW, 7, $row['total'] ?: '-', 1, 0, 'C', $fill);
            $this->Cell($totalW, 7, $row['average'] ?: '-', 1, 0, 'C', $fill);
            $this->Cell($totalW, 7, $row['total_agg'] ?: '-', 1, 0, 'C', $fill);
            
            // Division
            $div = $row['division'];
            $div_colors = ['I' => [76, 175, 80], 'II' => [33, 150, 243], 
                          'III' => [255, 152, 0], 'IV' => [244, 67, 54], 'U' => [156, 39, 176]];
            
            if ($div && isset($div_colors[$div])) {
                $this->SetFillColor($div_colors[$div][0], $div_colors[$div][1], $div_colors[$div][2]);
                $this->Cell($totalW, 7, $div, 1, 1, 'C', true);
                $this->SetFillColor(248, 248, 248);
            } else {
                $this->Cell($totalW, 7, '-', 1, 1, 'C', $fill);
            }
            
            $fill = !$fill;
        }
    }
    
    function PrintTableHeader() {
        $snW = $this->snW;
        $nameW = $this->nameW;
        $scoreW = $this->scoreW;
        $aggW = $this->aggW;
        $totalW = $this->totalW;
        $subjectWidth = $this->subjectWidth;
        $subjects = $this->subjects;
        
        $this->SetFillColor(74, 26, 58);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 7); // Larger font
        
        // Main header
        $this->Cell($snW, 11, 'S/N', 1, 0, 'C', true);
        $this->Cell($nameW, 11, 'STUDENT NAME', 1, 0, 'C', true);
        foreach ($subjects as $code) {
            $this->Cell($subjectWidth, 11, $code, 1, 0, 'C', true);
        }
        $this->Cell($totalW, 11, 'TOTAL', 1, 0, 'C', true);
        $this->Cell($totalW, 11, 'AVG', 1, 0, 'C', true);
        $this->Cell($totalW, 11, 'TOT AGG', 1, 0, 'C', true);
        $this->Cell($totalW, 11, 'DIV', 1, 1, 'C', true);
        
        // Sub-header - NO INITIALS COLUMN
        $this->SetFillColor(106, 43, 82);
        $this->SetFont('Arial', 'B', 6);
        $this->Cell($snW, 7, '', 1, 0, 'C', true);
        $this->Cell($nameW, 7, '', 1, 0, 'C', true);
        
        for ($i = 0; $i < count($subjects); $i++) {
            $this->Cell($scoreW, 7, 'Score', 1, 0, 'C', true);
            $this->Cell($aggW, 7, 'Agg', 1, 0, 'C', true);
        }
        
        $this->Cell($totalW, 7, '', 1, 0, 'C', true);
        $this->Cell($totalW, 7, '', 1, 0, 'C', true);
        $this->Cell($totalW, 7, '', 1, 0, 'C', true);
        $this->Cell($totalW, 7, '', 1, 1, 'C', true);
    }
    
    function PerformanceSummary($subject_stats, $division_stats, $aggregate_counts, $total_students) {
        $this->AddPage();
        
        // Title
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(74, 26, 58);
        $this->Cell(0, 10, 'PERFORMANCE SUMMARY', 0, 1, 'C');
        $this->Ln(5);
        
        // Division Summary
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(74, 26, 58);
        $this->Cell(0, 8, 'Overall Division Distribution', 0, 1, 'L');
        $this->Ln(2);
        
        // Division stats table
        $this->SetFillColor(74, 26, 58);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 10);
        
        $this->Cell(50, 10, 'Division', 1, 0, 'C', true);
        $this->Cell(50, 10, 'Number of Students', 1, 0, 'C', true);
        $this->Cell(50, 10, 'Percentage', 1, 1, 'C', true);
        
        $this->SetFillColor(248, 248, 248);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 10);
        
        $divisions = ['I', 'II', 'III', 'IV', 'U'];
        $div_names = ['Division I', 'Division II', 'Division III', 'Division IV', 'Ungraded'];
        $div_colors = [
            'I' => [200, 230, 200],
            'II' => [200, 220, 240],
            'III' => [255, 235, 200],
            'IV' => [255, 200, 200],
            'U' => [230, 200, 240]
        ];
        
        for ($i = 0; $i < count($divisions); $i++) {
            $div = $divisions[$i];
            $count = $division_stats[$div] ?? 0;
            $percentage = $total_students > 0 ? round(($count / $total_students) * 100, 1) : 0;
            
            $this->SetFillColor($div_colors[$div][0], $div_colors[$div][1], $div_colors[$div][2]);
            
            $this->Cell(50, 8, $div_names[$i], 1, 0, 'L', true);
            $this->Cell(50, 8, $count, 1, 0, 'C', true);
            $this->Cell(50, 8, $percentage . '%', 1, 1, 'C', true);
        }
        
        $this->Ln(10);
        
        // Subject Performance Summary
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(74, 26, 58);
        $this->Cell(0, 8, 'Subject Performance Analysis', 0, 1, 'L');
        $this->Ln(2);
        
        foreach ($subject_stats as $subject => $stats) {
            // Find the subject code
            $code = '';
            foreach ($GLOBALS['subjects'] as $subj_name => $subj_code) {
                if ($subj_name == $subject) {
                    $code = $subj_code;
                    break;
                }
            }
            
            // Subject header
            $this->SetFillColor(106, 43, 82);
            $this->SetTextColor(255);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 8, $subject . ' (' . $code . ')', 0, 1, 'L', true);
            
            // Subject summary stats
            $this->SetFillColor(248, 248, 248);
            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 9);
            
            $this->Cell(50, 7, 'Students Sat: ' . $stats['total_students'], 0, 0, 'L');
            $this->Cell(50, 7, 'Average: ' . $stats['avg_score'] . '%', 0, 0, 'L');
            $this->Cell(50, 7, 'Highest: ' . $stats['highest'] . '%', 0, 0, 'L');
            $this->Cell(50, 7, 'Lowest: ' . ($stats['lowest'] == 100 ? 'N/A' : $stats['lowest'] . '%'), 0, 1, 'L');
            
            $this->Ln(2);
            
            // Aggregate distribution table
            $this->SetFillColor(74, 26, 58);
            $this->SetTextColor(255);
            $this->SetFont('Arial', 'B', 8);
            
            $agg_headers = ['Agg 1', 'Agg 2', 'Agg 3', 'Agg 4', 'Agg 5', 'Agg 6', 'Agg 7', 'Agg 8', 'Agg 9'];
            $agg_ranges = ['90-100', '80-89', '70-79', '60-69', '50-59', '45-49', '40-44', '35-39', '0-34'];
            
            // Headers
            $this->Cell(25, 8, 'Grade', 1, 0, 'C', true);
            for ($i = 0; $i < 9; $i++) {
                $this->Cell(15, 8, $agg_headers[$i], 1, 0, 'C', true);
            }
            $this->Cell(25, 8, 'Total', 1, 1, 'C', true);
            
            // Ranges row
            $this->SetFillColor(106, 43, 82);
            $this->SetFont('Arial', 'B', 7);
            $this->Cell(25, 6, 'Range', 1, 0, 'C', true);
            for ($i = 0; $i < 9; $i++) {
                $this->Cell(15, 6, $agg_ranges[$i], 1, 0, 'C', true);
            }
            $this->Cell(25, 6, '', 1, 1, 'C', true);
            
            // Data row
            $this->SetFillColor(248, 248, 248);
            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 8);
            
            $this->Cell(25, 7, 'Students', 1, 0, 'L', true);
            $total = 0;
            for ($i = 1; $i <= 9; $i++) {
                $count = $stats['aggregates'][$i] ?? 0;
                $total += $count;
                $this->Cell(15, 7, $count, 1, 0, 'C', true);
            }
            $this->Cell(25, 7, $total, 1, 1, 'C', true);
            
            // Percentage row
            $this->Cell(25, 7, 'Percentage', 1, 0, 'L', true);
            for ($i = 1; $i <= 9; $i++) {
                $count = $stats['aggregates'][$i] ?? 0;
                $percent = $stats['total_students'] > 0 ? round(($count / $stats['total_students']) * 100, 1) : 0;
                $this->Cell(15, 7, $percent . '%', 1, 0, 'C', true);
            }
            $this->Cell(25, 7, '100%', 1, 1, 'C', true);
            
            $this->Ln(8);
        }
        
        // Overall Aggregate Distribution
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(74, 26, 58);
        $this->Cell(0, 8, 'Overall Aggregate Distribution (All Subjects)', 0, 1, 'L');
        $this->Ln(2);
        
        $this->SetFillColor(74, 26, 58);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 8);
        
        for ($i = 1; $i <= 9; $i++) {
            $this->Cell(20, 8, 'Agg ' . $i, 1, 0, 'C', true);
        }
        $this->Cell(30, 8, 'Total Entries', 1, 1, 'C', true);
        
        $this->SetFillColor(248, 248, 248);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 8);
        
        $total_entries = array_sum($aggregate_counts);
        for ($i = 1; $i <= 9; $i++) {
            $this->Cell(20, 7, $aggregate_counts[$i], 1, 0, 'C', true);
        }
        $this->Cell(30, 7, $total_entries, 1, 1, 'C', true);
        
        $this->Ln(2);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 4, 'Note: Aggregates are based on the Ugandan grading system: 1-2 (Distinction), 3-4 (Credit), 5-6 (Good), 7-8 (Pass), 9 (Fail)', 0, 1, 'L');
    }
}
        // Create PDF
        $pdf = new PDF('L', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();
        $pdf->ProfessionalTable($export_data, $subjects, $teacher_initials);
        
        // Add performance summary pages
        $total_students = count($export_data);
        $pdf->PerformanceSummary($subject_stats, $division_stats, $aggregate_counts, $total_students);
        
        // Output PDF
        $pdf->Output('D', str_replace(' ', '_', $exam_type) . '_TERM_' . $term . '_' . date('Y-m-d') . '.pdf');
        exit;
    }
}

// DOC EXPORT - WITH LOGO AND A4 LANDSCAPE
if ($export == 'doc') {
    $export_data = prepareExportData($students, $marks, $subjects, $core_subjects);
    
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename=' . str_replace(' ', '_', $exam_type) . '_TERM_' . $term . '_' . date('Y-m-d') . '.doc');
    
    // Convert logo to base64 for embedding
    $logo_path = 'images/school-logo.png';
    $logo_html = '';
    if (file_exists($logo_path)) {
        $image_data = file_get_contents($logo_path);
        $base64 = base64_encode($image_data);
        $logo_html = '<img src="data:image/png;base64,' . $base64 . '" style="width: 80px; height: auto; float: left; margin-right: 15px;">';
    }
    
    echo '<html>';
    echo '<head>';
    echo '<style>';
    echo '@page { size: A4 landscape; margin: 1.5cm; }';
    echo 'body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }';
    echo '.header { overflow: hidden; margin-bottom: 15px; border-bottom: 3px solid #4a1a3a; padding-bottom: 10px; }';
    echo '.logo { float: left; width: 80px; margin-right: 15px; }';
    echo '.school-info { float: left; }';
    echo '.school-name { color: #4a1a3a; font-size: 28px; font-weight: bold; margin: 0; }';
    echo '.school-details { color: #666; font-size: 12px; margin: 2px 0; }';
    echo '.motto { color: #ef5b2b; font-style: italic; font-size: 14px; margin: 5px 0 0 0; }';
    echo 'h2 { color: #4a1a3a; text-align: center; font-size: 22px; margin: 15px 0 5px; clear: both; }';
    echo '.subtitle { color: #666; font-size: 12px; text-align: center; margin-bottom: 20px; font-style: italic; }';
    echo 'table { border-collapse: collapse; width: 100%; font-size: 11px; clear: both; }';
    echo 'th { background: #4a1a3a; color: white; padding: 8px 4px; text-align: center; font-weight: bold; border: 1px solid #6a2b52; }';
    echo 'td { border: 1px solid #ccc; padding: 6px 4px; text-align: center; }';
    echo 'tr:nth-child(even) { background: #f8f8f8; }';
    echo '.student-name { text-align: left; font-weight: bold; color: #4a1a3a; }';
    echo '.agg-1, .agg-2 { background: #4CAF50; color: white; font-weight: bold; }';
    echo '.agg-3, .agg-4 { background: #2196F3; color: white; font-weight: bold; }';
    echo '.agg-5, .agg-6 { background: #FF9800; color: white; font-weight: bold; }';
    echo '.agg-7, .agg-8 { background: #f44336; color: white; font-weight: bold; }';
    echo '.agg-9 { background: #9C27B0; color: white; font-weight: bold; }';
    echo '.division-1 { background: #4CAF50; color: white; font-weight: bold; padding: 2px 8px; border-radius: 10px; }';
    echo '.division-2 { background: #2196F3; color: white; font-weight: bold; padding: 2px 8px; border-radius: 10px; }';
    echo '.division-3 { background: #FF9800; color: white; font-weight: bold; padding: 2px 8px; border-radius: 10px; }';
    echo '.division-4 { background: #f44336; color: white; font-weight: bold; padding: 2px 8px; border-radius: 10px; }';
    echo '.division-u { background: #9C27B0; color: white; font-weight: bold; padding: 2px 8px; border-radius: 10px; }';
    echo '.footer { margin-top: 20px; font-size: 10px; color: #999; text-align: center; border-top: 1px solid #ccc; padding-top: 10px; }';
    echo '.grading-scale { margin-top: 15px; font-size: 9px; color: #666; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Header with Logo
    echo '<div class="header">';
    if ($logo_html) {
        echo $logo_html;
    }
    echo '<div class="school-info">';
    echo '<div class="school-name">RAYS OF GRACE JUNIOR SCHOOL</div>';
    echo '<div class="school-details">P.O. Box XXX, Kampala | Tel: +256 XXX XXX XXX | info@raysofgrace.ac.ug</div>';
    echo '<div class="motto">"Knowledge Changing Lives Forever"</div>';
    echo '</div>';
    echo '</div>';
    
    // Title
    echo '<h2>' . strtoupper($exam_type) . ' TERM ' . $term . ' MARKSHEET - P.5 PURPLE</h2>';
    echo '<div class="subtitle">Generated on: ' . date('F j, Y') . ' • Academic Year ' . ACADEMIC_YEAR . '</div>';
    
    // Marksheet Table - NO INITIALS COLUMN
    echo '<table border="1">';
    
    // Main Header Row
    echo '<tr>';
    echo '<th rowspan="2" width="5%">S/N</th>';
    echo '<th rowspan="2" width="18%">STUDENT NAME</th>';
    foreach ($subjects as $code) {
        echo '<th colspan="2" width="' . (11 * count($subjects)) . '%">' . $code . '</th>';
    }
    echo '<th rowspan="2" width="7%">TOTAL</th>';
    echo '<th rowspan="2" width="7%">AVG</th>';
    echo '<th rowspan="2" width="7%">TOT AGG</th>';
    echo '<th rowspan="2" width="7%">DIV</th>';
    echo '</tr>';
    
    // Sub-header Row (Score & Agg only)
    echo '<tr>';
    for ($i = 0; $i < count($subjects); $i++) {
        echo '<th width="6%">Score</th>';
        echo '<th width="5%">Agg</th>';
    }
    echo '</tr>';
    
    // Data Rows
    foreach ($export_data as $row) {
        echo '<tr>';
        echo '<td>' . $row['sn'] . '</td>';
        echo '<td class="student-name">' . $row['name'] . '</td>';
        
        // MATH
        echo '<td>' . ($row['math_score'] ?: '-') . '</td>';
        echo '<td' . ($row['math_agg'] ? ' class="agg-' . $row['math_agg'] . '"' : '') . '>' . ($row['math_agg'] ?: '-') . '</td>';
        
        // ENG
        echo '<td>' . ($row['eng_score'] ?: '-') . '</td>';
        echo '<td' . ($row['eng_agg'] ? ' class="agg-' . $row['eng_agg'] . '"' : '') . '>' . ($row['eng_agg'] ?: '-') . '</td>';
        
        // SCI
        echo '<td>' . ($row['sci_score'] ?: '-') . '</td>';
        echo '<td' . ($row['sci_agg'] ? ' class="agg-' . $row['sci_agg'] . '"' : '') . '>' . ($row['sci_agg'] ?: '-') . '</td>';
        
        // SST
        echo '<td>' . ($row['sst_score'] ?: '-') . '</td>';
        echo '<td' . ($row['sst_agg'] ? ' class="agg-' . $row['sst_agg'] . '"' : '') . '>' . ($row['sst_agg'] ?: '-') . '</td>';
        
        // RE
        echo '<td>' . ($row['re_score'] ?: '-') . '</td>';
        echo '<td' . ($row['re_agg'] ? ' class="agg-' . $row['re_agg'] . '"' : '') . '>' . ($row['re_agg'] ?: '-') . '</td>';
        
        // KISW
        echo '<td>' . ($row['kisw_score'] ?: '-') . '</td>';
        echo '<td' . ($row['kisw_agg'] ? ' class="agg-' . $row['kisw_agg'] . '"' : '') . '>' . ($row['kisw_agg'] ?: '-') . '</td>';
        
        // Totals
        echo '<td><strong>' . ($row['total'] ?: '-') . '</strong></td>';
        echo '<td><strong>' . ($row['average'] ?: '-') . '</strong></td>';
        echo '<td><strong>' . ($row['total_agg'] ?: '-') . '</strong></td>';
        
        // Division
        $div = $row['division'];
        if ($div && in_array($div, ['I', 'II', 'III', 'IV', 'U'])) {
            $div_class = 'division-' . strtolower($div);
            echo '<td class="' . $div_class . '"><strong>' . $div . '</strong></td>';
        } else {
            echo '<td>-</td>';
        }
        
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Grading Scale
    echo '<div class="grading-scale">';
    echo '<p><strong>Grading Scale:</strong> ';
    echo '1(90-100% D1), 2(80-89% D2), 3(70-79% C3), 4(60-69% C4), ';
    echo '5(50-59% C5), 6(45-49% C6), 7(40-44% P7), 8(35-39% P8), 9(0-34% F9)</p>';
    echo '<p><strong>Division (4 Core Subjects):</strong> I(4-12), II(13-24), III(25-29), IV(30-33), U(34-36)</p>';
    echo '</div>';
    
    // Footer
    echo '<div class="footer">';
    echo 'RAYS OF GRACE JUNIOR SCHOOL - "Knowledge Changing Lives Forever" | Page 1/1';
    echo '</div>';
    
    echo '</body></html>';
    exit;
}
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

        h1 {
            color: var(--purple-dark);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        h1 i {
            color: var(--orange);
            margin-right: 12px;
        }

        .subtitle {
            color: var(--gray-600);
            margin-bottom: 30px;
            font-size: 1rem;
            border-left: 3px solid var(--orange);
            padding-left: 15px;
        }

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

        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: flex-end;
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

        .btn-export i {
            font-size: 16px;
        }

        .message {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
            border-left: 4px solid transparent;
        }

        .message.success {
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

        /* FIXED: Input fields now accept only whole numbers */
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

        /* Hide spinner arrows for cleaner look */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            opacity: 0.5;
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
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-table"></i> Marksheet - P.5 Purple</h1>
        <div class="subtitle">Academic Year <?php echo ACADEMIC_YEAR; ?> • <?php echo $exam_type; ?> Term <?php echo $term; ?></div>
        
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
        
        <!-- Export Buttons -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <div class="export-buttons">
                <a href="?term=<?php echo $term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=csv" class="btn-export"><i class="fas fa-file-csv"></i> CSV</a>
                <a href="?term=<?php echo $term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=excel" class="btn-export"><i class="fas fa-file-excel"></i> Excel</a>
                <a href="?term=<?php echo $term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=pdf" class="btn-export"><i class="fas fa-file-pdf"></i> PDF</a>
                <a href="?term=<?php echo $term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=doc" class="btn-export"><i class="fas fa-file-word"></i> Word</a>
            </div>
            <a href="report-selector.php?term=<?php echo $term; ?>" class="btn-export" style="background: #4a1a3a;">
                <i class="fas fa-file-alt"></i> Generate Report Cards
            </a>
        </div>
        
        <!-- Message -->
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
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