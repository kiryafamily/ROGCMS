<?php
// bulk-reports.php - Generate All Report Cards in One PDF
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$term = $_GET['term'] ?? CURRENT_TERM;
$exam_type = $_GET['type'] ?? 'MID';

// Map URL exam types to database exam types
$exam_type_map = [
    'BOT' => 'Beginning',
    'MID' => 'Mid-term',
    'END' => 'End of Term'
];

// Get the database exam type
$db_exam_type = $exam_type_map[$exam_type];

// Term in Roman numerals
$term_roman = ['1' => 'I', '2' => 'II', '3' => 'III'];

// Get all active students
$students = $pdo->query("SELECT * FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();

// Check if FPDF library exists
$fpdf_path = 'fpdf186/fpdf.php';
if (!file_exists($fpdf_path)) {
    die("PDF library not found. Please install FPDF in 'fpdf186' folder.");
}

require_once($fpdf_path);

// Core subjects
$core_subjects = ['Mathematics', 'English', 'Integrated Science', 'Social Studies'];
$all_subjects = ['Mathematics', 'English', 'Integrated Science', 'Social Studies', 'Religious Education', 'Kiswahili'];

// Subject display names (short)
$subject_short = [
    'Mathematics' => 'MTC',
    'English' => 'ENG',
    'Integrated Science' => 'SCI',
    'Social Studies' => 'SST',
    'Religious Education' => 'RE',
    'Kiswahili' => 'KSW'
];

// Teacher initials mapping
$teacher_initials = [
    'Mathematics' => 'GK',
    'English' => 'OJP',
    'Integrated Science' => 'CG',
    'Social Studies' => 'CS',
    'Religious Education' => 'KF',
    'Kiswahili' => 'KT'
];

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
    if ($total_agg >= 4 && $total_agg <= 12) return '1';
    if ($total_agg >= 13 && $total_agg <= 24) return '2';
    if ($total_agg >= 25 && $total_agg <= 29) return '3';
    if ($total_agg >= 30 && $total_agg <= 33) return '4';
    if ($total_agg >= 34 && $total_agg <= 36) return 'U';
    return '-';
}

function autoComment($score, $subject, $student_name) {
    if ($score >= 80) return "Excellent performance in $subject. Keep it up!";
    if ($score >= 70) return "Very good in $subject. Aim higher!";
    if ($score >= 60) return "Good work in $subject. Can do better.";
    if ($score >= 50) return "Fair in $subject. Needs more effort.";
    if ($score >= 40) return "Satisfactory in $subject. Work harder.";
    return "Poor performance in $subject. Needs serious attention.";
}

function getTeacherComment($student_name, $division, $exam_type) {
    $comments = [
        '1' => [
            'BOT' => "Excellent beginning of term! Keep up the outstanding work.",
            'MID' => "Outstanding performance! You're on track for a great end of term.",
            'END' => "Excellent performance throughout the term! Maintain this high standard."
        ],
        '2' => [
            'BOT' => "Very good start to the term. Aim even higher!",
            'MID' => "Very good progress. Keep working hard for better results.",
            'END' => "Very good performance this term. Aim for Division 1 next term."
        ],
        '3' => [
            'BOT' => "Good beginning. Put in more effort for better results.",
            'MID' => "Good performance. Work on your weak areas.",
            'END' => "Good effort this term. With more dedication, you can do better."
        ],
        '4' => [
            'BOT' => "Fair start. You need to work harder.",
            'MID' => "Fair performance. Seek help in subjects where you're struggling.",
            'END' => "Fair results. More effort needed next term."
        ],
        'U' => [
            'BOT' => "You need to put in more effort from the beginning.",
            'MID' => "Your performance needs improvement. Consult your teachers.",
            'END' => "Unsatisfactory performance. Please seek help and work harder."
        ]
    ];
    
    $div_key = $division === '-' ? 'U' : $division;
    return $comments[$div_key][$exam_type] ?? "Continue working hard to improve your grades.";
}

