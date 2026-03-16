<?php
// upload-student-photo.php - Handle student photo uploads
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    $student_id = $_POST['student_id'];
    
    // Check if student exists
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $error = "Student not found.";
    } elseif (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/students/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['student_photo']['name']);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is actual image
        $check = getimagesize($_FILES['student_photo']['tmp_name']);
        if ($check === false) {
            $error = "File is not an image.";
        }
        // Check file size (max 2MB)
        elseif ($_FILES['student_photo']['size'] > 2000000) {
            $error = "File is too large. Max size is 2MB.";
        }
        // Allow certain file formats
        elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
        else {
            // Delete old photo if exists
            if (!empty($student['photo_path']) && file_exists($student['photo_path'])) {
                unlink($student['photo_path']);
            }
            
            if (move_uploaded_file($_FILES['student_photo']['tmp_name'], $target_file)) {
                // Update database
                $stmt = $pdo->prepare("UPDATE students SET photo_path = ? WHERE id = ?");
                if ($stmt->execute([$target_file, $student_id])) {
                    $message = "Photo uploaded successfully!";
                } else {
                    $error = "Database update failed.";
                }
            } else {
                $error = "Error uploading file.";
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Get student list for dropdown
$students = $pdo->query("SELECT id, full_name, photo_path FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Student Photo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f0f5 0%, #faf5fa 100%);
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(75, 28, 60, 0.2);
            padding: 30px;
        }
        
        h1 {
            color: #4B1C3C;
            margin-bottom: 20px;
        }
        
        h1 i {
            color: #FFB800;
        }
        
        .upload-form {
            background: #f8f4f8;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #4B1C3C;
            font-weight: 500;
        }
        
        select, input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }
        
        button {
            background: #4B1C3C;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        button:hover {
            background: #2F1224;
        }
        
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .success {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
        }
        
        .error {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #f44336;
        }
        
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .student-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        
        .student-photo {
            width: 100%;
            height: 150px;
            background: #4B1C3C;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFB800;
            font-size: 3rem;
        }
        
        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .student-info {
            padding: 10px;
        }
        
        .student-name {
            font-weight: 600;
            color: #4B1C3C;
        }
        
        .btn-small {
            background: #4B1C3C;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            margin: 2px;
        }
        
        .btn-small:hover {
            background: #2F1224;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-camera"></i> Student Photo Management</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="upload-form">
            <h2 style="color: #4B1C3C; margin-bottom: 15px;">Upload New Photo</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" required>
                        <option value="">Choose student...</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Choose Photo (Max 2MB)</label>
                    <input type="file" name="student_photo" accept="image/*" required>
                </div>
                
                <button type="submit" name="upload_photo">
                    <i class="fas fa-upload"></i> Upload Photo
                </button>
            </form>
        </div>
        
        <h2 style="color: #4B1C3C; margin-bottom: 15px;">Student Photos</h2>
        <div class="student-grid">
            <?php foreach ($students as $s): ?>
            <div class="student-card">
                <div class="student-photo">
                    <?php if (!empty($s['photo_path']) && file_exists($s['photo_path'])): ?>
                        <img src="<?php echo $s['photo_path']; ?>" alt="<?php echo $s['full_name']; ?>">
                    <?php else: ?>
                        <i class="fas fa-user-graduate"></i>
                    <?php endif; ?>
                </div>
                <div class="student-info">
                    <div class="student-name"><?php echo $s['full_name']; ?></div>
                    <a href="students.php?view=<?php echo $s['id']; ?>" class="btn-small">View</a>
                    <a href="students.php?edit=<?php echo $s['id']; ?>" class="btn-small">Edit</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="students.php" class="btn-small" style="background: #666;">← Back to Students</a>
        </div>
    </div>
</body>
</html>