<?php
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE - Add to EVERY file
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// attendance.php - Double Roll Call System (Morning & Evening)
require_once 'includes/config.php';

$today = date('Y-m-d');
$message = '';
$message_type = '';

// Check if today is a holiday
$stmt = $pdo->prepare("SELECT * FROM public_holidays WHERE holiday_date = ?");
$stmt->execute([$today]);
$holiday = $stmt->fetch();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_morning_attendance'])) {
        $student_ids = $_POST['student_id'] ?? [];
        $statuses = $_POST['morning_status'] ?? [];
        $times = $_POST['arrival_time'] ?? [];
        $notes = $_POST['morning_notes'] ?? [];
        
        foreach ($student_ids as $index => $student_id) {
            // Check if attendance already exists
            $check = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND date = ?");
            $check->execute([$student_id, $today]);
            
            if ($check->fetch()) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE attendance SET 
                    morning_taken = TRUE,
                    morning_status = ?,
                    morning_arrival_time = ?,
                    morning_notes = ?,
                    student_type = (SELECT student_type FROM students WHERE id = ?)
                    WHERE student_id = ? AND date = ?");
                $stmt->execute([$statuses[$index], $times[$index], $notes[$index], $student_id, $student_id, $today]);
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO attendance 
                    (student_id, date, term, year, student_type, morning_taken, morning_status, morning_arrival_time, morning_notes)
                    SELECT ?, ?, ?, ?, student_type, TRUE, ?, ?, ? FROM students WHERE id = ?");
                $stmt->execute([$student_id, $today, CURRENT_TERM, ACADEMIC_YEAR, $statuses[$index], $times[$index], $notes[$index], $student_id]);
            }
        }
        $message = "Morning attendance saved successfully!";
        $message_type = "success";
    }
    
    if (isset($_POST['save_evening_attendance'])) {
        $student_ids = $_POST['student_id'] ?? [];
        $statuses = $_POST['evening_status'] ?? [];
        $departure_times = $_POST['departure_time'] ?? [];
        $prayer = $_POST['prayer_attended'] ?? [];
        $prep = $_POST['prep_attended'] ?? [];
        
        foreach ($student_ids as $index => $student_id) {
            $stmt = $pdo->prepare("UPDATE attendance SET 
                afternoon_taken = TRUE,
                afternoon_status = ?,
                afternoon_departure_time = ?,
                evening_prayer_attended = ?,
                evening_prep_attended = ?
                WHERE student_id = ? AND date = ?");
            $stmt->execute([
                $statuses[$index],
                $departure_times[$index],
                isset($prayer[$index]) ? 1 : 0,
                isset($prep[$index]) ? 1 : 0,
                $student_id,
                $today
            ]);
        }
        $message = "Evening attendance saved successfully!";
        $message_type = "success";
    }
    
    if (isset($_POST['save_final_roll_call'])) {
        $student_ids = $_POST['student_id'] ?? [];
        $final_statuses = $_POST['final_status'] ?? [];
        $lights_out = $_POST['lights_out'] ?? [];
        
        foreach ($student_ids as $index => $student_id) {
            $stmt = $pdo->prepare("UPDATE attendance SET 
                final_roll_call_taken = TRUE,
                final_roll_call_status = ?,
                lights_out_check = ?
                WHERE student_id = ? AND date = ?");
            $stmt->execute([
                $final_statuses[$index],
                isset($lights_out[$index]) ? 1 : 0,
                $student_id,
                $today
            ]);
        }
        $message = "Final roll call saved successfully!";
        $message_type = "success";
    }
}

// Get all active students
$students = $pdo->query("SELECT * FROM students WHERE status = 'Active' ORDER BY student_type, full_name")->fetchAll();

// Get today's attendance records
$attendance = [];
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE date = ?");
$stmt->execute([$today]);
foreach ($stmt->fetchAll() as $a) {
    $attendance[$a['student_id']] = $a;
}

// Get current time to determine which section to show
$current_hour = date('H');
$show_morning = $current_hour < 12;
$show_afternoon = $current_hour >= 12 && $current_hour < 17;
$show_evening = $current_hour >= 17;

// Separate students by type
$day_scholars = array_filter($students, function($s) { return $s['student_type'] == 'Day Scholar'; });
$boarders = array_filter($students, function($s) { return $s['student_type'] == 'Boarder'; });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - P.5 Purple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .attendance-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 12px 25px;
            background: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        
        .tab-btn.active {
            background: var(--primary);
            color: white;
        }
        
        .tab-btn.active i {
            color: var(--accent);
        }
        
        .tab-btn i {
            color: var(--accent);
        }
        
        .attendance-panel {
            display: none;
        }
        
        .attendance-panel.active {
            display: block;
        }
        
        .time-indicator {
            background: var(--accent);
            color: var(--primary);
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .student-row {
            display: flex;
            align-items: center;
            padding: 12px;
            background: white;
            border-radius: 10px;
            margin-bottom: 8px;
            border: 1px solid var(--gray-200);
        }
        
        .student-info {
            width: 200px;
        }
        
        .student-name {
            font-weight: 600;
            color: var(--primary);
        }
        
        .student-type {
            font-size: 0.7rem;
            color: var(--gray-500);
        }
        
        .attendance-controls {
            flex: 1;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .status-select {
            padding: 6px 12px;
            border: 2px solid var(--gray-300);
            border-radius: 5px;
        }
        
        .time-input {
            width: 100px;
            padding: 6px;
            border: 2px solid var(--gray-300);
            border-radius: 5px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        .boarder-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed var(--accent);
        }
        
        .boarder-section h3 {
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .save-btn {
            margin-top: 20px;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .student-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .student-info {
                width: 100%;
            }
            
            .attendance-controls {
                width: 100%;
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
                    <h1><i class="fas fa-clipboard-list"></i> Daily Attendance</h1>
                    <div class="class-slogan"><?php echo CLASS_NAME; ?> - <?php echo date('l, F j, Y'); ?></div>
                </div>
                
                <div class="class-badge">
                    <div class="teacher">
                        <i class="fas fa-clock"></i> 
                        <?php echo $holiday ? 'Holiday: ' . $holiday['holiday_name'] : 'School Day'; ?>
                    </div>
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

        <!-- Alert Message -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Attendance Tabs -->
        <div class="attendance-tabs">
            <button class="tab-btn <?php echo $show_morning ? 'active' : ''; ?>" onclick="switchTab('morning')">
                <i class="fas fa-sun"></i> Morning Roll Call (8:00 AM)
            </button>
            <button class="tab-btn <?php echo $show_afternoon ? 'active' : ''; ?>" onclick="switchTab('afternoon')">
                <i class="fas fa-cloud-sun"></i> Afternoon Roll Call (3:30 PM)
            </button>
            <button class="tab-btn <?php echo $show_evening ? 'active' : ''; ?>" onclick="switchTab('evening')">
                <i class="fas fa-moon"></i> Evening Prep (9:00 PM)
            </button>
        </div>

        <!-- Morning Attendance Panel -->
        <div id="morning-panel" class="attendance-panel <?php echo $show_morning ? 'active' : ''; ?>">
            <div class="time-indicator">
                <i class="fas fa-sun"></i> Morning Roll Call - 8:00 AM
            </div>
            
            <form method="POST">
                <?php foreach ($students as $student): 
                    $record = $attendance[$student['id']] ?? null;
                ?>
                <div class="student-row">
                    <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                    <div class="student-info">
                        <div class="student-name"><?php echo $student['full_name']; ?></div>
                        <div class="student-type">
                            <?php echo $student['student_type']; ?>
                            <?php if ($student['soccer_academy']): ?>⚽<?php endif; ?>
                        </div>
                    </div>
                    <div class="attendance-controls">
                        <select name="morning_status[]" class="status-select">
                            <option value="Present" <?php echo ($record['morning_status'] ?? '') == 'Present' ? 'selected' : ''; ?>>Present</option>
                            <option value="Absent" <?php echo ($record['morning_status'] ?? '') == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                            <option value="Late" <?php echo ($record['morning_status'] ?? '') == 'Late' ? 'selected' : ''; ?>>Late</option>
                            <option value="Excused" <?php echo ($record['morning_status'] ?? '') == 'Excused' ? 'selected' : ''; ?>>Excused</option>
                        </select>
                        <input type="time" name="arrival_time[]" class="time-input" value="<?php echo $record['morning_arrival_time'] ?? '08:00'; ?>">
                        <input type="text" name="morning_notes[]" class="form-control" placeholder="Notes" value="<?php echo $record['morning_notes'] ?? ''; ?>" style="width: 200px;">
                    </div>
                </div>
    
                <?php endforeach; ?>
                <button type="submit" name="save_morning_attendance" class="btn-premium save-btn">
                    <i class="fas fa-save"></i> Save Morning Attendance
                </button>
            </form>
        </div>

        <!-- Afternoon Attendance Panel (Day Scholars Departure) -->
        <div id="afternoon-panel" class="attendance-panel <?php echo $show_afternoon ? 'active' : ''; ?>">
            <div class="time-indicator">
                <i class="fas fa-cloud-sun"></i> Afternoon Roll Call - 3:30 PM (Day Scholars Departure)
            </div>
            
            <form method="POST">
                <h3 style="color: var(--primary); margin: 20px 0 10px;">Day Scholars</h3>
                <?php foreach ($day_scholars as $student): 
                    $record = $attendance[$student['id']] ?? null;
                ?>
                <div class="student-row">
                    <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                    <div class="student-info">
                        <div class="student-name"><?php echo $student['full_name']; ?></div>
                        <div class="student-type">Day Scholar</div>
                    </div>
                    <div class="attendance-controls">
                        <select name="evening_status[]" class="status-select">
                            <option value="Departed" <?php echo ($record['afternoon_status'] ?? '') == 'Departed' ? 'selected' : ''; ?>>Departed</option>
                            <option value="Present" <?php echo ($record['afternoon_status'] ?? '') == 'Present' ? 'selected' : ''; ?>>Still Here</option>
                            <option value="Absent" <?php echo ($record['afternoon_status'] ?? '') == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                        </select>
                        <input type="time" name="departure_time[]" class="time-input" value="<?php echo $record['afternoon_departure_time'] ?? '15:30'; ?>">
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="boarder-section">
                    <h3><i class="fas fa-moon"></i> Boarders - Evening Prayer & Prep</h3>
                    <?php foreach ($boarders as $student): 
                        $record = $attendance[$student['id']] ?? null;
                    ?>
                    <div class="student-row">
                        <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                        <div class="student-info">
                            <div class="student-name"><?php echo $student['full_name']; ?></div>
                            <div class="student-type">Boarder</div>
                        </div>
                        <div class="attendance-controls">
                            <select name="evening_status[]" class="status-select">
                                <option value="Present" <?php echo ($record['afternoon_status'] ?? '') == 'Present' ? 'selected' : ''; ?>>Present</option>
                                <option value="Absent" <?php echo ($record['afternoon_status'] ?? '') == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                            </select>
                            <label class="checkbox-label">
                                <input type="checkbox" name="prayer_attended[]" value="1" <?php echo ($record['evening_prayer_attended'] ?? 0) ? 'checked' : ''; ?>> Prayers
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="prep_attended[]" value="1" <?php echo ($record['evening_prep_attended'] ?? 0) ? 'checked' : ''; ?>> Prep
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" name="save_evening_attendance" class="btn-premium save-btn">
                    <i class="fas fa-save"></i> Save Afternoon & Evening Attendance
                </button>
            </form>
        </div>

        <!-- Evening Final Roll Call Panel (9:00 PM) -->
        <div id="evening-panel" class="attendance-panel <?php echo $show_evening ? 'active' : ''; ?>">
            <div class="time-indicator">
                <i class="fas fa-moon"></i> Final Roll Call - 9:00 PM (After Prep)
            </div>
            
            <form method="POST">
                <h3 style="color: var(--primary); margin: 20px 0 10px;">Boarders - Final Check</h3>
                <?php foreach ($boarders as $student): 
                    $record = $attendance[$student['id']] ?? null;
                ?>
                <div class="student-row">
                    <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                    <div class="student-info">
                        <div class="student-name"><?php echo $student['full_name']; ?></div>
                        <div class="student-type">Boarder - <?php echo $student['dormitory_number'] ?: 'Dorm'; ?></div>
                    </div>
                    <div class="attendance-controls">
                        <select name="final_status[]" class="status-select">
                            <option value="Present" <?php echo ($record['final_roll_call_status'] ?? '') == 'Present' ? 'selected' : ''; ?>>Present</option>
                            <option value="Absent" <?php echo ($record['final_roll_call_status'] ?? '') == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                            <option value="Excused" <?php echo ($record['final_roll_call_status'] ?? '') == 'Excused' ? 'selected' : ''; ?>>Excused</option>

                        </select>
                        <label class="checkbox-label">
                            <input type="checkbox" name="lights_out[]" value="1" <?php echo ($record['lights_out_check'] ?? 0) ? 'checked' : ''; ?>> Lights Out
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <button type="submit" name="save_final_roll_call" class="btn-premium save-btn">
                    <i class="fas fa-save"></i> Save Final Roll Call
                </button>
            </form>
        </div>
    </div>
<!-- Add this where you want the export button to appear -->
<div style="text-align: right; margin-bottom: 20px;">
    <a href="rollcall-selector.php?date=<?php echo date('Y-m-d'); ?>" class="btn-premium" style="background-color: #4B1C3C; display: inline-flex; align-items: center; gap: 8px;">
        <i class="fas fa-download"></i>
        Export Roll Call
    </a>
</div>
    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.attendance-panel').forEach(panel => panel.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tab + '-panel').classList.add('active');
        }
    </script>
</body>
</html>