// Create PDF class matching the individual report design
class PDF extends FPDF {
    function Header() {
        // School name
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(74, 26, 58); // Purple
        $this->Cell(0, 10, 'RAYS OF GRACE JUNIOR SCHOOL', 0, 1, 'C');
        
        // School details
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 4, 'P.O. Box XXX, Kampala (U) | Tel: +256 XXX XXX XXX', 0, 1, 'C');
        $this->Cell(0, 4, 'Email: info@raysofgrace.ac.ug | Website: www.raysofgrace.ac.ug', 0, 1, 'C');
        
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    function ReportCard($student, $scores, $db_exam_type, $term, $term_roman, $subject_short, $all_subjects, $core_subjects, $teacher_initials) {
        // Add a new page for each student
        $this->AddPage();
        
        // Map database exam type to display type
        $display_type = '';
        if ($db_exam_type == 'Beginning') $display_type = 'BOT';
        elseif ($db_exam_type == 'Mid-term') $display_type = 'MID';
        elseif ($db_exam_type == 'End of Term') $display_type = 'END';
        
        $report_titles = [
            'BOT' => 'BEGINNING OF TERM',
            'MID' => 'MID TERM',
            'END' => 'END OF TERM'
        ];
        
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(74, 26, 58);
        $this->Cell(0, 8, $report_titles[$display_type] . ' REPORT CARD', 0, 1, 'C');
        $this->Ln(5);
        
        // Student Information in two columns
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(240, 240, 240);
        
        // First row
        $this->Cell(30, 8, 'PUPIL\'S NAME:', 0, 0, 'L', true);
        $this->SetFont('Arial', '', 10);
        $this->Cell(100, 8, strtoupper($student['full_name']), 0, 0, 'L');
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 8, 'YEAR:', 0, 0, 'L', true);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 8, ACADEMIC_YEAR, 0, 1, 'L');
        
        // Second row
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(30, 8, 'CLASS:', 0, 0, 'L', true);
        $this->SetFont('Arial', '', 10);
        $this->Cell(100, 8, 'P.5 PURPLE', 0, 0, 'L');
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 8, 'TERM:', 0, 0, 'L', true);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 8, $term_roman[$term], 0, 1, 'L');
        
        $this->Ln(5);
        
        // Results Table
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(74, 26, 58); // Purple header
        $this->SetTextColor(255);
        
        // Calculate column widths
        $colWidths = [35, 25, 20, 70, 20]; // SUBJECT, SCORE, AGG, REMARKS, INIT
        
        // Headers
        $this->Cell($colWidths[0], 8, 'SUBJECT', 1, 0, 'C', true);
        $this->Cell($colWidths[1], 8, 'SCORE', 1, 0, 'C', true);
        $this->Cell($colWidths[2], 8, 'AGG', 1, 0, 'C', true);
        $this->Cell($colWidths[3], 8, 'REMARKS', 1, 0, 'C', true);
        $this->Cell($colWidths[4], 8, 'INIT', 1, 1, 'C', true);
        
        // Reset colors for data rows
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 8);
        $fill = false;
        
        $total_score = 0;
        $total_agg = 0;
        $core_total_agg = 0;
        
        foreach ($all_subjects as $subject) {
            // Get scores using the database exam type key
            $score = isset($scores[$db_exam_type][$subject]['score']) ? $scores[$db_exam_type][$subject]['score'] : '';
            $agg = $score ? getAggregate($score) : '';
            $comment = isset($scores[$db_exam_type][$subject]['comment']) ? $scores[$db_exam_type][$subject]['comment'] : 
                      ($score ? autoComment($score, $subject, $student['full_name']) : '-');
            $initials = isset($scores[$db_exam_type][$subject]['initials']) ? $scores[$db_exam_type][$subject]['initials'] : $teacher_initials[$subject];
            
            if ($score) {
                $total_score += $score;
                $total_agg += $agg;
                if (in_array($subject, $core_subjects)) {
                    $core_total_agg += $agg;
                }
            }
            
            // Subject row
            $this->Cell($colWidths[0], 6, $subject_short[$subject], 1, 0, 'L', $fill);
            $this->Cell($colWidths[1], 6, $score ?: '-', 1, 0, 'C', $fill);
            $this->Cell($colWidths[2], 6, $agg ?: '-', 1, 0, 'C', $fill);
            $this->Cell($colWidths[3], 6, substr($comment, 0, 45), 1, 0, 'L', $fill);
            $this->Cell($colWidths[4], 6, $initials ?: '-', 1, 1, 'C', $fill);
            
            $fill = !$fill;
        }
        
        // Totals row
        $division = getDivision($core_total_agg);
        
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(220, 220, 220);
        $this->Cell($colWidths[0], 6, 'TOTAL', 1, 0, 'L', true);
        $this->Cell($colWidths[1], 6, $total_score ?: '-', 1, 0, 'C', true);
        $this->Cell($colWidths[2], 6, $total_agg ?: '-', 1, 0, 'C', true);
        $this->Cell($colWidths[3], 6, 'DIVISION: ' . $division, 1, 0, 'L', true);
        $this->Cell($colWidths[4], 6, '', 1, 1, 'C', true);
        
        $this->Ln(5);
        
        // Class Teacher Comment
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 6, 'Class Teacher Comment:', 0, 1, 'L');
        $this->SetFont('Arial', 'I', 8);
        $this->MultiCell(0, 5, getTeacherComment($student['full_name'], $division, $display_type));
        
        $this->Ln(8);
        
        // Signature lines
        $this->Cell(70, 6, '____________________', 0, 0, 'L');
        $this->Cell(50, 6, '', 0, 0);
        $this->Cell(70, 6, '____________________', 0, 1, 'R');
        
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(70, 4, 'Class Teacher', 0, 0, 'L');
        $this->Cell(50, 4, '', 0, 0);
        $this->Cell(70, 4, 'Head Teacher', 0, 1, 'R');
        
        // Paycode
        $this->Ln(3);
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 4, 'PAYCODE: 100' . rand(1000000, 9999999), 0, 1, 'R');
    }
}

