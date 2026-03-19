<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Define all roll call sessions (must match the keys used in attendance.php)
$sessions = [
    'morning_prep'       => 'Morning Prep (7:20 AM)',
    'after_break'        => 'After Break (11:00 AM)',
    'after_lunch'        => 'After Lunch (1:40 PM)',
    'day_departure'      => 'Day Scholar Departure (4:00 PM)',
    'boarding_departure' => 'Boarding Departure (4:20 PM)',
    'evening_prep'       => 'Evening Prep (7:00 PM)'
];

// Get term start and end dates from config (if defined)
$term_start = defined('TERM_START_DATE') ? TERM_START_DATE : date('Y-m-01');
$term_end = defined('TERM_END_DATE') ? TERM_END_DATE : date('Y-m-t');

// Get filter values from GET
$from_date = $_GET['from'] ?? date('Y-m-d');
$to_date = $_GET['to'] ?? date('Y-m-d');
$period = $_GET['period'] ?? 'custom';
$selected_session = $_GET['session'] ?? 'all';

// Presets
if ($period == 'today') {
    $from_date = $to_date = date('Y-m-d');
} elseif ($period == 'week') {
    $from_date = date('Y-m-d', strtotime('monday this week'));
    $to_date = date('Y-m-d', strtotime('sunday this week'));
} elseif ($period == 'month') {
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-t');
} elseif ($period == 'term') {
    $from_date = $term_start;
    $to_date = $term_end;
}

// Build SQL query with optional session filter
$sql = "SELECT ar.*, s.full_name, s.admission_number, s.student_type, s.dormitory_number
        FROM attendance_records ar
        JOIN students s ON ar.student_id = s.id
        WHERE ar.date BETWEEN ? AND ?";
$params = [$from_date, $to_date];

if ($selected_session !== 'all') {
    $sql .= " AND ar.session = ?";
    $params[] = $selected_session;
}

