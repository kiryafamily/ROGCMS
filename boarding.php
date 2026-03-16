<?php
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE - Add to EVERY file
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// boarding.php - Boarding Students Management
require_once 'includes/config.php';

$message = '';
$message_type = '';

// Get all boarders
$boarders = $pdo->query("
    SELECT * FROM students 
    WHERE student_type = 'Boarder' AND status = 'Active' 
    ORDER BY dormitory_number, full_name
")->fetchAll();

// Get dormitory statistics
$dorm_stats = [];
foreach ($boarders as $b) {
    $dorm = $b['dormitory_number'] ?: 'Unassigned';
    if (!isset($dorm_stats[$dorm])) {
        $dorm_stats[$dorm] = ['count' => 0, 'students' => []];
    }
    $dorm_stats[$dorm]['count']++;
    $dorm_stats[$dorm]['students'][] = $b['full_name'];
}

// Get tonight's duty
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM daily_routines WHERE routine_date = ?");
$stmt->execute([$today]);
$tonight = $stmt->fetch();

// Handle dormitory assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_dorm'])) {
        $student_id = $_POST['student_id'];
        $dorm_number = sanitize($_POST['dormitory_number']);
        $bed_number = sanitize($_POST['bed_number']);
        
        $stmt = $pdo->prepare("UPDATE students SET dormitory_number = ?, bed_number = ? WHERE id = ?");
        if ($stmt->execute([$dorm_number, $bed_number, $student_id])) {
            $message = "Dormitory assigned successfully!";
            $message_type = "success";
        }
    }
    
    if (isset($_POST['save_evening_duty'])) {
        $supervisor = sanitize($_POST['supervisor']);
        $notes = sanitize($_POST['notes']);
        
        $check = $pdo->prepare("SELECT id FROM daily_routines WHERE routine_date = ?");
        $check->execute([$today]);
        
        if ($check->fetch()) {
            $stmt = $pdo->prepare("UPDATE daily_routines SET prep_supervisor = ?, dormitory_notes = ? WHERE routine_date = ?");
            $stmt->execute([$supervisor, $notes, $today]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO daily_routines (routine_date, term, year, prep_supervisor, dormitory_notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$today, CURRENT_TERM, ACADEMIC_YEAR, $supervisor, $notes]);
        }
        
        $message = "Evening duty saved!";
        $message_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boarding Management - P.5 Purple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dorm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .dorm-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        
        .dorm-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dorm-header h3 {
            color: white;
            margin: 0;
        }
        
        .dorm-count {
            background: var(--accent);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .dorm-body {
            padding: 15px;
        }
        
        .bed-list {
            list-style: none;
        }
        
        .bed-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .bed-number {
            background: var(--gray-200);
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .student-name {
            flex: 1;
            margin-left: 10px;
        }
        
        .soccer-badge {
            background: #4CAF50;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
        }
        
        .duty-card {
            background: linear-gradient(135deg, #FFF3E0, #FFE0B2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--warning);
        }
        
        .schedule-timeline {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .timeline-point {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
        }
        
        .timeline-time {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .timeline-activity {
            color: var(--gray-600);
        }
        
        .assign-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .assign-modal.active {
            display: flex;
        }
    </style>
</head>
<body>
    <div class="premium-container">
        <!-- Header -->
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1><i class="fas fa-bed"></i> Boarding Management</h1>
                    <div class="class-slogan"><?php echo count($boarders); ?> Boarders • Dormitory Tracking</div>
                </div>
                <div class="class-badge">
                    <button class="btn-premium" onclick="openAssignModal()">
                        <i class="fas fa-plus"></i> Assign Dorm
                    </button>
                </div>
            </div>
        </div>

        <!-- Alert -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Tonight's Duty -->
        <div class="duty-card">
            <h3 style="color: var(--primary); margin-bottom: 10px;">
                <i class="fas fa-moon"></i> Tonight's Duty (<?php echo date('l, F j, Y'); ?>)
            </h3>
            <form method="POST" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 1;">
                    <label>Prep Supervisor</label>
                    <input type="text" name="supervisor" class="form-control" 
                           value="<?php echo $tonight['prep_supervisor'] ?? CLASS_TEACHER; ?>" required>
                </div>
                <div style="flex: 2;">
                    <label>Notes</label>
                    <input type="text" name="notes" class="form-control" 
                           value="<?php echo $tonight['dormitory_notes'] ?? ''; ?>" 
                           placeholder="Any special instructions for tonight">
                </div>
                <button type="submit" name="save_evening_duty" class="btn-premium">
                    <i class="fas fa-save"></i> Save Duty
                </button>
            </form>
        </div>

        <!-- Daily Schedule Timeline -->
        <h2 style="color: var(--primary); margin: 30px 0 15px;">Boarding Daily Schedule</h2>
        <div class="schedule-timeline">
            <div class="timeline-point">
                <div class="timeline-time">5:30 PM</div>
                <div class="timeline-activity">Evening Prayers</div>
            </div>
            <div class="timeline-point">
                <div class="timeline-time">6:30 PM</div>
                <div class="timeline-activity">Prep Starts</div>
            </div>
            <div class="timeline-point">
                <div class="timeline-time">9:00 PM</div>
                <div class="timeline-activity">Prep Ends / Roll Call</div>
            </div>
            <div class="timeline-point">
                <div class="timeline-time">9:30 PM</div>
                <div class="timeline-activity">Lights Out</div>
            </div>
        </div>

        <!-- Dormitory Overview -->
        <h2 style="color: var(--primary); margin: 30px 0 15px;">Dormitory Assignments</h2>
        <div class="dorm-grid">
            <?php foreach ($dorm_stats as $dorm => $stats): ?>
            <div class="dorm-card">
                <div class="dorm-header">
                    <h3><i class="fas fa-building"></i> Dormitory <?php echo $dorm; ?></h3>
                    <span class="dorm-count"><?php echo $stats['count']; ?> students</span>
                </div>
                <div class="dorm-body">
                    <ul class="bed-list">
                        <?php 
                        $dorm_students = array_filter($boarders, function($s) use ($dorm) { 
                            return ($s['dormitory_number'] ?: 'Unassigned') == $dorm; 
                        });
                        foreach ($dorm_students as $student): 
                        ?>
                        <li class="bed-item">
                            <span class="bed-number">Bed <?php echo $student['bed_number'] ?: '—'; ?></span>
                            <span class="student-name"><?php echo $student['full_name']; ?></span>
                            <?php if ($student['soccer_academy']): ?>
                                <span class="soccer-badge">⚽ Soccer</span>
                            <?php endif; ?>
                            <button class="btn-outline" style="padding: 2px 8px;" 
                                    onclick="editBed(<?php echo $student['id']; ?>, '<?php echo $student['full_name']; ?>', '<?php echo $student['dormitory_number']; ?>', '<?php echo $student['bed_number']; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Unassigned Students -->
        <?php 
        $unassigned = array_filter($boarders, function($s) { 
            return !$s['dormitory_number']; 
        });
        if (!empty($unassigned)): 
        ?>
        <div class="alert alert-warning" style="margin-top: 20px;">
            <h4><i class="fas fa-exclamation-triangle"></i> Unassigned Students</h4>
            <ul>
                <?php foreach ($unassigned as $s): ?>
                <li><?php echo $s['full_name']; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- Assign Dorm Modal -->
    <div id="assignModal" class="assign-modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2><i class="fas fa-bed"></i> Assign Dormitory</h2>
                <button class="close-btn" onclick="closeAssignModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="student_id" id="assign_student_id">
                
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" class="form-control" id="assign_student_select" required>
                        <option value="">Select Student</option>
                        <?php foreach ($boarders as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Dormitory Number</label>
                    <input type="text" name="dormitory_number" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Bed Number</label>
                    <input type="text" name="bed_number" class="form-control" placeholder="e.g., B-12">
                </div>
                
                <button type="submit" name="assign_dorm" class="btn-premium" style="width: 100%;">
                    <i class="fas fa-save"></i> Assign Dormitory
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Bed Modal -->
    <div id="editBedModal" class="assign-modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Bed Assignment</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="student_id" id="edit_student_id">
                
                <div class="form-group">
                    <label>Student Name</label>
                    <input type="text" id="edit_student_name" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Dormitory Number</label>
                    <input type="text" name="dormitory_number" id="edit_dorm" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Bed Number</label>
                    <input type="text" name="bed_number" id="edit_bed" class="form-control">
                </div>
                
                <button type="submit" name="assign_dorm" class="btn-premium" style="width: 100%;">
                    <i class="fas fa-save"></i> Update Assignment
                </button>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal() {
            document.getElementById('assignModal').classList.add('active');
        }
        
        function closeAssignModal() {
            document.getElementById('assignModal').classList.remove('active');
        }
        
        function editBed(id, name, dorm, bed) {
            document.getElementById('edit_student_id').value = id;
            document.getElementById('edit_student_name').value = name;
            document.getElementById('edit_dorm').value = dorm;
            document.getElementById('edit_bed').value = bed;
            document.getElementById('editBedModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editBedModal').classList.remove('active');
        }
        
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.assign-modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>