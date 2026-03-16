<?php
// assessments.php - Ugandan  System
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get current term and exam type
$current_term = $_GET['term'] ?? CURRENT_TERM;
$exam_type = $_GET['exam_type'] ?? 'Mid-term'; // Beginning, Mid-term, End of Term
$export_type = $_GET['export'] ?? '';

// Core subjects (4 main subjects for division)
$core_subjects = ['Mathematics', 'English', 'Integrated Science', 'Social Studies'];
$all_subjects = ['Mathematics', 'English', 'Integrated Science', 'Social Studies', 'Religious Education', 'Kiswahili'];

// Subject display names
$subject_display = [
    'Mathematics' => 'MATH',
    'English' => 'ENG',
    'Integrated Science' => 'SCI',
    'Social Studies' => 'SST',
    'Religious Education' => 'R.E',
    'Kiswahili' => 'KISW'
];

// Get all active students
$students = $pdo->query("SELECT * FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();

// Get all assessments for this term and exam type
$stmt = $pdo->prepare("
    SELECT a.*, s.full_name 
    FROM assessments a
    JOIN students s ON a.student_id = s.id
    WHERE a.year = ? AND a.term = ? AND a.exam_type = ?
    ORDER BY s.full_name
");
$stmt->execute([ACADEMIC_YEAR, $current_term, $exam_type]);
$results = $stmt->fetchAll();

// Organize assessments by student and subject
$marks_data = [];
foreach ($results as $row) {
    $student_id = $row['student_id'];
    if (!isset($marks_data[$student_id])) {
        $marks_data[$student_id] = [
            'name' => $row['full_name'],
            'scores' => [],
            'initials' => []
        ];
    }
    $marks_data[$student_id]['scores'][$row['subject']] = $row['score'];
    $marks_data[$student_id]['initials'][$row['subject']] = $row['subject_teacher_initials'] ?? '';
}
// DEBUG: Check what data we have for student 70
error_log("Marks data for student 70: " . print_r($marks_data[70] ?? [], true));

// Handle form submission for saving marks
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marksheet'])) {
    $exam_date = $_POST['exam_date'];
    $student_ids = $_POST['student_id'] ?? [];
    $success_count = 0;
    $error_count = 0;

    // Get the current exam type from the URL or POST
    $current_exam_type = $exam_type; // This comes from the page load
    
    foreach ($student_ids as $index => $student_id) {
        foreach ($all_subjects as $subject) {
            $score_key = "score_{$student_id}_{$subject}";
            $initials_key = "initials_{$student_id}_{$subject}";
            
            // Get the score value (can be empty)
            $score = isset($_POST[$score_key]) && $_POST[$score_key] !== '' ? $_POST[$score_key] : null;
            $initials = $_POST[$initials_key] ?? '';
            
            // Only process if score is not null
            if ($score !== null) {
                // Check if record exists
                $check_sql = "SELECT id FROM assessments WHERE student_id = ? AND year = ? AND term = ? AND exam_type = ? AND subject = ?";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$student_id, ACADEMIC_YEAR, $current_term, $current_exam_type, $subject]);
                $existing = $check_stmt->fetch();
                
                if ($existing) {
                    // UPDATE existing record
                    $update_sql = "UPDATE assessments SET score = ?, exam_date = ?, subject_teacher_initials = ? WHERE id = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    if ($update_stmt->execute([$score, $exam_date, $initials, $existing['id']])) {
                        $success_count++;
                    }
                } else {
                    // INSERT new record
                    $insert_sql = "INSERT INTO assessments 
                                  (student_id, year, term, exam_type, subject, score, exam_date, subject_teacher_initials) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = $pdo->prepare($insert_sql);
                    if ($insert_stmt->execute([
                        $student_id, 
                        ACADEMIC_YEAR, 
                        $current_term, 
                        $current_exam_type, 
                        $subject, 
                        $score, 
                        $exam_date, 
                        $initials
                    ])) {
                        $success_count++;
                    }
                }
            }
        }
    }
    
    if ($success_count > 0) {
        $message = "$success_count scores saved successfully!";
        $message_type = "success";
        
        // Refresh data
        $stmt = $pdo->prepare("
            SELECT a.*, s.full_name 
            FROM assessments a
            JOIN students s ON a.student_id = s.id
            WHERE a.year = ? AND a.term = ? AND a.exam_type = ?
            ORDER BY s.full_name
        ");
        $stmt->execute([ACADEMIC_YEAR, $current_term, $current_exam_type]);
        $results = $stmt->fetchAll();
        
        $marks_data = [];
        foreach ($results as $row) {
            $student_id = $row['student_id'];
            if (!isset($marks_data[$student_id])) {
                $marks_data[$student_id] = [
                    'name' => $row['full_name'],
                    'scores' => [],
                    'initials' => []
                ];
            }
            $marks_data[$student_id]['scores'][$row['subject']] = $row['score'];
            $marks_data[$student_id]['initials'][$row['subject']] = $row['subject_teacher_initials'] ?? '';
        }
    }
}   
// Prepare data for export
function prepareExportData($students, $marks_data, $all_subjects, $core_subjects) {
    $export_data = [];
    $sn = 1;
    foreach ($students as $student) {
        $row = [
            'sn' => $sn++,
            'name' => $student['full_name']
        ];
        
        $total_score = 0;
        $subject_count = 0;
        $core_agg_total = 0;
        
        foreach ($all_subjects as $subject) {
            $score = isset($marks_data[$student['id']]['scores'][$subject]) ? $marks_data[$student['id']]['scores'][$subject] : '';
            $agg = '';
            
            if ($score !== '' && is_numeric($score)) {
                $agg = getAggregate($score);
                $total_score += $score;
                $subject_count++;
                
                if (in_array($subject, $core_subjects)) {
                    $core_agg_total += $agg;
                }
            }
            
            $row[$subject . '_score'] = $score;
            $row[$subject . '_agg'] = $agg;
        }
        
        $row['total'] = $total_score ?: '';
        $row['average'] = $subject_count > 0 ? round($total_score / $subject_count, 1) : '';
        $row['total_agg'] = $core_agg_total ?: '';
        $row['division'] = getDivisionFromAgg($core_agg_total);
        
        $export_data[] = $row;
    }
    return $export_data;
}