$sql .= " ORDER BY ar.date DESC, ar.session, s.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// For CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    $filename = 'attendance_' . ($selected_session !== 'all' ? $selected_session . '_' : '') . $from_date . '_to_' . $to_date . '.csv';
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Session', 'Student Name', 'Admission No', 'Type', 'Status', 'Time', 'Notes']);
    
    foreach ($records as $r) {
        fputcsv($output, [
            $r['date'],
            $sessions[$r['session']] ?? $r['session'],
            $r['full_name'],
            $r['admission_number'],
            $r['student_type'],
            $r['status'],
            $r['time'],
            $r['notes']
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- html2canvas & jsPDF for PDF/PNG export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        .report-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1 1 200px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
        }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--gray-300);
            border-radius: 8px;
            font-family: inherit;
        }
        .btn-filter {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-filter:hover {
            background: var(--primary-dark);
        }
        .export-buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-export {
            background: var(--accent);
            color: var(--primary-dark);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .btn-export:hover {
            background: var(--accent-dark);
        }
        .btn-pdf {
            background: #4B1C3C;
            color: white;
        }
        .btn-png {
            background: #6A2B52;
            color: white;
        }
        .btn-print {
            background: var(--gray-600);
            color: white;
        }
        .btn-print:hover {
            background: var(--gray-800);
        }
        .summary-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--accent);
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        .stat-label {
            color: var(--gray-600);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        th {
            background: var(--primary);
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--gray-200);
        }
        tr:hover {
            background: var(--gray-100);
        }
        .session-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: var(--accent-light);
            color: var(--primary-dark);
        }
        .status-present { color: #27ae60; font-weight: 600; }
        .status-absent { color: #e74c3c; font-weight: 600; }
        .status-late { color: #f39c12; font-weight: 600; }
        .status-departed { color: #3498db; font-weight: 600; }
        
        /* Print styles for A4 */
        @media print {
            .filter-card, .export-buttons, .premium-header .class-badge, .btn-premium {
                display: none;
            }
            body { background: white; }
            .report-container { padding: 0; }
            table { box-shadow: none; }
            .premium-header {
                background-color: #4B1C3C !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            th {
                background-color: #4B1C3C !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="report-container" id="reportContainer">
        <!-- Header -->
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1><i class="fas fa-chart-bar"></i> Attendance Reports</h1>
                    <div class="class-slogan"><?php echo CLASS_NAME; ?></div>
                </div>
                <div class="class-badge">
                    <a href="attendance.php" class="btn-premium"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>

        <!-- Filter Card (excluded from PDF/PNG) -->
        <div class="filter-card" id="filterCard">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Period</label>
                    <select name="period" onchange="this.form.submit()">
                        <option value="today" <?php echo $period == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="term" <?php echo $period == 'term' ? 'selected' : ''; ?>>This Term</option>
                        <option value="custom" <?php echo $period == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="from" value="<?php echo $from_date; ?>" <?php echo $period != 'custom' ? 'disabled' : ''; ?>>
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="to" value="<?php echo $to_date; ?>" <?php echo $period != 'custom' ? 'disabled' : ''; ?>>
                </div>
                <div class="filter-group">
                    <label>Session</label>
                    <select name="session">
                        <option value="all" <?php echo $selected_session == 'all' ? 'selected' : ''; ?>>All Sessions</option>
                        <?php foreach ($sessions as $key => $name): ?>
                        <option value="<?php echo $key; ?>" <?php echo $selected_session == $key ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Apply</button>
                </div>
            </form>
        </div>

        <!-- Summary Stats (included in export) -->
        <div class="summary-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($records); ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_unique(array_column($records, 'date'))); ?></div>
                <div class="stat-label">Days</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_unique(array_column($records, 'student_id'))); ?></div>
                <div class="stat-label">Students</div>
            </div>
        </div>

        <!-- Export Buttons (excluded from PDF/PNG) -->
        <div class="export-buttons" id="exportButtons">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn-export">
                <i class="fas fa-file-csv"></i> CSV
            </a>
            <button class="btn-export btn-pdf" onclick="exportAsPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
            <button class="btn-export btn-png" onclick="exportAsPNG()"><i class="fas fa-image"></i> PNG</button>
            <button onclick="window.print()" class="btn-export btn-print"><i class="fas fa-print"></i> Print</button>
        </div>

        <!-- Attendance Table -->
        <?php if (empty($records)): ?>
            <div class="alert alert-info">No attendance records found for the selected period and session.</div>
        <?php else: ?>
            <table id="attendanceTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Session</th>
                        <th>Student</th>
                        <th>Admission</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                    <tr>
                        <td><?php echo date('D, M j, Y', strtotime($r['date'])); ?></td>
                        <td><span class="session-badge"><?php echo $sessions[$r['session']] ?? ucfirst(str_replace('_', ' ', $r['session'])); ?></span></td>
                        <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                        <td><?php echo $r['admission_number']; ?></td>
                        <td><?php echo $r['student_type']; ?></td>
                        <td class="status-<?php echo strtolower($r['status']); ?>"><?php echo $r['status']; ?></td>
                        <td><?php echo $r['time'] ? date('h:i A', strtotime($r['time'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($r['notes']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        // Enable/disable custom date inputs based on period selection
        document.querySelector('select[name="period"]').addEventListener('change', function() {
            const custom = this.value === 'custom';
            document.querySelectorAll('input[name="from"], input[name="to"]').forEach(input => {
                input.disabled = !custom;
            });
        });

        // PDF Export
        function exportAsPDF() {
            const element = document.getElementById('reportContainer');
            // Hide filter card and export buttons before capture
            const filterCard = document.getElementById('filterCard');
            const exportBtns = document.getElementById('exportButtons');
            filterCard.style.visibility = 'hidden';
            exportBtns.style.visibility = 'hidden';
            
            html2canvas(element, {
                scale: 2,
                backgroundColor: '#ffffff',
                logging: false
            }).then(canvas => {
                // Restore visibility
                filterCard.style.visibility = 'visible';
                exportBtns.style.visibility = 'visible';
                
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save('attendance_<?php echo $from_date; ?>_to_<?php echo $to_date; ?>.pdf');
            });
        }

        // PNG Export
        function exportAsPNG() {
            const element = document.getElementById('reportContainer');
            const filterCard = document.getElementById('filterCard');
            const exportBtns = document.getElementById('exportButtons');
            filterCard.style.visibility = 'hidden';
            exportBtns.style.visibility = 'hidden';
            
            html2canvas(element, {
                scale: 2,
                backgroundColor: '#ffffff',
                logging: false
            }).then(canvas => {
                filterCard.style.visibility = 'visible';
                exportBtns.style.visibility = 'visible';
                
                const link = document.createElement('a');
                link.download = 'attendance_<?php echo $from_date; ?>_to_<?php echo $to_date; ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>
</body>
</html>