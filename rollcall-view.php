<?php
// rollcall-view.php - Simple Roll Call View
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');

// Get attendance data
$stmt = $pdo->prepare("
    SELECT s.*, a.*
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
    WHERE s.status = 'Active'
    ORDER BY s.student_type, s.full_name
");
$stmt->execute([$date]);
$students = $stmt->fetchAll();

// Calculate stats
$present = 0;
$absent = 0;
$late = 0;
$departed = 0;

foreach ($students as $s) {
    if (isset($s['morning_status'])) {
        if ($s['morning_status'] == 'Present') $present++;
        if ($s['morning_status'] == 'Absent') $absent++;
        if ($s['morning_status'] == 'Late') $late++;
    }
    if (isset($s['afternoon_status']) && $s['afternoon_status'] == 'Departed') $departed++;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Roll Call - <?php echo $date; ?></title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        h1 { color: #4B1C3C; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat { background: #f0e8f0; padding: 15px; border-radius: 5px; flex: 1; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #4B1C3C; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #4B1C3C; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f9f9f9; }
        .present { color: green; font-weight: bold; }
        .absent { color: red; font-weight: bold; }
        .late { color: orange; font-weight: bold; }
        .btn { background: #4B1C3C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px; }
        .btn:hover { background: #2F1224; }
        .btn-excel { background: #FFB800; color: #4B1C3C; }
        .btn-excel:hover { background: #D99B00; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Roll Call - <?php echo date('l, F j, Y', strtotime($date)); ?></h1>
            <div>
                <a href="attendance.php" class="btn">← Back</a>
                <a href="rollcall-export.php?date=<?php echo $date; ?>" class="btn btn-excel">📥 Download Excel</a>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-value"><?php echo count($students); ?></div>
                <div>Total Students</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo $present; ?></div>
                <div>Present</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo $late; ?></div>
                <div>Late</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo $absent; ?></div>
                <div>Absent</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo $departed; ?></div>
                <div>Departed</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Morning</th>
                    <th>Arrival</th>
                    <th>Afternoon</th>
                    <th>Departure</th>
                    <th>Evening</th>
                    <th>Prayers</th>
                    <th>Prep</th>
                    <th>Lights</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><strong><?php echo $student['full_name']; ?></strong></td>
                    <td><?php echo $student['student_type']; ?></td>
                    <td class="<?php echo strtolower($student['morning_status'] ?? ''); ?>">
                        <?php echo $student['morning_status'] ?? '-'; ?>
                    </td>
                    <td><?php echo isset($student['morning_arrival_time']) ? date('g:i A', strtotime($student['morning_arrival_time'])) : '-'; ?></td>
                    <td><?php echo $student['afternoon_status'] ?? '-'; ?></td>
                    <td><?php echo isset($student['afternoon_departure_time']) ? date('g:i A', strtotime($student['afternoon_departure_time'])) : '-'; ?></td>
                    <td><?php echo $student['final_roll_call_status'] ?? '-'; ?></td>
                    <td><?php echo $student['evening_prayer_attended'] ? '✓' : '-'; ?></td>
                    <td><?php echo $student['evening_prep_attended'] ? '✓' : '-'; ?></td>
                    <td><?php echo $student['lights_out_check'] ? '✓' : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px; color: #666; text-align: center;">
            Report generated on <?php echo date('F j, Y \a\t g:i A'); ?>
        </p>
    </div>
</body>
</html>