// ============================================
// HANDLE CSV EXPORT
// ============================================
if ($export_type == 'csv') {
    $export_data = prepareExportData($students, $marks_data, $all_subjects, $core_subjects);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . str_replace(' ', '_', $exam_type) . '_TERM_' . $current_term . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    // Header row - MATCHING THE  LAYOUT
    $header = ['S/N', "LEARNER'S NAME"];
    foreach ($all_subjects as $subject) {
        $header[] = $subject_display[$subject] . ' Score';
        $header[] = $subject_display[$subject] . ' Agg';
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
            $row['name']
        ];
        foreach ($all_subjects as $subject) {
            $csv_row[] = $row[$subject . '_score'];
            $csv_row[] = $row[$subject . '_agg'];
        }
        $csv_row[] = $row['total'];
        $csv_row[] = $row['average'];
        $csv_row[] = $row['total_agg'];
        $csv_row[] = $row['division'];
        fputcsv($output, $csv_row);
    }
    
    fclose($output);
    exit;
}

// ============================================
// HANDLE EXCEL EXPORT (XLS) - A4 LANDSCAPE
// ============================================
if ($export_type == 'excel') {
    $export_data = prepareExportData($students, $marks_data, $all_subjects, $core_subjects);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=' . str_replace(' ', '_', $exam_type) . '_TERM_' . $current_term . '_' . date('Y-m-d') . '.xls');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<!--[if gte mso 9]><xml>';
    echo '<x:ExcelWorkbook>';
    echo '<x:ExcelWorksheets>';
    echo '<x:ExcelWorksheet>';
    echo '<x:Name>Marksheet</x:Name>';
    echo '<x:WorksheetOptions>';
    echo '<x:PageSetup>';
    echo '<x:Layout x:Orientation="Landscape"/>'; // FORCE LANDSCAPE
    echo '<x:PageSize>9</x:PageSize>'; // 9 = A4
    echo '</x:PageSetup>';
    echo '<x:Print>';
    echo '<x:PaperSizeIndex>9</x:PaperSizeIndex>'; // 9 = A4
    echo '</x:Print>';
    echo '</x:WorksheetOptions>';
    echo '</x:ExcelWorksheet>';
    echo '</x:ExcelWorksheets>';
    echo '</x:ExcelWorkbook>';
    echo '</xml><![endif]-->';
    echo '<style>';
    echo '@page { size: A4 landscape; margin: 1cm; }';
    echo 'td { border: 1px solid #000; padding: 5px; text-align: center; }';
    echo 'th { background: #4a1a3a; color: #fff; padding: 8px; text-align: center; font-weight: bold; }';
    echo '.subject-header { background: #6a2b52; }';
    echo '.division-1 { background: #4CAF50; color: white; }';
    echo '.division-2 { background: #2196F3; color: white; }';
    echo '.division-3 { background: #FF9800; color: white; }';
    echo '.division-4 { background: #f44336; color: white; }';
    echo '.division-u { background: #9C27B0; color: white; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Title
    echo '<table border="1">';
    echo '<tr><th colspan="' . (count($all_subjects) * 2 + 6) . '" style="font-size: 16px; background: #4a1a3a;">';
    echo 'RAYS OF GRACE JUNIOR SCHOOL - P.5 PURPLE<br>';
    echo strtoupper($exam_type) . ' TERM ' . $current_term . ' MARKSHEET</th></tr>';
    
    // Main Header
    echo '<tr>';
    echo '<th rowspan="2">S/N</th>';
    echo '<th rowspan="2">LEARNER\'S NAME</th>';
    foreach ($all_subjects as $subject) {
        echo '<th colspan="2" class="subject-header">' . $subject_display[$subject] . '</th>';
    }
    echo '<th rowspan="2">TOTAL</th>';
    echo '<th rowspan="2">AVERAGE</th>';
    echo '<th rowspan="2">TOTAL AGG</th>';
    echo '<th rowspan="2">DIV</th>';
    echo '</tr>';
    
    // Sub-header
    echo '<tr>';
    foreach ($all_subjects as $subject) {
        echo '<th>Score</th><th>Agg</th>';
    }
    echo '</tr>';
    
    // Data rows
    foreach ($export_data as $row) {
        echo '<tr>';
        echo '<td>' . $row['sn'] . '</td>';
        echo '<td style="text-align: left;">' . $row['name'] . '</td>';
        
        foreach ($all_subjects as $subject) {
            echo '<td>' . $row[$subject . '_score'] . '</td>';
            echo '<td>' . $row[$subject . '_agg'] . '</td>';
        }
        
        echo '<td><strong>' . $row['total'] . '</strong></td>';
        echo '<td><strong>' . $row['average'] . '</strong></td>';
        echo '<td><strong>' . $row['total_agg'] . '</strong></td>';
        
        $div_class = '';
        if ($row['division'] == 'I') $div_class = 'division-1';
        elseif ($row['division'] == 'II') $div_class = 'division-2';
        elseif ($row['division'] == 'III') $div_class = 'division-3';
        elseif ($row['division'] == 'IV') $div_class = 'division-4';
        elseif ($row['division'] == 'U') $div_class = 'division-u';
        
        echo '<td class="' . $div_class . '"><strong>' . $row['division'] . '</strong></td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

// ============================================
// HANDLE PDF EXPORT - WITH SCHOOL LOGO (A4 LANDSCAPE)
// ============================================
if ($export_type == 'pdf') {
    // Check if FPDF library exists
    $fpdf_path = 'fpdf186/fpdf.php';
    
    if (!file_exists($fpdf_path)) {
        $message = "PDF library not found. Please download FPDF from http://www.fpdf.org/ and extract to 'fpdf186' folder.";
        $message_type = "warning";
    } else {
        require_once($fpdf_path);
        
        // Prepare export data
        $export_data = prepareExportData($students, $marks_data, $all_subjects, $core_subjects);
        
        // Create custom PDF class with logo
        class PDF extends FPDF {
            function Header() {
                // Logo path - adjust if your logo is in a different location
                $logo_path = 'images/school-logo.png';
                
                // Position logo on left side (x=10, y=8)
                if (file_exists($logo_path)) {
                    // Place logo - 30mm width, height auto-proportional
                    $this->Image($logo_path, 10, 8, 30);
                } else {
                    // If logo doesn't exist, show a placeholder box
                    $this->SetFillColor(200, 200, 200);
                    $this->Rect(10, 8, 30, 30, 'F');
                    $this->SetFont('Arial', 'B', 8);
                    $this->SetXY(12, 18);
                    $this->Cell(26, 10, 'LOGO', 0, 0, 'C');
                }
                
                // School name and details - positioned to the right of logo
                $this->SetXY(45, 12); // X=45 (after logo + margin), Y=12
                $this->SetFont('Arial', 'B', 16);
                $this->Cell(0, 8, 'RAYS OF GRACE JUNIOR SCHOOL', 0, 1, 'L');
                
                $this->SetX(45);
                $this->SetFont('Arial', '', 10);
                $this->Cell(0, 5, 'P.O. Box XXX, Kampala (U) | Tel: +256 XXX XXX XXX', 0, 1, 'L');
                
                $this->SetX(45);
                $this->SetFont('Arial', 'I', 9);
                $this->Cell(0, 5, 'Email: info@raysofgrace.ac.ug | Website: www.raysofgrace.ac.ug', 0, 1, 'L');
                
                // Line separator
                $this->Ln(8);
                $this->SetDrawColor(74, 26, 58); // Purple line
                $this->SetLineWidth(0.5);
                $this->Line(10, 45, 287, 45); // Line across the page
                
                // Title
                $this->Ln(8);
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(0, 6, 'P.5 PURPLE - ' . strtoupper($GLOBALS['exam_type']) . ' TERM ' . $GLOBALS['current_term'] . ' MARKSHEET', 0, 1, 'C');
                $this->SetFont('Arial', 'I', 9);
                $this->Cell(0, 5, 'Generated on: ' . date('F j, Y'), 0, 1, 'C');
                $this->Ln(5);
            }
            
            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 7);
                $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
                
                // Add school motto at bottom
                $this->SetY(-10);
                $this->SetFont('Arial', 'I', 8);
                $this->SetTextColor(74, 26, 58); // Purple
                $this->Cell(0, 4, '"Purple Hearts, Bright Minds"', 0, 0, 'C');
            }
            
            function MarksheetTable($data, $all_subjects, $subject_display) {
                // Calculate page width for A4 Landscape (297mm)
                $pageWidth = 277; // Leave margins
                
                // Calculate column widths - OPTIMIZED FOR A4 LANDSCAPE
                $snW = 12;      // S/N
                $nameW = 50;     // Student Name
                $scoreW = 15;    // Score
                $aggW = 15;      // Aggregate
                $totalW = 18;    // Total, Average, Total Agg, Division
                
                // Calculate total width to ensure it fits
                $totalWidth = $snW + $nameW + (count($all_subjects) * ($scoreW + $aggW)) + ($totalW * 4);
                
                // If too wide, scale down proportionally
                if ($totalWidth > $pageWidth) {
                    $scale = $pageWidth / $totalWidth;
                    $snW = round($snW * $scale);
                    $nameW = round($nameW * $scale);
                    $scoreW = round($scoreW * $scale);
                    $aggW = round($aggW * $scale);
                    $totalW = round($totalW * $scale);
                }
                
                // Colors
                $this->SetFillColor(74, 26, 58); // Dark purple
                $this->SetTextColor(255);
                $this->SetDrawColor(0, 0, 0);
                $this->SetLineWidth(0.2);
                $this->SetFont('Arial', 'B', 7);
                
                // Main header
                $this->Cell($snW, 10, 'S/N', 1, 0, 'C', true);
                $this->Cell($nameW, 10, 'NAME', 1, 0, 'C', true);
                
                foreach ($all_subjects as $subject) {
                    $this->Cell($scoreW + $aggW, 10, $subject_display[$subject], 1, 0, 'C', true);
                }
                
                $this->Cell($totalW, 10, 'TOT', 1, 0, 'C', true);
                $this->Cell($totalW, 10, 'AVG', 1, 0, 'C', true);
                $this->Cell($totalW, 10, 'T-AGG', 1, 0, 'C', true);
                $this->Cell($totalW, 10, 'DIV', 1, 1, 'C', true);
                
                // Sub-header
                $this->SetFillColor(106, 43, 82); // Lighter purple
                $this->SetFont('Arial', 'B', 6);
                $this->Cell($snW, 8, '', 1, 0, 'C', true);
                $this->Cell($nameW, 8, '', 1, 0, 'C', true);
                
                foreach ($all_subjects as $subject) {
                    $this->Cell($scoreW, 8, 'Sc', 1, 0, 'C', true);
                    $this->Cell($aggW, 8, 'Agg', 1, 0, 'C', true);
                }
                
                $this->Cell($totalW, 8, '', 1, 0, 'C', true);
                $this->Cell($totalW, 8, '', 1, 0, 'C', true);
                $this->Cell($totalW, 8, '', 1, 0, 'C', true);
                $this->Cell($totalW, 8, '', 1, 1, 'C', true);
                
                // Data rows
                $this->SetFillColor(248, 248, 248);
                $this->SetTextColor(0);
                $this->SetFont('Arial', '', 6);
                $fill = false;
                
                foreach ($data as $row) {
                    // Check if we need a new page
                    if ($this->GetY() > 170) {
                        $this->AddPage();
                        // Reprint headers on new page
                        $this->SetFillColor(74, 26, 58);
                        $this->SetTextColor(255);
                        $this->SetFont('Arial', 'B', 7);
                        
                        $this->Cell($snW, 10, 'S/N', 1, 0, 'C', true);
                        $this->Cell($nameW, 10, 'NAME', 1, 0, 'C', true);
                        foreach ($all_subjects as $subject) {
                            $this->Cell($scoreW + $aggW, 10, $subject_display[$subject], 1, 0, 'C', true);
                        }
                        $this->Cell($totalW, 10, 'TOT', 1, 0, 'C', true);
                        $this->Cell($totalW, 10, 'AVG', 1, 0, 'C', true);
                        $this->Cell($totalW, 10, 'T-AGG', 1, 0, 'C', true);
                        $this->Cell($totalW, 10, 'DIV', 1, 1, 'C', true);
                        
                        $this->SetFillColor(106, 43, 82);
                        $this->SetFont('Arial', 'B', 6);
                        $this->Cell($snW, 8, '', 1, 0, 'C', true);
                        $this->Cell($nameW, 8, '', 1, 0, 'C', true);
                        foreach ($all_subjects as $subject) {
                            $this->Cell($scoreW, 8, 'Sc', 1, 0, 'C', true);
                            $this->Cell($aggW, 8, 'Agg', 1, 0, 'C', true);
                        }
                        $this->Cell($totalW, 8, '', 1, 0, 'C', true);
                        $this->Cell($totalW, 8, '', 1, 0, 'C', true);
                        $this->Cell($totalW, 8, '', 1, 0, 'C', true);
                        $this->Cell($totalW, 8, '', 1, 1, 'C', true);
                        
                        $this->SetTextColor(0);
                        $this->SetFont('Arial', '', 6);
                    }
                    
                    $this->Cell($snW, 6, $row['sn'], 1, 0, 'C', $fill);
                    
                    // Truncate long names
                    $name = strlen($row['name']) > 22 ? substr($row['name'], 0, 20) . '..' : $row['name'];
                    $this->Cell($nameW, 6, $name, 1, 0, 'L', $fill);
                    
                    foreach ($all_subjects as $subject) {
                        $this->Cell($scoreW, 6, $row[$subject . '_score'], 1, 0, 'C', $fill);
                        
                        $agg = $row[$subject . '_agg'];
                        if ($agg) {
                            // Color code the aggregate
                            if ($agg <= 2) $this->SetFillColor(76, 175, 80); // Green
                            elseif ($agg <= 4) $this->SetFillColor(33, 150, 243); // Blue
                            elseif ($agg <= 6) $this->SetFillColor(255, 152, 0); // Orange
                            elseif ($agg <= 8) $this->SetFillColor(244, 67, 54); // Red
                            else $this->SetFillColor(156, 39, 176); // Purple
                            
                            $this->Cell($aggW, 6, $agg, 1, 0, 'C', true);
                            $this->SetFillColor(248, 248, 248); // Reset fill
                        } else {
                            $this->Cell($aggW, 6, '', 1, 0, 'C', $fill);
                        }
                    }
                    
                    $this->Cell($totalW, 6, $row['total'], 1, 0, 'C', $fill);
                    $this->Cell($totalW, 6, $row['average'], 1, 0, 'C', $fill);
                    $this->Cell($totalW, 6, $row['total_agg'], 1, 0, 'C', $fill);
                    
                    // Division with color
                    $div = $row['division'];
                    if ($div == 'I') $this->SetFillColor(76, 175, 80);
                    elseif ($div == 'II') $this->SetFillColor(33, 150, 243);
                    elseif ($div == 'III') $this->SetFillColor(255, 152, 0);
                    elseif ($div == 'IV') $this->SetFillColor(244, 67, 54);
                    elseif ($div == 'U') $this->SetFillColor(156, 39, 176);
                    else $this->SetFillColor(248, 248, 248);
                    
                    $this->Cell($totalW, 6, $div, 1, 1, 'C', true);
                    
                    $fill = !$fill;
                }
                
                // Add grading scale at the bottom
                $this->Ln(5);
                $this->SetFont('Arial', 'B', 7);
                $this->Cell(0, 5, 'Grading Scale:', 0, 1, 'L');
                $this->SetFont('Arial', '', 6);
                $this->Cell(0, 4, '1(90-100% D1), 2(80-89% D2), 3(70-79% C3), 4(60-69% C4), 5(50-59% C5), 6(45-49% C6), 7(40-44% P7), 8(35-39% P8), 9(0-34% F9)', 0, 1, 'L');
                $this->Cell(0, 4, 'Division (4 Core Subjects): I(4-12), II(13-24), III(25-29), IV(30-33), U(34-36)', 0, 1, 'L');
            }
        }
        
        // Create PDF in LANDSCAPE mode with A4 size and smaller margins
        $pdf = new PDF('L', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetMargins(10, 10, 10); // Smaller margins for more space
        $pdf->AddPage();
        $pdf->MarksheetTable($export_data, $all_subjects, $subject_display);
        
        // Output PDF
        $pdf->Output('D', str_replace(' ', '_', $exam_type) . '_TERM_' . $current_term . '_' . date('Y-m-d') . '.pdf');
        exit;
    }
}
// ============================================
// HANDLE DOC EXPORT - WITH SCHOOL LOGO (A4 LANDSCAPE)
// ============================================
if ($export_type == 'doc') {
    $export_data = prepareExportData($students, $marks_data, $all_subjects, $core_subjects);
    
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename=' . str_replace(' ', '_', $exam_type) . '_TERM_' . $current_term . '_' . date('Y-m-d') . '.doc');
    
    // Convert logo to base64 for embedding in HTML
    $logo_path = 'images/school-logo.png';
    $logo_html = '';
    
    if (file_exists($logo_path)) {
        $image_data = file_get_contents($logo_path);
        $base64 = base64_encode($image_data);
        $logo_html = '<img src="data:image/png;base64,' . $base64 . '" style="width: 100px; height: auto; float: left; margin-right: 15px;">';
    }
    
    echo '<html>';
    echo '<head>';
    echo '<style>';
    echo '@page { size: A4 landscape; margin: 1.5cm; }';
    echo 'body { font-family: Arial, sans-serif; }';
    echo '.header { overflow: hidden; margin-bottom: 20px; border-bottom: 3px solid #4a1a3a; padding-bottom: 10px; }';
    echo '.logo { float: left; width: 100px; margin-right: 15px; }';
    echo '.school-info { float: left; }';
    echo '.school-name { color: #4a1a3a; font-size: 24px; font-weight: bold; margin: 0; }';
    echo '.school-details { color: #666; font-size: 12px; margin: 2px 0; }';
    echo '.motto { color: #4a1a3a; font-style: italic; text-align: center; margin-top: 10px; font-size: 11px; }';
    echo 'h2 { color: #4a1a3a; text-align: center; font-size: 18pt; clear: both; }';
    echo 'table { border-collapse: collapse; width: 100%; font-size: 10pt; clear: both; }';
    echo 'th { background: #4a1a3a; color: white; padding: 8px; text-align: center; font-weight: bold; }';
    echo 'td { border: 1px solid #000; padding: 5px; text-align: center; }';
    echo '.subject-header { background: #6a2b52; color: white; }';
    echo '.division-1 { background: #4CAF50; color: white; }';
    echo '.division-2 { background: #2196F3; color: white; }';
    echo '.division-3 { background: #FF9800; color: white; }';
    echo '.division-4 { background: #f44336; color: white; }';
    echo '.division-u { background: #9C27B0; color: white; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Header with logo
    echo '<div class="header">';
    echo $logo_html;
    echo '<div class="school-info">';
    echo '<p class="school-name">RAYS OF GRACE JUNIOR SCHOOL</p>';
    echo '<p class="school-details">P.O. Box XXX, Kampala (U) | Tel: +256 XXX XXX XXX</p>';
    echo '<p class="school-details">Email: info@raysofgrace.ac.ug | Website: www.raysofgrace.ac.ug</p>';
    echo '</div>';
    echo '</div>';
    
    echo '<h2>P.5 PURPLE - ' . strtoupper($exam_type) . ' TERM ' . $current_term . ' MARKSHEET</h2>';
    echo '<p style="text-align: right; font-style: italic;">Generated on: ' . date('F j, Y') . '</p>';
    
    echo '<table border="1">';
    
    // Header
    echo '<tr>';
    echo '<th rowspan="2">S/N</th>';
    echo '<th rowspan="2">LEARNER\'S NAME</th>';
    foreach ($all_subjects as $subject) {
        echo '<th colspan="2" class="subject-header">' . $subject_display[$subject] . '</th>';
    }
    echo '<th rowspan="2">TOTAL</th>';
    echo '<th rowspan="2">AVG</th>';
    echo '<th rowspan="2">TOT AGG</th>';
    echo '<th rowspan="2">DIV</th>';
    echo '</tr>';
    
    echo '<tr>';
    foreach ($all_subjects as $subject) {
        echo '<th>Score</th><th>Agg</th>';
    }
    echo '</tr>';
    
    // Data
    foreach ($export_data as $row) {
        echo '<tr>';
        echo '<td>' . $row['sn'] . '</td>';
        echo '<td style="text-align: left;">' . $row['name'] . '</td>';
        
        foreach ($all_subjects as $subject) {
            echo '<td>' . $row[$subject . '_score'] . '</td>';
            echo '<td>' . $row[$subject . '_agg'] . '</td>';
        }
        
        echo '<td><strong>' . $row['total'] . '</strong></td>';
        echo '<td><strong>' . $row['average'] . '</strong></td>';
        echo '<td><strong>' . $row['total_agg'] . '</strong></td>';
        
        $div_class = '';
        if ($row['division'] == 'I') $div_class = 'division-1';
        elseif ($row['division'] == 'II') $div_class = 'division-2';
        elseif ($row['division'] == 'III') $div_class = 'division-3';
        elseif ($row['division'] == 'IV') $div_class = 'division-4';
        elseif ($row['division'] == 'U') $div_class = 'division-u';
        
        echo '<td class="' . $div_class . '"><strong>' . $row['division'] . '</strong></td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Add grading scale footer
    echo '<div style="margin-top: 20px; font-size: 8pt;">';
    echo '<p><strong>Grading Scale:</strong> 1(90-100% D1), 2(80-89% D2), 3(70-79% C3), 4(60-69% C4), 5(50-59% C5), 6(45-49% C6), 7(40-44% P7), 8(35-39% P8), 9(0-34% F9)</p>';
    echo '<p><strong>Division (4 Core Subjects):</strong> I(4-12), II(13-24), III(25-29), IV(30-33), U(34-36)</p>';
    echo '<p class="motto">"Purple Hearts, Bright Minds"</p>';
    echo '</div>';
    
    echo '</body></html>';
    exit;
}
// Helper functions
if (!function_exists('getAggregate')) {
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
}

if (!function_exists('getGrade')) {
    function getGrade($score) {
        if ($score >= 90) return 'D1';
        if ($score >= 80) return 'D2';
        if ($score >= 70) return 'C3';
        if ($score >= 60) return 'C4';
        if ($score >= 50) return 'C5';
        if ($score >= 45) return 'C6';
        if ($score >= 40) return 'P7';
        if ($score >= 35) return 'P8';
        return 'F9';
    }
}

function getDivisionFromAgg($total_agg) {
    if ($total_agg >= 4 && $total_agg <= 12) return 'I';
    if ($total_agg >= 13 && $total_agg <= 24) return 'II';
    if ($total_agg >= 25 && $total_agg <= 29) return 'III';
    if ($total_agg >= 30 && $total_agg <= 33) return 'IV';
    if ($total_agg >= 34 && $total_agg <= 36) return 'U';
    return '-';
}

function getGradeClass($grade) {
    $classes = [
        'D1' => 'grade-d1', 'D2' => 'grade-d2',
        'C3' => 'grade-c3', 'C4' => 'grade-c4',
        'C5' => 'grade-c5', 'C6' => 'grade-c6',
        'P7' => 'grade-p7', 'P8' => 'grade-p8',
        'F9' => 'grade-f9'
    ];
    return $classes[$grade] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marksheet - P.5 Purple</title>
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
            padding: 20px;
        }

        .premium-container {
            max-width: 1600px;
            margin: 0 auto;
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
        }

        .btn-premium:hover {
            background: rgba(239, 91, 43, 0.3);
            border-color: var(--orange);
            transform: translateY(-2px);
        }

        .btn-premium i {
            color: var(--orange);
        }

        .btn-orange {
            background: var(--orange);
            border: none;
        }

        .btn-orange:hover {
            background: var(--orange-dark);
        }

        /* Term Tabs */
        .term-tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .term-tab {
            padding: 12px 25px;
            background: white;
            border: 2px solid var(--gray-300);
            border-radius: 12px;
            font-weight: 600;
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition);
        }

        .term-tab:hover {
            border-color: var(--orange);
            color: var(--orange);
        }

        .term-tab.active {
            background: var(--purple);
            border-color: var(--purple);
            color: white;
        }

        /* Exam Type Tabs */
        .exam-tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0 30px;
            flex-wrap: wrap;
        }

        .exam-tab {
            padding: 15px 30px;
            background: white;
            border: 2px solid var(--gray-300);
            border-radius: 50px;
            font-weight: 600;
            color: var(--gray-700);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .exam-tab i {
            color: var(--orange);
        }

        .exam-tab:hover {
            border-color: var(--orange);
            transform: translateY(-2px);
        }

        .exam-tab.active {
            background: var(--purple);
            border-color: var(--purple);
            color: white;
        }

        .exam-tab.active i {
            color: white;
        }

        /* Export Dropdown */
        .export-dropdown {
            position: relative;
            display: inline-block;
        }

        .export-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            min-width: 200px;
            box-shadow: var(--shadow-lg);
            border-radius: 12px;
            z-index: 100;
            border: 1px solid var(--gray-300);
            overflow: hidden;
        }

        .export-dropdown:hover .export-menu {
            display: block;
        }

        .export-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition);
            border-bottom: 1px solid var(--gray-200);
        }

        .export-menu a:last-child {
            border-bottom: none;
        }

        .export-menu a:hover {
            background: var(--orange);
            color: white;
        }

        .export-menu a:hover i {
            color: white;
        }

        .export-menu i {
            width: 20px;
            color: var(--orange);
        }

        /* Marksheet Container */
        .marksheet-container {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            padding: 25px;
            margin: 20px 0 40px;
            overflow-x: auto;
        }

        .marksheet-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .marksheet-title h2 {
            color: var(--purple-dark);
            font-size: 1.8rem;
            font-weight: 700;
        }

        .marksheet-title h2 span {
            color: var(--orange);
            font-size: 1.2rem;
            margin-left: 10px;
        }

        .marksheet-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Marksheet Table */
        .marksheet-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1400px;
            font-size: 0.9rem;
        }

        .marksheet-table th {
            background: var(--purple);
            color: white;
            padding: 15px 8px;
            font-weight: 600;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .marksheet-table td {
            border: 1px solid var(--gray-300);
            padding: 10px 5px;
            text-align: center;
            vertical-align: middle;
        }

        .marksheet-table tr:hover td {
            background: var(--gray-50);
        }

        .student-name-cell {
            font-weight: 600;
            color: var(--purple-dark);
            text-align: left !important;
            padding-left: 10px !important;
        }

        .score-input {
            width: 60px;
            padding: 6px;
            border: 2px solid var(--gray-300);
            border-radius: 6px;
            text-align: center;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .score-input:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(239,91,43,0.1);
        }

        .initials-input {
            width: 50px;
            padding: 6px;
            border: 2px solid var(--gray-300);
            border-radius: 6px;
            text-align: center;
            font-size: 0.8rem;
        }

        .agg-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 0.9rem;
            margin: 0 auto;
        }

        .agg-1, .agg-2 { background: #4CAF50; color: white; }
        .agg-3, .agg-4 { background: #2196F3; color: white; }
        .agg-5, .agg-6 { background: #FF9800; color: white; }
        .agg-7, .agg-8 { background: #f44336; color: white; }
        .agg-9 { background: #9C27B0; color: white; }

        .division-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
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

        /* Grading Legend */
        .grading-legend {
            background: var(--gray-50);
            border-radius: 16px;
            padding: 20px;
            margin: 30px 0;
            border: 1px solid var(--gray-300);
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            background: white;
            border-radius: 8px;
        }

        .grade-sample {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .premium-header {
                padding: 20px;
            }
            
            .class-title h1 {
                font-size: 2rem;
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
                    <h1><i class="fas fa-table"></i>Marksheet P.5 Purple</h1>
                    <div class="class-slogan">P.5 Purple • <?php echo ACADEMIC_YEAR; ?></div>
                </div>
                <div class="class-badge">
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

        <!-- Term Selector -->
        <div class="term-tabs">
            <a href="?term=1&exam_type=<?php echo urlencode($exam_type); ?>" 
               class="term-tab <?php echo $current_term == '1' ? 'active' : ''; ?>">Term 1</a>
            <a href="?term=2&exam_type=<?php echo urlencode($exam_type); ?>" 
               class="term-tab <?php echo $current_term == '2' ? 'active' : ''; ?>">Term 2</a>
            <a href="?term=3&exam_type=<?php echo urlencode($exam_type); ?>" 
               class="term-tab <?php echo $current_term == '3' ? 'active' : ''; ?>">Term 3</a>
        </div>

        <!-- Exam Type Tabs -->
        <div class="exam-tabs">
            <a href="?term=<?php echo $current_term; ?>&exam_type=Beginning" 
               class="exam-tab <?php echo $exam_type == 'Beginning' ? 'active' : ''; ?>">
                <i class="fas fa-play"></i> Beginning of Term (BOT)
            </a>
            <a href="?term=<?php echo $current_term; ?>&exam_type=Mid-term" 
               class="exam-tab <?php echo $exam_type == 'Mid-term' ? 'active' : ''; ?>">
                <i class="fas fa-pause"></i> Mid-Term (MID)
            </a>
            <a href="?term=<?php echo $current_term; ?>&exam_type=End of Term" 
               class="exam-tab <?php echo $exam_type == 'End of Term' ? 'active' : ''; ?>">
                <i class="fas fa-flag-checkered"></i> End of Term (END)
            </a>
        </div>
 <!-- Add this line -->
    <a href="report-selector.php?term=<?php echo $current_term; ?>" class="btn-premium" style="background: #4a1a3a;">
        <i class="fas fa-file-alt"></i> Report Cards
    </a>
</div>  
        <!-- Marksheet -->
        <div class="marksheet-container">
            <div class="marksheet-title">
                <h2>
                    <?php echo $exam_type; ?> TERM <?php echo $current_term; ?> MARKSHEET
                    <span>(<?php echo date('F Y'); ?>)</span>
                </h2>
                <div class="marksheet-controls">
                    <div class="export-dropdown">
                        <button class="btn-premium btn-orange">
                            <i class="fas fa-download"></i> Export <i class="fas fa-caret-down"></i>
                        </button>
                        
                        <div class="export-menu">
                            <a href="?term=<?php echo $current_term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=csv">
                                <i class="fas fa-file-csv"></i> Export as CSV
                            </a>
                            <a href="?term=<?php echo $current_term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=excel">
                                <i class="fas fa-file-excel"></i> Export as Excel (XLS)
                            </a>
                            <a href="?term=<?php echo $current_term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=pdf">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </a>
                            <a href="?term=<?php echo $current_term; ?>&exam_type=<?php echo urlencode($exam_type); ?>&export=doc">
                                <i class="fas fa-file-word"></i> Export as Word (DOC)
                            </a>
                            
                        </div>
                    </div>
                </div>
            </div>
            

            <form method="POST">
                <input type="hidden" name="exam_date" value="<?php echo date('Y-m-d'); ?>">
                
                <table class="marksheet-table">
                    <thead>
                        <tr>
                            <th rowspan="2">S/N</th>
                            <th rowspan="2">LEARNER'S NAME</th>
                            <th colspan="2">MATH</th>
                            <th colspan="2">ENG</th>
                            <th colspan="2">SCI</th>
                            <th colspan="2">SST</th>
                            <th colspan="2">R.E</th>
                            <th colspan="2">KISW</th>
                            <th rowspan="2">TOTAL</th>
                            <th rowspan="2">AVERAGE</th>
                            <th rowspan="2">TOTAL AGG</th>
                            <th rowspan="2">DIV</th>
                        </tr>
                        <tr>
                            <th>Score</th><th>Agg</th>
                            <th>Score</th><th>Agg</th>
                            <th>Score</th><th>Agg</th>
                            <th>Score</th><th>Agg</th>
                            <th>Score</th><th>Agg</th>
                            <th>Score</th><th>Agg</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php 
    $sn = 1;
    foreach ($students as $student): 
        $student_id = $student['id'];
        $total_score = 0;
        $subject_count = 0;
        $core_agg_total = 0;
        
        // Calculate totals first
        foreach ($all_subjects as $subject) {
            $score = isset($marks_data[$student_id]['scores'][$subject]) ? $marks_data[$student_id]['scores'][$subject] : '';
            if ($score !== '' && is_numeric($score)) {
                $total_score += $score;
                $subject_count++;
                $agg = getAggregate($score);
                if (in_array($subject, $core_subjects)) {
                    $core_agg_total += $agg;
                }
            }
        }
        $average = $subject_count > 0 ? round($total_score / $subject_count, 1) : '';
        $division = getDivisionFromAgg($core_agg_total);
        $division_class = $division == 'I' ? 'division-1' : 
                         ($division == 'II' ? 'division-2' : 
                         ($division == 'III' ? 'division-3' : 
                         ($division == 'IV' ? 'division-4' : 
                         ($division == 'U' ? 'division-u' : ''))));
    ?>
    <tr>
        <td><?php echo $sn++; ?></td>
        <td class="student-name-cell">
            <?php echo htmlspecialchars($student['full_name']); ?>
            <input type="hidden" name="student_id[]" value="<?php echo $student_id; ?>">
        </td>
        
        <?php foreach ($all_subjects as $subject): 
            $score = isset($marks_data[$student_id]['scores'][$subject]) ? $marks_data[$student_id]['scores'][$subject] : '';
            $initials = isset($marks_data[$student_id]['initials'][$subject]) ? $marks_data[$student_id]['initials'][$subject] : '';
            
            // Calculate aggregate if score exists
            $agg = '';
            if ($score !== '' && is_numeric($score)) {
                $agg = getAggregate($score);
            }
        ?>
        <td>
            <input type="number" name="score_<?php echo $student_id; ?>_<?php echo $subject; ?>" 
                   class="score-input" min="0" max="100" step="0.5" 
                   value="<?php echo $score; ?>"
                   onchange="calculateRow(this)"
                   data-student="<?php echo $student_id; ?>"
                   data-subject="<?php echo $subject; ?>">
        </td>
        <td class="agg-cell-<?php echo $student_id . '_' . $subject; ?>">
            <?php if ($agg): ?>
                <span class="agg-badge agg-<?php echo $agg; ?>"><?php echo $agg; ?></span>
            <?php endif; ?>
            <input type="hidden" name="initials_<?php echo $student_id; ?>_<?php echo $subject; ?>" 
                   class="initials-input" value="<?php echo $initials; ?>">
        </td>
        <?php endforeach; ?>
        
        <!-- TOTAL, AVERAGE, TOTAL AGG, DIVISION columns -->
        <td class="total-cell total-<?php echo $student_id; ?>"><?php echo $total_score ?: '-'; ?></td>
        <td class="avg-cell avg-<?php echo $student_id; ?>"><?php echo $average ?: '-'; ?></td>
        <td class="total-agg-cell total-agg-<?php echo $student_id; ?>"><?php echo $core_agg_total ?: '-'; ?></td>
        <td class="div-cell-<?php echo $student_id; ?>">
            <?php if ($division && $division != '-'): ?>
                <span class="division-badge <?php echo $division_class; ?>"><?php echo $division; ?></span>
            <?php else: ?>
                <span class="division-badge">-</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
                </table>

                <div style="display: flex; gap: 15px; margin-top: 30px; justify-content: flex-end;">
                    <button type="submit" name="save_marksheet" class="btn-premium" style="background: var(--purple);">
                        <i class="fas fa-save"></i> Save All Marks
                    </button>
                </div>
            </form>
        </div>

        <!-- Grading Legend -->
        <div class="grading-legend">
            <h3 style="color: var(--purple-dark); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-info-circle" style="color: var(--orange);"></i>
                Grading Scale & Division Calculation
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div>
                    <h4 style="color: var(--purple); margin-bottom: 10px;">Subject Grading</h4>
                    <div class="legend-grid">
                        <div class="legend-item"><span class="grade-sample" style="background: #4CAF50;">1</span> 90-100% (D1)</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #4CAF50;">2</span> 80-89% (D2)</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #2196F3;">3</span> 70-79% (C3)</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #2196F3;">4</span> 60-69% (C4)</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #FF9800;">5</span> 50-59% (C5)</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #FF9800;">6</span> 45-49% (C6)</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #f44336;">7</span> 40-44% (P7)</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #f44336;">8</span> 35-39% (P8)</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #9C27B0;">9</span> 0-34% (F9)</div>
                    </div>
                </div>
                <div>
                    <h4 style="color: var(--purple); margin-bottom: 10px;">Division (4 Core Subjects: Math, Eng, Sci, SST)</h4>
                    <div class="legend-grid">
                        <div class="legend-item"><span class="grade-sample" style="background: #4CAF50;">I</span> Total Agg 4-12</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #2196F3;">II</span> Total Agg 13-24</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #FF9800;">III</span> Total Agg 25-29</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #f44336;">IV</span> Total Agg 30-33</div>
                        <div class="legend-item"><span class="grade-sample" style="background: #9C27B0;">U</span> Total Agg 34-36</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
 function calculateRow(input) {
    const row = input.closest('tr');
    const studentId = input.getAttribute('data-student');
    
    // Get all score inputs in this row
    const scoreInputs = row.querySelectorAll('input[type="number"]');
    
    let totalScore = 0;
    let subjectCount = 0;
    let coreAggTotal = 0;
    
    // Core subjects are first 4 (Math, Eng, Sci, SST)
    const coreSubjects = [0, 1, 2, 3];
    
    scoreInputs.forEach((input, index) => {
        const score = parseFloat(input.value);
        
        // Find the agg cell for this subject (next cell)
        const currentCell = input.closest('td');
        const aggCell = currentCell.nextElementSibling;
        
        if (!isNaN(score) && score >= 0 && score <= 100) {
            // Calculate aggregate
            let agg = 9;
            if (score >= 90) agg = 1;
            else if (score >= 80) agg = 2;
            else if (score >= 70) agg = 3;
            else if (score >= 60) agg = 4;
            else if (score >= 50) agg = 5;
            else if (score >= 45) agg = 6;
            else if (score >= 40) agg = 7;
            else if (score >= 35) agg = 8;
            
            totalScore += score;
            subjectCount++;
            
            // Update aggregate display
            if (aggCell) {
                // Keep the hidden input that's already there
                const hiddenInput = aggCell.querySelector('input[type="hidden"]');
                aggCell.innerHTML = `<span class="agg-badge agg-${agg}">${agg}</span>`;
                if (hiddenInput) {
                    aggCell.appendChild(hiddenInput);
                }
            }
            
            // Add to core aggregate if it's a core subject
            if (coreSubjects.includes(index)) {
                coreAggTotal += agg;
            }
        } else {
            // Clear aggregate if no score
            if (aggCell) {
                // Keep the hidden input
                const hiddenInput = aggCell.querySelector('input[type="hidden"]');
                aggCell.innerHTML = '';
                if (hiddenInput) {
                    aggCell.appendChild(hiddenInput);
                }
            }
        }
    });
    
    // Calculate average
    const average = subjectCount > 0 ? (totalScore / subjectCount).toFixed(1) : '';
    
    // Update total, average, and total aggregate cells
    const totalCell = row.querySelector('.total-' + studentId);
    const avgCell = row.querySelector('.avg-' + studentId);
    const totalAggCell = row.querySelector('.total-agg-' + studentId);
    const divCell = row.querySelector('.div-cell-' + studentId);
    
    if (totalCell) totalCell.textContent = totalScore || '';
    if (avgCell) avgCell.textContent = average;
    if (totalAggCell) totalAggCell.textContent = coreAggTotal || '';
    
    // Calculate division
    let division = '-';
    let divisionClass = '';
    if (coreAggTotal >= 4 && coreAggTotal <= 12) {
        division = 'I';
        divisionClass = 'division-1';
    } else if (coreAggTotal >= 13 && coreAggTotal <= 24) {
        division = 'II';
        divisionClass = 'division-2';
    } else if (coreAggTotal >= 25 && coreAggTotal <= 29) {
        division = 'III';
        divisionClass = 'division-3';
    } else if (coreAggTotal >= 30 && coreAggTotal <= 33) {
        division = 'IV';
        divisionClass = 'division-4';
    } else if (coreAggTotal >= 34 && coreAggTotal <= 36) {
        division = 'U';
        divisionClass = 'division-u';
    }
    
    // Update division cell
    if (divCell) {
        if (division !== '-') {
            divCell.innerHTML = `<span class="division-badge ${divisionClass}">${division}</span>`;
        } else {
            divCell.innerHTML = '';
        }
    }
}
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
    <script>
// Debug: Check form input names when saving
document.querySelector('form').addEventListener('submit', function(e) {
    console.log('=== FORM SUBMIT DEBUG ===');
    const formData = new FormData(this);
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    console.log('=== END DEBUG ===');
});
</script>
<!-- // Add this at the bottom of your page, before the closing </body> tag -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== FORM FIELDS CHECK ===');
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input[type="number"]');
    inputs.forEach(input => {
        console.log(input.name + ' = ' + input.value);
    });
    console.log('=== END CHECK ===');
});
</script>
</body>
</html> 