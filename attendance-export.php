<?php
// attendance-export.php - COMPLETELY FIXED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/config.php';

// Debug: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "DEBUG: Not logged in. Session: ";
    print_r($_SESSION);
    header('Location: login.php');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');

// Debug: Show what date we're looking for
echo "<!-- DEBUG: Looking for date: $date -->";

// First, let's check if any data exists in attendance table
$check = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
echo "<!-- DEBUG: Total attendance records in table: $check -->";

// Check records for this specific date
$check_date = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date = ?");
$check_date->execute([$date]);
$count_for_date = $check_date->fetchColumn();
echo "<!-- DEBUG: Records for $date: $count_for_date -->";

// Using 'date' column (correct for your database)
$stmt = $pdo->prepare("
    SELECT a.*, s.full_name, s.student_type, s.dormitory_number, s.soccer_academy
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.date = ?
    ORDER BY s.student_type, s.full_name
");
$stmt->execute([$date]);
$attendance = $stmt->fetchAll();

// Debug: Show number of records found
echo "<!-- DEBUG: Records fetched: " . count($attendance) . " -->";

// Get statistics
$total_present_morning = 0;
$total_absent_morning = 0;
$total_late = 0;
$total_boarders_night = 0;
$total_day_departed = 0;

foreach ($attendance as $a) {
    if (isset($a['morning_status']) && $a['morning_status'] == 'Present') $total_present_morning++;
    if (isset($a['morning_status']) && $a['morning_status'] == 'Absent') $total_absent_morning++;
    if (isset($a['morning_status']) && $a['morning_status'] == 'Late') $total_late++;
    if ($a['student_type'] == 'Boarder' && isset($a['final_roll_call_status']) && $a['final_roll_call_status'] == 'Present') $total_boarders_night++;
    if ($a['student_type'] == 'Day Scholar' && isset($a['afternoon_status']) && $a['afternoon_status'] == 'Departed') $total_day_departed++;
}

// Define CLASS_SLOGAN if not defined in config
if (!defined('CLASS_SLOGAN')) {
    define('CLASS_SLOGAN', 'Purple Hearts, Bright Minds');
}
if (!defined('CLASS_TEACHER')) {
    define('CLASS_TEACHER', 'Class Teacher');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - <?php echo $date; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', Arial, sans-serif;
        }
        
        body {
            padding: 30px;
            background: white;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #4B1C3C;
        }
        
        .school-name {
            font-size: 24px;
            font-weight: 700;
            color: #4B1C3C;
        }
        
        .school-sub {
            color: #FFB800;
            font-size: 18px;
            margin: 5px 0;
        }
        
        .report-title {
            font-size: 22px;
            font-weight: 600;
            margin: 15px 0 5px;
        }
        
        .date-info {
            color: #666;
            font-size: 16px;
        }
        
        .stats-box {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            padding: 20px;
            background: #f8f4f8;
            border-radius: 10px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
            min-width: 120px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #4B1C3C;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 1px solid #4B1C3C;
        }
        
        th {
            background: #4B1C3C;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        tr:nth-child(even) {
            background: #f8f4f8;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-Present { background: #E8F5E9; color: #2E7D32; }
        .status-Absent { background: #FFEBEE; color: #C62828; }
        .status-Late { background: #FFF3E0; color: #EF6C00; }
        .status-Departed { background: #E3F2FD; color: #1565C0; }
        
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            color: #999;
            font-size: 12px;
        }
        
        .signature-line {
            width: 200px;
            border-bottom: 2px solid #4B1C3C;
            margin-bottom: 5px;
        }
        
        .print-btn {
            background: #4B1C3C;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
            margin-right: 10px;
        }
        
        .print-btn:hover {
            background: #2F1224;
        }
        
        .print-btn.secondary {
            background: #666;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
        
        .debug-info {
            background: #f0f0f0;
            border: 1px solid #ff0000;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            display: none; /* Hide debug by default - remove 'none' to see */
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            background: #f8f4f8;
            border-radius: 10px;
            margin: 30px 0;
        }
        
        .no-data i {
            font-size: 48px;
            color: #FFB800;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Debug Info (hidden by default) -->
    <div class="debug-info no-print">
        <h3>Debug Information</h3>
        <p>Date requested: <?php echo $date; ?></p>
        <p>Total records in table: <?php echo $check; ?></p>
        <p>Records for this date: <?php echo $count_for_date; ?></p>
        <p>Records fetched: <?php echo count($attendance); ?></p>
    </div>

    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
        <button class="print-btn secondary" onclick="window.close()">✖ Close</button>
        <button class="print-btn secondary" onclick="document.querySelector('.debug-info').style.display='block'">🐞 Show Debug</button>
    </div>
    
    <div class="report-header">
        <div class="school-name">RAYS OF GRACE JUNIOR SCHOOL</div>
        <div class="school-sub">P.5 Purple - <?php echo CLASS_SLOGAN; ?></div>
        <div class="report-title">DAILY ATTENDANCE REPORT</div>
        <div class="date-info"><?php echo date('l, F j, Y', strtotime($date)); ?></div>
    </div>
    
    <?php if (empty($attendance)): ?>
        <div class="no-data">
            <i class="fas fa-clipboard-list"></i>
            <h3>No Attendance Records Found</h3>
            <p>Please take attendance for <?php echo date('F j, Y', strtotime($date)); ?> first.</p>
            <a href="attendance.php" style="display: inline-block; background: #4B1C3C; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-top: 15px;">Take Attendance Now</a>
        </div>
    <?php else: ?>
        <div class="stats-box">
            <div class="stat-item">
                <div class="stat-value"><?php echo count($attendance); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_present_morning; ?></div>
                <div class="stat-label">Present (AM)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_late; ?></div>
                <div class="stat-label">Late</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_absent_morning; ?></div>
                <div class="stat-label">Absent</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Type</th>
                    <th>Morning</th>
                    <th>Arrival</th>
                    <th>Afternoon</th>
                    <th>Evening</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance as $a): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($a['full_name']); ?></strong></td>
                    <td><?php echo $a['student_type']; ?></td>
                    <td>
                        <?php $status = $a['morning_status'] ?? 'Not Taken'; ?>
                        <span class="status-badge status-<?php echo str_replace(' ', '', $status); ?>">
                            <?php echo $status; ?>
                        </span>
                    </td>
                    <td><?php echo !empty($a['morning_arrival_time']) ? date('g:i A', strtotime($a['morning_arrival_time'])) : '-'; ?></td>
                    <td>
                        <?php if ($a['student_type'] == 'Day Scholar'): ?>
                            <?php $status = $a['afternoon_status'] ?? '-'; ?>
                            <?php if ($status != '-'): ?>
                                <span class="status-badge status-<?php echo str_replace(' ', '', $status); ?>">
                                    <?php echo $status; ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        <?php else: ?>
                            Present
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($a['student_type'] == 'Boarder'): ?>
                            <?php if (!empty($a['final_roll_call_status'])): ?>
                                <span class="status-badge status-<?php echo $a['final_roll_call_status']; ?>">
                                    <?php echo $a['final_roll_call_status']; ?>
                                </span>
                            <?php else: ?>
                                In Dorm
                            <?php endif; ?>
                        <?php else: ?>
                            Departed
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($a['morning_notes'] ?? '-'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin: 20px 0;">
            <h3 style="color: #4B1C3C;">Summary</h3>
            <p>✓ Morning Present: <?php echo $total_present_morning; ?> students</p>
            <p>✓ Late Arrivals: <?php echo $total_late; ?> students</p>
            <p>✓ Absent: <?php echo $total_absent_morning; ?> students</p>
            <p>✓ Day Scholars Departed: <?php echo $total_day_departed; ?> students</p>
            <p>✓ Boarders in Dorm: <?php echo $total_boarders_night; ?> students</p>
        </div>
    <?php endif; ?>
    
    <div class="footer">
        <div>
            <div class="signature-line"></div>
            <p><strong>Class Teacher</strong><br><?php echo CLASS_TEACHER; ?></p>
        </div>
        <div>
            <p>Generated on: <?php echo date('F j, Y \a\t g:i A'); ?></p>
        </div>
    </div>
</body>
</html>