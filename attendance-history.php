<?php
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE - Add to EVERY file
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// attendance-history.php - Attendance Reports
require_once 'includes/config.php';

// Get filter parameters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? ACADEMIC_YEAR;
$term = $_GET['term'] ?? CURRENT_TERM;
$student_id = $_GET['student_id'] ?? '';

// Get all active students for filter
$students = $pdo->query("SELECT id, full_name FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();

// Build query based on filters
$query = "
    SELECT a.*, s.full_name, s.student_type
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.year = ? AND a.term = ?
";
$params = [$year, $term];

if ($student_id) {
    $query .= " AND a.student_id = ?";
    $params[] = $student_id;
}

if ($month) {
    $query .= " AND MONTH(a.date) = ?";
    $params[] = $month;
}

$query .= " ORDER BY a.date DESC, s.full_name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendance_history = $stmt->fetchAll();

// Group by date
$attendance_by_date = [];
foreach ($attendance_history as $record) {
    $date = $record['date'];
    if (!isset($attendance_by_date[$date])) {
        $attendance_by_date[$date] = [];
    }
    $attendance_by_date[$date][] = $record;
}

// Calculate summary statistics
$total_days = count($attendance_by_date);
$total_records = count($attendance_history);
$avg_attendance = 0;
$total_present = 0;
$total_absent = 0;
$total_late = 0;

foreach ($attendance_history as $record) {
    if ($record['morning_status'] == 'Present') $total_present++;
    if ($record['morning_status'] == 'Absent') $total_absent++;
    if ($record['morning_status'] == 'Late') $total_late++;
}

if ($total_records > 0) {
    $avg_attendance = round(($total_present / $total_records) * 100, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History - P.5 Purple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            margin-bottom: 0;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .history-date {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin: 30px 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .history-date h3 {
            color: white;
            margin: 0;
        }
        
        .history-date .day-summary {
            background: var(--accent);
            color: var(--primary);
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .history-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .history-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr;
            background: var(--gray-200);
            padding: 12px 15px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .history-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr;
            padding: 10px 15px;
            border-bottom: 1px solid var(--gray-200);
            align-items: center;
        }
        
        .history-row:hover {
            background: var(--gray-100);
        }
        
        .attendance-badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 70px;
        }
        
        .badge-present { background: #E8F5E9; color: #2E7D32; }
        .badge-absent { background: #FFEBEE; color: #C62828; }
        .badge-late { background: #FFF3E0; color: #EF6C00; }
        .badge-departed { background: #E3F2FD; color: #1565C0; }
        
        .no-data {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 10px;
            color: var(--gray-500);
        }
        
        @media (max-width: 768px) {
            .history-header, .history-row {
                grid-template-columns: 1fr;
                gap: 5px;
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
                    <h1><i class="fas fa-chart-line"></i> Attendance History</h1>
                    <div class="class-slogan">Track attendance patterns over time</div>
                </div>
                <div class="class-badge">
                    <a href="attendance.php" class="btn-premium">
                        <i class="fas fa-check-circle"></i> Today's Attendance
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group filter-group">
                    <label>Year</label>
                    <select name="year" class="form-control">
                        <option value="2026" <?php echo $year == '2026' ? 'selected' : ''; ?>>2026</option>
                    </select>
                </div>
                
                <div class="form-group filter-group">
                    <label>Term</label>
                    <select name="term" class="form-control">
                        <option value="1" <?php echo $term == '1' ? 'selected' : ''; ?>>Term 1</option>
                        <option value="2" <?php echo $term == '2' ? 'selected' : ''; ?>>Term 2</option>
                        <option value="3" <?php echo $term == '3' ? 'selected' : ''; ?>>Term 3</option>
                    </select>
                </div>
                
                <div class="form-group filter-group">
                    <label>Month</label>
                    <select name="month" class="form-control">
                        <option value="">All Months</option>
                        <option value="01" <?php echo $month == '01' ? 'selected' : ''; ?>>January</option>
                        <option value="02" <?php echo $month == '02' ? 'selected' : ''; ?>>February</option>
                        <option value="03" <?php echo $month == '03' ? 'selected' : ''; ?>>March</option>
                        <option value="04" <?php echo $month == '04' ? 'selected' : ''; ?>>April</option>
                        <option value="05" <?php echo $month == '05' ? 'selected' : ''; ?>>May</option>
                        <option value="06" <?php echo $month == '06' ? 'selected' : ''; ?>>June</option>
                        <option value="07" <?php echo $month == '07' ? 'selected' : ''; ?>>July</option>
                        <option value="08" <?php echo $month == '08' ? 'selected' : ''; ?>>August</option>
                        <option value="09" <?php echo $month == '09' ? 'selected' : ''; ?>>September</option>
                        <option value="10" <?php echo $month == '10' ? 'selected' : ''; ?>>October</option>
                        <option value="11" <?php echo $month == '11' ? 'selected' : ''; ?>>November</option>
                        <option value="12" <?php echo $month == '12' ? 'selected' : ''; ?>>December</option>
                    </select>
                </div>
                
                <div class="form-group filter-group">
                    <label>Student</label>
                    <select name="student_id" class="form-control">
                        <option value="">All Students</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $student_id == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo $s['full_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group filter-group">
                    <button type="submit" class="btn-premium" style="width: 100%;">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-content">
                    <h3><?php echo $total_days; ?></h3>
                    <p>School Days</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-content">
                    <h3><?php echo $total_present; ?></h3>
                    <p>Present</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                <div class="stat-content">
                    <h3><?php echo $total_late; ?></h3>
                    <p>Late</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-times"></i></div>
                <div class="stat-content">
                    <h3><?php echo $total_absent; ?></h3>
                    <p>Absent</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-content">
                    <h3><?php echo $avg_attendance; ?>%</h3>
                    <p>Attendance Rate</p>
                </div>
            </div>
        </div>

        <!-- Attendance History by Date -->
        <?php if (empty($attendance_by_date)): ?>
        <div class="no-data">
            <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <h3>No attendance records found</h3>
            <p>Try adjusting your filters or take attendance for some days.</p>
            <a href="attendance.php" class="btn-premium" style="margin-top: 15px;">
                <i class="fas fa-check-circle"></i> Take Attendance
            </a>
        </div>
        <?php else: ?>
            <?php foreach ($attendance_by_date as $date => $records): 
                $day_present = 0;
                $day_absent = 0;
                $day_late = 0;
                foreach ($records as $r) {
                    if ($r['morning_status'] == 'Present') $day_present++;
                    if ($r['morning_status'] == 'Absent') $day_absent++;
                    if ($r['morning_status'] == 'Late') $day_late++;
                }
                $total_day = count($records);
            ?>
            <div class="history-date">
                <h3><i class="fas fa-calendar-day"></i> <?php echo date('l, F j, Y', strtotime($date)); ?></h3>
                <span class="day-summary">
                    Present: <?php echo $day_present; ?> | Late: <?php echo $day_late; ?> | Absent: <?php echo $day_absent; ?>
                </span>
            </div>
            
            <div class="history-table">
                <div class="history-header">
                    <div>Student</div>
                    <div>Type</div>
                    <div>Morning</div>
                    <div>Arrival</div>
                    <div>Afternoon</div>
                    <div>Evening</div>
                </div>
                
                <?php foreach ($records as $record): ?>
                <div class="history-row">
                    <div>
                        <strong><?php echo $record['full_name']; ?></strong>
                    </div>
                    <div>
                        <?php if ($record['student_type'] == 'Boarder'): ?>
                            <span class="badge-boarder">B</span>
                        <?php else: ?>
                            <span class="badge-day">D</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="attendance-badge badge-<?php echo strtolower($record['morning_status']); ?>">
                            <?php echo $record['morning_status']; ?>
                        </span>
                    </div>
                    <div>
                        <?php echo $record['morning_arrival_time'] ? date('g:i A', strtotime($record['morning_arrival_time'])) : '-'; ?>
                    </div>
                    <div>
                        <?php if ($record['student_type'] == 'Day Scholar'): ?>
                            <span class="attendance-badge badge-<?php echo strtolower($record['afternoon_status']); ?>">
                                <?php echo $record['afternoon_status']; ?>
                            </span>
                        <?php else: ?>
                            <span>—</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($record['student_type'] == 'Boarder'): ?>
                            <?php if ($record['final_roll_call_status']): ?>
                                <span class="attendance-badge badge-<?php echo strtolower($record['final_roll_call_status']); ?>">
                                    <?php echo $record['final_roll_call_status']; ?>
                                </span>
                            <?php else: ?>
                                <span class="attendance-badge badge-present">Present</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span>—</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>