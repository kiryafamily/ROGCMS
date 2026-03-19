<?php
// behavior.php - Behavior and Conduct Management
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Get current term
$current_term = $_GET['term'] ?? CURRENT_TERM;

// Get all active students
$students = $pdo->query("SELECT * FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_behavior'])) {
        $student_id = $_POST['student_id'];
        $behavior_type = $_POST['behavior_type'];
        $description = sanitize($_POST['description']);
        $action_taken = sanitize($_POST['action_taken'] ?? '');
        $points = $_POST['points'] ?? 0;
        $parent_notified = isset($_POST['parent_notified']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO behavior_log 
            (student_id, log_date, term, year, behavior_type, description, action_taken, points_awarded, parent_notified)
            VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$student_id, $current_term, ACADEMIC_YEAR, $behavior_type, $description, $action_taken, $points, $parent_notified])) {
            $message = "Behavior record added successfully!";
            $message_type = "success";
            
            // Log activity
            $log = $pdo->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, 'add_behavior', ?)");
            $log->execute([$_SESSION['user_id'], "Added $behavior_type for student ID: $student_id"]);
            
            // Refresh the page to show new data
            header("Location: behavior.php?term=$current_term&success=1");
            exit;
        } else {
            $message = "Error adding behavior record.";
            $message_type = "error";
        }
    }
    
    // Handle delete
    if (isset($_POST['delete_behavior'])) {
        $id = $_POST['behavior_id'];
        $stmt = $pdo->prepare("DELETE FROM behavior_log WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Behavior record deleted.";
            $message_type = "success";
            header("Location: behavior.php?term=$current_term&deleted=1");
            exit;
        }
    }
}

