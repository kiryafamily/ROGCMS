<?php
// generate-report.php - Report Generator with Student Selection
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$type = $_GET['type'] ?? 'MID';
$term = $_GET['term'] ?? CURRENT_TERM;

// Get list of students for dropdown
$students = $pdo->query("SELECT id, full_name FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .selector-container {
            max-width: 600px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #4B1C3C;
            margin-bottom: 20px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: #4B1C3C;
            color: white;
        }
        
        .btn-secondary {
            background-color: #f0e8f0;
            color: #4B1C3C;
        }
    </style>
</head>
<body>
    <div class="selector-container">
        <h2><i class="fas fa-file-pdf"></i> Generate <?php echo $type; ?> Reports</h2>
        <p>Select a student to generate report card:</p>
        
        <select id="studentSelect" class="form-control" style="margin: 20px 0;">
            <option value="">Choose student...</option>
            <?php foreach ($students as $s): ?>
            <option value="<?php echo $s['id']; ?>"><?php echo $s['full_name']; ?></option>
            <?php endforeach; ?>
        </select>
        
        <div class="btn-group">
            <a href="#" onclick="generateSingle()" class="btn btn-primary">Generate Report</a>
            <a href="assessments.php" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
    
    <script>
        function generateSingle() {
            const studentId = document.getElementById('studentSelect').value;
            if (!studentId) {
                alert('Please select a student');
                return;
            }
            window.location.href = `report-card.php?student_id=${studentId}&term=<?php echo $term; ?>&type=<?php echo $type; ?>`;
        }
    </script>
</body>
</html>