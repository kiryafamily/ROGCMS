<?php
// upload-photo.php - Handle teacher photo upload
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['teacher_photo'])) {
    $target_dir = "uploads/teachers/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($_FILES['teacher_photo']['name']);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($_FILES['teacher_photo']['tmp_name']);
    if ($check === false) {
        $error = "File is not an image.";
    }
    
    // Check file size (max 2MB)
    elseif ($_FILES['teacher_photo']['size'] > 2000000) {
        $error = "File is too large. Max size is 2MB.";
    }
    
    // Allow certain file formats
    elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
    }
    
    else {
        if (move_uploaded_file($_FILES['teacher_photo']['tmp_name'], $target_file)) {
            // Update database
            $stmt = $pdo->prepare("UPDATE teacher_profile SET teacher_photo = ?, updated_at = NOW() WHERE id = 1");
            if ($stmt->execute([$file_name])) {
                $message = "Photo uploaded successfully!";
            } else {
                $error = "Database update failed.";
            }
        } else {
            $error = "Error uploading file.";
        }
    }
}

// Get current photo
$stmt = $pdo->query("SELECT * FROM teacher_profile WHERE id = 1");
$profile = $stmt->fetch();
$current_photo = $profile['teacher_photo'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Teacher Photo</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f0f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .upload-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        h2 {
            color: #4B1C3C;
            margin-bottom: 20px;
        }
        .current-photo {
            text-align: center;
            margin-bottom: 20px;
        }
        .current-photo img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #FFB800;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4B1C3C;
            font-weight: 500;
        }
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }
        .btn-upload {
            background-color: #4B1C3C;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
        }
        .btn-upload:hover {
            background-color: #2F1224;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4CAF50;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #4B1C3C;
            text-decoration: none;
        }
        .back-link:hover {
            color: #FFB800;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h2><i class="fas fa-camera"></i> Update Teacher Photo</h2>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($current_photo): ?>
        <div class="current-photo">
            <img src="uploads/teachers/<?php echo $current_photo; ?>" alt="Teacher Photo">
            <p>Current Photo</p>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Choose New Photo</label>
                <input type="file" name="teacher_photo" accept="image/*" required>
            </div>
            <button type="submit" class="btn-upload">Upload Photo</button>
        </form>
        
        <a href="index.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>