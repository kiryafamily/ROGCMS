<?php
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE - Add to EVERY file
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// visitation.php - Visitation Day Management
require_once 'includes/config.php';

$message = '';
$message_type = '';

// Get current term
$current_term = $_GET['term'] ?? CURRENT_TERM;

// Get term info for visitation dates
$term_info = $pdo->prepare("SELECT * FROM academic_terms WHERE year = ? AND term_number = ?");
$term_info->execute([ACADEMIC_YEAR, $current_term]);
$term = $term_info->fetch();

// Get all boarders (visitation is mainly for boarders)
$boarders = $pdo->query("SELECT * FROM students WHERE student_type = 'Boarder' AND status = 'Active' ORDER BY full_name")->fetchAll();

// Get visitation records for current term
$stmt = $pdo->prepare("
    SELECT v.*, s.full_name, s.dormitory_number, s.parent_name, s.parent_phone
    FROM visitation_records v
    JOIN students s ON v.student_id = s.id
    WHERE v.year = ? AND v.term = ?
    ORDER BY v.visitation_date DESC
");
$stmt->execute([ACADEMIC_YEAR, $current_term]);
$visitations = $stmt->fetchAll();

// Group by date
$visits_by_date = [];
foreach ($visitations as $v) {
    $date = $v['visitation_date'];
    if (!isset($visits_by_date[$date])) {
        $visits_by_date[$date] = [];
    }
    $visits_by_date[$date][] = $v;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_visitation'])) {
        $student_id = $_POST['student_id'];
        $visitation_date = $_POST['visitation_date'];
        $parent_attended = isset($_POST['parent_attended']) ? 1 : 0;
        $parent_name = sanitize($_POST['parent_name']);
        $parent_phone = sanitize($_POST['parent_phone']);
        $meeting_with_teacher = isset($_POST['meeting_with_teacher']) ? 1 : 0;
        $teacher_notes = sanitize($_POST['teacher_notes']);
        $items_brought = sanitize($_POST['items_brought']);
        $pocket_money = $_POST['pocket_money'] ?? 0;
        $academic_discussed = isset($_POST['academic_discussed']) ? 1 : 0;
        $behavior_discussed = isset($_POST['behavior_discussed']) ? 1 : 0;
        $follow_up_needed = isset($_POST['follow_up_needed']) ? 1 : 0;
        $follow_up_notes = sanitize($_POST['follow_up_notes']);
        
        // Check if record exists
        $check = $pdo->prepare("SELECT id FROM visitation_records WHERE student_id = ? AND visitation_date = ?");
        $check->execute([$student_id, $visitation_date]);
        
        if ($check->fetch()) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE visitation_records SET 
                    parent_attended = ?, parent_name = ?, parent_phone = ?,
                    meeting_with_teacher = ?, teacher_notes = ?,
                    items_brought = ?, pocket_money_given = ?,
                    academic_discussed = ?, behavior_discussed = ?,
                    follow_up_needed = ?, follow_up_notes = ?
                WHERE student_id = ? AND visitation_date = ?
            ");
            $stmt->execute([
                $parent_attended, $parent_name, $parent_phone,
                $meeting_with_teacher, $teacher_notes,
                $items_brought, $pocket_money,
                $academic_discussed, $behavior_discussed,
                $follow_up_needed, $follow_up_notes,
                $student_id, $visitation_date
            ]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO visitation_records 
                (student_id, visitation_date, term, year, parent_attended, parent_name, parent_phone,
                 meeting_with_teacher, teacher_notes, items_brought, pocket_money_given,
                 academic_discussed, behavior_discussed, follow_up_needed, follow_up_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $student_id, $visitation_date, $current_term, ACADEMIC_YEAR,
                $parent_attended, $parent_name, $parent_phone,
                $meeting_with_teacher, $teacher_notes,
                $items_brought, $pocket_money,
                $academic_discussed, $behavior_discussed,
                $follow_up_needed, $follow_up_notes
            ]);
        }
        
        $message = "Visitation record saved successfully!";
        $message_type = "success";
    }
    
    if (isset($_POST['bulk_visitation'])) {
        $visitation_date = $_POST['visitation_date'];
        $student_ids = $_POST['student_id'] ?? [];
        $parent_attended = $_POST['parent_attended'] ?? [];
        
        foreach ($student_ids as $index => $sid) {
            if (isset($parent_attended[$index])) {
                // Insert record for students whose parents attended
                $stmt = $pdo->prepare("
                    INSERT INTO visitation_records 
                    (student_id, visitation_date, term, year, parent_attended)
                    VALUES (?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE parent_attended = 1
                ");
                $stmt->execute([$sid, $visitation_date, $current_term, ACADEMIC_YEAR]);
            }
        }
        
        $message = "Bulk visitation records saved!";
        $message_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitation Day - P.5 Purple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .visitation-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .date-selector {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .stat-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .visitation-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: 25px;
        }
        
        .date-header {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .date-header h3 {
            color: white;
            margin: 0;
        }
        
        .attendance-count {
            background: var(--accent);
            color: var(--primary);
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .visitor-table {
            padding: 20px;
        }
        
        .visitor-row {
            display: grid;
            grid-template-columns: 2fr 1fr 2fr 2fr 1fr;
            padding: 12px;
            border-bottom: 1px solid var(--gray-200);
            align-items: center;
        }
        
        .visitor-row.header {
            background: var(--gray-100);
            font-weight: 600;
            color: var(--primary);
            border-radius: 5px;
            margin-bottom: 5px;
        }
        
        .money-badge {
            background: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        
        .items-list {
            font-size: 0.9rem;
            color: var(--gray-600);
        }
        
        .quick-add {
            background: #FFF3E0;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--warning);
        }
        
        .student-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        
        .student-checkbox-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .visitor-row {
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
                    <h1><i class="fas fa-users"></i> Visitation Day Management</h1>
                    <div class="class-slogan">Track parent visits and interactions</div>
                </div>
                <div class="class-badge">
                    <button class="btn-premium" onclick="openQuickAdd()">
                        <i class="fas fa-bolt"></i> Quick Add
                    </button>
                </div>
            </div>
        </div>

        <!-- Term Info -->
        <?php if ($term && $term['visitation_day']): ?>
        <div class="visitation-header">
            <h2><i class="fas fa-calendar-check"></i> Next Visitation Day</h2>
            <div style="font-size: 2rem; margin: 10px 0;">
                <?php echo date('l, F j, Y', strtotime($term['visitation_day'])); ?>
            </div>
            <p>Parents are welcome to visit their children, bring items, and discuss progress.</p>
        </div>
        <?php endif; ?>

        <!-- Alert -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Term Selector -->
        <div class="date-selector">
            <a href="?term=1" class="btn-<?php echo $current_term == '1' ? 'premium' : 'outline'; ?>">Term 1</a>
            <a href="?term=2" class="btn-<?php echo $current_term == '2' ? 'premium' : 'outline'; ?>">Term 2</a>
            <a href="?term=3" class="btn-<?php echo $current_term == '3' ? 'premium' : 'outline'; ?>">Term 3</a>
            
            <div style="flex: 1; text-align: right;">
                <a href="?term=<?php echo $current_term; ?>&export=1" class="btn-outline">
                    <i class="fas fa-download"></i> Export Report
                </a>
            </div>
        </div>

        <!-- Statistics Panel -->
        <div class="stat-panel">
            <?php
            $total_visits = count($visitations);
            $parents_attended = 0;
            $total_pocket_money = 0;
            $meetings_held = 0;
            
            foreach ($visitations as $v) {
                if ($v['parent_attended']) $parents_attended++;
                $total_pocket_money += $v['pocket_money_given'];
                if ($v['meeting_with_teacher']) $meetings_held++;
            }
            ?>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-content">
                    <h3><?php echo count($visits_by_date); ?></h3>
                    <p>Visitation Days</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-content">
                    <h3><?php echo $parents_attended; ?>/<?php echo count($boarders); ?></h3>
                    <p>Parents Attended</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-content">
                    <h3>UGX <?php echo number_format($total_pocket_money); ?></h3>
                    <p>Total Pocket Money</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-handshake"></i></div>
                <div class="stat-content">
                    <h3><?php echo $meetings_held; ?></h3>
                    <p>Teacher Meetings</p>
                </div>
            </div>
        </div>

        <!-- Quick Add Form (Hidden by default) -->
        <div id="quickAddForm" class="quick-add" style="display: none;">
            <h3 style="color: var(--primary); margin-bottom: 15px;">
                <i class="fas fa-bolt"></i> Quick Add - Mark Parents Who Attended
            </h3>
            <form method="POST">
                <div class="form-group">
                    <label>Visitation Date</label>
                    <input type="date" name="visitation_date" class="form-control" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="student-checkbox-grid">
                    <?php foreach ($boarders as $student): ?>
                    <div class="student-checkbox-item">
                        <input type="checkbox" name="student_id[]" value="<?php echo $student['id']; ?>">
                        <label><?php echo $student['full_name']; ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" name="bulk_visitation" class="btn-premium">
                    <i class="fas fa-save"></i> Save Attendance
                </button>
                <button type="button" class="btn-outline" onclick="closeQuickAdd()">Cancel</button>
            </form>
        </div>

        <!-- Visitation Records by Date -->
        <?php if (empty($visits_by_date)): ?>
        <div class="alert alert-info" style="text-align: center; padding: 40px;">
            <i class="fas fa-calendar-alt" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <h3>No Visitation Records Yet</h3>
            <p>Use the "Quick Add" button to record parent visits.</p>
        </div>
        <?php else: ?>
            <?php foreach ($visits_by_date as $date => $visits): 
                $day_total = 0;
                $day_attended = 0;
                foreach ($visits as $v) {
                    if ($v['parent_attended']) $day_attended++;
                    $day_total += $v['pocket_money_given'];
                }
            ?>
            <div class="visitation-card">
                <div class="date-header">
                    <h3><i class="fas fa-calendar-day"></i> <?php echo date('l, F j, Y', strtotime($date)); ?></h3>
                    <span class="attendance-count">
                        <?php echo $day_attended; ?> / <?php echo count($visits); ?> Parents
                    </span>
                </div>
                
                <div class="visitor-table">
                    <div class="visitor-row header">
                        <div>Student</div>
                        <div>Parent</div>
                        <div>Items Brought</div>
                        <div>Pocket Money</div>
                        <div>Meeting</div>
                    </div>
                    
                    <?php foreach ($visits as $visit): ?>
                    <div class="visitor-row">
                        <div>
                            <strong><?php echo $visit['full_name']; ?></strong>
                            <br><small>Dorm: <?php echo $visit['dormitory_number']; ?></small>
                        </div>
                        <div>
                            <?php if ($visit['parent_attended']): ?>
                                <span style="color: #4CAF50;">
                                    <i class="fas fa-check-circle"></i> <?php echo $visit['parent_name'] ?: 'Attended'; ?>
                                </span>
                                <br><small><?php echo $visit['parent_phone']; ?></small>
                            <?php else: ?>
                                <span style="color: #f44336;">No parent</span>
                            <?php endif; ?>
                        </div>
                        <div class="items-list">
                            <?php echo $visit['items_brought'] ?: '—'; ?>
                        </div>
                        <div>
                            <?php if ($visit['pocket_money_given'] > 0): ?>
                                <span class="money-badge">
                                    UGX <?php echo number_format($visit['pocket_money_given']); ?>
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($visit['meeting_with_teacher']): ?>
                                <i class="fas fa-handshake" style="color: #4CAF50;"></i> Yes
                                <?php if ($visit['teacher_notes']): ?>
                                    <br><small><?php echo substr($visit['teacher_notes'], 0, 30); ?>...</small>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Day Summary -->
                    <div style="margin-top: 15px; padding: 10px; background: var(--gray-100); border-radius: 5px;">
                        <strong>Day Total:</strong> UGX <?php echo number_format($day_total); ?> pocket money given
                    </div>
                </div>
                
                <!-- Add More Button for this Date -->
                <div style="padding: 0 20px 20px;">
                    <button class="btn-outline" onclick="addForDate('<?php echo $date; ?>')">
                        <i class="fas fa-plus"></i> Add Record for this Date
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Individual Add Form (Hidden) -->
        <div id="individualForm" style="display: none;">
            <!-- This will be populated by JavaScript -->
        </div>
    </div>

    <script>
        function openQuickAdd() {
            document.getElementById('quickAddForm').style.display = 'block';
        }
        
        function closeQuickAdd() {
            document.getElementById('quickAddForm').style.display = 'none';
        }
        
        function addForDate(date) {
            const formHtml = `
                <div class="modal" id="dateModal" style="display: flex;">
                    <div class="modal-content" style="max-width: 500px;">
                        <div class="modal-header">
                            <h2><i class="fas fa-plus"></i> Add Visitation Record</h2>
                            <button class="close-btn" onclick="closeDateModal()">&times;</button>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="visitation_date" value="${date}">
                            
                            <div class="form-group">
                                <label>Student</label>
                                <select name="student_id" class="form-control" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($boarders as $student): ?>
                                    <option value="<?php echo $student['id']; ?>"><?php echo $student['full_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox">
                                    <input type="checkbox" name="parent_attended" value="1"> Parent Attended
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>Parent Name</label>
                                <input type="text" name="parent_name" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Parent Phone</label>
                                <input type="text" name="parent_phone" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Items Brought</label>
                                <input type="text" name="items_brought" class="form-control" placeholder="e.g., Food, clothes, books">
                            </div>
                            
                            <div class="form-group">
                                <label>Pocket Money Given (UGX)</label>
                                <input type="number" name="pocket_money" class="form-control" min="0" step="1000">
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox">
                                    <input type="checkbox" name="meeting_with_teacher" value="1"> Met with Teacher
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>Teacher Notes</label>
                                <textarea name="teacher_notes" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Topics Discussed</label>
                                <div>
                                    <label class="checkbox" style="display: inline-block; margin-right: 10px;">
                                        <input type="checkbox" name="academic_discussed" value="1"> Academic
                                    </label>
                                    <label class="checkbox" style="display: inline-block;">
                                        <input type="checkbox" name="behavior_discussed" value="1"> Behavior
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox">
                                    <input type="checkbox" name="follow_up_needed" value="1"> Follow-up Needed
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>Follow-up Notes</label>
                                <textarea name="follow_up_notes" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" name="save_visitation" class="btn-premium" style="width: 100%;">
                                <i class="fas fa-save"></i> Save Record
                            </button>
                        </form>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', formHtml);
        }
        
        function closeDateModal() {
            document.getElementById('dateModal').remove();
        }
    </script>
</body>
</html>