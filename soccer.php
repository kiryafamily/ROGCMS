<?php
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE - Add to EVERY file
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// soccer.php - Soccer Academy Management
require_once 'includes/config.php';

$message = '';
$message_type = '';

// Get current term
$current_term = $_GET['term'] ?? CURRENT_TERM;

// Get all soccer academy students
$soccer_students = $pdo->query("
    SELECT s.*, e.training_days, e.training_start_time, e.training_end_time, e.coach_name, e.coach_phone
    FROM students s
    LEFT JOIN extracurricular e ON s.id = e.student_id AND e.activity_type = 'Soccer Academy'
    WHERE s.soccer_academy = TRUE AND s.status = 'Active'
    ORDER BY s.full_name
")->fetchAll();

// Get all students (for adding to soccer)
$all_students = $pdo->query("SELECT * FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();

// Get training schedule
$training_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$training_schedule = [];

foreach ($soccer_students as $student) {
    if ($student['training_days']) {
        $days = explode(', ', $student['training_days']);
        foreach ($days as $day) {
            if (!isset($training_schedule[$day])) {
                $training_schedule[$day] = [];
            }
            $training_schedule[$day][] = $student['full_name'];
        }
    }
}

// Get today's training attendance
$today = date('Y-m-d');
$day_of_week = date('l');

$stmt = $pdo->prepare("
    SELECT a.*, s.full_name 
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.date = ? AND s.soccer_academy = TRUE
");
$stmt->execute([$today]);
$today_training = $stmt->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_soccer'])) {
        $student_id = $_POST['student_id'];
        $training_days = $_POST['training_days'] ?? '';
        $start_time = $_POST['training_start_time'];
        $end_time = $_POST['training_end_time'];
        $coach_name = sanitize($_POST['coach_name']);
        $coach_phone = sanitize($_POST['coach_phone']);
        
        // Update student soccer flag
        $stmt = $pdo->prepare("UPDATE students SET soccer_academy = TRUE WHERE id = ?");
        $stmt->execute([$student_id]);
        
        // Check if extracurricular record exists
        $check = $pdo->prepare("SELECT id FROM extracurricular WHERE student_id = ? AND activity_type = 'Soccer Academy'");
        $check->execute([$student_id]);
        
        if ($check->fetch()) {
            $stmt = $pdo->prepare("
                UPDATE extracurricular SET 
                    training_days = ?, training_start_time = ?, training_end_time = ?,
                    coach_name = ?, coach_phone = ?
                WHERE student_id = ? AND activity_type = 'Soccer Academy'
            ");
            $stmt->execute([$training_days, $start_time, $end_time, $coach_name, $coach_phone, $student_id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO extracurricular 
                (student_id, activity_type, training_days, training_start_time, training_end_time, coach_name, coach_phone, joined_date)
                VALUES (?, 'Soccer Academy', ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$student_id, $training_days, $start_time, $end_time, $coach_name, $coach_phone, $today]);
        }
        
        $message = "Student added to Soccer Academy successfully!";
        $message_type = "success";
    }
    
    if (isset($_POST['update_schedule'])) {
        $student_id = $_POST['student_id'];
        $training_days = $_POST['training_days'] ?? '';
        $start_time = $_POST['training_start_time'];
        $end_time = $_POST['training_end_time'];
        
        $stmt = $pdo->prepare("
            UPDATE extracurricular SET 
                training_days = ?, training_start_time = ?, training_end_time = ?
            WHERE student_id = ? AND activity_type = 'Soccer Academy'
        ");
        $stmt->execute([$training_days, $start_time, $end_time, $student_id]);
        
        $message = "Training schedule updated!";
        $message_type = "success";
    }
    
    if (isset($_POST['record_training'])) {
        $student_ids = $_POST['student_id'] ?? [];
        $attended = $_POST['attended'] ?? [];
        
        foreach ($student_ids as $index => $sid) {
            $stmt = $pdo->prepare("
                UPDATE attendance SET 
                    soccer_training_today = TRUE,
                    soccer_attended = ?
                WHERE student_id = ? AND date = ?
            ");
            $stmt->execute([isset($attended[$index]) ? 1 : 0, $sid, $today]);
        }
        
        $message = "Training attendance recorded!";
        $message_type = "success";
    }
    
    if (isset($_POST['remove_from_soccer'])) {
        $student_id = $_POST['student_id'];
        
        $stmt = $pdo->prepare("UPDATE students SET soccer_academy = FALSE WHERE id = ?");
        $stmt->execute([$student_id]);
        
        $stmt = $pdo->prepare("DELETE FROM extracurricular WHERE student_id = ? AND activity_type = 'Soccer Academy'");
        $stmt->execute([$student_id]);
        
        $message = "Student removed from Soccer Academy.";
        $message_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soccer Academy - P.5 Purple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .soccer-header {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .player-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: 1px solid #4CAF50;
        }
        
        .player-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .player-header {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .player-number {
            background: var(--accent);
            color: var(--primary);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        
        .player-body {
            padding: 15px;
        }
        
        .schedule-badge {
            background: #E8F5E9;
            color: #2E7D32;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin: 2px;
        }
        
        .coach-info {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--gray-200);
        }
        
        .training-today {
            background: #FFF3E0;
            border-radius: 15px;
            padding: 20px;
            margin: 30px 0;
            border-left: 4px solid var(--warning);
        }
        
        .day-schedule {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .day-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .player-tag {
            display: inline-block;
            background: #E8F5E9;
            color: #2E7D32;
            padding: 5px 12px;
            border-radius: 50px;
            margin: 3px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .player-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="premium-container">
        <!-- Header -->
        <div class="soccer-header">
            <h1><i class="fas fa-futbol"></i> P.5 Purple Soccer Academy</h1>
            <p style="font-size: 1.2rem; margin-top: 10px;"><?php echo count($soccer_students); ?> Players • Training Today: <?php echo $day_of_week; ?></p>
        </div>

        <!-- Alert -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
            <button class="btn-premium" onclick="openAddModal()">
                <i class="fas fa-user-plus"></i> Add Player
            </button>
            <button class="btn-outline" onclick="openTrainingModal()">
                <i class="fas fa-futbol"></i> Record Today's Training
            </button>
        </div>

        <!-- Today's Training Attendance -->
        <?php if (!empty($soccer_students)): ?>
        <div class="training-today">
            <h3 style="color: var(--primary); margin-bottom: 10px;">
                <i class="fas fa-clock"></i> Today's Training (<?php echo $day_of_week; ?>)
            </h3>
            
            <?php 
            $today_trainees = array_filter($soccer_students, function($s) use ($day_of_week) {
                return $s['training_days'] && strpos($s['training_days'], $day_of_week) !== false;
            });
            ?>
            
            <p><strong>Training Time:</strong> 3:30 PM - 5:00 PM</p>
            
            <?php if (empty($today_trainees)): ?>
                <p>No training scheduled for today.</p>
            <?php else: ?>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                    <?php foreach ($today_trainees as $player): 
                        $attended = false;
                        foreach ($today_training as $t) {
                            if ($t['student_id'] == $player['id'] && $t['soccer_attended']) {
                                $attended = true;
                                break;
                            }
                        }
                    ?>
                    <div style="background: <?php echo $attended ? '#E8F5E9' : '#FFF3E0'; ?>; padding: 8px 15px; border-radius: 50px; border: 1px solid <?php echo $attended ? '#4CAF50' : '#FF9800'; ?>;">
                        <?php echo $player['full_name']; ?>
                        <?php if ($attended): ?>
                            <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Training Schedule by Day -->
        <h2 style="color: var(--primary); margin: 30px 0 15px;">Weekly Training Schedule</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <?php foreach ($training_days as $day): ?>
            <div class="day-schedule">
                <div class="day-title"><?php echo $day; ?></div>
                <?php if (isset($training_schedule[$day])): ?>
                    <?php foreach ($training_schedule[$day] as $player): ?>
                        <span class="player-tag"><?php echo $player; ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--gray-400);">No training</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Players Grid -->
        <h2 style="color: var(--primary); margin: 40px 0 15px;">Squad</h2>
        <div class="player-grid">
            <?php foreach ($soccer_students as $index => $player): ?>
            <div class="player-card">
                <div class="player-header">
                    <span class="player-number"><?php echo $index + 1; ?></span>
                    <h3 style="color: white; margin: 0;"><?php echo $player['full_name']; ?></h3>
                </div>
                <div class="player-body">
                    <div style="margin-bottom: 10px;">
                        <?php if ($player['training_days']): 
                            $days = explode(', ', $player['training_days']);
                            foreach ($days as $day): ?>
                            <span class="schedule-badge"><?php echo $day; ?></span>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color: var(--gray-400);">Schedule not set</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($player['training_start_time']): ?>
                    <p><i class="fas fa-clock" style="color: #4CAF50;"></i> 
                        <?php echo date('g:i A', strtotime($player['training_start_time'])); ?> - 
                        <?php echo date('g:i A', strtotime($player['training_end_time'])); ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="coach-info">
                        <p><i class="fas fa-user-tie"></i> Coach: <?php echo $player['coach_name'] ?: 'Coach Mukasa'; ?></p>
                        <?php if ($player['coach_phone']): ?>
                            <p><i class="fas fa-phone"></i> <?php echo $player['coach_phone']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 5px; margin-top: 10px;">
                        <button class="btn-outline" style="flex: 1; padding: 5px;" 
                                onclick="editPlayer(<?php echo $player['id']; ?>, '<?php echo $player['full_name']; ?>', '<?php echo $player['training_days']; ?>', '<?php echo $player['training_start_time']; ?>', '<?php echo $player['training_end_time']; ?>')">
                            <i class="fas fa-edit"></i> Schedule
                        </button>
                        <form method="POST" style="flex: 0;" onsubmit="return confirm('Remove this player from Soccer Academy?')">
                            <input type="hidden" name="student_id" value="<?php echo $player['id']; ?>">
                            <button type="submit" name="remove_from_soccer" class="btn-outline" style="border-color: #f44336; color: #f44336;">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($soccer_students)): ?>
        <div class="alert alert-info" style="text-align: center; padding: 40px;">
            <i class="fas fa-futbol" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <h3>No Soccer Academy Players Yet</h3>
            <p>Click "Add Player" to start building your squad.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Player Modal -->
    <div id="addModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add to Soccer Academy</h2>
                <button class="close-btn" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">Choose a student...</option>
                        <?php foreach ($all_students as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Training Days</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px;">
                        <label class="checkbox"><input type="checkbox" name="training_days[]" value="Monday"> Monday</label>
                        <label class="checkbox"><input type="checkbox" name="training_days[]" value="Tuesday"> Tuesday</label>
                        <label class="checkbox"><input type="checkbox" name="training_days[]" value="Wednesday"> Wednesday</label>
                        <label class="checkbox"><input type="checkbox" name="training_days[]" value="Thursday"> Thursday</label>
                        <label class="checkbox"><input type="checkbox" name="training_days[]" value="Friday"> Friday</label>
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="training_start_time" class="form-control" value="15:30">
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="training_end_time" class="form-control" value="17:00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Coach Name</label>
                    <input type="text" name="coach_name" class="form-control" value="Coach Mukasa">
                </div>
                
                <div class="form-group">
                    <label>Coach Phone</label>
                    <input type="text" name="coach_phone" class="form-control" placeholder="256...">
                </div>
                
                <button type="submit" name="add_to_soccer" class="btn-premium" style="width: 100%;">
                    <i class="fas fa-save"></i> Add to Academy
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Training Schedule</h2>
                <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="student_id" id="edit_student_id">
                
                <div class="form-group">
                    <label>Student</label>
                    <input type="text" id="edit_student_name" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Training Days</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px;" id="edit_days_container">
                        <!-- Will be populated by JS -->
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="training_start_time" id="edit_start_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="training_end_time" id="edit_end_time" class="form-control">
                    </div>
                </div>
                
                <button type="submit" name="update_schedule" class="btn-premium" style="width: 100%;">
                    <i class="fas fa-save"></i> Update Schedule
                </button>
            </form>
        </div>
    </div>

    <!-- Record Training Modal -->
    <div id="trainingModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-futbol"></i> Record Training Attendance</h2>
                <button class="close-btn" onclick="closeModal('trainingModal')">&times;</button>
            </div>
            <form method="POST">
                <p><strong>Date:</strong> <?php echo date('l, F j, Y'); ?></p>
                
                <?php 
                $today_trainees = array_filter($soccer_students, function($s) use ($day_of_week) {
                    return $s['training_days'] && strpos($s['training_days'], $day_of_week) !== false;
                });
                
                if (empty($today_trainees)): 
                ?>
                <p>No training scheduled for today.</p>
                <?php else: ?>
                    <?php foreach ($today_trainees as $player): ?>
                    <div style="display: flex; align-items: center; padding: 8px; border-bottom: 1px solid var(--gray-200);">
                        <input type="hidden" name="student_id[]" value="<?php echo $player['id']; ?>">
                        <label style="flex: 1; display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" name="attended[]" value="<?php echo $player['id']; ?>">
                            <?php echo $player['full_name']; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" name="record_training" class="btn-premium" style="width: 100%; margin-top: 15px;">
                        <i class="fas fa-save"></i> Save Attendance
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        
        function openTrainingModal() {
            document.getElementById('trainingModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function editPlayer(id, name, days, start, end) {
            document.getElementById('edit_student_id').value = id;
            document.getElementById('edit_student_name').value = name;
            document.getElementById('edit_start_time').value = start || '15:30';
            document.getElementById('edit_end_time').value = end || '17:00';
            
            // Populate days checkboxes
            const daysArray = days ? days.split(', ') : [];
            const container = document.getElementById('edit_days_container');
            container.innerHTML = '';
            
            const allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            allDays.forEach(day => {
                const label = document.createElement('label');
                label.className = 'checkbox';
                label.style.display = 'block';
                label.innerHTML = `
                    <input type="checkbox" name="training_days[]" value="${day}" ${daysArray.includes(day) ? 'checked' : ''}> ${day}
                `;
                container.appendChild(label);
            });
            
            document.getElementById('editModal').style.display = 'flex';
        }
        
        window.onclick = function(event) {
            const modals = ['addModal', 'editModal', 'trainingModal'];
            modals.forEach(id => {
                const modal = document.getElementById(id);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>