<?php
// attendance-today.php - Today's Attendance Summary with PDF Export
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$today = date('Y-m-d');
$current_time = date('H:i:s');
$current_hour = date('H');

// FIXED: Use correct column name 'date' (not 'attendance_date')
$stmt = $pdo->prepare("
    SELECT a.*, s.full_name, s.student_type, s.soccer_academy, s.dormitory_number
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.date = ?
    ORDER BY s.student_type, s.full_name
");
$stmt->execute([$today]);
$attendance_records = $stmt->fetchAll();

// Get statistics
$total_present_morning = 0;
$total_absent_morning = 0;
$total_late = 0;
$total_present_evening = 0;
$total_boarders_present = 0;
$total_prayer_attended = 0;
$total_prep_attended = 0;

foreach ($attendance_records as $record) {
    if (isset($record['morning_status'])) {
        if ($record['morning_status'] == 'Present') $total_present_morning++;
        if ($record['morning_status'] == 'Absent') $total_absent_morning++;
        if ($record['morning_status'] == 'Late') $total_late++;
    }
    
    if (isset($record['afternoon_status']) && ($record['afternoon_status'] == 'Present' || $record['afternoon_status'] == 'Departed')) {
        $total_present_evening++;
    }
    
    if ($record['student_type'] == 'Boarder' && isset($record['final_roll_call_status']) && $record['final_roll_call_status'] == 'Present') {
        $total_boarders_present++;
    }
    
    if (isset($record['evening_prayer_attended']) && $record['evening_prayer_attended']) $total_prayer_attended++;
    if (isset($record['evening_prep_attended']) && $record['evening_prep_attended']) $total_prep_attended++;
}

$total_students = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn();
$total_boarders = $pdo->query("SELECT COUNT(*) FROM students WHERE student_type = 'Boarder' AND status = 'Active'")->fetchColumn();
$total_day = $pdo->query("SELECT COUNT(*) FROM students WHERE student_type = 'Day Scholar' AND status = 'Active'")->fetchColumn();

// Check if today is a holiday
$stmt = $pdo->prepare("SELECT * FROM public_holidays WHERE holiday_date = ?");
$stmt->execute([$today]);
$holiday = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Attendance - P.5 Purple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #FFB800;
        }
        
        .summary-card h3 {
            color: #4B1C3C;
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .summary-card p {
            color: #666;
        }
        
        .timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .timeline-item {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
        }
        
        .timeline-item.active {
            background: #4B1C3C;
            color: white;
        }
        
        .timeline-item.active .timeline-time {
            color: #FFB800;
        }
        
        .timeline-time {
            font-weight: 700;
            color: #4B1C3C;
            margin-bottom: 5px;
        }
        
        .timeline-label {
            font-size: 0.9rem;
        }
        
        .timeline-connector {
            color: #FFB800;
            font-size: 1.5rem;
        }
        
        .student-status-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr 2fr;
            background: #4B1C3C;
            color: white;
            padding: 15px;
            font-weight: 600;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr 2fr;
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            align-items: center;
        }
        
        .table-row:hover {
            background: #f8f4f8;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-present { background: #4CAF50; }
        .status-absent { background: #f44336; }
        .status-late { background: #FF9800; }
        .status-departed { background: #2196F3; }
        
        .badge-day {
            background: #FFB800;
            color: #4B1C3C;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .badge-boarder {
            background: #4B1C3C;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .badge-soccer {
            background: #4CAF50;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.6rem;
            margin-left: 5px;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        
        .btn-pdf {
            background-color: #4B1C3C;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .btn-pdf:hover {
            background-color: #2F1224;
        }
        
        .btn-pdf i {
            color: #FFB800;
        }
        
        .btn-excel {
            background-color: #FFB800;
            color: #4B1C3C;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .btn-excel:hover {
            background-color: #D99B00;
        }
        
        @media (max-width: 768px) {
            .table-header, .table-row {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            
            .timeline {
                flex-direction: column;
            }
            
            .timeline-connector {
                transform: rotate(90deg);
            }
        }
    </style>
</head>
<body>
    <div class="premium-container">
        <!-- Header -->
        <div class="premium-header" style="background-color: #4B1C3C; padding: 20px 30px;">
            <div class="header-content">
                <div class="class-title">
                    <h1 style="color: white;"><i class="fas fa-calendar-check"></i> Today's Attendance</h1>
                    <div class="class-slogan" style="color: #FFB800;"><?php echo date('l, F j, Y'); ?></div>
                </div>
                <div class="class-badge">
                    <div class="teacher" style="color: #FFB800;">
                        <i class="fas fa-clock"></i> 
                        <?php echo $current_time; ?>
                    </div>
                    <a href="attendance.php" class="logout-btn" style="background-color: #2F1224; margin-top: 10px; display: inline-block;">
                        <i class="fas fa-edit"></i> Take Attendance
                    </a>
                </div>
            </div>
        </div>

        <!-- Holiday Alert -->
        <?php if ($holiday): ?>
        <div class="alert alert-info">
            <i class="fas fa-calendar-day"></i>
            <strong>Today is <?php echo $holiday['holiday_name']; ?> - No classes!</strong>
        </div>
        <?php endif; ?>

       <!-- Add this near your other buttons -->
<a href="rollcall-selector.php?date=<?php echo $today; ?>" class="btn-pdf" style="background-color: #4B1C3C;">
    <i class="fas fa-download"></i> Export Roll Call
</a>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
                <small><?php echo $total_day; ?> Day, <?php echo $total_boarders; ?> Boarders</small>
            </div>
            <div class="summary-card">
                <h3><?php echo $total_present_morning; ?></h3>
                <p>Present this Morning</p>
                <small><?php echo $total_late; ?> late, <?php echo $total_absent_morning; ?> absent</small>
            </div>
            <div class="summary-card">
                <h3><?php echo $total_present_evening; ?></h3>
                <p>Present this Afternoon</p>
                <small><?php echo count($attendance_records); ?> total students</small>
            </div>
            <div class="summary-card">
                <h3><?php echo $total_boarders_present; ?>/<?php echo $total_boarders; ?></h3>
                <p>Boarders in Dormitory</p>
                <small>Prayers: <?php echo $total_prayer_attended; ?>, Prep: <?php echo $total_prep_attended; ?></small>
            </div>
        </div>

        <!-- Daily Timeline -->
        <div class="timeline">
            <div class="timeline-item <?php echo $current_hour < 12 ? 'active' : ''; ?>">
                <div class="timeline-time">8:00 AM</div>
                <div class="timeline-label">Morning Roll Call</div>
            </div>
            <div class="timeline-connector"><i class="fas fa-arrow-right"></i></div>
            <div class="timeline-item <?php echo $current_hour >= 12 && $current_hour < 15 ? 'active' : ''; ?>">
                <div class="timeline-time">3:30 PM</div>
                <div class="timeline-label">Day Scholars Depart</div>
            </div>
            <div class="timeline-connector"><i class="fas fa-arrow-right"></i></div>
            <div class="timeline-item <?php echo $current_hour >= 15 && $current_hour < 17 ? 'active' : ''; ?>">
                <div class="timeline-time">5:30 PM</div>
                <div class="timeline-label">Evening Prayers</div>
            </div>
            <div class="timeline-connector"><i class="fas fa-arrow-right"></i></div>
            <div class="timeline-item <?php echo $current_hour >= 17 && $current_hour < 21 ? 'active' : ''; ?>">
                <div class="timeline-time">6:30 PM</div>
                <div class="timeline-label">Evening Prep</div>
            </div>
            <div class="timeline-connector"><i class="fas fa-arrow-right"></i></div>
            <div class="timeline-item <?php echo $current_hour >= 21 ? 'active' : ''; ?>">
                <div class="timeline-time">9:00 PM</div>
                <div class="timeline-label">Final Roll Call</div>
            </div>
        </div>

        <!-- Student Status Table -->
        <div class="student-status-table">
            <div class="table-header">
                <div>Student</div>
                <div>Type</div>
                <div>Morning</div>
                <div>Afternoon</div>
                <div>Evening Status</div>
            </div>
            
            <?php if (empty($attendance_records)): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 10px;"></i>
                <p>No attendance records for today. Please take attendance first.</p>
                <a href="attendance.php" class="btn-pdf" style="display: inline-block; margin-top: 10px;">
                    <i class="fas fa-edit"></i> Take Attendance Now
                </a>
            </div>
            <?php else: ?>
                <?php foreach ($attendance_records as $record): ?>
                <div class="table-row">
                    <div>
                        <strong><?php echo $record['full_name']; ?></strong>
                        <?php if (!empty($record['soccer_academy']) && $record['soccer_academy']): ?>
                            <span class="badge-soccer">⚽ Soccer</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($record['student_type'] == 'Day Scholar'): ?>
                            <span class="badge-day">Day Scholar</span>
                        <?php else: ?>
                            <span class="badge-boarder">Boarder</span>
                            <?php if (!empty($record['dormitory_number'])): ?>
                                <small style="display: block;"><?php echo $record['dormitory_number']; ?></small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if (isset($record['morning_status'])): ?>
                            <span class="status-indicator status-<?php echo strtolower($record['morning_status']); ?>"></span>
                            <?php echo $record['morning_status']; ?>
                            <?php if (!empty($record['morning_arrival_time'])): ?>
                                <br><small><?php echo date('g:i A', strtotime($record['morning_arrival_time'])); ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-indicator status-absent"></span> Not Taken
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($record['student_type'] == 'Day Scholar'): ?>
                            <?php if (isset($record['afternoon_status'])): ?>
                                <span class="status-indicator status-<?php echo strtolower($record['afternoon_status']); ?>"></span>
                                <?php echo $record['afternoon_status']; ?>
                                <?php if (!empty($record['afternoon_departure_time'])): ?>
                                    <br><small>Left at <?php echo date('g:i A', strtotime($record['afternoon_departure_time'])); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="status-indicator status-absent"></span> Not Taken
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-indicator status-present"></span> Present
                            <?php if (!empty($record['evening_prayer_attended']) || !empty($record['evening_prep_attended'])): ?>
                                <br><small>
                                    <?php if (!empty($record['evening_prayer_attended'])): ?>🙏<?php endif; ?>
                                    <?php if (!empty($record['evening_prep_attended'])): ?>📚<?php endif; ?>
                                </small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($record['student_type'] == 'Boarder'): ?>
                            <?php if (!empty($record['final_roll_call_status'])): ?>
                                <span class="status-indicator status-<?php echo strtolower($record['final_roll_call_status']); ?>"></span>
                                <?php echo $record['final_roll_call_status']; ?>
                            <?php else: ?>
                                <span class="status-indicator status-present"></span> In Dorm
                            <?php endif; ?>
                            <?php if (!empty($record['lights_out_check'])): ?>
                                <br><small>Lights out ✓</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-indicator status-departed"></span> Departed
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Footer Note -->
        <div style="margin-top: 30px; text-align: center; color: #999; font-size: 0.9rem;">
            <i class="fas fa-check-circle" style="color: #FFB800;"></i> 
            Attendance recorded for <?php echo date('l, F j, Y'); ?>
        </div>
    </div>
</body>
</html>