// Create PDF
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 20);

$student_count = 0;
$students_with_data = 0;

// Generate report for each student
foreach ($students as $student) {
    // Get student's scores for ALL exam types - using database keys
    $scores = [
        'Beginning' => [],
        'Mid-term' => [],
        'End of Term' => []
    ];
    
    $stmt = $pdo->prepare("
        SELECT exam_type, subject, score, subject_teacher_initials, comments 
        FROM assessments 
        WHERE student_id = ? AND year = ? AND term = ?
    ");
    $stmt->execute([$student['id'], ACADEMIC_YEAR, $term]);
    $results = $stmt->fetchAll();
    
    foreach ($results as $row) {
        $scores[$row['exam_type']][$row['subject']] = [
            'score' => $row['score'],
            'initials' => $row['subject_teacher_initials'],
            'comment' => $row['comments']
        ];
    }
    
    // Check if student has at least one score for the selected exam type
    $has_data = false;
    if (isset($scores[$db_exam_type])) {
        foreach ($scores[$db_exam_type] as $subject => $data) {
            if (!empty($data['score'])) {
                $has_data = true;
                break;
            }
        }
    }
    
    if ($has_data) {
        $students_with_data++;
        $pdf->ReportCard($student, $scores, $db_exam_type, $term, $term_roman, $subject_short, $all_subjects, $core_subjects, $teacher_initials);
    }
    $student_count++;
}

// If no students have data, add a page with message
if ($students_with_data == 0) {
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 20, 'No assessment data found for Term ' . $term . ' ' . $exam_type, 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Please enter marks in the marksheet first.', 0, 1, 'C');
}

// Output PDF
$filename = 'ALL_REPORTS_' . $exam_type . '_TERM_' . $term . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
exit;       