<?php
// view-student.php - Return student details HTML
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo '<p style="color:red">Unauthorized</p>';
    exit;
}

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    echo '<p style="color:red">Student not found</p>';
    exit;
}
?>
<div style="text-align: center;">
    <?php if (!empty($student['photo_path']) && file_exists($student['photo_path'])): ?>
        <img src="<?php echo $student['photo_path']; ?>" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #FFB800; margin-bottom: 15px;">
    <?php else: ?>
        <div style="width: 150px; height: 150px; border-radius: 50%; background: #4B1C3C; color: #FFB800; display: flex; align-items: center; justify-content: center; font-size: 4rem; margin: 0 auto 15px; border: 3px solid #FFB800;">
            <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
        </div>
    <?php endif; ?>
    
    <h3 style="color: #4B1C3C;"><?php echo $student['full_name']; ?></h3>
    <p style="color: #666;"><?php echo $student['admission_number']; ?></p>
</div>

<div style="margin-top: 20px;">
    <div class="view-detail">
        <div class="view-label">Gender</div>
        <div class="view-value"><?php echo $student['gender']; ?></div>
    </div>
    
    <div class="view-detail">
        <div class="view-label">Student Type</div>
        <div class="view-value"><?php echo $student['student_type']; ?></div>
    </div>
    
    <?php if ($student['student_type'] == 'Boarder'): ?>
    <div class="view-detail">
        <div class="view-label">Dormitory</div>
        <div class="view-value"><?php echo $student['dormitory_number'] ?: 'Not assigned'; ?> (Bed: <?php echo $student['bed_number'] ?: 'N/A'; ?>)</div>
    </div>
    <?php endif; ?>
    
    <div class="view-detail">
        <div class="view-label">Parent/Guardian</div>
        <div class="view-value"><?php echo $student['parent_name']; ?></div>
    </div>
    
    <div class="view-detail">
        <div class="view-label">Parent Phone</div>
        <div class="view-value"><?php echo $student['parent_phone']; ?></div>
    </div>
    
    <div class="view-detail">
        <div class="view-label">Soccer Academy</div>
        <div class="view-value"><?php echo $student['soccer_academy'] ? 'Yes ⚽' : 'No'; ?></div>
    </div>
    
    <div class="view-detail">
        <div class="view-label">Status</div>
        <div class="view-value"><?php echo $student['status']; ?></div>
    </div>
    
    <div class="view-detail">
        <div class="view-label">Joined</div>
        <div class="view-value"><?php echo date('F j, Y', strtotime($student['joined_date'])); ?></div>
    </div>
</div>