// Get behavior records for current term
$stmt = $pdo->prepare("
    SELECT b.*, s.full_name, s.student_type
    FROM behavior_log b
    JOIN students s ON b.student_id = s.id
    WHERE b.year = ? AND b.term = ?
    ORDER BY b.log_date DESC, b.id DESC
");
$stmt->execute([ACADEMIC_YEAR, $current_term]);
$behaviors = $stmt->fetchAll();

// Group by student for summary
$student_summary = [];
foreach ($behaviors as $b) {
    $sid = $b['student_id'];
    if (!isset($student_summary[$sid])) {
        $student_summary[$sid] = [
            'name' => $b['full_name'],
            'type' => $b['student_type'],
            'positive' => 0,
            'warnings' => 0,
            'incidents' => 0,
            'achievements' => 0,
            'total_points' => 0
        ];
    }
    
    switch ($b['behavior_type']) {
        case 'Positive': $student_summary[$sid]['positive']++; break;
        case 'Warning': $student_summary[$sid]['warnings']++; break;
        case 'Incident': $student_summary[$sid]['incidents']++; break;
        case 'Achievement': $student_summary[$sid]['achievements']++; break;
    }
    $student_summary[$sid]['total_points'] += $b['points_awarded'];
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $message = "Behavior record added successfully!";
    $message_type = "success";
}
if (isset($_GET['deleted'])) {
    $message = "Behavior record deleted.";
    $message_type = "success";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Behavior Log - P.5 Purple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .behavior-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #FFB800;
        }
        
        .stat-icon-positive { color: #4CAF50; font-size: 2rem; }
        .stat-icon-warning { color: #FF9800; font-size: 2rem; }
        .stat-icon-incident { color: #f44336; font-size: 2rem; }
        .stat-icon-achievement { color: #9C27B0; font-size: 2rem; }
        
        .student-summary {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .summary-header {
            display: grid;
            grid-template-columns: 2fr 1fr 80px 80px 80px 80px 100px 80px;
            background: #4B1C3C;
            color: white;
            padding: 12px 15px;
            font-weight: 600;
        }
        
        .summary-row {
            display: grid;
            grid-template-columns: 2fr 1fr 80px 80px 80px 80px 100px 80px;
            padding: 10px 15px;
            border-bottom: 1px solid #e0e0e0;
            align-items: center;
        }
        
        .summary-row:hover {
            background: #f8f4f8;
        }
        
        .badge-positive { background: #4CAF50; color: white; padding: 2px 8px; border-radius: 3px; }
        .badge-warning { background: #FF9800; color: white; padding: 2px 8px; border-radius: 3px; }
        .badge-incident { background: #f44336; color: white; padding: 2px 8px; border-radius: 3px; }
        .badge-achievement { background: #9C27B0; color: white; padding: 2px 8px; border-radius: 3px; }
        
        .timeline {
            position: relative;
            padding-left: 50px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 25px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #FFB800;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .timeline-icon {
            position: absolute;
            left: -50px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .timeline-date {
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 5px;
        }
        
        .timeline-student {
            font-weight: 600;
            color: #4B1C3C;
        }
        
        .delete-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 3px 8px;
            border-radius: 3px;
            cursor: pointer;
            float: right;
        }
        
        .delete-btn:hover {
            background: #d32f2f;
        }
        
        .modal {
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
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            color: #4B1C3C;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4B1C3C;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }
        
        .btn-premium {
            background: #4B1C3C;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-premium:hover {
            background: #2F1224;
        }
        
        @media (max-width: 768px) {
            .summary-header, .summary-row {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }
        
        .btn-outline {
            border: 2px solid #4B1C3C;
            color: #4B1C3C;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .btn-outline:hover {
            background: #4B1C3C;
            color: white;
        }
    </style>
</head>
<body>
    <div class="premium-container">
        <!-- Header -->
        <div class="premium-header" style="background-color: #4B1C3C; padding: 20px 30px;">
            <div class="header-content">
                <div class="class-title">
                    <h1 style="color: white;"><i class="fas fa-smile"></i> Behavior & Conduct Log</h1>
                    <div class="class-slogan" style="color: #FFB800;">Track positive behaviors and incidents</div>
                </div>
                <div class="class-badge">
                    <button class="btn-premium" onclick="openAddModal()" style="background-color: #FFB800; color: #4B1C3C;">
                        <i class="fas fa-plus"></i> Add Record
                    </button>
                    <a href="index.php" class="btn-premium" style="background-color: #2F1224;">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Alert -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type == 'success' ? 'success' : 'error'; ?>" style="margin: 20px 0;">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Term Selector -->
        <div style="display: flex; gap: 10px; margin: 20px 0;">
            <a href="?term=1" class="<?php echo $current_term == '1' ? 'btn-premium' : 'btn-outline'; ?>">Term 1</a>
            <a href="?term=2" class="<?php echo $current_term == '2' ? 'btn-premium' : 'btn-outline'; ?>">Term 2</a>
            <a href="?term=3" class="<?php echo $current_term == '3' ? 'btn-premium' : 'btn-outline'; ?>">Term 3</a>
        </div>

        <!-- Statistics -->
        <div class="behavior-stats">
            <?php
            $total_positive = 0;
            $total_warnings = 0;
            $total_incidents = 0;
            $total_achievements = 0;
            
            foreach ($behaviors as $b) {
                switch ($b['behavior_type']) {
                    case 'Positive': $total_positive++; break;
                    case 'Warning': $total_warnings++; break;
                    case 'Incident': $total_incidents++; break;
                    case 'Achievement': $total_achievements++; break;
                }
            }
            ?>
            <div class="stat-card">
                <i class="fas fa-star stat-icon-positive"></i>
                <h3><?php echo $total_positive; ?></h3>
                <p>Positive Mentions</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-exclamation stat-icon-warning"></i>
                <h3><?php echo $total_warnings; ?></h3>
                <p>Warnings</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-exclamation-triangle stat-icon-incident"></i>
                <h3><?php echo $total_incidents; ?></h3>
                <p>Incidents</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-trophy stat-icon-achievement"></i>
                <h3><?php echo $total_achievements; ?></h3>
                <p>Achievements</p>
            </div>
        </div>

        <!-- Student Summary -->
        <div class="student-summary">
            <div class="summary-header">
                <div>Student</div>
                <div>Type</div>
                <div>✨</div>
                <div>⚠️</div>
                <div>❗</div>
                <div>🏆</div>
                <div>Points</div>
                <div>Action</div>
            </div>
            
            <?php foreach ($student_summary as $sid => $sum): ?>
            <div class="summary-row">
                <div><strong><?php echo $sum['name']; ?></strong></div>
                <div><?php echo $sum['type'] == 'Boarder' ? '🏠 Boarder' : '☀️ Day'; ?></div>
                <div><?php echo $sum['positive']; ?></div>
                <div><?php echo $sum['warnings']; ?></div>
                <div><?php echo $sum['incidents']; ?></div>
                <div><?php echo $sum['achievements']; ?></div>
                <div><strong style="color: #FFB800;"><?php echo $sum['total_points']; ?></strong></div>
                <div>
                    <button class="delete-btn" onclick="deleteStudentBehaviors(<?php echo $sid; ?>, '<?php echo $sum['name']; ?>')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Timeline View -->
        <h2 style="color: #4B1C3C; margin: 30px 0 20px;">Recent Activity</h2>
        <div class="timeline">
            <?php if (empty($behaviors)): ?>
                <div style="text-align: center; padding: 40px; background: white; border-radius: 10px;">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #FFB800; margin-bottom: 10px;"></i>
                    <p>No behavior records found for this term.</p>
                </div>
            <?php else: ?>
                <?php foreach ($behaviors as $b): 
                    $icon = '';
                    $color = '';
                    switch ($b['behavior_type']) {
                        case 'Positive':
                            $icon = 'fa-star';
                            $color = '#4CAF50';
                            break;
                        case 'Warning':
                            $icon = 'fa-exclamation';
                            $color = '#FF9800';
                            break;
                        case 'Incident':
                            $icon = 'fa-exclamation-triangle';
                            $color = '#f44336';
                            break;
                        case 'Achievement':
                            $icon = 'fa-trophy';
                            $color = '#9C27B0';
                            break;
                    }
                ?>
                <div class="timeline-item">
                    <div class="timeline-icon" style="background: <?php echo $color; ?>">
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="timeline-date">
                        <i class="far fa-calendar"></i> <?php echo date('l, F j, Y', strtotime($b['log_date'])); ?>
                        <form method="POST" style="display: inline; float: right;" onsubmit="return confirm('Delete this record?')">
                            <input type="hidden" name="behavior_id" value="<?php echo $b['id']; ?>">
                            <button type="submit" name="delete_behavior" class="delete-btn" style="padding: 2px 8px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    <div class="timeline-student">
                        <?php echo $b['full_name']; ?> 
                        <span style="font-size: 0.8rem; color: #999;">(<?php echo $b['student_type']; ?>)</span>
                    </div>
                    <p style="margin: 5px 0;"><strong><?php echo $b['behavior_type']; ?>:</strong> <?php echo $b['description']; ?></p>
                    <?php if ($b['action_taken']): ?>
                        <p style="color: #666; font-size: 0.9rem;"><i class="fas fa-check-circle" style="color: <?php echo $color; ?>"></i> <?php echo $b['action_taken']; ?></p>
                    <?php endif; ?>
                    <?php if ($b['points_awarded'] > 0): ?>
                        <span style="display: inline-block; background: #FFB800; color: #4B1C3C; padding: 2px 8px; border-radius: 3px; margin-top: 5px;">
                            +<?php echo $b['points_awarded']; ?> points
                        </span>
                    <?php endif; ?>
                    <?php if ($b['parent_notified']): ?>
                        <span style="margin-left: 10px; color: #2196F3;"><i class="fas fa-phone"></i> Parent Notified</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Behavior Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Add Behavior Record</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Behavior Type</label>
                    <select name="behavior_type" class="form-control" required>
                        <option value="Positive">✨ Positive</option>
                        <option value="Warning">⚠️ Warning</option>
                        <option value="Incident">❗ Incident</option>
                        <option value="Achievement">🏆 Achievement</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Action Taken (if any)</label>
                    <input type="text" name="action_taken" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Points to Award</label>
                    <input type="number" name="points" class="form-control" min="0" max="100" value="0">
                </div>
                
                <div class="form-group">
                    <label class="checkbox">
                        <input type="checkbox" name="parent_notified" value="1"> Parent Notified
                    </label>
                </div>
                
                <button type="submit" name="add_behavior" class="btn-premium" style="width: 100%;">
                    <i class="fas fa-save"></i> Save Record
                </button>
            </form>
        </div>
    </div>

    <!-- Delete All Confirmation Modal -->
    <div id="deleteAllModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-trash"></i> Delete All Records</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <p>Are you sure you want to delete all behavior records for <span id="deleteStudentName"></span>?</p>
            <p style="color: #f44336;">This action cannot be undone!</p>
            <form method="POST" id="deleteAllForm">
                <input type="hidden" name="student_id" id="deleteStudentId">
                <input type="hidden" name="delete_all" value="1">
                <button type="submit" class="btn-premium" style="background: #f44336; width: 100%;">Yes, Delete All</button>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function deleteStudentBehaviors(studentId, studentName) {
            if (confirm('Delete all behavior records for ' + studentName + '?')) {
                // Create a form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="student_id" value="${studentId}">
                    <input type="hidden" name="delete_all" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>